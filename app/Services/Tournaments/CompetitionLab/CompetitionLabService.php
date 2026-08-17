<?php

namespace App\Services\Tournaments\CompetitionLab;

use App\Models\TournamentTemplate;
use App\Models\User;
use App\Services\Tournaments\CompetitionLab\Engines\LabPhaseEngineManager;
use App\Services\Tournaments\CompetitionLab\Runtime\TournamentGraphRuntimeService;
use Illuminate\Validation\ValidationException;

class CompetitionLabService
{
    public function __construct(
        private readonly
        LabStateFactory $stateFactory,

        private readonly
        LabStateTokenService $tokenService,

        private readonly
        LabPhaseEngineManager $engineManager,

        private readonly
        TournamentGraphRuntimeService $graphRuntime
    ) {}

    public function compatibility(
        TournamentTemplate $template
    ): array {
        $template->loadMissing(
            'graphNodes.phaseTemplate'
        );

        $errors = [];

        foreach (
            $template->graphNodes
            as
            $node
        ) {
            $phase = $node->phaseTemplate;

            if (! $phase) {
                $errors[] = [
                    'code' => 'LAB_PHASE_MISSING',
                    'message' =>
                    'El nodo “'
                    . $node->name
                    . '” no tiene una PhaseTemplate disponible.',
                ];

                continue;
            }

            if (
                ! $this->engineManager->supports(
                    $phase->phase_type
                )
            ) {
                $errors[] = [
                    'code' => 'LAB_ENGINE_UNAVAILABLE',
                    'message' =>
                    'El nodo “'
                    . $node->name
                    . '” usa '
                    . $phase->type_label
                    . ', un tipo que todavía no tiene motor de ejecución.',
                ];
            }
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    public function initialize(
        TournamentTemplate $template,
        User $user,
        array $configuration
    ): array {
        $this->assertCompatibleGraph(
            $template
        );

        $state =
            $this->stateFactory
            ->create(
                $template,
                $user,
                $configuration
            );

        return $this->response(
            $state
        );
    }

    public function execute(
        TournamentTemplate $template,
        User $user,
        string $token,
        string $action,
        array $payload = []
    ): array {
        $state =
            $this->tokenService
            ->decode(
                $token
            );

        $this->validateOwnership(
            $state,
            $template,
            $user
        );

        if (
            in_array(
                $action,
                [
                    'PREPARE_NODE',
                    'START_TOURNAMENT',
                    'STEP_RUNTIME',
                    'RUN_TOURNAMENT',
                    'RESOLVE_MANUAL_DECISION',
                ],
                true
            )
        ) {
            $this->assertCompatibleGraph(
                $template
            );
        }

        $state =
            match ($action) {
                'START' =>
                $this->start(
                    $state
                ),

                'PAUSE' =>
                $this->pause(
                    $state
                ),

                'RESUME' =>
                $this->resume(
                    $state
                ),

                'RESET' =>
                $this->reset(
                    $state
                ),
                'PREPARE_NODE' =>
                $this->prepareNode(
                    $state,
                    $template,
                    $payload
                ),

                'SUBMIT_MATCH_RESULT' =>
                $this->submitResult(
                    $state,
                    $template,
                    $payload
                ),

                'SUBMIT_ENCOUNTER_RESULT' =>
                $this->submitEncounterResult(
                    $state,
                    $template,
                    $payload
                ),

                'SIMULATE_MATCH' =>
                $this->simulateMatch(
                    $state,
                    $template,
                    $payload
                ),

                'SIMULATE_ROUND' =>
                $this->simulateRound(
                    $state,
                    $template,
                    $payload
                ),

                'RESOLVE_MANUAL_DECISION' =>
                $this->resolveManualDecision(
                    $state,
                    $template,
                    $payload
                ),

                'START_TOURNAMENT' =>
                $this->graphRuntime
                    ->initialize(
                        $state,
                        $template
                    ),

                'STEP_RUNTIME' =>
                $this->graphRuntime
                    ->step(
                        $state,
                        $template
                    ),

                'RUN_TOURNAMENT' =>
                $this->graphRuntime
                    ->run(
                        $state,
                        $template,
                        (int)
                        (
                            $payload['maximum_operations']
                            ??
                            1000
                        )
                    ),

                default =>
                $this->fail(
                    'La acción solicitada no está disponible.'
                ),
            };

        return $this->response(
            $state
        );
    }


    private function assertCompatibleGraph(
        TournamentTemplate $template
    ): void {
        $compatibility =
            $this->compatibility(
                $template
            );

        if ($compatibility['valid']) {
            return;
        }

        throw ValidationException::withMessages([
            'graph' => collect(
                $compatibility['errors']
            )
                ->pluck('message')
                ->values()
                ->all(),
        ]);
    }

    private function start(
        array $state
    ): array {
        if (
            $state['status']
            !==
            'READY'
        ) {
            $this->fail(
                'El Lab solo puede iniciarse desde el estado READY.'
            );
        }

        $state['status'] =
            'RUNNING';

        foreach (
            $state['participants']
            as
            &$participant
        ) {
            $participant['status'] =
                'ACTIVE';
        }

        unset($participant);

        $this->addEvent(
            $state,
            'LAB_STARTED',
            'SUCCESS',
            'La prueba temporal comenzó. Los participantes están activos en sus Starts.'
        );

        return $state;
    }

    private function pause(
        array $state
    ): array {
        if (
            $state['status']
            !==
            'RUNNING'
        ) {
            $this->fail(
                'Solo puede pausarse un Lab en ejecución.'
            );
        }

        $state['status'] =
            'PAUSED';

        $this->addEvent(
            $state,
            'LAB_PAUSED',
            'WARNING',
            'La prueba temporal fue pausada.'
        );

        return $state;
    }

    private function resume(
        array $state
    ): array {
        if (
            $state['status']
            !==
            'PAUSED'
        ) {
            $this->fail(
                'Solo puede reanudarse un Lab pausado.'
            );
        }

        $state['status'] =
            'RUNNING';

        $this->addEvent(
            $state,
            'LAB_RESUMED',
            'SUCCESS',
            'La prueba temporal fue reanudada.'
        );

        return $state;
    }

    private function reset(
        array $state
    ): array {
        $state['status'] =
            'READY';

        foreach (
            $state['participants']
            as
            &$participant
        ) {
            $participant['status'] =
                'WAITING';

            $participant['statistics'] = [
                'matches' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'points' => 0,
            ];

            $participant['journey'] =
                array_slice(
                    $participant['journey'],
                    0,
                    1
                );

            $participant['current_location'] =
                $participant['journey'][0];
        }

        unset($participant);

        foreach (
            $state['nodes']
            as
            &$node
        ) {
            $node['status'] =
                'LOCKED';

            $node['participant_ids'] =
                [];
            unset(
                $node['runtime']
            );

            foreach (
                $node['entry_ports']
                as
                &$port
            ) {
                $port['status'] =
                    'EMPTY';

                $port['participant_ids'] =
                    [];
                $port['received_connection_ids'] =
                    [];
                $port['connection_payloads'] =
                    [];
            }

            unset($port);
        }

        unset($node);

        foreach (
            $state['terminals']
            as
            &$terminal
        ) {
            $terminal['status'] =
                'EMPTY';

            $terminal['participant_ids'] =
                [];
        }

        unset($terminal);

        foreach (
            $state['connections']
                ??
                []
            as
            &$connection
        ) {
            $connection['status'] =
                'PENDING';

            $connection['participant_ids'] =
                [];

            $connection['routed_count'] =
                0;
        }

        unset($connection);

        unset(
            $state['graph_runtime']
        );

        $state['timeline'] = [];

        $this->addEvent(
            $state,
            'LAB_RESET',
            'INFO',
            'El Competition Lab volvió a su estado inicial.'
        );

        return $state;
    }

    private function validateOwnership(
        array $state,
        TournamentTemplate $template,
        User $user
    ): void {
        if (
            ($state['schema_version'] ?? null)
            !==
            1
        ) {
            $this->fail(
                'La versión del estado temporal no es compatible.'
            );
        }

        if (
            (int) (
                $state['user_id']
                ??
                0
            )
            !==
            (int) $user->id
        ) {
            $this->fail(
                'El estado temporal pertenece a otro usuario.'
            );
        }

        if (
            (int) (
                $state['tournament_template_id']
                ??
                0
            )
            !==
            (int) $template->id
        ) {
            $this->fail(
                'El estado temporal pertenece a otra plantilla.'
            );
        }
    }

    private function prepareNode(
        array $state,
        TournamentTemplate $template,
        array $payload
    ): array {
        $this->requireRunning(
            $state
        );

        $nodeId =
            (int)
            (
                $payload['node_id']
                ??
                0
            );

        $participantIds =
            array_values(
                $payload['participant_ids']
                    ??
                    []
            );

        if (
            ! isset(
                $state['nodes'][$nodeId]
            )
        ) {
            $this->fail(
                'El nodo solicitado no pertenece al Lab.'
            );
        }

        $node =
            $template
            ->graphNodes()
            ->with([
                'phaseTemplate.singleEliminationSetting',
                'phaseTemplate.singleEliminationRoundRules',
                'phaseTemplate.inputGates.outgoingConnections',
                'phaseTemplate.singleEliminationRounds.encounters.slots',
                'phaseTemplate.singleEliminationRounds.encounters.results.outgoingConnections',
                'phaseTemplate.singleEliminationConnections',
                'phaseTemplate.exits',

                'phaseTemplate.roundRobinSetting',
                'phaseTemplate.roundRobinTiebreakers',

                'phaseTemplate.groupStageSetting',
                'phaseTemplate.groupStageGroups',
                'phaseTemplate.groupStageTiebreakers',
                'phaseTemplate.groupStageAdvancementRules.phaseExit',
                'phaseTemplate.groupStageAdvancementRules.group',

                'phaseTemplate.swissSetting',
                'phaseTemplate.swissRoundRules',
                'phaseTemplate.swissTiebreakers',
                'phaseTemplate.swissAdvancementRules.phaseExit',
            ])
            ->find(
                $nodeId
            );

        if (
            ! $node
            ||
            ! $node->phaseTemplate
        ) {
            $this->fail(
                'El nodo no tiene una fase válida.'
            );
        }

        if (
            isset(
                $state['nodes'][$nodeId]['runtime']
            )
        ) {
            $this->fail(
                'El nodo ya fue preparado. Reinicia el Lab para prepararlo nuevamente.'
            );
        }

        foreach (
            $participantIds
            as
            $participantId
        ) {
            if (
                ! isset(
                    $state['participants'][$participantId]
                )
            ) {
                $this->fail(
                    "El participante {$participantId} no pertenece al Lab."
                );
            }
        }

        $runtime =
            $this->engineManager
            ->prepare(
                $node->phaseTemplate,
                $participantIds,
                $state['participants']
            );

        $state['nodes'][$nodeId]['runtime'] =
            $runtime;

        $state['nodes'][$nodeId]['participant_ids'] =
            $participantIds;

        $state['nodes'][$nodeId]['status'] =
            $runtime['status'];

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
                $node->code,

                'name' =>
                $node->name,
            ];

            $state['participants'][$participantId]['status'] =
                'COMPETING';

            $state['participants'][$participantId]['current_location'] =
                $location;

            $state['participants'][$participantId]['journey'][] =
                $location;
        }

        $this->syncSummary(
            $state
        );

        $this->addEvent(
            $state,
            'NODE_PREPARED',
            'SUCCESS',
            $node->name
                .
                ' fue preparado con '
                .
                count($participantIds)
                .
                ' participantes.'
        );

        return $state;
    }

