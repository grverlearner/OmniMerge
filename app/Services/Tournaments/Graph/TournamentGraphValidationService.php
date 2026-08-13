<?php

namespace App\Services\Tournaments\Graph;

use App\Models\TournamentPhaseConnection;
use App\Models\TournamentTemplate;

class TournamentGraphValidationService
{
    public function __construct(
        private readonly
        TournamentGraphTopologyService $topologyService
    ) {}


    public function validate(
        TournamentTemplate $template
    ): array {
        $template->load([
            'graphNodes.phaseTemplate.exits',
            'graphNodes.entryPorts.incomingConnections',

            'graphStarts.outgoingConnections',

            'graphTerminals.incomingConnections',

            'graphConnections.targetEntryPort.node',
        ]);


        $errors = [];
        $warnings = [];


        $activeNodes =
            $template
            ->graphNodes
            ->where(
                'status',
                'ACTIVE'
            );


        $activeStarts =
            $template
            ->graphStarts
            ->where(
                'status',
                'ACTIVE'
            );


        $activeTerminals =
            $template
            ->graphTerminals
            ->where(
                'status',
                'ACTIVE'
            );


        $activeConnections =
            $template
            ->graphConnections
            ->where(
                'status',
                'ACTIVE'
            );


        /*
        |--------------------------------------------------------------------------
        | Basic structure
        |--------------------------------------------------------------------------
        */

        if ($activeStarts->isEmpty()) {
            $errors[] = [
                'code' =>
                'NO_START',

                'message' =>
                'El grafo necesita al menos un punto de inicio activo.',
            ];
        }


        if ($activeNodes->isEmpty()) {
            $errors[] = [
                'code' =>
                'NO_PHASE_NODE',

                'message' =>
                'El grafo necesita al menos un Phase Node activo.',
            ];
        }


        if ($activeTerminals->isEmpty()) {
            $errors[] = [
                'code' =>
                'NO_TERMINAL',

                'message' =>
                'El grafo necesita al menos un terminal activo.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Starts
        |--------------------------------------------------------------------------
        */

        foreach (
            $activeStarts
            as
            $start
        ) {
            if (
                $start
                ->outgoingConnections
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->isEmpty()
            ) {
                $errors[] = [
                    'code' =>
                    'START_WITHOUT_OUTPUT',

                    'message' =>
                    'El inicio “'
                        .
                        $start->name
                        .
                        '” no está conectado.',
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Entry Ports
        |--------------------------------------------------------------------------
        */

        foreach (
            $activeNodes
            as
            $node
        ) {
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
                    );


                if (
                    $port->is_required
                    &&
                    $incoming->isEmpty()
                ) {
                    $errors[] = [
                        'code' =>
                        'REQUIRED_ENTRY_UNCONNECTED',

                        'message' =>
                        'La entrada requerida “'
                            .
                            $port->name
                            .
                            '” de “'
                            .
                            $node->name
                            .
                            '” no tiene ninguna conexión.',
                    ];
                }


                if (
                    ! $port
                        ->accepts_multiple_connections
                    &&
                    $incoming->count()
                    >
                    1
                ) {
                    $errors[] = [
                        'code' =>
                        'ENTRY_MULTIPLE_NOT_ALLOWED',

                        'message' =>
                        'La entrada “'
                            .
                            $port->name
                            .
                            '” no permite múltiples rutas.',
                    ];
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Outputs
        |--------------------------------------------------------------------------
        */

        foreach (
            $activeNodes
            as
            $node
        ) {
            $activeExits =
                $node
                ->phaseTemplate
                ?->exits
                ?->where(
                    'status',
                    'ACTIVE'
                )
                ??
                collect();


            if (
                $activeExits->isEmpty()
            ) {
                $warnings[] = [
                    'code' =>
                    'NODE_WITHOUT_EXITS',

                    'message' =>
                    '“'
                        .
                        $node->name
                        .
                        '” utiliza una Fase sin puertas de salida activas.',
                ];

                continue;
            }


            $connectedExitIds =
                $activeConnections
                ->where(
                    'source_node_id',
                    $node->id
                )
                ->pluck(
                    'source_phase_exit_id'
                )
                ->filter()
                ->unique();


            if (
                $connectedExitIds->isEmpty()
            ) {
                $errors[] = [
                    'code' =>
                    'NODE_WITHOUT_OUTGOING',

                    'message' =>
                    '“'
                        .
                        $node->name
                        .
                        '” no envía ningún resultado hacia otra ruta o terminal.',
                ];
            }


            foreach (
                $activeExits
                as
                $exit
            ) {
                if (
                    ! $connectedExitIds
                        ->contains(
                            $exit->id
                        )
                ) {
                    $warnings[] = [
                        'code' =>
                        'UNUSED_PHASE_EXIT',

                        'message' =>
                        'La salida “'
                            .
                            $exit->name
                            .
                            '” de “'
                            .
                            $node->name
                            .
                            '” no está conectada.',
                    ];
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Terminals
        |--------------------------------------------------------------------------
        */

        foreach (
            $activeTerminals
            as
            $terminal
        ) {
            if (
                $terminal
                ->incomingConnections
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->isEmpty()
            ) {
                $errors[] = [
                    'code' =>
                    'TERMINAL_WITHOUT_INPUT',

                    'message' =>
                    'El terminal “'
                        .
                        $terminal->name
                        .
                        '” no recibe ninguna ruta.',
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Node Edges
        |--------------------------------------------------------------------------
        */

        $nodeEdges =
            $activeConnections
            ->filter(
                fn($connection) =>
                $connection
                    ->source_type
                    ===
                    'PHASE_EXIT'
                    &&
                    $connection
                    ->target_type
                    ===
                    'ENTRY_PORT'
                    &&
                    $connection
                    ->targetEntryPort
            )
            ->map(
                fn($connection) => [
                    (int)
                    $connection
                        ->source_node_id,

                    (int)
                    $connection
                        ->targetEntryPort
                        ->tournament_phase_node_id,
                ]
            )
            ->values()
            ->all();


        /*
        |--------------------------------------------------------------------------
        | Cycle
        |--------------------------------------------------------------------------
        */

        $nodeIds =
            $activeNodes
            ->pluck('id')
            ->map(
                fn($id) =>
                (int)
                $id
            )
            ->all();


        if (
            $this
            ->topologyService
            ->hasCycle(
                $nodeIds,
                $nodeEdges
            )
        ) {
            $errors[] = [
                'code' =>
                'GRAPH_CYCLE',

                'message' =>
                'El grafo contiene un ciclo. Una Fase no puede volver a una ejecución contextual anterior.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Reachability from Starts
        |--------------------------------------------------------------------------
        */

        $startNodeIds =
            $activeConnections
            ->filter(
                fn($connection) =>
                $connection
                    ->source_type
                    ===
                    'START'
                    &&
                    $connection
                    ->target_type
                    ===
                    'ENTRY_PORT'
                    &&
                    $connection
                    ->targetEntryPort
            )
            ->map(
                fn($connection) =>
                (int)
                $connection
                    ->targetEntryPort
                    ->tournament_phase_node_id
            )
            ->unique()
            ->values()
            ->all();


        $reachable =
            $this
            ->topologyService
            ->reachableFrom(
                $startNodeIds,
                $nodeEdges
            );


        foreach (
            $activeNodes
            as
            $node
        ) {
            if (
                ! in_array(
                    $node->id,
                    $reachable,
                    true
                )
            ) {
                $errors[] = [
                    'code' =>
                    'UNREACHABLE_NODE',

                    'message' =>
                    '“'
                        .
                        $node->name
                        .
                        '” no puede alcanzarse desde ningún Tournament Start.',
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Fan-out validation
        |--------------------------------------------------------------------------
        */

        $sourceGroups =
            $activeConnections
            ->groupBy(
                function (
                    TournamentPhaseConnection $connection
                ) {
                    if (
                        $connection
                        ->source_type
                        ===
                        'START'
                    ) {
                        return
                            'START:'
                            .
                            $connection
                            ->source_start_id;
                    }

                    return
                        'EXIT:'
                        .
                        $connection
                        ->source_node_id
                        .
                        ':'
                        .
                        $connection
                        ->source_phase_exit_id;
                }
            );


        foreach (
            $sourceGroups
            as
            $sourceKey => $connections
        ) {
            if (
                $connections->count()
                <=
                1
            ) {
                continue;
            }


            if (
                $connections
                ->where(
                    'allocation_mode',
                    'ALL'
                )
                ->isNotEmpty()
            ) {
                $errors[] = [
                    'code' =>
                    'FANOUT_ALL_CONFLICT',

                    'message' =>
                    'El origen '
                        .
                        $sourceKey
                        .
                        ' tiene múltiples ramas pero una utiliza ALL.',
                ];
            }


            if (
                $connections
                ->where(
                    'allocation_mode',
                    'REMAINDER'
                )
                ->count()
                >
                1
            ) {
                $errors[] = [
                    'code' =>
                    'MULTIPLE_REMAINDER',

                    'message' =>
                    'El origen '
                        .
                        $sourceKey
                        .
                        ' tiene más de una rama REMAINDER.',
                ];
            }


            $percentage =
                (float)
                $connections
                    ->where(
                        'allocation_mode',
                        'PERCENTAGE'
                    )
                    ->sum(
                        'allocation_value'
                    );


            if (
                $percentage
                >
                100
            ) {
                $errors[] = [
                    'code' =>
                    'PERCENTAGE_OVER_100',

                    'message' =>
                    'Las rutas porcentuales de '
                        .
                        $sourceKey
                        .
                        ' superan el 100%.',
                ];
            }


            if (
                $connections
                ->where(
                    'allocation_mode',
                    'REMAINDER'
                )
                ->isEmpty()
                &&
                $percentage > 0
                &&
                $percentage < 100
            ) {
                $warnings[] = [
                    'code' =>
                    'PARTIAL_PERCENTAGE',

                    'message' =>
                    'Las ramas porcentuales de '
                        .
                        $sourceKey
                        .
                        ' solo distribuyen '
                        .
                        $percentage
                        .
                        '%.',
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Tournament participant start forecast
        |--------------------------------------------------------------------------
        */

        $knownStartTotal =
            $activeStarts
            ->whereNotNull(
                'expected_participants'
            )
            ->sum(
                'expected_participants'
            );


        if (
            $activeStarts->isNotEmpty()
            &&
            $activeStarts
            ->every(
                fn($start) =>
                $start
                    ->expected_participants
                    !==
                    null
            )
        ) {
            if (
                $knownStartTotal
                <
                $template
                ->min_participants
            ) {
                $warnings[] = [
                    'code' =>
                    'STARTS_BELOW_TOURNAMENT_MIN',

                    'message' =>
                    'Los Starts declaran '
                        .
                        $knownStartTotal
                        .
                        ' participantes, menos que el mínimo del torneo ('
                        .
                        $template
                        ->min_participants
                        .
                        ').',
                ];
            }


            if (
                $template
                ->max_participants
                !==
                null
                &&
                $knownStartTotal
                >
                $template
                ->max_participants
            ) {
                $warnings[] = [
                    'code' =>
                    'STARTS_OVER_TOURNAMENT_MAX',

                    'message' =>
                    'Los Starts declaran '
                        .
                        $knownStartTotal
                        .
                        ' participantes, más que el máximo del torneo ('
                        .
                        $template
                        ->max_participants
                        .
                        ').',
                ];
            }
        }


        return [
            'valid' =>
            $errors === [],

            'errors' =>
            $errors,

            'warnings' =>
            $warnings,

            'stats' => [
                'starts' =>
                $activeStarts->count(),

                'nodes' =>
                $activeNodes->count(),

                'connections' =>
                $activeConnections->count(),

                'terminals' =>
                $activeTerminals->count(),

                'errors' =>
                count(
                    $errors
                ),

                'warnings' =>
                count(
                    $warnings
                ),
            ],
        ];
    }
}
