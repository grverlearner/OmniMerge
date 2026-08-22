<?php

namespace App\Services\Tournaments\CompetitionLab;

use App\Models\TournamentTemplate;
use App\Models\User;
use App\Services\Tournaments\Graph\Preview\PreviewParticipantFactory;
use Illuminate\Support\Str;

class LabStateFactory
{
    public function __construct(
        private readonly
        PreviewParticipantFactory $participantFactory
    ) {}

    public function create(
        TournamentTemplate $template,
        User $user,
        array $configuration
    ): array {
        $template->loadMissing([
            'graphStarts',
            'graphNodes.phaseTemplate',
            'graphNodes.phaseTemplate.exits',
            'graphNodes.entryPorts.incomingConnections',
            'graphTerminals',
            'graphConnections.sourceStart',
            'graphConnections.sourceNode',
            'graphConnections.sourcePhaseExit',
            'graphConnections.targetEntryPort.node',
            'graphConnections.targetTerminal',
        ]);

        $participants = [];
        $starts = [];

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

            $generated =
                $this->participantFactory
                ->generate(
                    $start,
                    (int) $startConfiguration['count'],
                    $startConfiguration['prefix']
                        ??
                        null,
                    $user
                );

            $generated =
                $this->participantFactory
                ->reorder(
                    $generated,
                    $configuration['ordering_strategy'],
                    (int) $configuration['seed']
                        +
                        $start->id
                );

            foreach (
                $generated
                as
                $participant
            ) {
                $participant['lab_id'] =
                    'LAB-'
                    .
                    $participant['preview_id'];

                $participant['status'] =
                    'WAITING';

                $participant['current_location'] = [
                    'type' =>
                    'START',

                    'id' =>
                    (int) $start->id,

                    'code' =>
                    $start->code,

                    'name' =>
                    $start->name,
                ];

                $participant['statistics'] = [
                    'matches' => 0,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'points' => 0,
                ];

                $participants[$participant['lab_id']] =
                    $participant;
            }

            $starts[$start->id] = [
                'id' =>
                (int) $start->id,

                'code' =>
                $start->code,

                'name' =>
                $start->name,

                'status' =>
                'READY',

                'participant_ids' =>
                collect(
                    $generated
                )
                    ->map(
                        fn($participant) =>
                        'LAB-'
                            .
                            $participant['preview_id']
                    )
                    ->values()
                    ->all(),

                'participant_count' =>
                count($generated),
            ];
        }