    private function resolveManualDecision(
        array $state,
        TournamentTemplate $template,
        array $payload
    ): array {
        $this->requireRunning($state);

        $nodeId = (int) ($payload['node_id'] ?? 0);
        $runtime = $state['nodes'][$nodeId]['runtime'] ?? null;

        if (! $runtime || ($runtime['status'] ?? null) !== 'AWAITING_DECISION') {
            $this->fail('La fase seleccionada no está esperando una decisión manual.');
        }

        $node = $template->graphNodes()
            ->with('phaseTemplate')
            ->find($nodeId);

        if (! $node?->phaseTemplate) {
            $this->fail('El nodo no tiene una PhaseTemplate disponible.');
        }

        $runtime = $this->engineManager->resolveDecision(
            $node->phaseTemplate,
            $runtime,
            $state['participants'],
            $payload
        );

        $state = $this->applyRuntimeState(
            $state,
            $template,
            $nodeId,
            $runtime
        );

        $this->addEvent(
            $state,
            'MANUAL_DECISION_RESOLVED',
            'SUCCESS',
            'La decisión manual fue validada y la fase puede continuar.'
        );

        return $state;
    }

    private function submitResult(
        array $state,
        TournamentTemplate $template,
        array $payload
    ): array {
        $this->requireRunning(
            $state
        );

        return $this->applyResult(
            $state,
            $template,
            (int)
            ($payload['node_id'] ?? 0),
            (string)
            ($payload['match_id'] ?? ''),
            (int)
            ($payload['score_a'] ?? 0),
            (int)
            ($payload['score_b'] ?? 0)
        );
    }

