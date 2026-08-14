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
            'graphNodes.entryPorts',
            'graphTerminals',
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
                        null
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

                        'status' =>
                        'EMPTY',

                        'participant_ids' =>
                        [],
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
            (int) $user->id,

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
            ],

            'created_at' =>
            now()->toIso8601String(),

            'updated_at' =>
            now()->toIso8601String(),
        ];
    }
}