        return $this->assemble(
            $template,
            (int) $user->id,
            $configuration,
            $participants,
            $starts
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Ensamblado del estado
    |--------------------------------------------------------------------------
    |
    | Construye el estado del motor a partir del grafo y de una lista de
    | participantes YA resuelta.
    |
    | Está separado del reparto de participantes a propósito: el
    | Competition Lab genera participantes sintéticos, mientras que el
    | Tournament Runtime persistente (Fase 6) usa competidores reales del
    | Universo. Todo lo demás — nodos, puertos, conexiones, terminales,
    | resumen — es idéntico y se comparte aquí en vez de duplicarse.
    |
    */

    public function assemble(
        TournamentTemplate $template,
        int $userId,
        array $configuration,
        array $participants,
        array $starts
    ): array {

        $nodes =
            $template
            ->graphNodes
            ->mapWithKeys(
                fn($node) => [
                    $node->id => [
                        'id' =>
                        (int) $node->id,

                        'code' =>
                        $node->code,

                        'name' =>
                        $node->name,

                        'phase_type' =>
                        $node
                            ->phaseTemplate
                            ?->phase_type,

                        'phase_type_label' =>
                        $node
                            ->phaseTemplate
                            ?->type_label,

                        'status' =>
                        'LOCKED',

                        'participant_ids' =>
                        [],

                        'entry_ports' =>
                        $node
                            ->entryPorts
                            ->mapWithKeys(
                                fn($port) => [
                                    $port->id => [
                                        'id' =>
                                        (int) $port->id,

                                        'name' =>
                                        $port->name,

                                        'code' =>
                                        $port->code,

                                        'merge_policy' =>
                                        $port->merge_policy,

                                        'is_required' =>
                                        (bool)
                                        $port->is_required,

                                        'accepts_multiple_connections' =>
                                        (bool)
                                        $port->accepts_multiple_connections,

                                        'min_participants' =>
                                        $port->min_participants,

                                        'max_participants' =>
                                        $port->max_participants,

                                        'exact_participants' =>
                                        $port->exact_participants,

                                        'incoming_connection_ids' =>
                                        $port
                                            ->incomingConnections
                                            ->where('status', 'ACTIVE')
                                            ->sortBy(
                                                fn($connection) =>
                                                sprintf(
                                                    '%010d-%010d-%010d',
                                                    $connection->priority,
                                                    $connection->sequence_number,
                                                    $connection->id
                                                )
                                            )
                                            ->pluck('id')
                                            ->map(
                                                fn($id) =>
                                                (int)
                                                $id
                                            )
                                            ->values()
                                            ->all(),

                                        'received_connection_ids' =>
                                        [],

                                        'connection_payloads' =>
                                        [],

                                        'status' =>
                                        'EMPTY',

                                        'participant_ids' =>
                                        [],
                                    ],
                                ]
                            )
                            ->all(),
                    ],
                ]
            )
            ->all();

        $terminals =
            $template
            ->graphTerminals
            ->mapWithKeys(
                fn($terminal) => [
                    $terminal->id => [
                        'id' =>
                        (int) $terminal->id,

                        'code' =>
                        $terminal->code,

                        'name' =>
                        $terminal->name,

                        'type' =>
                        $terminal
                            ->terminal_type,

                        'type_label' =>
                        $terminal
                            ->terminal_type_label,

                        'expected_participants' =>
                        $terminal->expected_participants,

                        'received_connection_ids' =>
                        [],

                        'status' =>
                        'EMPTY',

                        'participant_ids' =>
                        [],
                    ],
                ]
            )
            ->all();

        $connections =
            $template
            ->graphConnections
            ->mapWithKeys(
                fn($connection) => [
                    $connection->id => [
                        'id' =>
                        (int)
                        $connection->id,

                        'code' =>
                        $connection->code,

                        'label' =>
                        $connection->label,

                        'source_type' =>
                        $connection->source_type,

                        'source_start_id' =>
                        $connection->source_start_id
                            ? (int)
                            $connection->source_start_id
                            : null,

                        'source_node_id' =>
                        $connection->source_node_id
                            ? (int)
                            $connection->source_node_id
                            : null,

                        'source_phase_exit_id' =>
                        $connection->source_phase_exit_id
                            ? (int)
                            $connection->source_phase_exit_id
                            : null,

                        'target_type' =>
                        $connection->target_type,

                        'target_entry_port_id' =>
                        $connection->target_entry_port_id
                            ? (int)
                            $connection->target_entry_port_id
                            : null,

                        'target_terminal_id' =>
                        $connection->target_terminal_id
                            ? (int)
                            $connection->target_terminal_id
                            : null,

                        'allocation_mode' =>
                        $connection->allocation_mode,

                        'allocation_value' =>
                        $connection->allocation_value,

                        'priority' =>
                        (int)
                        $connection->priority,

                        'sequence_number' =>
                        (int)
                        $connection->sequence_number,

                        'status' =>
                        'PENDING',

                        'participant_ids' =>
                        [],

                        'routed_count' =>
                        0,
                    ],
                ]
            )
            ->all();

        $labId =
            (string) Str::uuid();

        return [
            'schema_version' =>
            1,

            'lab_id' =>
            $labId,

            'user_id' =>
            $userId,

            'tournament_template_id' =>
            (int) $template->id,

            'tournament' => [
                'id' =>
                (int) $template->id,

                'code' =>
                $template->code,

                'name' =>
                $template->name,
            ],

            'status' =>
            'READY',

            'configuration' => [
                'participant_mode' =>
                $configuration['participant_mode'],

                'ordering_strategy' =>
                $configuration['ordering_strategy'],

                'seed' =>
                (int) $configuration['seed'],
            ],

            'participants' =>
            $participants,

            'starts' =>
            $starts,

            'nodes' =>
            $nodes,

            'connections' =>
            $connections,

            'terminals' =>
            $terminals,

            'timeline' => [
                [
                    'step' =>
                    1,

                    'type' =>
                    'LAB_INITIALIZED',

                    'level' =>
                    'SUCCESS',

                    'message' =>
                    'Competition Lab inicializado con '
                        .
                        count($participants)
                        .
                        ' participantes temporales.',
                ],
            ],

            'summary' => [
                'participants' =>
                count($participants),

                'starts' =>
                count($starts),

                'nodes' =>
                count($nodes),

                'terminals' =>
                count($terminals),

                'completed_nodes' =>
                0,

                'matches' =>
                0,

                'completed_matches' =>
                0,

                'routed_connections' =>
                0,

                'completed_terminals' =>
                0,

                'stranded' =>
                0,
            ],

            'created_at' =>
            now()->toIso8601String(),

            'updated_at' =>
            now()->toIso8601String(),
        ];
    }
}