    private function submitEncounterResult(
        array $state,
        TournamentTemplate $template,
        array $payload
    ): array {
        $this->requireRunning(
            $state
        );

        return $this->applySelectionResult(
            $state,
            $template,
            (int)
            ($payload['node_id'] ?? 0),
            (string)
            ($payload['match_id'] ?? ''),
            array_values(
                $payload['qualifier_ids'] ?? []
            )
        );
    }

    private function simulateMatch(
        array $state,
        TournamentTemplate $template,
        array $payload
    ): array {
        $this->requireRunning(
            $state
        );

        $nodeId =
            (int)
            ($payload['node_id'] ?? 0);

        $runtime =
            $state['nodes'][$nodeId]['runtime']
            ??
            null;

        if (! $runtime) {
            $this->fail(
                'Primero debes preparar el nodo.'
            );
        }

        if (
            ($runtime['mode'] ?? null)
            ===
            'STRUCTURE_GRAPH'
        ) {
            $matchId = (string) ($payload['match_id'] ?? '');
            $match = collect($runtime['rounds'] ?? [])
                ->flatMap(fn($round) => $round['matches'] ?? [])
                ->firstWhere('id', $matchId);

            if (
                $match
                && ($match['resolution_mode'] ?? null) === 'SCORE'
                && count($match['participant_ids'] ?? []) === 2
                && (int) ($match['qualifiers_count'] ?? 1) === 1
            ) {
                [$scoreA, $scoreB] = $this->randomScore($runtime);

                return $this->applyResult(
                    $state,
                    $template,
                    $nodeId,
                    $matchId,
                    $scoreA,
                    $scoreB
                );
            }

            return $this->applySimulatedSelection(
                $state,
                $template,
                $nodeId,
                $matchId
            );
        }

        [
            $scoreA,
            $scoreB,
        ] = $this->randomScore(
            $runtime
        );

        return $this->applyResult(
            $state,
            $template,
            $nodeId,
            (string)
            ($payload['match_id'] ?? ''),
            $scoreA,
            $scoreB
        );
    }

