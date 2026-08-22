<?php

namespace App\Services\Tournaments\CompetitionLab\Runtime;

use App\Models\TournamentTemplate;
use App\Services\Games\Runtime\EncounterRuntime;
use App\Services\Tournaments\CompetitionLab\Engines\LabPhaseEngineManager;
use Illuminate\Validation\ValidationException;

class TournamentGraphRuntimeService
{
    private const MAX_OPERATIONS = 1000;

    public function __construct(
        private readonly
        RuntimeConnectionRouter $connectionRouter,

        private readonly
        RuntimeOutcomeResolver $outcomeResolver,

        private readonly
        LabPhaseEngineManager $engineManager,

        /*
         * Motor de juegos (Fase 11). Decide el resultado de cada
         * enfrentamiento cuando la competición tiene un juego asignado.
         * Sin juego —el Lab de diseño, con participantes sintéticos— el
         * motor sigue resolviendo al azar como siempre.
         */
        private readonly
        EncounterRuntime $encounterRuntime
    ) {}

    public function initialize(
        array $state,
        TournamentTemplate $template
    ): array {
        if (
            ($state['status'] ?? null)
            !==
            'READY'
        ) {
            $this->fail(
                'El Tournament Runtime solo puede iniciarse desde READY.'
            );
        }

        $this->loadGraph(
            $template
        );

        $state['status'] =
            'RUNNING';

        $state['graph_runtime'] = [
            'status' =>
            'RUNNING',

            'operation_queue' =>
            $template
                ->graphStarts
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->map(
                    fn($start) => [
                        'key' =>
                        'DISPATCH_START:'
                            .
                            $start->id,

                        'type' =>
                        'DISPATCH_START',

                        'start_id' =>
                        (int)
                        $start->id,
                    ]
                )
                ->values()
                ->all(),

            'processed_operations' =>
            [],

            'operation_count' =>
            0,

            'diagnostics' =>
            [],

            'stranded_participant_ids' =>
            [],

            /*
             * Ledger lógico de emisiones. Una salida temporizada puede
             * recalcularse muchas veces, pero el mismo evento competitivo no
             * debe cruzar físicamente el Tournament Graph más de una vez.
             */
            'emission_ledger' =>
            [],

            'phase_exit_emissions' =>
            [],

            'started_at' =>
            now()->toIso8601String(),

            'completed_at' =>
            null,
        ];

        foreach (
            $state['participants']
            as
            &$participant
        ) {
            $participant['status'] =
                'ACTIVE';
        }

        unset($participant);

        foreach (
            $state['nodes']
            as
            &$node
        ) {
            $node['status'] =
                'WAITING_INPUTS';
        }

        unset($node);

        $this->event(
            $state,
            'GRAPH_RUNTIME_STARTED',
            'SUCCESS',
            'El Tournament Graph Runtime fue iniciado.'
        );

        return $state;
    }

