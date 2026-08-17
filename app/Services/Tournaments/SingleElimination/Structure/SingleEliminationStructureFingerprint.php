<?php

namespace App\Services\Tournaments\SingleElimination\Structure;

use App\Models\PhaseTemplate;

final class SingleEliminationStructureFingerprint
{
    public function forPhase(
        PhaseTemplate $phaseTemplate
    ): string {
        $phaseTemplate->load([
            'singleEliminationSetting',
            'singleEliminationRoundRules',
            'inputGates',
            'singleEliminationRounds.encounters.slots',
            'singleEliminationRounds.encounters.results',
            'exits',
        ]);

        $settings =
            $phaseTemplate->singleEliminationSetting;

        $connections =
            $phaseTemplate
            ->singleEliminationConnections()
            ->orderBy('priority')
            ->orderBy('sequence_number')
            ->orderBy('id')
            ->get();

        $payload = [
            'schema' =>
            1,

            'phase_contract' => [
                'phase_type' =>
                $phaseTemplate->phase_type,

                'participant_mode' =>
                $phaseTemplate->participant_mode,

                'min_participants' =>
                $phaseTemplate->min_participants,

                'max_participants' =>
                $phaseTemplate->max_participants,

                'exact_participants' =>
                $phaseTemplate->exact_participants,

                'participant_multiple' =>
                $phaseTemplate->participant_multiple,

                'allow_byes' =>
                (bool) $phaseTemplate->allow_byes,
            ],

            'settings' =>
            $settings
                ? [
                    'configuration_mode' =>
                    $settings->configuration_mode,

                    'input_mode' =>
                    $settings->input_mode,

                    'routing_mode' =>
                    $settings->routing_mode,

                    'entrants_per_match' =>
                    (int) $settings->entrants_per_match,

                    'qualifiers_per_match' =>
                    (int) $settings->qualifiers_per_match,

                    'encounter_profile' =>
                    $settings->encounter_profile,

                    'remainder_policy' =>
                    $settings->remainder_policy,

                    'completion_mode' =>
                    $settings->completion_mode,

                    'target_survivors' =>
                    (int) $settings->target_survivors,

                    'seeding_mode' =>
                    $settings->seeding_mode,

                    'pairing_mode' =>
                    $settings->pairing_mode,

                    'bye_assignment' =>
                    $settings->bye_assignment,

                    'reseed_each_round' =>
                    (bool) $settings->reseed_each_round,

                    'series_format' =>
                    $settings->series_format,

                    'default_best_of' =>
                    (int) $settings->default_best_of,

                    'fixed_games' =>
                    (int) $settings->fixed_games,

                    'custom_graph_participants' =>
                    (int) data_get(
                        $settings->settings,
                        'custom_graph_participants',
                        0
                    ),
                ]
                : null,

            'round_rules' =>
            $phaseTemplate
                ->singleEliminationRoundRules
                ->sortBy([
                    ['participants_in_round', 'desc'],
                    ['sort_order', 'asc'],
                    ['id', 'asc'],
                ])
                ->values()
                ->map(
                    fn($rule) => [
                        'participants_in_round' =>
                        (int) $rule->participants_in_round,

                        'entrants_per_match' =>
                        $rule->entrants_per_match === null
                            ? null
                            : (int) $rule->entrants_per_match,

                        'qualifiers_per_match' =>
                        $rule->qualifiers_per_match === null
                            ? null
                            : (int) $rule->qualifiers_per_match,

                        'encounter_profile' =>
                        $rule->encounter_profile,

                        'series_format' =>
                        $rule->series_format,

                        'best_of' =>
                        $rule->best_of === null
                            ? null
                            : (int) $rule->best_of,

                        'fixed_games' =>
                        $rule->fixed_games === null
                            ? null
                            : (int) $rule->fixed_games,

                        'sort_order' =>
                        (int) $rule->sort_order,
                    ]
                )
                ->all(),

            'input_gates' =>
            $phaseTemplate
                ->inputGates
                ->sortBy([
                    ['priority', 'asc'],
                    ['sort_order', 'asc'],
                    ['sequence_number', 'asc'],
                    ['id', 'asc'],
                ])
                ->values()
                ->map(
                    fn($gate) => [
                        'id' =>
                        (int) $gate->id,

                        'sequence_number' =>
                        (int) $gate->sequence_number,

                        'code' =>
                        $gate->code,

                        'input_type' =>
                        $gate->input_type,

                        'merge_policy' =>
                        $gate->merge_policy,

                        'distribution_mode' =>
                        $gate->distribution_mode,

                        'empty_behavior' =>
                        $gate->empty_behavior,

                        'min_participants' =>
                        $gate->min_participants === null
                            ? null
                            : (int) $gate->min_participants,

                        'max_participants' =>
                        $gate->max_participants === null
                            ? null
                            : (int) $gate->max_participants,

                        'exact_participants' =>
                        $gate->exact_participants === null
                            ? null
                            : (int) $gate->exact_participants,

                        'is_required' =>
                        (bool) $gate->is_required,

                        'accepts_batch' =>
                        (bool) $gate->accepts_batch,

                        'accepts_multiple_connections' =>
                        (bool) $gate->accepts_multiple_connections,

                        'priority' =>
                        (int) $gate->priority,

                        'sort_order' =>
                        (int) $gate->sort_order,

                        'status' =>
                        $gate->status,

                        'seed' =>
                        data_get(
                            $gate->settings,
                            'seed'
                        ),

                        'generated_placeholder' =>
                        (bool) data_get(
                            $gate->settings,
                            'generated_placeholder',
                            false
                        ),
                    ]
                )
                ->all(),

            'rounds' =>
            $phaseTemplate
                ->singleEliminationRounds
                ->sortBy([
                    ['stage_number', 'asc'],
                    ['sequence_number', 'asc'],
                    ['id', 'asc'],
                ])
                ->values()
                ->map(
                    fn($round) => [
                        'id' =>
                        (int) $round->id,

                        'sequence_number' =>
                        (int) $round->sequence_number,

                        'code' =>
                        $round->code,

                        'stage_number' =>
                        (int) $round->stage_number,

                        'branch_code' =>
                        $round->branch_code,

                        'round_type' =>
                        $round->round_type,

                        'participants_expected' =>
                        $round->participants_expected === null
                            ? null
                            : (int) $round->participants_expected,

                        'qualifiers_expected' =>
                        $round->qualifiers_expected === null
                            ? null
                            : (int) $round->qualifiers_expected,

                        'sort_order' =>
                        (int) $round->sort_order,

                        'status' =>
                        $round->status,

                        'byes' =>
                        (int) data_get(
                            $round->settings,
                            'byes',
                            0
                        ),

                        'remainder_policy' =>
                        data_get(
                            $round->settings,
                            'remainder_policy'
                        ),

                        'encounters' =>
                        $round
                            ->encounters
                            ->sortBy([
                                ['position', 'asc'],
                                ['sequence_number', 'asc'],
                                ['id', 'asc'],
                            ])
                            ->values()
                            ->map(
                                fn($encounter) => [
                                    'id' =>
                                    (int) $encounter->id,

                                    'sequence_number' =>
                                    (int) $encounter->sequence_number,

                                    'code' =>
                                    $encounter->code,

                                    'position' =>
                                    (int) $encounter->position,

                                    'entrants_count' =>
                                    (int) $encounter->entrants_count,

                                    'qualifiers_count' =>
                                    (int) $encounter->qualifiers_count,

                                    'min_entrants_to_start' =>
                                    (int) $encounter->min_entrants_to_start,

                                    'encounter_profile' =>
                                    $encounter->encounter_profile,

                                    'activation_policy' =>
                                    $encounter->activation_policy,

                                    'allows_incomplete' =>
                                    (bool) $encounter->allows_incomplete,

                                    'series_format' =>
                                    $encounter->series_format,

                                    'best_of' =>
                                    $encounter->best_of === null
                                        ? null
                                        : (int) $encounter->best_of,

                                    'fixed_games' =>
                                    $encounter->fixed_games === null
                                        ? null
                                        : (int) $encounter->fixed_games,

                                    'sort_order' =>
                                    (int) $encounter->sort_order,

                                    'status' =>
                                    $encounter->status,

                                    'actual_entrants' =>
                                    (int) data_get(
                                        $encounter->settings,
                                        'actual_entrants',
                                        $encounter->entrants_count
                                    ),

                                    'resolution_mode' =>
                                    data_get(
                                        $encounter->settings,
                                        'resolution_mode'
                                    ),

                                    'qualifier_ordering' =>
                                    data_get(
                                        $encounter->settings,
                                        'qualifier_ordering'
                                    ),

                                    'slots' =>
                                    $encounter
                                        ->slots
                                        ->sortBy([
                                            ['position', 'asc'],
                                            ['id', 'asc'],
                                        ])
                                        ->values()
                                        ->map(
                                            fn($slot) => [
                                                'id' =>
                                                (int) $slot->id,

                                                'code' =>
                                                $slot->code,

                                                'position' =>
                                                (int) $slot->position,

                                                'slot_type' =>
                                                $slot->slot_type,

                                                'capacity' =>
                                                (int) $slot->capacity,

                                                'is_required' =>
                                                (bool) $slot->is_required,

                                                'source_policy' =>
                                                $slot->source_policy,

                                                'empty_behavior' =>
                                                $slot->empty_behavior,

                                                'assignment_rule' =>
                                                $slot->assignment_rule,

                                                'sort_order' =>
                                                (int) $slot->sort_order,

                                                'status' =>
                                                $slot->status,
                                            ]
                                        )
                                        ->all(),

                                    'results' =>
                                    $encounter
                                        ->results
                                        ->sortBy([
                                            ['sort_order', 'asc'],
                                            ['sequence_number', 'asc'],
                                            ['id', 'asc'],
                                        ])
                                        ->values()
                                        ->map(
                                            fn($result) => [
                                                'id' =>
                                                (int) $result->id,

                                                'sequence_number' =>
                                                (int) $result->sequence_number,

                                                'code' =>
                                                $result->code,

                                                'result_type' =>
                                                $result->result_type,

                                                'position_from' =>
                                                $result->position_from === null
                                                    ? null
                                                    : (int) $result->position_from,

                                                'position_to' =>
                                                $result->position_to === null
                                                    ? null
                                                    : (int) $result->position_to,

                                                'quantity' =>
                                                (int) $result->quantity,

                                                'flow_mode' =>
                                                $result->flow_mode,

                                                'participant_status' =>
                                                $result->participant_status,

                                                'is_required' =>
                                                (bool) $result->is_required,

                                                'is_splittable' =>
                                                (bool) $result->is_splittable,

                                                'accepts_multiple_connections' =>
                                                (bool) $result->accepts_multiple_connections,

                                                'priority' =>
                                                (int) $result->priority,

                                                'sort_order' =>
                                                (int) $result->sort_order,

                                                'status' =>
                                                $result->status,
                                            ]
                                        )
                                        ->all(),
                                ]
                            )
                            ->all(),
                    ]
                )
                ->all(),

            'connections' =>
            $connections
                ->map(
                    fn($connection) => [
                        'id' =>
                        (int) $connection->id,

                        'sequence_number' =>
                        (int) $connection->sequence_number,

                        'code' =>
                        $connection->code,

                        'source_type' =>
                        $connection->source_type,

                        'source_input_gate_id' =>
                        $connection->source_input_gate_id === null
                            ? null
                            : (int) $connection->source_input_gate_id,

                        'source_result_id' =>
                        $connection->source_result_id === null
                            ? null
                            : (int) $connection->source_result_id,

                        'target_type' =>
                        $connection->target_type,

                        'target_slot_id' =>
                        $connection->target_slot_id === null
                            ? null
                            : (int) $connection->target_slot_id,

                        'target_phase_exit_id' =>
                        $connection->target_phase_exit_id === null
                            ? null
                            : (int) $connection->target_phase_exit_id,

                        'allocation_mode' =>
                        $connection->allocation_mode,

                        'allocation_value' =>
                        $connection->allocation_value === null
                            ? null
                            : (int) $connection->allocation_value,

                        'priority' =>
                        (int) $connection->priority,

                        'condition_type' =>
                        $connection->condition_type,

                        'condition' =>
                        $connection->condition,

                        'status' =>
                        $connection->status,

                    ]
                )
                ->all(),

            'exits' =>
            $phaseTemplate
                ->exits
                ->sortBy([
                    ['priority', 'asc'],
                    ['sort_order', 'asc'],
                    ['sequence_number', 'asc'],
                    ['id', 'asc'],
                ])
                ->values()
                ->map(
                    fn($exit) => [
                        'id' =>
                        (int) $exit->id,

                        'sequence_number' =>
                        (int) $exit->sequence_number,

                        'code' =>
                        $exit->code,

                        'selector_type' =>
                        $exit->selector_type,

                        'resolution_mode' =>
                        $exit->resolution_mode,

                        'exit_timing' =>
                        $exit->exit_timing,

                        'min_participants' =>
                        $exit->min_participants === null
                            ? null
                            : (int) $exit->min_participants,

                        'max_participants' =>
                        $exit->max_participants === null
                            ? null
                            : (int) $exit->max_participants,

                        'exact_participants' =>
                        $exit->exact_participants === null
                            ? null
                            : (int) $exit->exact_participants,

                        'priority' =>
                        (int) $exit->priority,

                        'sort_order' =>
                        (int) $exit->sort_order,

                        'status' =>
                        $exit->status,
                    ]
                )
                ->all(),
        ];

        return hash(
            'sha256',
            json_encode(
                $this->normalize(
                    $payload
                ),
                JSON_THROW_ON_ERROR
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
            )
        );
    }

    private function normalize(
        mixed $value
    ): mixed {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] =
                $this->normalize(
                    $item
                );
        }

        return $value;
    }
}
