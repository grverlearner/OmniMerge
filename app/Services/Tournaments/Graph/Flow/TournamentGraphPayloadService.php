<?php

namespace App\Services\Tournaments\Graph\Flow;

use App\Models\TournamentTemplate;

class TournamentGraphPayloadService
{
    public function build(
        TournamentTemplate $template,
        array $analysis,
        array $validation
    ): array {
        $template->loadMissing([
            'graphNodes.phaseTemplate.exits',
            'graphNodes.entryPorts.incomingConnections',
            'graphStarts.outgoingConnections',
            'graphTerminals.incomingConnections',
            'graphConnections.sourceStart',
            'graphConnections.sourceNode',
            'graphConnections.sourcePhaseExit',
            'graphConnections.targetEntryPort.node',
            'graphConnections.targetTerminal',
        ]);

        return [
            'template' => [
                'id' => (int) $template->id,
                'code' => $template->code,
                'name' => $template->name,
                'status' => $template->status,
                'min_participants' =>
                $template->min_participants,
                'max_participants' =>
                $template->max_participants,
            ],

            'starts' => $template->graphStarts
                ->map(fn($start) => [
                    'id' => (int) $start->id,
                    'kind' => 'START',
                    'code' => $start->code,
                    'name' => $start->name,
                    'description' => $start->description,
                    'source_type' => $start->source_type,
                    'source_type_label' =>
                    $start->source_type_label,
                    'expected_participants' =>
                    $start->expected_participants,
                    'status' => $start->status,
                    'outgoing_count' =>
                    $start->outgoingConnections
                        ->where('status', 'ACTIVE')
                        ->count(),
                    'update_url' => route(
                        'tournaments.graph.starts.update',
                        [$template, $start]
                    ),
                    'delete_url' => route(
                        'tournaments.graph.starts.destroy',
                        [$template, $start]
                    ),
                ])
                ->values()
                ->all(),

            'nodes' => $template->graphNodes
                ->map(function ($node) use (
                    $analysis,
                    $template,
                    $validation
                ) {
                    $level = collect($analysis['levels'])
                        ->first(
                            fn(array $item) =>
                            in_array(
                                (int) $node->id,
                                $item['node_ids'],
                                true
                            )
                        )['level'] ?? null;

                    return [
                        'id' => (int) $node->id,
                        'kind' => 'NODE',
                        'code' => $node->code,
                        'name' => $node->name,
                        'description' => $node->description,
                        'status' => $node->status,
                        'level' => $level,
                        'phase_template_id' =>
                        (int) $node->phase_template_id,
                        'phase_template_name' =>
                        $node->phaseTemplate?->name,
                        'phase_type' =>
                        $node->phaseTemplate?->phase_type,
                        'phase_type_label' =>
                        $node->phaseTemplate?->type_label,
                        'participant_contract' =>
                        $node
                            ->phaseTemplate
                            ?->participant_contract_label,
                        'flow_forecast' =>
                        $validation['forecasts']['nodes'][$node->id]
                            ??
                            null,

                        'flow_forecast_label' =>
                        isset(
                            $validation['forecasts']['nodes'][$node->id]
                        )
                            ? app(
                                TournamentGraphCapacityCalculator::class
                            )->label(
                                $validation['forecasts']['nodes'][$node->id]
                            )
                            : 'Sin cálculo',

                        'entries' => $node->entryPorts
                            ->map(fn($port) => [
                                'id' => (int) $port->id,
                                'code' => $port->code,
                                'name' => $port->name,
                                'description' =>
                                $port->description,
                                'merge_policy' =>
                                $port->merge_policy,
                                'merge_policy_label' =>
                                $port->merge_policy_label,
                                'is_required' =>
                                $port->is_required,
                                'accepts_multiple_connections' =>
                                $port
                                    ->accepts_multiple_connections,
                                'contract' =>
                                $port->contract_label,
                                'flow_forecast' =>
                                $validation['forecasts']['entries'][$port->id]
                                    ??
                                    null,

                                'flow_forecast_label' =>
                                isset(
                                    $validation['forecasts']['entries'][$port->id]
                                )
                                    ? app(
                                        TournamentGraphCapacityCalculator::class
                                    )->label(
                                        $validation['forecasts']['entries'][$port->id]
                                    )
                                    : 'Sin cálculo',
                                'incoming_count' =>
                                $port
                                    ->incomingConnections
                                    ->where('status', 'ACTIVE')
                                    ->count(),
                                'update_url' => route(
                                    'tournaments.graph.entry-ports.update',
                                    [$template, $node, $port]
                                ),
                                'delete_url' => route(
                                    'tournaments.graph.entry-ports.destroy',
                                    [$template, $node, $port]
                                ),
                            ])
                            ->values()
                            ->all(),

                        'exits' => $node
                            ->phaseTemplate
                            ?->exits
                            ?->map(fn($exit) => [
                                'id' => (int) $exit->id,
                                'code' => $exit->code,
                                'name' => $exit->name,
                                'selector' =>
                                $exit->selector_label,
                                'timing' => $exit->timing_label,
                                'flow_forecast' =>
                                $validation['forecasts']['exits'][$node->id
                                    .
                                    ':'
                                    .
                                    $exit->id]
                                    ??
                                    null,

                                'flow_forecast_label' =>
                                isset(
                                    $validation['forecasts']['exits'][$node->id
                                        .
                                        ':'
                                        .
                                        $exit->id]
                                )
                                    ? app(
                                        TournamentGraphCapacityCalculator::class
                                    )->label(
                                        $validation['forecasts']['exits'][$node->id
                                            .
                                            ':'
                                            .
                                            $exit->id]
                                    )
                                    : 'Variable',
                            ])
                            ->values()
                            ->all() ?? [],

                        'update_url' => route(
                            'tournaments.graph.nodes.update',
                            [$template, $node]
                        ),
                        'duplicate_url' => route(
                            'tournaments.graph.nodes.duplicate',
                            [$template, $node]
                        ),
                        'delete_url' => route(
                            'tournaments.graph.nodes.destroy',
                            [$template, $node]
                        ),
                        'entry_store_url' => route(
                            'tournaments.graph.entry-ports.store',
                            [$template, $node]
                        ),
                    ];
                })
                ->values()
                ->all(),

            'terminals' => $template->graphTerminals
                ->map(fn($terminal) => [
                    'id' => (int) $terminal->id,
                    'kind' => 'TERMINAL',
                    'code' => $terminal->code,
                    'name' => $terminal->name,
                    'description' => $terminal->description,
                    'terminal_type' =>
                    $terminal->terminal_type,
                    'terminal_type_label' =>
                    $terminal->terminal_type_label,
                    'expected_participants' =>
                    $terminal->expected_participants,
                    'status' => $terminal->status,
                    'incoming_count' =>
                    $terminal->incomingConnections
                        ->where('status', 'ACTIVE')
                        ->count(),
                    'flow_forecast' =>
                    $validation['forecasts']['terminals'][$terminal->id]
                        ??
                        null,

                    'flow_forecast_label' =>
                    isset(
                        $validation['forecasts']['terminals'][$terminal->id]
                    )
                        ? app(
                            TournamentGraphCapacityCalculator::class
                        )->label(
                            $validation['forecasts']['terminals'][$terminal->id]
                        )
                        : 'Sin cálculo',
                    'update_url' => route(
                        'tournaments.graph.terminals.update',
                        [$template, $terminal]
                    ),
                    'delete_url' => route(
                        'tournaments.graph.terminals.destroy',
                        [$template, $terminal]
                    ),
                ])
                ->values()
                ->all(),

            'connections' => $template->graphConnections
                ->map(fn($connection) => [
                    'id' => (int) $connection->id,
                    'kind' => 'CONNECTION',
                    'code' => $connection->code,
                    'label' => $connection->label,
                    'description' =>
                    $connection->description,
                    'source_type' =>
                    $connection->source_type,
                    'source_start_id' =>
                    $connection->source_start_id,
                    'source_node_id' =>
                    $connection->source_node_id,
                    'source_phase_exit_id' =>
                    $connection->source_phase_exit_id,
                    'source_label' =>
                    $connection->source_label,
                    'target_type' =>
                    $connection->target_type,
                    'target_entry_port_id' =>
                    $connection->target_entry_port_id,
                    'target_terminal_id' =>
                    $connection->target_terminal_id,
                    'target_label' =>
                    $connection->target_label,
                    'allocation_mode' =>
                    $connection->allocation_mode,
                    'allocation_value' =>
                    $connection->allocation_value,
                    'allocation_label' =>
                    $connection->allocation_label,
                    'priority' =>
                    $connection->priority,
                    'status' =>
                    $connection->status,
                    'update_url' => route(
                        'tournaments.graph.connections.update',
                        [$template, $connection]
                    ),
                    'delete_url' => route(
                        'tournaments.graph.connections.destroy',
                        [$template, $connection]
                    ),
                ])
                ->values()
                ->all(),

            'analysis' => $analysis,
            'validation' => $validation,

            'urls' => [
                'node_store' => route(
                    'tournaments.graph.nodes.store',
                    $template
                ),
                'start_store' => route(
                    'tournaments.graph.starts.store',
                    $template
                ),
                'terminal_store' => route(
                    'tournaments.graph.terminals.store',
                    $template
                ),
                'connection_store' => route(
                    'tournaments.graph.connections.store',
                    $template
                ),
                'validate' => route(
                    'tournaments.graph.validate',
                    $template
                ),
                'auto_layout' => route(
                    'tournaments.graph.auto-layout',
                    $template
                ),
            ],
        ];
    }
}
