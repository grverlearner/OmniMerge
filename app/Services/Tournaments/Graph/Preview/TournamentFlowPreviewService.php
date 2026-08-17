<?php

namespace App\Services\Tournaments\Graph\Preview;

use App\Models\PhaseEntryPort;
use App\Models\TournamentPhaseNode;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\Graph\Flow\EntryPortMergePolicy;
use App\Services\Tournaments\Graph\Flow\TournamentGraphFlowAnalysisService;
use App\Services\Tournaments\Graph\Flow\TournamentGraphFlowValidationService;
use App\Services\Tournaments\Graph\TournamentGraphValidationService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TournamentFlowPreviewService
{
    public function __construct(
        private readonly
        TournamentGraphValidationService $graphValidationService,

        private readonly
        TournamentGraphFlowAnalysisService $flowAnalysisService,

        private readonly
        TournamentGraphFlowValidationService $flowValidationService,

        private readonly
        PreviewParticipantFactory $participantFactory,

        private readonly
        PreviewConnectionAllocator $connectionAllocator,

        private readonly
        PreviewExitResolver $exitResolver,

        private readonly
        PreviewIntegrityService $integrityService
    ) {}

    public function preview(
        TournamentTemplate $template,
        array $configuration
    ): array {
        $this->loadGraph(
            $template
        );

        $graphValidation =
            $this->graphValidationService
            ->validate(
                $template
            );

        $flowAnalysis =
            $this->flowAnalysisService
            ->analyze(
                $template
            );

        $flowValidation =
            $this->flowValidationService
            ->validate(
                $template,
                $flowAnalysis
            );

        if (
            ! $graphValidation['valid']
            ||
            ! $flowValidation['valid']
        ) {
            throw ValidationException::withMessages([
                'graph' => [
                    'El Tournament Graph contiene problemas bloqueantes. Corrige el camino antes de ejecutar el Preview.',
                ],
            ]);
        }

        $strategy =
            $configuration['resolution_strategy'];

        $seed =
            (int) $configuration['seed'];

        $timeline = [];
        $warnings = [];
        $errors = [];

        $allParticipants = [];
        $connectionBuffers = [];
        $nodeStates = [];
        $terminalStates = [];

        /*
        |--------------------------------------------------------------------------
        | Starts
        |--------------------------------------------------------------------------
        */

        foreach (
            $configuration['starts']
            as
            $startConfiguration
        ) {
            $start =
                $template
                ->graphStarts
                ->firstWhere(
                    'id',
                    (int) $startConfiguration['start_id']
                );

            if (! $start) {
                continue;
            }

            $participants =
                $this->participantFactory
                ->generate(
                    $start,
                    (int) $startConfiguration['count'],
                    $startConfiguration['prefix']
                        ??
                        null
                );

            $participants =
                $this->participantFactory
                ->reorder(
                    $participants,
                    $strategy,
                    $seed
                        +
                        $start->id
                );

            $allParticipants = [
                ...$allParticipants,
                ...$participants,
            ];

            $distribution =
                $this->connectionAllocator
                ->distribute(
                    $participants,
                    $start
                        ->outgoingConnections
                );

            foreach (
                $distribution['allocations']
                as
                $connectionId => $allocation
            ) {
                $connection =
                    $template
                    ->graphConnections
                    ->firstWhere(
                        'id',
                        $connectionId
                    );

                if (! $connection) {
                    continue;
                }

                $moved =
                    $this->participantFactory
                    ->appendJourney(
                        $allocation['participants'],
                        $this->connectionLocation(
                            $connection
                        )
                    );

                $connectionBuffers[$connectionId] =
                    $moved;

                $timeline[] =
                    $this->event(
                        'START_DISPATCHED',
                        'SUCCESS',
                        $allocation['count']
                            .
                            ' participantes salieron de “'
                            .
                            $start->name
                            .
                            '” hacia '
                            .
                            $connection->target_label
                            .
                            '.',
                        $moved
                    );
            }

            if (
                $distribution['remaining']
                !==
                []
            ) {
                $warnings[] = [
                    'code' =>
                    'START_REMAINDER',

                    'message' =>
                    count(
                        $distribution['remaining']
                    )
                        .
                        ' participantes de “'
                        .
                        $start->name
                        .
                        '” no fueron enviados por ninguna conexión.',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Nodes by Levels
        |--------------------------------------------------------------------------
        */

        foreach (
            $flowAnalysis['levels']
            as
            $level
        ) {
            foreach (
                $level['node_ids']
                as
                $nodeId
            ) {
                $node =
                    $template
                    ->graphNodes
                    ->firstWhere(
                        'id',
                        (int) $nodeId
                    );

                if (! $node) {
                    continue;
                }

                $entryResolution =
                    $this->resolveNodeEntries(
                        $node,
                        $connectionBuffers
                    );

                if (
                    ! $entryResolution['ready']
                ) {
                    $nodeStates[$node->id] = [
                        'status' =>
                        'BLOCKED',

                        'name' =>
                        $node->name,

                        'received' =>
                        count(
                            $entryResolution['participants']
                        ),

                        'sent' =>
                        0,

                        'participants' =>
                        $entryResolution['participants'],

                        'reason' =>
                        $entryResolution['reason'],
                    ];

                    $timeline[] =
                        $this->event(
                            'NODE_BLOCKED',
                            'WARNING',
                            'La fase “'
                                .
                                $node->name
                                .
                                '” quedó bloqueada: '
                                .
                                $entryResolution['reason']
                                .
                                '.',
                            $entryResolution['participants']
                        );

                    continue;
                }

                $participants =
                    $this->uniqueParticipants(
                        $entryResolution['participants']
                    );

                $participants =
                    $this->participantFactory
                    ->appendJourney(
                        $participants,
                        [
                            'type' =>
                            'NODE',

                            'id' =>
                            (int) $node->id,

                            'code' =>
                            $node->code,

                            'name' =>
                            $node->name,
                        ]
                    );

                $exitResolution =
                    $this->exitResolver
                    ->resolve(
                        $participants,

                        $node
                            ->phaseTemplate
                            ->exits,

                        $strategy,

                        $seed
                            +
                            $node->id,

                        $node
                            ->phaseTemplate
                            ->singleEliminationSetting
                    );

                $warnings = [
                    ...$warnings,
                    ...$exitResolution['warnings'],
                ];

                $sentCount = 0;

                foreach (
                    $exitResolution['assignments']
                    as
                    $exitId => $assignment
                ) {
                    $exitParticipants =
                        $this->participantFactory
                        ->appendJourney(
                            $assignment['participants'],
                            [
                                'type' =>
                                'PHASE_EXIT',

                                'id' =>
                                (int) $exitId,

                                'code' =>
                                $assignment['exit_code'],

                                'name' =>
                                $assignment['exit_name'],
                            ]
                        );

                    $connections =
                        $template
                        ->graphConnections
                        ->where(
                            'source_type',
                            'PHASE_EXIT'
                        )
                        ->where(
                            'source_node_id',
                            $node->id
                        )
                        ->where(
                            'source_phase_exit_id',
                            $exitId
                        );

                    $distribution =
                        $this->connectionAllocator
                        ->distribute(
                            $exitParticipants,
                            $connections
                        );

                    foreach (
                        $distribution['allocations']
                        as
                        $connectionId =>
                        $allocation
                    ) {
                        $connection =
                            $template
                            ->graphConnections
                            ->firstWhere(
                                'id',
                                $connectionId
                            );

                        if (! $connection) {
                            continue;
                        }

                        $moved =
                            $this->participantFactory
                            ->appendJourney(
                                $allocation['participants'],
                                $this->connectionLocation(
                                    $connection
                                )
                            );

                        $connectionBuffers[$connectionId] =
                            $moved;

                        $sentCount +=
                            count($moved);

                        $timeline[] =
                            $this->event(
                                'CONNECTION_APPLIED',
                                'INFO',
                                count($moved)
                                    .
                                    ' participantes avanzaron desde “'
                                    .
                                    $node->name
                                    .
                                    ' · '
                                    .
                                    $assignment['exit_name']
                                    .
                                    '” hacia '
                                    .
                                    $connection
                                    ->target_label
                                    .
                                    '.',
                                $moved
                            );
                    }

                    if (
                        $connections->isEmpty()
                        &&
                        $exitParticipants !== []
                    ) {
                        $warnings[] = [
                            'code' =>
                            'EXIT_WITHOUT_ROUTE',

                            'message' =>
                            count(
                                $exitParticipants
                            )
                                .
                                ' participantes quedaron en la salida “'
                                .
                                $assignment['exit_name']
                                .
                                '” de “'
                                .
                                $node->name
                                .
                                '”.',
                        ];
                    }
                }

                $nodeStates[$node->id] = [
                    'status' =>
                    'PROCESSED',

                    'name' =>
                    $node->name,

                    'phase_type' =>
                    $node
                        ->phaseTemplate
                        ->type_label,

                    'received' =>
                    count($participants),

                    'sent' =>
                    $sentCount,

                    'participants' =>
                    $participants,

                    'exit_assignments' =>
                    $exitResolution['assignments'],

                    'unassigned' =>
                    $exitResolution['remaining'],
                ];

                $timeline[] =
                    $this->event(
                        'NODE_PROCESSED',
                        'SUCCESS',
                        'La fase “'
                            .
                            $node->name
                            .
                            '” procesó '
                            .
                            count($participants)
                            .
                            ' participantes.',
                        $participants
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Terminals
        |--------------------------------------------------------------------------
        */

        $terminalParticipants = [];

        foreach (
            $template->graphTerminals
            as
            $terminal
        ) {
            $participants = [];

            foreach (
                $terminal
                    ->incomingConnections
                as
                $connection
            ) {
                $participants = [
                    ...$participants,
                    ...(
                        $connectionBuffers[$connection->id]
                        ??
                        []
                    ),
                ];
            }

            $participants =
                $this->participantFactory
                ->appendJourney(
                    $participants,
                    [
                        'type' =>
                        'TERMINAL',

                        'id' =>
                        (int) $terminal->id,

                        'code' =>
                        $terminal->code,

                        'name' =>
                        $terminal->name,
                    ]
                );

            $terminalParticipants = [
                ...$terminalParticipants,
                ...$participants,
            ];

            $terminalStates[$terminal->id] = [
                'id' =>
                (int) $terminal->id,

                'code' =>
                $terminal->code,

                'name' =>
                $terminal->name,

                'type' =>
                $terminal
                    ->terminal_type_label,

                'expected' =>
                $terminal
                    ->expected_participants,

                'count' =>
                count($participants),

                'participants' =>
                $participants,

                'status' =>
                $this->terminalStatus(
                    $terminal
                        ->expected_participants,
                    count($participants)
                ),
            ];
        }

        $stoppedParticipants =
            collect(
                $nodeStates
            )
            ->flatMap(
                fn($state) =>
                $state['status']
                    ===
                    'BLOCKED'
                    ? $state['participants']
                    : [
                        ...(
                            $state['unassigned']
                            ??
                            []
                        ),
                    ]
            )
            ->values()
            ->all();

        $integrity =
            $this->integrityService
            ->inspect(
                $allParticipants,
                $terminalParticipants,
                $stoppedParticipants
            );

        $warnings = [
            ...$warnings,
            ...$integrity['warnings'],
        ];

        $errors = [
            ...$errors,
            ...$integrity['errors'],
        ];

        return [
            'completed' =>
            $errors === [],

            'configuration' => [
                'participant_mode' =>
                $configuration['participant_mode'],

                'resolution_strategy' =>
                $strategy,

                'seed' =>
                $seed,
            ],

            'summary' => [
                ...$integrity,

                'nodes_processed' =>
                collect(
                    $nodeStates
                )
                    ->where(
                        'status',
                        'PROCESSED'
                    )
                    ->count(),

                'nodes_blocked' =>
                collect(
                    $nodeStates
                )
                    ->where(
                        'status',
                        'BLOCKED'
                    )
                    ->count(),

                'connections_applied' =>
                count(
                    array_filter(
                        $connectionBuffers,
                        fn($participants) =>
                        $participants !== []
                    )
                ),

                'terminals_reached' =>
                collect(
                    $terminalStates
                )
                    ->where(
                        'count',
                        '>',
                        0
                    )
                    ->count(),
            ],

            'participants' =>
            $allParticipants,

            'nodes' =>
            $nodeStates,

            'connections' =>
            $connectionBuffers,

            'terminals' =>
            $terminalStates,

            'timeline' =>
            array_values(
                $timeline
            ),

            'errors' =>
            $this->uniqueProblems(
                $errors
            ),

            'warnings' =>
            $this->uniqueProblems(
                $warnings
            ),

            'graph_analysis' =>
            $flowAnalysis,
        ];
    }

    private function resolveNodeEntries(
        TournamentPhaseNode $node,
        array $connectionBuffers
    ): array {
        $allParticipants = [];
        $missing = [];

        foreach (
            $node
                ->entryPorts
                ->where(
                    'status',
                    'ACTIVE'
                )
            as
            $port
        ) {
            $incoming =
                $port
                ->incomingConnections
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->sortBy(
                    fn($connection) =>
                    sprintf(
                        '%010d-%010d-%010d',
                        $connection->priority,
                        $connection->sequence_number,
                        $connection->id
                    )
                )
                ->values();

            $available =
                $incoming
                ->filter(
                    fn($connection) =>
                    array_key_exists(
                        $connection->id,
                        $connectionBuffers
                    )
                );

            if (
                $port->is_required
                &&
                $available->isEmpty()
            ) {
                $missing[] =
                    $port->name;

                continue;
            }

            if (
                $port->merge_policy
                ===
                'WAIT_ALL'
                &&
                $available->count()
                <
                $incoming->count()
            ) {
                $missing[] =
                    $port->name
                    .
                    ' espera '
                    .
                    (
                        $incoming->count()
                        -
                        $available->count()
                    )
                    .
                    ' rutas';

                continue;
            }

            $portParticipants =
                $this->resolvePortParticipants(
                    $port,
                    $available,
                    $connectionBuffers
                );

            $allParticipants = [
                ...$allParticipants,
                ...$portParticipants,
            ];
        }

        return [
            'ready' =>
            $missing === [],

            'reason' =>
            $missing === []
                ? null
                : implode(
                    '; ',
                    $missing
                ),

            'participants' =>
            $allParticipants,
        ];
    }

    private function resolvePortParticipants(
        PhaseEntryPort $port,
        Collection $connections,
        array $connectionBuffers
    ): array {
        if ($connections->isEmpty()) {
            return [];
        }

        $orderedConnectionIds = $connections
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $payloads = [];
        foreach ($orderedConnectionIds as $connectionId) {
            $payloads[$connectionId] =
                $connectionBuffers[$connectionId]
                ?? [];
        }

        return EntryPortMergePolicy::merge(
            $port->merge_policy,
            $orderedConnectionIds,
            $orderedConnectionIds,
            $payloads
        );
    }

    private function connectionLocation(
        $connection
    ): array {
        return [
            'type' =>
            'CONNECTION',

            'id' =>
            (int) $connection->id,

            'code' =>
            $connection->code,

            'name' =>
            $connection->source_label
                .
                ' → '
                .
                $connection->target_label,
        ];
    }

    private function uniqueParticipants(
        array $participants
    ): array {
        return collect(
            $participants
        )
            ->unique(
                'preview_id'
            )
            ->values()
            ->all();
    }

    private function terminalStatus(
        ?int $expected,
        int $received
    ): string {
        if ($received === 0) {
            return 'EMPTY';
        }

        if (
            $expected !== null
            &&
            $received !== $expected
        ) {
            return 'INCOMPATIBLE';
        }

        return 'COMPLETE';
    }

    private function event(
        string $type,
        string $level,
        string $message,
        array $participants = []
    ): array {
        return [
            'step' =>
            null,

            'type' =>
            $type,

            'level' =>
            $level,

            'message' =>
            $message,

            'participant_ids' =>
            array_column(
                $participants,
                'preview_id'
            ),
        ];
    }

    private function uniqueProblems(
        array $problems
    ): array {
        return collect(
            $problems
        )
            ->unique(
                fn($problem) =>
                $problem['code']
                    .
                    ':'
                    .
                    $problem['message']
            )
            ->values()
            ->all();
    }

    private function loadGraph(
        TournamentTemplate $template
    ): void {
        $template->load([
            'graphStarts.outgoingConnections',

            'graphNodes.phaseTemplate.exits',
            'graphNodes.phaseTemplate.singleEliminationSetting',

            'graphNodes.entryPorts.incomingConnections',

            'graphTerminals.incomingConnections',

            'graphConnections.sourceStart',

            'graphConnections.sourceNode',

            'graphConnections.sourcePhaseExit',

            'graphConnections.targetEntryPort.node',

            'graphConnections.targetTerminal',
        ]);
    }
}
