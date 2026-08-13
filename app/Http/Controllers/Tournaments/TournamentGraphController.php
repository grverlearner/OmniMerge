<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Models\PhaseTemplate;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\Graph\TournamentGraphLayoutService;
use App\Services\Tournaments\Graph\TournamentGraphValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentGraphController
extends Controller
{
    public function __construct(
        private readonly
        TournamentGraphValidationService $validationService,

        private readonly
        TournamentGraphLayoutService $layoutService
    ) {}


    public function show(
        Request $request,
        TournamentTemplate $tournamentTemplate
    ): View {
        $this->authorize(
            'update',
            $tournamentTemplate
        );


        $tournamentTemplate->load([
            'graphNodes.phaseTemplate.exits' =>
            fn($query) =>
            $query->where(
                'status',
                'ACTIVE'
            ),

            'graphNodes.entryPorts',

            'graphStarts',

            'graphTerminals',

            'graphConnections.sourceStart',

            'graphConnections.sourceNode',

            'graphConnections.sourcePhaseExit',

            'graphConnections.targetEntryPort.node',

            'graphConnections.targetTerminal',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Fases disponibles
        |--------------------------------------------------------------------------
        */

        $availablePhaseTemplates =
            PhaseTemplate::query()
            ->ownedBy(
                $request->user()
            )
            ->active()
            ->with([
                'exits' =>
                fn($query) =>
                $query->where(
                    'status',
                    'ACTIVE'
                ),
            ])
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $graphValidation =
            $this
            ->validationService
            ->validate(
                $tournamentTemplate
            );


        /*
        |--------------------------------------------------------------------------
        | Graph Payload
        |--------------------------------------------------------------------------
        */

        $graphPayload = [
            'nodes' =>
            $tournamentTemplate
                ->graphNodes
                ->map(
                    fn($node) => [
                        'id' =>
                        $node->id,

                        'code' =>
                        $node->code,

                        'name' =>
                        $node->name,

                        'phase_type' =>
                        $node
                            ->phaseTemplate
                            ->phase_type,

                        'phase_type_label' =>
                        $node
                            ->phaseTemplate
                            ->type_label,

                        'phase_template_name' =>
                        $node
                            ->phaseTemplate
                            ->name,

                        'contract' =>
                        $node
                            ->phaseTemplate
                            ->participant_contract_label,

                        'status' =>
                        $node->status,

                        'x' =>
                        $node->x_position,

                        'y' =>
                        $node->y_position,

                        'entries' =>
                        $node
                            ->entryPorts
                            ->map(
                                fn($port) => [
                                    'id' =>
                                    $port->id,

                                    'code' =>
                                    $port->code,

                                    'name' =>
                                    $port->name,

                                    'required' =>
                                    $port
                                        ->is_required,

                                    'multiple' =>
                                    $port
                                        ->accepts_multiple_connections,

                                    'merge_policy' =>
                                    $port
                                        ->merge_policy,

                                    'merge_label' =>
                                    $port
                                        ->merge_policy_label,

                                    'contract' =>
                                    $port
                                        ->contract_label,

                                    'delete_url' =>
                                    route(
                                        'tournaments.graph.entry-ports.destroy',
                                        [
                                            $tournamentTemplate,
                                            $node,
                                            $port,
                                        ]
                                    ),
                                ]
                            )
                            ->values()
                            ->all(),

                        'exits' =>
                        $node
                            ->phaseTemplate
                            ->exits
                            ->map(
                                fn($exit) => [
                                    'id' =>
                                    $exit->id,

                                    'code' =>
                                    $exit->code,

                                    'name' =>
                                    $exit->name,

                                    'selector' =>
                                    $exit
                                        ->selector_label,

                                    'timing' =>
                                    $exit
                                        ->timing_label,
                                ]
                            )
                            ->values()
                            ->all(),

                        'position_url' =>
                        route(
                            'tournaments.graph.nodes.position',
                            [
                                $tournamentTemplate,
                                $node,
                            ]
                        ),

                        'duplicate_url' =>
                        route(
                            'tournaments.graph.nodes.duplicate',
                            [
                                $tournamentTemplate,
                                $node,
                            ]
                        ),

                        'delete_url' =>
                        route(
                            'tournaments.graph.nodes.destroy',
                            [
                                $tournamentTemplate,
                                $node,
                            ]
                        ),

                        'entry_store_url' =>
                        route(
                            'tournaments.graph.entry-ports.store',
                            [
                                $tournamentTemplate,
                                $node,
                            ]
                        ),
                    ]
                )
                ->values()
                ->all(),


            'starts' =>
            $tournamentTemplate
                ->graphStarts
                ->map(
                    fn($start) => [
                        'id' =>
                        $start->id,

                        'code' =>
                        $start->code,

                        'name' =>
                        $start->name,

                        'type' =>
                        $start
                            ->source_type_label,

                        'expected' =>
                        $start
                            ->expected_participants,

                        'status' =>
                        $start->status,

                        'x' =>
                        $start->x_position,

                        'y' =>
                        $start->y_position,

                        'position_url' =>
                        route(
                            'tournaments.graph.starts.position',
                            [
                                $tournamentTemplate,
                                $start,
                            ]
                        ),

                        'delete_url' =>
                        route(
                            'tournaments.graph.starts.destroy',
                            [
                                $tournamentTemplate,
                                $start,
                            ]
                        ),
                    ]
                )
                ->values()
                ->all(),


            'terminals' =>
            $tournamentTemplate
                ->graphTerminals
                ->map(
                    fn($terminal) => [
                        'id' =>
                        $terminal->id,

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

                        'status' =>
                        $terminal->status,

                        'x' =>
                        $terminal->x_position,

                        'y' =>
                        $terminal->y_position,

                        'position_url' =>
                        route(
                            'tournaments.graph.terminals.position',
                            [
                                $tournamentTemplate,
                                $terminal,
                            ]
                        ),

                        'delete_url' =>
                        route(
                            'tournaments.graph.terminals.destroy',
                            [
                                $tournamentTemplate,
                                $terminal,
                            ]
                        ),
                    ]
                )
                ->values()
                ->all(),


            'connections' =>
            $tournamentTemplate
                ->graphConnections
                ->map(
                    fn($connection) => [
                        'id' =>
                        $connection->id,

                        'code' =>
                        $connection->code,

                        'label' =>
                        $connection->label,

                        'source_type' =>
                        $connection
                            ->source_type,

                        'source_start_id' =>
                        $connection
                            ->source_start_id,

                        'source_node_id' =>
                        $connection
                            ->source_node_id,

                        'source_phase_exit_id' =>
                        $connection
                            ->source_phase_exit_id,

                        'target_type' =>
                        $connection
                            ->target_type,

                        'target_entry_port_id' =>
                        $connection
                            ->target_entry_port_id,

                        'target_terminal_id' =>
                        $connection
                            ->target_terminal_id,

                        'source_label' =>
                        $connection
                            ->source_label,

                        'target_label' =>
                        $connection
                            ->target_label,

                        'allocation_mode' =>
                        $connection
                            ->allocation_mode,

                        'allocation_value' =>
                        $connection
                            ->allocation_value,

                        'allocation_label' =>
                        $connection
                            ->allocation_label,

                        'priority' =>
                        $connection
                            ->priority,

                        'status' =>
                        $connection
                            ->status,

                        'update_url' =>
                        route(
                            'tournaments.graph.connections.update',
                            [
                                $tournamentTemplate,
                                $connection,
                            ]
                        ),

                        'delete_url' =>
                        route(
                            'tournaments.graph.connections.destroy',
                            [
                                $tournamentTemplate,
                                $connection,
                            ]
                        ),
                    ]
                )
                ->values()
                ->all(),

            'connection_store_url' =>
            route(
                'tournaments.graph.connections.store',
                $tournamentTemplate
            ),
        ];


        return view(
            'tournaments.graph.show',
            compact(
                'tournamentTemplate',
                'availablePhaseTemplates',
                'graphValidation',
                'graphPayload'
            )
        );
    }


    public function validateGraph(
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {
        $this->authorize(
            'update',
            $tournamentTemplate
        );


        $validation =
            $this
            ->validationService
            ->validate(
                $tournamentTemplate
            );


        if (
            $validation['valid']
        ) {
            return back()
                ->with(
                    'success',
                    'El Tournament Graph es estructuralmente válido.'
                );
        }


        return back()
            ->with(
                'warning',
                'El Tournament Graph todavía contiene problemas estructurales.'
            );
    }


    public function autoLayout(
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {
        $this->authorize(
            'update',
            $tournamentTemplate
        );


        $this
            ->layoutService
            ->layout(
                $tournamentTemplate
            );


        return back()
            ->with(
                'success',
                'Auto-layout aplicado.'
            );
    }
}