    private function simulateRound(
        array $state,
        TournamentTemplate $template,
        array $payload
    ): array {
        $this->requireRunning(
            $state
        );

        $nodeId =
            (int)
            ($payload['node_id'] ?? 0);

        $runtime =
            $state['nodes'][$nodeId]['runtime']
            ??
            null;

        if (! $runtime) {
            $this->fail(
                'Primero debes preparar el nodo.'
            );
        }

        if (
            ($runtime['status'] ?? null)
            !==
            'RUNNING'
        ) {
            $this->fail(
                'La fase seleccionada ya está completada.'
            );
        }


        $isStructureGraph =
            ($runtime['mode'] ?? null)
            ===
            'STRUCTURE_GRAPH';

        $isExecutableMatch =
            fn(array $match): bool =>
            ($match['status'] ?? null)
                ===
                'PENDING'
            &&
            (
                $isStructureGraph
                    ? count($match['participant_ids'] ?? [])
                        >= (int) ($match['qualifiers_count'] ?? 1)
                    : ! empty($match['participant_a_id'])
                        && ! empty($match['participant_b_id'])
            );

        $round =
            collect(
                $runtime['rounds']
                    ??
                    []
            )
            ->first(
                fn($round) =>
                collect(
                    $round['matches']
                        ??
                        []
                )
                    ->contains(
                        $isExecutableMatch
                    )
            );

        if (! $round) {
            $this->fail(
                'La fase no tiene una ronda con encuentros pendientes.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Capturar IDs antes de modificar el runtime
    |--------------------------------------------------------------------------
    |
    | Swiss puede crear la siguiente ronda al terminar la actual.
    | Guardando estos IDs evitamos simular accidentalmente la nueva ronda.
    |
    */

        $pendingMatchIds =
            collect(
                $round['matches']
            )
            ->filter(
                $isExecutableMatch
            )
            ->pluck('id')
            ->values()
            ->all();

        if ($pendingMatchIds === []) {
            $this->fail(
                'La ronda no tiene encuentros ejecutables.'
            );
        }

        foreach (
            $pendingMatchIds
            as
            $matchId
        ) {
            if ($isStructureGraph) {
                $currentMatch = collect(
                    $state['nodes'][$nodeId]['runtime']['rounds'] ?? []
                )
                    ->flatMap(fn($candidateRound) => $candidateRound['matches'] ?? [])
                    ->firstWhere('id', $matchId);

                if (
                    $currentMatch
                    && ($currentMatch['resolution_mode'] ?? null) === 'SCORE'
                    && count($currentMatch['participant_ids'] ?? []) === 2
                    && (int) ($currentMatch['qualifiers_count'] ?? 1) === 1
                ) {
                    do {
                        $currentRuntime = $state['nodes'][$nodeId]['runtime'];
                        [$scoreA, $scoreB] = $this->randomScore($currentRuntime);
                        $state = $this->applyResult(
                            $state,
                            $template,
                            $nodeId,
                            $matchId,
                            $scoreA,
                            $scoreB,
                            false
                        );

                        $currentMatch = collect($state['nodes'][$nodeId]['runtime']['rounds'] ?? [])
                            ->flatMap(fn($candidateRound) => $candidateRound['matches'] ?? [])
                            ->firstWhere('id', $matchId);
                    } while (($currentMatch['status'] ?? null) === 'PENDING');
                } else {
                    $state =
                        $this->applySimulatedSelection(
                            $state,
                            $template,
                            $nodeId,
                            $matchId,
                            false
                        );
                }

                continue;
            }

            $currentRuntime =
                $state['nodes'][$nodeId]['runtime'];

            [
                $scoreA,
                $scoreB,
            ] = $this->randomScore(
                $currentRuntime
            );

            do {
                $state =
                    $this->applyResult(
                        $state,
                        $template,
                        $nodeId,
                        $matchId,
                        $scoreA,
                        $scoreB,
                        false
                    );

                $currentMatch = collect($state['nodes'][$nodeId]['runtime']['rounds'] ?? [])
                    ->flatMap(fn($candidateRound) => $candidateRound['matches'] ?? [])
                    ->firstWhere('id', $matchId);

                if (($currentMatch['status'] ?? null) === 'PENDING') {
                    [$scoreA, $scoreB] = $this->randomScore(
                        $state['nodes'][$nodeId]['runtime']
                    );
                }
            } while (($currentMatch['status'] ?? null) === 'PENDING');
        }

        $this->addEvent(
            $state,
            'ROUND_SIMULATED',
            'SUCCESS',
            'La ronda '
                .
                ($round['label'] ?? '')
                .
                ' fue simulada completamente.'
        );

        return $state;
    }

    private function applySelectionResult(
        array $state,
        TournamentTemplate $template,
        int $nodeId,
        string $matchId,
        array $qualifierIds,
        bool $registerEvent = true
    ): array {
        $runtime =
            $state['nodes'][$nodeId]['runtime']
            ??
            null;

        if (! $runtime) {
            $this->fail(
                'Primero debes preparar el nodo.'
            );
        }

        $runtime =
            $this->engineManager
            ->submitSelection(
                $state['nodes'][$nodeId]['phase_type'],
                $runtime,
                $matchId,
                $qualifierIds
            );

        $state =
            $this->applyRuntimeState(
                $state,
                $template,
                $nodeId,
                $runtime
            );

        if ($registerEvent) {
            $this->addEvent(
                $state,
                'ENCOUNTER_COMPLETED',
                'SUCCESS',
                count($qualifierIds)
                    . " clasificado(s) registrados en {$matchId}."
            );
        }

        return $state;
    }

    private function applySimulatedSelection(
        array $state,
        TournamentTemplate $template,
        int $nodeId,
        string $matchId,
        bool $registerEvent = true
    ): array {
        $runtime =
            $state['nodes'][$nodeId]['runtime']
            ??
            null;

        if (! $runtime) {
            $this->fail(
                'Primero debes preparar el nodo.'
            );
        }

        $runtime =
            $this->engineManager
            ->simulateSelection(
                $state['nodes'][$nodeId]['phase_type'],
                $runtime,
                $matchId
            );

        $state =
            $this->applyRuntimeState(
                $state,
                $template,
                $nodeId,
                $runtime
            );

        if ($registerEvent) {
            $this->addEvent(
                $state,
                'ENCOUNTER_SIMULATED',
                'INFO',
                "El encuentro {$matchId} fue simulado."
            );
        }

        return $state;
    }

    private function applyResult(
        array $state,
        TournamentTemplate $template,
        int $nodeId,
        string $matchId,
        int $scoreA,
        int $scoreB,
        bool $registerEvent = true
    ): array {
        $runtime =
            $state['nodes'][$nodeId]['runtime']
            ??
            null;

        if (! $runtime) {
            $this->fail(
                'Primero debes preparar el nodo.'
            );
        }

        $runtime =
            $this->engineManager
            ->submit(
                $state['nodes'][$nodeId]['phase_type'],
                $runtime,
                $matchId,
                $scoreA,
                $scoreB
            );

        $state =
            $this->applyRuntimeState(
                $state,
                $template,
                $nodeId,
                $runtime
            );

        if ($registerEvent) {
            $series = $runtime['series'][$matchId] ?? null;
            $completed = ($series['status'] ?? null) === 'COMPLETED';

            $this->addEvent(
                $state,
                $completed ? 'MATCH_COMPLETED' : 'SERIES_GAME_RECORDED',
                $completed ? 'SUCCESS' : 'INFO',
                $completed
                    ? "La serie {$matchId} fue completada."
                    : "Juego {$scoreA}-{$scoreB} registrado en {$matchId}."
            );
        }

        return $state;
    }

    private function applyRuntimeState(
        array $state,
        TournamentTemplate $template,
        int $nodeId,
        array $runtime
    ): array {
        $state['nodes'][$nodeId]['runtime'] =
            $runtime;

        $state['nodes'][$nodeId]['status'] =
            $runtime['status'];

        $this->syncParticipantStatistics(
            $state,
            $nodeId
        );

        if (
            $runtime['status']
            ===
            'COMPLETED'
        ) {
            $this->completeNodeParticipants(
                $state,
                $nodeId
            );
        }

        $this->syncSummary(
            $state
        );
        $state =
            $this->graphRuntime
            ->afterNodeResult(
                $state,
                $template,
                $nodeId
            );

        return $state;
    }

    private function syncParticipantStatistics(
        array &$state,
        int $nodeId
    ): void {
        $runtime =
            $state['nodes'][$nodeId]['runtime'];

        if (
            ($runtime['mode'] ?? null)
            ===
            'STRUCTURE_GRAPH'
        ) {
            $this->syncStructureGraphStatistics(
                $state,
                $runtime
            );

            return;
        }

        if (
            in_array(
                $runtime['engine'],
                [
                    'ROUND_ROBIN',
                    'GROUP_STAGE',
                    'SWISS',
                ],
                true
            )
        ) {
            foreach (
                $runtime['standings']
                as
                $row
            ) {
                $state['participants'][$row['participant_id']]['statistics'] = [
                    'matches' =>
                    $row['played'],

                    'wins' =>
                    $row['wins'],

                    'draws' =>
                    $row['draws'],

                    'losses' =>
                    $row['losses'],

                    'points' =>
                    $row['points'],
                ];
            }

            return;
        }

        $statistics =
            [];

        foreach (
            $runtime['rounds']
            as
            $round
        ) {
            foreach (
                $round['matches']
                as
                $match
            ) {
                if (
                    $match['status']
                    !==
                    'COMPLETED'
                ) {
                    continue;
                }

                foreach (
                    [
                        $match['participant_a_id'],
                        $match['participant_b_id'],
                    ]
                    as
                    $participantId
                ) {
                    $statistics[$participantId] ??= [
                        'matches' => 0,
                        'wins' => 0,
                        'draws' => 0,
                        'losses' => 0,
                        'points' => 0,
                    ];
                }

                $statistics[$match['participant_a_id']]['matches']++;

                $statistics[$match['participant_b_id']]['matches']++;

                $statistics[$match['winner_id']]['wins']++;

                $statistics[$match['loser_id']]['losses']++;
            }
        }

        foreach (
            $statistics
            as
            $participantId =>
            $values
        ) {
            $state['participants'][$participantId]['statistics'] =
                $values;
        }
    }

    private function syncStructureGraphStatistics(
        array &$state,
        array $runtime
    ): void {
        $statistics = [];

        foreach ($runtime['rounds'] ?? [] as $round) {
            foreach ($round['matches'] ?? [] as $match) {
                if (($match['status'] ?? null) !== 'COMPLETED') {
                    continue;
                }

                foreach ($match['participant_ids'] ?? [] as $participantId) {
                    $statistics[$participantId] ??= [
                        'matches' => 0,
                        'wins' => 0,
                        'draws' => 0,
                        'losses' => 0,
                        'points' => 0,
                    ];
                    $statistics[$participantId]['matches']++;
                }

                foreach ($match['qualifier_ids'] ?? [] as $participantId) {
                    $statistics[$participantId]['wins']++;
                }

                foreach ($match['eliminated_ids'] ?? [] as $participantId) {
                    $statistics[$participantId]['losses']++;
                }
            }
        }

        foreach ($statistics as $participantId => $values) {
            if (isset($state['participants'][$participantId])) {
                $state['participants'][$participantId]['statistics'] = $values;
            }
        }
    }

    private function completeNodeParticipants(
        array &$state,
        int $nodeId
    ): void {
        $runtime =
            $state['nodes'][$nodeId]['runtime'];

        foreach (
            $state['nodes'][$nodeId]['participant_ids']
            as
            $participantId
        ) {
            $state['participants'][$participantId]['status'] =
                in_array(
                    $participantId,
                    $runtime['survivor_ids'],
                    true
                )
                ? 'QUALIFIED'
                : 'ELIMINATED';
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

    private function syncSummary(
        array &$state
    ): void {
        $runtimes =
            collect(
                $state['nodes']
            )
            ->pluck(
                'runtime'
            )
            ->filter();

        $state['summary']['matches'] =
            $runtimes
            ->sum(
                fn($runtime) =>
                $runtime['matches_total']
            );

        $state['summary']['completed_matches'] =
            $runtimes
            ->sum(
                fn($runtime) =>
                $runtime['matches_completed']
            );

        $state['summary']['completed_nodes'] =
            $runtimes
            ->filter(
                fn($runtime) =>
                $runtime['status']
                    ===
                    'COMPLETED'
            )
            ->count();
    }

    private function requireRunning(
        array $state
    ): void {
        if (
            ($state['status'] ?? null)
            !==
            'RUNNING'
        ) {
            $this->fail(
                'El Lab debe estar en ejecución para utilizar los motores.'
            );
        }
    }

    private function addEvent(
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

    private function response(
        array $state
    ): array {
        return [
            'state' =>
            $state,

            'state_token' =>
            $this->tokenService
                ->encode(
                    $state
                ),
        ];
    }

    private function fail(
        string $message
    ): never {
        throw ValidationException::withMessages([
            'lab' => [
                $message,
            ],
        ]);
    }
}