    public function step(
        array $state,
        TournamentTemplate $template
    ): array {
        $this->requireRuntime(
            $state
        );

        $this->loadGraph(
            $template
        );

        if (
            $state['graph_runtime']['operation_queue']
            !==
            []
        ) {
            $operation =
                array_shift(
                    $state['graph_runtime']['operation_queue']
                );

            $key =
                $operation['key'];

            if (
                in_array(
                    $key,
                    $state['graph_runtime']['processed_operations'],
                    true
                )
            ) {
                return $state;
            }

            $state =
                $this->processOperation(
                    $state,
                    $template,
                    $operation
                );

            $state['graph_runtime']['processed_operations'][] =
                $key;

            $state['graph_runtime']['operation_count']++;

            return $this->checkCompletion(
                $state,
                $template
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Si no hay operaciones, resolver un encuentro pendiente
        |--------------------------------------------------------------------------
        */

        $runningNode =
            collect(
                $state['nodes']
            )
            ->first(
                fn($node) =>
                isset(
                    $node['runtime']
                )
                    &&
                    $node['status']
                    ===
                    'RUNNING'
                    &&
                    $node['runtime']['status']
                    ===
                    'RUNNING'
            );

        if ($runningNode) {
            return $this->simulateOneMatch(
                $state,
                $template,
                (int)
                $runningNode['id']
            );
        }

        return $this->checkCompletion(
            $state,
            $template
        );
    }

    public function run(
        array $state,
        TournamentTemplate $template,
        int $maximumOperations = self::MAX_OPERATIONS
    ): array {
        $this->requireRuntime(
            $state
        );

        $maximumOperations =
            max(
                1,
                min(
                    self::MAX_OPERATIONS,
                    $maximumOperations
                )
            );

        for (
            $index = 0;
            $index < $maximumOperations;
            $index++
        ) {
            if (
                in_array(
                    $state['graph_runtime']['status'],
                    [
                        'COMPLETED',
                        'BLOCKED',
                        'AWAITING_DECISION',
                    ],
                    true
                )
            ) {
                break;
            }

            $before =
                $this->progressFingerprint(
                    $state
                );

            $state =
                $this->step(
                    $state,
                    $template
                );

            $after =
                $this->progressFingerprint(
                    $state
                );

            if (
                $before === $after
                &&
                $state['graph_runtime']['operation_queue']
                ===
                []
            ) {
                break;
            }
        }

        return $this->checkCompletion(
            $state,
            $template
        );
    }

    public function afterNodeResult(
        array $state,
        TournamentTemplate $template,
        int $nodeId
    ): array {
        if (
            ! isset(
                $state['graph_runtime']
            )
            ||
            ! isset(
                $state['nodes'][$nodeId]['runtime']
            )
        ) {
            return $state;
        }

        $state = $this->routeTimedOutputs(
            $state,
            $template,
            $nodeId
        );

        if (
            ($state['graph_runtime']['status'] ?? null) === 'AWAITING_DECISION'
            && ($state['nodes'][$nodeId]['runtime']['status'] ?? null) !== 'AWAITING_DECISION'
        ) {
            $state['graph_runtime']['status'] = 'RUNNING';
        }

        if (
            $state['nodes'][$nodeId]['runtime']['status']
            ===
            'COMPLETED'
        ) {
            $state['nodes'][$nodeId]['status'] =
                'COMPLETED';

            $this->enqueue(
                $state,
                [
                    'key' =>
                    'ROUTE_NODE:'
                        .
                        $nodeId,

                    'type' =>
                    'ROUTE_NODE',

                    'node_id' =>
                    $nodeId,
                ]
            );
        }

        return $state;
    }

    private function processOperation(
        array $state,
        TournamentTemplate $template,
        array $operation
    ): array {
        return match ($operation['type']) {
            'DISPATCH_START' =>
            $this->dispatchStart(
                $state,
                $template,
                (int)
                $operation['start_id']
            ),

            'EVALUATE_NODE' =>
            $this->evaluateNode(
                $state,
                $template,
                (int)
                $operation['node_id']
            ),

            'ROUTE_NODE' =>
            $this->routeNode(
                $state,
                $template,
                (int)
                $operation['node_id']
            ),

            default =>
            $state,
        };
    }

    private function dispatchStart(
        array $state,
        TournamentTemplate $template,
        int $startId
    ): array {
        $start =
            $template
            ->graphStarts
            ->firstWhere(
                'id',
                $startId
            );

        if (
            ! $start
            ||
            ! isset(
                $state['starts'][$startId]
            )
        ) {
            return $state;
        }

        $participantIds =
            $state['starts'][$startId]['participant_ids'];

        $connections =
            $template
            ->graphConnections
            ->where(
                'source_type',
                'START'
            )
            ->where(
                'source_start_id',
                $startId
            );

        $result =
            $this->connectionRouter
            ->route(
                $state,
                $connections,
                $participantIds,
                'START',
                $startId
            );

        $state =
            $result['state'];

        $state['starts'][$startId]['status'] =
            'DISPATCHED';

        if (
            $result['remaining_ids']
            !==
            []
        ) {
            $this->stranded(
                $state,
                $result['remaining_ids'],
                "El Start {$start->name} dejó participantes sin conexión."
            );
        }

        foreach (
            $result['touched_node_ids']
            as
            $nodeId
        ) {
            $this->enqueueNodeEvaluation(
                $state,
                $nodeId
            );
        }

        $this->event(
            $state,
            'START_DISPATCHED',
            'SUCCESS',
            $start->name
                .
                ' despachó '
                .
                count($participantIds)
                .
                ' participantes.'
        );

        return $state;
    }

    private function evaluateNode(
        array $state,
        TournamentTemplate $template,
        int $nodeId
    ): array {
        if (
            ! isset(
                $state['nodes'][$nodeId]
            )
        ) {
            return $state;
        }

        $nodeModel =
            $template
            ->graphNodes
            ->firstWhere(
                'id',
                $nodeId
            );

        if (
            ! $nodeModel
            ||
            ! $nodeModel->phaseTemplate
        ) {
            return $state;
        }

        $node =
            &$state['nodes'][$nodeId];

        if (
            isset(
                $node['runtime']
            )
        ) {
            unset($node);

            return $state;
        }

        if (
            ! $this->allIncomingConnectionsClosed(
                $state,
                $nodeModel
            )
        ) {
            $node['status'] =
                'WAITING_INPUTS';

            unset($node);

            return $state;
        }

        $participantIds =
            collect(
                $node['entry_ports']
            )
            ->flatMap(
                fn($port) =>
                $port['participant_ids']
            )
            ->unique()
            ->values()
            ->all();

        $portErrors =
            $this->portContractErrors(
                $node
            );

        if ($portErrors !== []) {
            $node['status'] =
                'BLOCKED';

            foreach (
                $portErrors
                as
                $message
            ) {
                $state['graph_runtime']['diagnostics'][] = [
                    'level' =>
                    'ERROR',

                    'code' =>
                    'ENTRY_PORT_CONTRACT_ERROR',

                    'message' =>
                    $message,
                ];
            }

            unset($node);

            return $state;
        }

        if ($participantIds === []) {
            $node['status'] =
                'SKIPPED';

            unset($node);

            return $state;
        }

        $runtime =
            $this->engineManager
            ->prepare(
                $nodeModel->phaseTemplate,
                $participantIds,
                $state['participants']
            );

        $node['participant_ids'] =
            $participantIds;

        $node['runtime'] =
            $runtime;

        $node['status'] =
            $runtime['status'];

        if (($runtime['status'] ?? null) === 'AWAITING_DECISION') {
            $state['graph_runtime']['status'] = 'AWAITING_DECISION';
        }

        foreach (
            $node['entry_ports']
            as
            &$port
        ) {
            $port['status'] =
                'CLOSED';
        }

        unset($port);

        foreach (
            $participantIds
            as
            $participantId
        ) {
            $location = [
                'type' =>
                'NODE',

                'id' =>
                $nodeId,

                'code' =>
                $node['code'],

                'name' =>
                $node['name'],
            ];

            $state['participants'][$participantId]['status'] =
                'COMPETING';

            $state['participants'][$participantId]['current_location'] =
                $location;

            $state['participants'][$participantId]['journey'][] =
                $location;
        }

        if (
            $runtime['status']
            ===
            'COMPLETED'
        ) {
            $this->enqueue(
                $state,
                [
                    'key' =>
                    'ROUTE_NODE:'
                        .
                        $nodeId,

                    'type' =>
                    'ROUTE_NODE',

                    'node_id' =>
                    $nodeId,
                ]
            );
        }

        $this->event(
            $state,
            'NODE_PREPARED',
            'SUCCESS',
            $node['name']
                .
                ' fue preparado automáticamente con '
                .
                count($participantIds)
                .
                ' participantes.'
        );

        unset($node);

        return $state;
    }

    private function routeTimedOutputs(
        array $state,
        TournamentTemplate $template,
        int $nodeId
    ): array {
        $nodeModel =
            $template
            ->graphNodes
            ->firstWhere(
                'id',
                $nodeId
            );

        if (
            ! $nodeModel
            ||
            ! isset(
                $state['nodes'][$nodeId]['runtime']
            )
        ) {
            return $state;
        }

        $state['graph_runtime']['emission_ledger'] ??= [];
        $state['graph_runtime']['phase_exit_emissions'] ??= [];

        $runtime =
            &$state['nodes'][$nodeId]['runtime'];

        $runtime['timed_outcomes'] ??= [];

        foreach (
            $nodeModel
                ->phaseTemplate
                ->exits
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->whereIn(
                    'exit_timing',
                    [
                        'ON_ELIMINATION',
                        'ON_RULE_TRIGGER',
                    ]
                )
                ->sortBy(
                    fn($exit) =>
                    sprintf(
                        '%010d:%010d:%010d',
                        (int) $exit->priority,
                        (int) $exit->sort_order,
                        (int) $exit->id
                    )
                )
            as
            $exit
        ) {
            $events =
                $this->timedEventsForExit(
                    $runtime,
                    $exit,
                    $nodeId,
                    $state['nodes'][$nodeId]['participant_ids']
                );

            $newEvents = [];

            foreach (
                $events
                as
                $event
            ) {
                $eventId =
                    (string) $event['id'];

                $key =
                    $this->emissionKey(
                        $nodeId,
                        (int) $exit->id,
                        $eventId
                    );

                if (
                    isset(
                        $state['graph_runtime']['emission_ledger'][$key]
                    )
                ) {
                    continue;
                }

                $event['ledger_key'] =
                    $key;

                $newEvents[] =
                    $event;
            }

            if ($newEvents === []) {
                continue;
            }

            $participantIds =
                array_values(
                    array_unique(
                        array_column(
                            $newEvents,
                            'participant_id'
                        )
                    )
                );

            if ($participantIds === []) {
                continue;
            }

            $currentTimed =
                $runtime['timed_outcomes'][$exit->id]['participant_ids']
                ??
                [];

            $cumulativeTimed =
                array_values(
                    array_unique([
                        ...$currentTimed,
                        ...$participantIds,
                    ])
                );

            $this->assertTimedExitCapacity(
                $exit,
                $cumulativeTimed
            );

            $runtime['timed_outcomes'][$exit->id] = [
                'exit_id' =>
                (int) $exit->id,

                'exit_name' =>
                $exit->name,

                'selector_type' =>
                $exit->selector_type,

                'exit_timing' =>
                $exit->exit_timing,

                'participant_ids' =>
                $cumulativeTimed,
            ];

            /*
             * El router recibe únicamente el delta. Él conserva su propio
             * payload acumulado por conexión, de modo que una emisión anterior
             * puede finalizarse al PHASE_END sin duplicar journeys.
             */
            unset($runtime);

            $connections =
                $template
                ->graphConnections
                ->where(
                    'source_type',
                    'PHASE_EXIT'
                )
                ->where(
                    'source_node_id',
                    $nodeId
                )
                ->where(
                    'source_phase_exit_id',
                    $exit->id
                );

            $result =
                $this->connectionRouter
                ->route(
                    $state,
                    $connections,
                    $participantIds,
                    'NODE',
                    $nodeId,
                    (int) $exit->id,
                    false
                );

            $state =
                $result['state'];

            $runtime =
                &$state['nodes'][$nodeId]['runtime'];

            $eventIds = [];

            foreach (
                $newEvents
                as
                $event
            ) {
                $key =
                    $event['ledger_key'];

                $eventIds[] =
                    (string) $event['id'];

                $state['graph_runtime']['emission_ledger'][$key] = [
                    'key' =>
                    $key,

                    'node_id' =>
                    $nodeId,

                    'exit_id' =>
                    (int) $exit->id,

                    'exit_timing' =>
                    $exit->exit_timing,

                    'event_id' =>
                    (string) $event['id'],

                    'participant_id' =>
                    $event['participant_id'],

                    'event_type' =>
                    $event['type']
                    ??
                    null,

                    'match_id' =>
                    $event['match_id']
                    ??
                    null,

                    'round_number' =>
                    $event['round_number']
                    ??
                    null,
                ];
            }

            $state['graph_runtime']['phase_exit_emissions'][] = [
                'node_id' =>
                $nodeId,

                'exit_id' =>
                (int) $exit->id,

                'exit_name' =>
                $exit->name,

                'exit_timing' =>
                $exit->exit_timing,

                'event_ids' =>
                array_values(
                    array_unique(
                        $eventIds
                    )
                ),

                'participant_ids' =>
                $participantIds,
            ];

            foreach (
                $result['touched_node_ids']
                as
                $targetNodeId
            ) {
                $this->enqueueNodeEvaluation(
                    $state,
                    (int) $targetNodeId
                );
            }

            $this->event(
                $state,
                'PHASE_EXIT_EMITTED',
                'INFO',
                $exit->name
                    . ' emitió '
                    . count($participantIds)
                    . ' participante(s) desde '
                    . $state['nodes'][$nodeId]['name']
                    . '.'
            );
        }

        unset($runtime);

        return $state;
    }

    /**
     * Devuelve eventos lógicos, no un snapshot acumulado. Ese detalle permite
     * deduplicar por causa competitiva y no solamente por participant_id.
     */
    private function timedEventsForExit(
        array $runtime,
        $exit,
        int $nodeId,
        array $participantIds
    ): array {
        if ($exit->exit_timing === 'ON_ELIMINATION') {
            if (
                ! in_array(
                    $exit->selector_type,
                    [
                        'ELIMINATED',
                        'ELIMINATED_IN_ROUND',
                        'MATCH_LOSERS',
                    ],
                    true
                )
            ) {
                $this->fail(
                    "La salida {$exit->name} usa ON_ELIMINATION con un selector no soportado por Stable V1."
                );
            }

            $events = [];

            foreach (
                $runtime['eliminations']
                ??
                []
                as
                $event
            ) {
                $participantId =
                    $event['participant_id']
                    ??
                    null;

                if (
                    $participantId === null
                    ||
                    ! in_array(
                        $participantId,
                        $participantIds,
                        true
                    )
                    ||
                    ! $this->eliminationMatchesExit(
                        $runtime,
                        $event,
                        $exit
                    )
                ) {
                    continue;
                }

                $matchId =
                    (string) (
                        $event['match_id']
                        ??
                        'UNKNOWN_MATCH'
                    );

                $eventId =
                    (string) (
                        $event['id']
                        ??
                        'ELIMINATION:'
                            . $matchId
                            . ':'
                            . $participantId
                    );

                $events[] = [
                    'id' =>
                    $eventId,

                    'type' =>
                    'ELIMINATION',

                    'participant_id' =>
                    $participantId,

                    'match_id' =>
                    $matchId,

                    'round_number' =>
                    (int) ($event['round_number'] ?? 0),

                    'round_participants' =>
                    (int) (
                        $event['round_participants']
                        ??
                        $this->roundParticipantsForElimination(
                            $runtime,
                            $event
                        )
                    ),
                ];
            }

            return $events;
        }

        /*
         * ON_RULE_TRIGGER solo existe cuando el Engine declaró explícitamente
         * ese outcome. No se debe ejecutar un selector genérico antes de que
         * la regla competitiva haya ocurrido.
         */
        $triggered =
            collect(
                $runtime['outcomes']
                ??
                []
            )
            ->contains(
                fn($outcome) =>
                (int) ($outcome['exit_id'] ?? 0)
                ===
                (int) $exit->id
            );

        if (! $triggered) {
            return [];
        }

        /*
         * ON_RULE_TRIGGER todavía no posee un event store específico por
         * Engine. Se convierte su outcome actual en eventos deterministas por
         * participante, garantizando exactamente-una-emisión mientras ese
         * participante permanezca seleccionado por la regla.
         */
        $resolutionRuntime =
            $runtime;

        unset(
            $resolutionRuntime['timed_outcomes']
        );

        $resolution =
            $this->outcomeResolver
            ->resolve(
                collect([
                    $exit,
                ]),
                $resolutionRuntime,
                $participantIds
            );

        $outcome =
            collect(
                $resolution['outcomes']
            )
            ->firstWhere(
                'exit_id',
                (int) $exit->id
            );

        if (! $outcome) {
            return [];
        }

        return collect(
            $outcome['participant_ids']
            ??
            []
        )
            ->unique()
            ->values()
            ->map(
                fn($participantId) => [
                    'id' =>
                    'RULE_TRIGGER:'
                        . $nodeId
                        . ':'
                        . $exit->id
                        . ':'
                        . $participantId,

                    'type' =>
                    'RULE_TRIGGER',

                    'participant_id' =>
                    $participantId,
                ]
            )
            ->all();
    }

    private function eliminationMatchesExit(
        array $runtime,
        array $event,
        $exit
    ): bool {
        if (
            in_array(
                $exit->selector_type,
                [
                    'ELIMINATED',
                    'MATCH_LOSERS',
                ],
                true
            )
        ) {
            return true;
        }

        $expectedRoundSize =
            (int) (
                $exit->selector_round_size
                ??
                0
            );

        if ($expectedRoundSize <= 1) {
            return false;
        }

        $actualRoundSize =
            (int) (
                $event['round_participants']
                ??
                $this->roundParticipantsForElimination(
                    $runtime,
                    $event
                )
            );

        return
            $actualRoundSize
            ===
            $expectedRoundSize;
    }

    private function roundParticipantsForElimination(
        array $runtime,
        array $event
    ): int {
        $roundNumber =
            (int) (
                $event['round_number']
                ??
                0
            );

        $round =
            collect(
                $runtime['rounds']
                ??
                []
            )
            ->first(
                fn($candidate) =>
                (int) ($candidate['number'] ?? 0)
                ===
                $roundNumber
            );

        return (int) (
            $round['participants_in_round']
            ??
            $round['participants_count']
            ??
            0
        );
    }

    private function emissionKey(
        int $nodeId,
        int $exitId,
        string $eventId
    ): string {
        return
            'NODE:'
            . $nodeId
            . ':EXIT:'
            . $exitId
            . ':EVENT:'
            . $eventId;
    }

    private function routeNode(
        array $state,
        TournamentTemplate $template,
        int $nodeId
    ): array {
        $nodeModel =
            $template
            ->graphNodes
            ->firstWhere(
                'id',
                $nodeId
            );

        if (
            ! $nodeModel
            ||
            ! isset(
                $state['nodes'][$nodeId]['runtime']
            )
        ) {
            return $state;
        }

        $node =
            &$state['nodes'][$nodeId];

        if (
            $node['runtime']['status']
            !==
            'COMPLETED'
        ) {
            unset($node);

            return $state;
        }

        $resolution =
            $this->outcomeResolver
            ->resolve(
                $nodeModel
                    ->phaseTemplate
                    ->exits,
                $node['runtime'],
                $node['participant_ids']
            );

        $node['runtime']['normalized_outcomes'] =
            $resolution['outcomes'];

        $node['runtime']['unassigned_participant_ids'] =
            $resolution['unassigned_ids'];

        foreach (
            $resolution['outcomes']
            as
            $outcome
        ) {
            $exit =
                $nodeModel
                ->phaseTemplate
                ->exits
                ->firstWhere(
                    'id',
                    (int) $outcome['exit_id']
                );

            if ($exit) {
                $this->assertExitContract(
                    $exit,
                    $outcome['participant_ids']
                );
            }

            $connections =
                $template
                ->graphConnections
                ->where(
                    'source_type',
                    'PHASE_EXIT'
                )
                ->where(
                    'source_node_id',
                    $nodeId
                )
                ->where(
                    'source_phase_exit_id',
                    $outcome['exit_id']
                );

            $result =
                $this->connectionRouter
                ->route(
                    $state,
                    $connections,
                    $outcome['participant_ids'],
                    'NODE',
                    $nodeId,
                    $outcome['exit_id']
                );

            $state =
                $result['state'];

            if (
                $result['remaining_ids']
                !==
                []
            ) {
                $this->stranded(
                    $state,
                    $result['remaining_ids'],
                    'La salida '
                        .
                        $outcome['exit_name']
                        .
                        ' dejó participantes sin ruta.'
                );
            }

            foreach (
                $result['touched_node_ids']
                as
                $targetNodeId
            ) {
                $this->enqueueNodeEvaluation(
                    $state,
                    $targetNodeId
                );
            }
        }

        if (
            $resolution['unassigned_ids']
            !==
            []
        ) {
            $this->stranded(
                $state,
                $resolution['unassigned_ids'],
                "El nodo {$node['name']} produjo participantes sin Phase Exit."
            );
        }

        $node['status'] =
            'ROUTED';

        $this->event(
            $state,
            'NODE_ROUTED',
            'SUCCESS',
            $node['name']
                .
                ' completó el envío de sus resultados.'
        );

        unset($node);

        return $state;
    }

    private function assertTimedExitCapacity(
        $exit,
        array $participantIds
    ): void {
        $count =
            count(
                array_values(
                    array_unique(
                        $participantIds
                    )
                )
            );

        if (
            $exit->exact_participants !== null
            &&
            $count > (int) $exit->exact_participants
        ) {
            $this->fail(
                "La salida temporizada {$exit->name} superó su contrato exacto de {$exit->exact_participants} participante(s)."
            );
        }

        if (
            $exit->max_participants !== null
            &&
            $count > (int) $exit->max_participants
        ) {
            $this->fail(
                "La salida temporizada {$exit->name} superó su máximo de {$exit->max_participants} participante(s)."
            );
        }
    }

    private function assertExitContract(
        $exit,
        array $participantIds
    ): void {
        $count =
            count(
                array_values(
                    array_unique(
                        $participantIds
                    )
                )
            );

        if (
            $exit->exact_participants !== null
            &&
            $count !== (int) $exit->exact_participants
        ) {
            $this->fail(
                "La salida {$exit->name} debe producir exactamente {$exit->exact_participants} participante(s), pero produjo {$count}."
            );
        }

        if (
            $exit->min_participants !== null
            &&
            $count < (int) $exit->min_participants
        ) {
            $this->fail(
                "La salida {$exit->name} no alcanzó su mínimo de {$exit->min_participants} participante(s)."
            );
        }

        if (
            $exit->max_participants !== null
            &&
            $count > (int) $exit->max_participants
        ) {
            $this->fail(
                "La salida {$exit->name} superó su máximo de {$exit->max_participants} participante(s)."
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Simulación interactiva (Fase 11)
    |--------------------------------------------------------------------------
    |
    | El modo automático resuelve el enfrentamiento entero de golpe. Estos
    | tres métodos permiten hacerlo a mano: preparar el enfrentamiento,
    | generar el resultado de un participante cada vez, y avanzar al
    | siguiente cuando ya hay ganador.
    |
    | El motor solo orquesta: quién genera qué número lo decide el Game
    | Engine, y cuándo termina la batalla lo decide la serie.
    |
    */

    public function prepareEncounter(
        array $state,
        TournamentTemplate $template
    ): array {

        $this->requireRuntime($state);

        $status =
            $state['encounter']['status'] ?? null;

        /* Ya hay uno en curso o resuelto pendiente de avanzar */
        if ($status !== null) {
            return $state;
        }

        $this->loadGraph($template);

        /*
         * Justo después de iniciar, el grafo todavía tiene operaciones
         * pendientes: repartir los Starts, abrir la primera fase. Sin
         * consumirlas no existe ningún encuentro que jugar.
         *
         * Se avanzan SOLO esas operaciones. En cuanto la cola se vacía se
         * para, porque el siguiente paso ya sería simular el encuentro
         * automáticamente, que es justo lo contrario de lo que ha pedido
         * el usuario.
         */
        $state =
            $this->openNextEncounter(
                $state,
                $template
            );

        $target =
            $this->pendingEncounterTarget($state);

        if (! $target) {
            $this->fail(
                'No hay ningún enfrentamiento listo para jugarse. '
                    . 'Puede que la fase actual espere una decisión manual '
                    . 'o que la competición ya haya terminado.'
            );
        }

        [$nodeId, $match] = $target;

        if (
            ! $this->encounterRuntime
                ->isPlayable($state, $match)
        ) {
            $this->fail(
                'Esta competición no tiene un juego asignado, así que sus '
                    . 'enfrentamientos solo pueden resolverse automáticamente.'
            );
        }

        return $this->encounterRuntime
            ->prepare(
                $state,
                $nodeId,
                $match,
                $state['nodes'][$nodeId]['name'] ?? null
            );
    }

    public function rollEncounter(
        array $state,
        TournamentTemplate $template,
        array $payload = []
    ): array {

        $state =
            $this->prepareEncounter(
                $state,
                $template
            );

        $state =
            $this->encounterRuntime
            ->roll(
                $state,
                isset($payload['participant_id'])
                    ? (string) $payload['participant_id']
                    : null,
                (bool) ($payload['all'] ?? false)
            );

        /*
         * En cuanto el enfrentamiento tiene ganador se entrega a la serie.
         * No se espera a que el usuario avance: así el resultado queda
         * guardado aunque cierre la ventana justo después de verlo.
         */
        if (
            ($state['encounter']['status'] ?? null)
            === 'RESOLVED'
        ) {
            $state =
                $this->commitEncounter(
                    $state,
                    $template
                );
        }

        return $state;
    }

    public function advanceEncounter(
        array $state,
        TournamentTemplate $template
    ): array {

        $this->requireRuntime($state);

        if (
            ($state['encounter']['status'] ?? null)
            === 'ROLLING'
        ) {
            $this->fail(
                'Este enfrentamiento todavía no ha terminado.'
            );
        }

        unset($state['encounter']);

        /*
         * Se intenta abrir el siguiente. Que no haya ninguno no es un
         * error: puede que la fase haya terminado.
         */
        $this->loadGraph($template);

        $state =
            $this->openNextEncounter(
                $state,
                $template
            );

        $target =
            $this->pendingEncounterTarget($state);

        if (! $target) {
            return $state;
        }

        [$nodeId, $match] = $target;

        if (
            ! $this->encounterRuntime
                ->isPlayable($state, $match)
        ) {
            return $state;
        }

        return $this->encounterRuntime
            ->prepare(
                $state,
                $nodeId,
                $match,
                $state['nodes'][$nodeId]['name'] ?? null
            );
    }

    /**
     * Entrega el enfrentamiento resuelto a la serie y deja que el grafo
     * siga su curso. Es exactamente lo que hace el modo automático después
     * de resolver, sin volver a generar nada.
     */
    private function commitEncounter(
        array $state,
        TournamentTemplate $template
    ): array {

        $encounter =
            $state['encounter'];

        $nodeId =
            (int) $encounter['node_id'];

        $runtime =
            $state['nodes'][$nodeId]['runtime'];

        $match =
            collect($runtime['rounds'] ?? [])
            ->flatMap(
                fn($round) =>
                $round['matches'] ?? []
            )
            ->firstWhere('id', $encounter['battle_key']);

        if (! $match) {
            $this->fail(
                'El enfrentamiento ya no existe en el Runtime.'
            );
        }

        [$scoreA, $scoreB] =
            $this->encounterRuntime
            ->seriesScores(
                $encounter,
                $match
            );

        $runtime =
            $this->engineManager
            ->submit(
                $state['nodes'][$nodeId]['phase_type'],
                $runtime,
                $match['id'],
                $scoreA,
                $scoreB
            );

        $state['nodes'][$nodeId]['runtime'] =
            $runtime;

        $state['nodes'][$nodeId]['status'] =
            $runtime['status'];

        $this->syncStatistics(
            $state,
            $runtime
        );

        /* Marcador de la batalla ya actualizado, para pintarlo al momento */
        $series =
            $runtime['series'][$match['id']] ?? null;

        $state['encounter']['series']['score_a'] =
            (int) ($series['game_wins_a'] ?? 0);

        $state['encounter']['series']['score_b'] =
            (int) ($series['game_wins_b'] ?? 0);

        $state['encounter']['series']['games_played'] =
            (int) ($series['games_played'] ?? 0);

        $state['encounter']['battle_completed'] =
            ($series['status'] ?? null) === 'COMPLETED'
            || $series === null;

        $this->event(
            $state,
            'ENCOUNTER_PLAYED',
            'INFO',
            $match['id']
                . ' — '
                . ($encounter['summary'] ?? 'enfrentamiento resuelto')
        );

        return $this->afterNodeResult(
            $state,
            $template,
            $nodeId
        );
    }

    /**
     * Consume las operaciones pendientes del grafo hasta que aparezca un
     * encuentro jugable.
     *
     * Nunca simula: en cuanto la cola de operaciones se vacía, se detiene.
     */
    private function openNextEncounter(
        array $state,
        TournamentTemplate $template
    ): array {

        $guard = 0;

        while (
            $this->pendingEncounterTarget($state) === null
            && $guard < self::MAX_OPERATIONS
        ) {
            if (
                ($state['graph_runtime']['operation_queue'] ?? [])
                === []
            ) {
                break;
            }

            $guard++;

            $state =
                $this->step(
                    $state,
                    $template
                );
        }

        return $state;
    }

    /**
     * Primer enfrentamiento jugable del recorrido.
     *
     * @return array{0: int, 1: array}|null
     */
    private function pendingEncounterTarget(array $state): ?array
    {
        $node =
            collect($state['nodes'] ?? [])
            ->first(
                fn($node) =>
                isset($node['runtime'])
                    && $node['status'] === 'RUNNING'
                    && $node['runtime']['status'] === 'RUNNING'
            );

        if (! $node) {
            return null;
        }

        $runtime =
            $node['runtime'];

        /*
         * Solo encuentros de dos participantes.
         *
         * Los encuentros de selección —N entran, K clasifican— los resuelve
         * el motor de la fase con su propia lógica, no una serie, así que
         * no hay marcador que entregarle. El Game Engine ya sabe ordenar a
         * N participantes, pero conectarlo a esa ruta es trabajo de la
         * fase, no del simulador: ofrecerlo aquí dejaría el encuentro
         * resuelto sin poder registrarlo.
         */
        $match =
            collect($runtime['rounds'] ?? [])
            ->flatMap(
                fn($round) =>
                $round['matches'] ?? []
            )
            ->first(
                fn($match) =>
                $match['status'] === 'PENDING'
                    && ($match['participant_a_id'] ?? null)
                    && ($match['participant_b_id'] ?? null)
            );

        return $match
            ? [(int) $node['id'], $match]
            : null;
    }

    private function simulateOneMatch(
        array $state,
        TournamentTemplate $template,
        int $nodeId
    ): array {
        $runtime =
            $state['nodes'][$nodeId]['runtime'];

        $isStructureGraph =
            ($runtime['mode'] ?? null)
            ===
            'STRUCTURE_GRAPH';

        $match =
            collect(
                $runtime['rounds']
            )
            ->flatMap(
                fn($round) =>
                $round['matches']
                    ??
                    []
            )
            ->first(
                fn($match) =>
                $match['status']
                    ===
                    'PENDING'
                &&
                (
                    $isStructureGraph
                        ? count($match['participant_ids'] ?? [])
                            >= (int) ($match['qualifiers_count'] ?? 1)
                        : $match['participant_a_id']
                            && $match['participant_b_id']
                )
            );

        if (! $match) {
            $state['nodes'][$nodeId]['status'] =
                'BLOCKED';

            $state['graph_runtime']['diagnostics'][] = [
                'level' =>
                'ERROR',

                'code' =>
                'ENGINE_WITHOUT_PENDING_MATCH',

                'message' =>
                $state['nodes'][$nodeId]['name']
                    .
                    ' está activo, pero no tiene encuentros ejecutables.',
            ];

            return $state;
        }

        if ($isStructureGraph) {
            $scoreBased =
                ($match['resolution_mode'] ?? null) === 'SCORE'
                && count($match['participant_ids'] ?? []) === 2
                && (int) ($match['qualifiers_count'] ?? 1) === 1;

            if ($scoreBased) {

                if (
                    $this->encounterRuntime
                        ->isPlayable($state, $match)
                ) {
                    $state =
                        $this->encounterRuntime
                        ->resolveNow(
                            $state,
                            $nodeId,
                            $match,
                            $state['nodes'][$nodeId]['name'] ?? null
                        );

                    [$scoreA, $scoreB] =
                        $this->encounterRuntime
                        ->seriesScores(
                            $state['encounter'],
                            $match
                        );
                } else {
                    [$scoreA, $scoreB] = $this->randomScore($runtime);
                }

                $runtime = $this->engineManager->submit(
                    $state['nodes'][$nodeId]['phase_type'],
                    $runtime,
                    $match['id'],
                    $scoreA,
                    $scoreB
                );
            } else {
                $runtime =
                    $this->engineManager
                    ->simulateSelection(
                        $state['nodes'][$nodeId]['phase_type'],
                        $runtime,
                        $match['id']
                    );
            }

            $state['nodes'][$nodeId]['runtime'] =
                $runtime;

            $state['nodes'][$nodeId]['status'] =
                $runtime['status'];

            $this->syncStatistics(
                $state,
                $runtime
            );

            $completedMatch =
                collect($runtime['rounds'])
                ->flatMap(fn($round) => $round['matches'] ?? [])
                ->firstWhere('id', $match['id']);

            $seriesPending = ($runtime['series'][$match['id']]['status'] ?? null) === 'RUNNING';

            $this->event(
                $state,
                $seriesPending ? 'SERIES_GAME_SIMULATED' : 'ENCOUNTER_SIMULATED',
                'INFO',
                $seriesPending
                    ? $match['id'] . ' registró un juego de su serie.'
                    : $match['id'] . ' completó su resolución.'
            );

            return $this->afterNodeResult(
                $state,
                $template,
                $nodeId
            );
        }

        /*
         * Aquí es donde entra el juego (Fase 11). Antes, el resultado lo
         * decidía un random_int incrustado en el motor; ahora lo decide el
         * Game Engine de la competición, y el motor solo se encarga de
         * llevar ese resultado a la serie.
         */
        $narration = null;

        if (
            $this->encounterRuntime
                ->isPlayable($state, $match)
        ) {
            $state =
                $this->encounterRuntime
                ->resolveNow(
                    $state,
                    $nodeId,
                    $match,
                    $state['nodes'][$nodeId]['name'] ?? null
                );

            [
                $scoreA,
                $scoreB,
            ] = $this->encounterRuntime
                ->seriesScores(
                    $state['encounter'],
                    $match
                );

            $narration =
                $state['encounter']['summary'] ?? null;
        } else {
            [
                $scoreA,
                $scoreB,
            ] = $this->randomScore(
                $runtime
            );
        }

        $runtime =
            $this->engineManager
            ->submit(
                $state['nodes'][$nodeId]['phase_type'],
                $runtime,
                $match['id'],
                $scoreA,
                $scoreB
            );

        $state['nodes'][$nodeId]['runtime'] =
            $runtime;

        $state['nodes'][$nodeId]['status'] =
            $runtime['status'];

        $this->syncStatistics(
            $state,
            $runtime
        );

        $this->event(
            $state,
            'MATCH_SIMULATED',
            'INFO',
            $narration
                ? $match['id'] . ' — ' . $narration
                : $match['id'] . " terminó {$scoreA}-{$scoreB}."
        );

        return $this->afterNodeResult(
            $state,
            $template,
            $nodeId
        );
    }

    private function checkCompletion(
        array $state,
        TournamentTemplate $template
    ): array {
        if (
            $state['graph_runtime']['operation_queue']
            !==
            []
        ) {
            return $state;
        }

        $hasPendingDecision = collect($state['nodes'])
            ->contains(fn($node) =>
                ($node['status'] ?? null) === 'AWAITING_DECISION'
                || (($node['runtime']['status'] ?? null) === 'AWAITING_DECISION')
            );

        if ($hasPendingDecision) {
            $state['graph_runtime']['status'] = 'AWAITING_DECISION';
            return $state;
        }

        $hasRunningNodes =
            collect(
                $state['nodes']
            )
            ->contains(
                fn($node) =>
                in_array(
                    $node['status'],
                    [
                        'RUNNING',
                        'READY',
                        'COMPLETED',
                    ],
                    true
                )
            );

        if ($hasRunningNodes) {
            return $state;
        }

        $outsideTerminalIds =
            collect(
                $state['participants']
            )
            ->reject(
                fn($participant) =>
                $participant['status']
                    ===
                    'FINISHED'
            )
            ->pluck(
                'lab_id'
            )
            ->values()
            ->all();

        if (
            $outsideTerminalIds === []
            &&
            $state['graph_runtime']['stranded_participant_ids']
            ===
            []
        ) {
            $state['graph_runtime']['status'] =
                'COMPLETED';

            $state['graph_runtime']['completed_at'] =
                now()->toIso8601String();

            $state['status'] =
                'COMPLETED';

            $this->event(
                $state,
                'TOURNAMENT_COMPLETED',
                'SUCCESS',
                'La simulación completa del torneo terminó correctamente.'
            );

            return $state;
        }

        /*
         * Antes de declarar deadlock, evaluar nodos que ya tengan
         * cerradas todas sus conexiones.
         */

        foreach (
            $template->graphNodes
            as
            $node
        ) {
            if (
                $this->allIncomingConnectionsClosed(
                    $state,
                    $node
                )
                &&
                ! isset(
                    $state['nodes'][$node->id]['runtime']
                )
                &&
                ! in_array(
                    $state['nodes'][$node->id]['status'],
                    [
                        'BLOCKED',
                        'SKIPPED',
                    ],
                    true
                )
            ) {
                $this->enqueueNodeEvaluation(
                    $state,
                    (int)
                    $node->id
                );
            }
        }

        if (
            $state['graph_runtime']['operation_queue']
            !==
            []
        ) {
            return $state;
        }

        $state['graph_runtime']['status'] =
            'BLOCKED';

        $state['status'] =
            'BLOCKED';

        $state['graph_runtime']['diagnostics'][] = [
            'level' =>
            'ERROR',

            'code' =>
            'GRAPH_RUNTIME_DEADLOCK',

            'message' =>
            'La simulación no puede avanzar y todavía existen participantes fuera de un terminal.',
        ];

        return $state;
    }

    private function allIncomingConnectionsClosed(
        array $state,
        $node
    ): bool {
        $hasIncoming = false;

        foreach ($node->entryPorts->where('status', 'ACTIVE') as $port) {
            $incomingIds = $port
                ->incomingConnections
                ->where('status', 'ACTIVE')
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();

            if ($incomingIds === []) {
                continue;
            }

            $hasIncoming = true;

            if (! \App\Services\Tournaments\Graph\Flow\EntryPortMergePolicy::allFinal(
                $incomingIds,
                $state['connections'] ?? []
            )) {
                return false;
            }
        }

        return $hasIncoming;
    }

    private function portContractErrors(
        array $node
    ): array {
        $errors =
            [];

        foreach (
            $node['entry_ports']
            as
            $port
        ) {
            $count =
                count(
                    $port['participant_ids']
                );

            if (
                $port['is_required']
                &&
                $count === 0
            ) {
                $errors[] =
                    "El puerto {$port['name']} es obligatorio y no recibió participantes.";
            }

            if (
                $port['exact_participants'] !== null
                &&
                $count
                !==
                $port['exact_participants']
            ) {
                $errors[] =
                    "El puerto {$port['name']} necesita exactamente {$port['exact_participants']} participantes.";
            }

            if (
                $port['min_participants'] !== null
                &&
                $count
                <
                $port['min_participants']
            ) {
                $errors[] =
                    "El puerto {$port['name']} necesita al menos {$port['min_participants']} participantes.";
            }

            if (
                $port['max_participants'] !== null
                &&
                $count
                >
                $port['max_participants']
            ) {
                $errors[] =
                    "El puerto {$port['name']} admite como máximo {$port['max_participants']} participantes.";
            }
        }

        return $errors;
    }

    private function enqueueNodeEvaluation(
        array &$state,
        int $nodeId
    ): void {
        $this->enqueue(
            $state,
            [
                'key' =>
                'EVALUATE_NODE:'
                    .
                    $nodeId
                    .
                    ':'
                    .
                    count(
                        $state['graph_runtime']['processed_operations']
                    ),

                'type' =>
                'EVALUATE_NODE',

                'node_id' =>
                $nodeId,
            ]
        );
    }

    private function enqueue(
        array &$state,
        array $operation
    ): void {
        $queuedKeys =
            collect(
                $state['graph_runtime']['operation_queue']
            )
            ->pluck(
                'key'
            );

        if (
            $queuedKeys->contains(
                $operation['key']
            )
            ||
            in_array(
                $operation['key'],
                $state['graph_runtime']['processed_operations'],
                true
            )
        ) {
            return;
        }

        $state['graph_runtime']['operation_queue'][] =
            $operation;
    }

    private function stranded(
        array &$state,
        array $participantIds,
        string $message
    ): void {
        $state['graph_runtime']['stranded_participant_ids'] =
            array_values(
                array_unique([
                    ...$state['graph_runtime']['stranded_participant_ids'],
                    ...$participantIds,
                ])
            );

        foreach (
            $participantIds
            as
            $participantId
        ) {
            if (
                isset(
                    $state['participants'][$participantId]
                )
            ) {
                $state['participants'][$participantId]['status'] =
                    'STRANDED';
            }
        }

        $state['graph_runtime']['diagnostics'][] = [
            'level' =>
            'ERROR',

            'code' =>
            'UNROUTED_PARTICIPANTS',

            'message' =>
            $message,

            'participant_ids' =>
            $participantIds,
        ];
    }

    private function syncStatistics(
        array &$state,
        array $runtime
    ): void {
        foreach (
            $runtime['standings']
                ??
                []
            as
            $row
        ) {
            $participantId =
                $row['participant_id'];

            if (
                ! isset(
                    $state['participants'][$participantId]
                )
            ) {
                continue;
            }

            $state['participants'][$participantId]['statistics'] = [
                'matches' =>
                $row['played']
                    ??
                    0,

                'wins' =>
                $row['wins']
                    ??
                    0,

                'draws' =>
                $row['draws']
                    ??
                    0,

                'losses' =>
                $row['losses']
                    ??
                    0,

                'points' =>
                $row['points']
                    ??
                    0,
            ];
        }
    }

    private function randomScore(
        array $runtime
    ): array {
        $scoreA =
            random_int(
                0,
                5
            );

        $scoreB =
            random_int(
                0,
                5
            );

        if (
            $runtime['engine']
            ===
            'SINGLE_ELIMINATION'
            ||
            ! (
                $runtime['allow_draws']
                ??
                false
            )
        ) {
            while (
                $scoreA
                ===
                $scoreB
            ) {
                $scoreB =
                    random_int(
                        0,
                        5
                    );
            }
        }

        return [
            $scoreA,
            $scoreB,
        ];
    }

    private function progressFingerprint(
        array $state
    ): string {
        $nodeProgress =
            collect(
                $state['nodes']
            )
            ->mapWithKeys(
                fn($node, $nodeId) => [
                    $nodeId => [
                        'status' =>
                        $node['status']
                        ??
                        null,

                        'runtime_status' =>
                        $node['runtime']['status']
                        ??
                        null,

                        'matches_completed' =>
                        (int) (
                            $node['runtime']['matches_completed']
                            ??
                            0
                        ),

                        'eliminations' =>
                        count(
                            $node['runtime']['eliminations']
                            ??
                            []
                        ),

                        'series_games' =>
                        collect(
                            $node['runtime']['series']
                            ??
                            []
                        )
                        ->sum(
                            fn($series) =>
                            count(
                                $series['games']
                                ??
                                []
                            )
                        ),
                    ],
                ]
            )
            ->all();

        return md5(
            json_encode([
                $state['graph_runtime']['operation_queue'],
                $state['graph_runtime']['operation_count'],
                $nodeProgress,
                collect(
                    $state['terminals']
                )
                    ->pluck(
                        'status',
                        'id'
                    )
                    ->all(),
                count(
                    $state['graph_runtime']['emission_ledger']
                    ??
                    []
                ),
                $state['summary']['completed_matches']
                    ??
                    0,
            ])
        );
    }

    private function event(
        array &$state,
        string $type,
        string $level,
        string $message
    ): void {
        $state['timeline'][] = [
            'step' =>
            count(
                $state['timeline']
            )
                +
                1,

            'type' =>
            $type,

            'level' =>
            $level,

            'message' =>
            $message,

            'created_at' =>
            now()->toIso8601String(),
        ];

        $state['updated_at'] =
            now()->toIso8601String();
    }

    private function loadGraph(
        TournamentTemplate $template
    ): void {
        $template->load([
            'graphStarts.outgoingConnections',

            'graphNodes.phaseTemplate.exits',

            'graphNodes.phaseTemplate.singleEliminationSetting',
            'graphNodes.phaseTemplate.singleEliminationRoundRules',
            'graphNodes.phaseTemplate.inputGates.outgoingConnections',
            'graphNodes.phaseTemplate.singleEliminationRounds.encounters.slots',
            'graphNodes.phaseTemplate.singleEliminationRounds.encounters.results.outgoingConnections',
            'graphNodes.phaseTemplate.singleEliminationConnections',

            'graphNodes.phaseTemplate.roundRobinSetting',
            'graphNodes.phaseTemplate.roundRobinTiebreakers',

            'graphNodes.phaseTemplate.groupStageSetting',
            'graphNodes.phaseTemplate.groupStageGroups',
            'graphNodes.phaseTemplate.groupStageTiebreakers',
            'graphNodes.phaseTemplate.groupStageAdvancementRules.phaseExit',
            'graphNodes.phaseTemplate.groupStageAdvancementRules.group',

            'graphNodes.phaseTemplate.swissSetting',
            'graphNodes.phaseTemplate.swissRoundRules',
            'graphNodes.phaseTemplate.swissTiebreakers',
            'graphNodes.phaseTemplate.swissAdvancementRules.phaseExit',

            'graphNodes.entryPorts.incomingConnections',
            'graphTerminals.incomingConnections',

            'graphConnections.sourceStart',
            'graphConnections.sourceNode',
            'graphConnections.sourcePhaseExit',
            'graphConnections.targetEntryPort.node',
            'graphConnections.targetTerminal',
        ]);
    }

    private function requireRuntime(
        array $state
    ): void {
        if (
            ! isset(
                $state['graph_runtime']
            )
            ||
            ($state['graph_runtime']['status'] ?? null)
            !==
            'RUNNING'
        ) {
            $this->fail(
                'El Tournament Graph Runtime no está en ejecución.'
            );
        }
    }

    private function fail(
        string $message
    ): never {
        throw ValidationException::withMessages([
            'runtime' => [
                $message,
            ],
        ]);
    }
}
