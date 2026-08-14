<?php

namespace App\Services\Tournaments\CompetitionLab;

use App\Models\TournamentTemplate;
use App\Models\User;
use App\Services\Tournaments\CompetitionLab\Engines\LabPhaseEngineManager;
use Illuminate\Validation\ValidationException;

class CompetitionLabService
{
    public function __construct(
        private readonly
        LabStateFactory $stateFactory,

        private readonly
        LabStateTokenService $tokenService,

        private readonly
        LabPhaseEngineManager $engineManager
    ) {}

    public function initialize(
        TournamentTemplate $template,
        User $user,
        array $configuration
    ): array {
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
                    $payload
                ),

                'SIMULATE_MATCH' =>
                $this->simulateMatch(
                    $state,
                    $payload
                ),

                'SIMULATE_ROUND' =>
                $this->simulateRound(
                    $state,
                    $payload
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
                'phaseTemplate.roundRobinSetting',
                'phaseTemplate.roundRobinTiebreakers',
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

    private function submitResult(
        array $state,
        array $payload
    ): array {
        $this->requireRunning(
            $state
        );

        return $this->applyResult(
            $state,
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

    private function simulateMatch(
        array $state,
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

        [
            $scoreA,
            $scoreB,
        ] = $this->randomScore(
            $runtime
        );

        return $this->applyResult(
            $state,
            $nodeId,
            (string)
            ($payload['match_id'] ?? ''),
            $scoreA,
            $scoreB
        );
    }

    private function simulateRound(
        array $state,
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

        $round =
            collect(
                $runtime['rounds']
            )
            ->first(
                fn($round) =>
                collect(
                    $round['matches']
                )
                    ->contains(
                        fn($match) =>
                        $match['status']
                            ===
                            'PENDING'
                            &&
                            $match['participant_a_id']
                            &&
                            $match['participant_b_id']
                    )
            );

        if (! $round) {
            $this->fail(
                'El nodo no tiene encuentros pendientes.'
            );
        }

        foreach (
            $round['matches']
            as
            $match
        ) {
            if (
                $match['status']
                !==
                'PENDING'
                ||
                ! $match['participant_a_id']
                ||
                ! $match['participant_b_id']
            ) {
                continue;
            }

            [
                $scoreA,
                $scoreB,
            ] = $this->randomScore(
                $state['nodes'][$nodeId]['runtime']
            );

            $state =
                $this->applyResult(
                    $state,
                    $nodeId,
                    $match['id'],
                    $scoreA,
                    $scoreB,
                    false
                );
        }

        $this->addEvent(
            $state,
            'ROUND_SIMULATED',
            'INFO',
            'Se simuló una ronda del nodo '
                .
                $state['nodes'][$nodeId]['name']
                .
                '.'
        );

        return $state;
    }

    private function applyResult(
        array $state,
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

        if ($registerEvent) {
            $this->addEvent(
                $state,
                'MATCH_COMPLETED',
                'SUCCESS',
                "Resultado {$scoreA}-{$scoreB} registrado en {$matchId}."
            );
        }

        return $state;
    }

    private function syncParticipantStatistics(
        array &$state,
        int $nodeId
    ): void {
        $runtime =
            $state['nodes'][$nodeId]['runtime'];

        if (
            $runtime['engine']
            ===
            'ROUND_ROBIN'
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
