<?php

namespace App\Services\Tournaments\CompetitionLab\Runtime;

use App\Models\TournamentTemplate;
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
        LabPhaseEngineManager $engineManager
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

    private function simulateOneMatch(
        array $state,
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
            $runtime =
                $this->engineManager
                ->simulateSelection(
                    $state['nodes'][$nodeId]['phase_type'],
                    $runtime,
                    $match['id']
                );

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

            $this->event(
                $state,
                'ENCOUNTER_SIMULATED',
                'INFO',
                $match['id']
                    . ' seleccionó '
                    . count(
                        $completedMatch['qualifier_ids'] ?? []
                    )
                    . ' clasificado(s).'
            );

            return $this->afterNodeResult(
                $state,
                $nodeId
            );
        }

        [
            $scoreA,
            $scoreB,
        ] = $this->randomScore(
            $runtime
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

        $this->event(
            $state,
            'MATCH_SIMULATED',
            'INFO',
            $match['id']
                .
                " terminó {$scoreA}-{$scoreB}."
        );

        return $this->afterNodeResult(
            $state,
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
        $incomingIds =
            $node
            ->entryPorts
            ->flatMap(
                fn($port) =>
                $port
                    ->incomingConnections
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->pluck(
                        'id'
                    )
            )
            ->unique()
            ->values();

        if ($incomingIds->isEmpty()) {
            return false;
        }

        return $incomingIds
            ->every(
                fn($connectionId) =>
                in_array(
                    $state['connections'][$connectionId]['status']
                        ??
                        'PENDING',
                    [
                        'ROUTED',
                        'CLOSED_EMPTY',
                    ],
                    true
                )
            );
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
        return md5(
            json_encode([
                $state['graph_runtime']['operation_queue'],
                $state['graph_runtime']['operation_count'],
                collect(
                    $state['nodes']
                )
                    ->pluck(
                        'status',
                        'id'
                    )
                    ->all(),
                collect(
                    $state['terminals']
                )
                    ->pluck(
                        'status',
                        'id'
                    )
                    ->all(),
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
