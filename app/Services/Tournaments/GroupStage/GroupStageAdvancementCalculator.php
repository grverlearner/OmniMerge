<?php

namespace App\Services\Tournaments\GroupStage;

use Illuminate\Support\Collection;

class GroupStageAdvancementCalculator
{
    public function forecast(
        array $groups,
        Collection $rules,
        string $cutoffTiePolicy
    ): array {
        $candidates =
            $this->buildCandidates(
                $groups
            );

        $selected = [];

        $ruleResults = [];

        $outputs = [];

        foreach (
            $rules
                ->where(
                    'status',
                    'ACTIVE'
                )
            as
            $rule
        ) {
            $eligible =
                array_values(
                    array_filter(
                        $candidates,
                        fn($candidate) =>
                        ! isset(
                            $selected[$candidate['key']]
                        )
                    )
                );

            $selection =
                $this->selectForRule(
                    $eligible,
                    $groups,
                    $rule
                );

            foreach (
                $selection
                as
                $candidate
            ) {
                $selected[$candidate['key']] =
                    true;
            }

            $variable =
                $cutoffTiePolicy
                ===
                'INCLUDE_ALL_TIED'
                &&
                in_array(
                    $rule->rule_type,
                    [
                        'CROSS_GROUP_POSITION_TOP_N',
                        'CROSS_GROUP_POSITION_BOTTOM_N',
                        'BEST_REMAINING',
                        'WORST_REMAINING',
                    ],
                    true
                );

            $exitId =
                $rule->phase_exit_id;

            $exitName =
                $rule->phaseExit?->name
                ??
                'Sin puerta vinculada';

            $ruleResults[] = [
                'id' =>
                $rule->id,

                'rule_type' =>
                $rule->rule_type,

                'label' =>
                $rule->rule_type_label,

                'summary' =>
                $rule->rule_summary,

                'output_id' =>
                $exitId,

                'output_name' =>
                $exitName,

                'candidate_count' =>
                count(
                    $eligible
                ),

                'expected_count' =>
                count(
                    $selection
                ),

                'variable_output' =>
                $variable,
            ];

            $outputKey =
                $exitId
                !==
                null
                ? 'EXIT_'
                .
                $exitId
                : 'NO_EXIT';

            if (
                ! isset(
                    $outputs[$outputKey]
                )
            ) {
                $outputs[$outputKey] = [
                    'exit_id' =>
                    $exitId,

                    'name' =>
                    $exitName,

                    'expected_count' =>
                    0,

                    'variable_output' =>
                    false,
                ];
            }

            $outputs[$outputKey]['expected_count'] +=
                count(
                    $selection
                );

            $outputs[$outputKey]['variable_output'] =
                $outputs[$outputKey]['variable_output']
                ||
                $variable;
        }

        return [
            'participants' =>
            count(
                $candidates
            ),

            'selected_count' =>
            count(
                $selected
            ),

            'unselected_count' =>
            count(
                $candidates
            )
                -
                count(
                    $selected
                ),

            'rules' =>
            $ruleResults,

            'outputs' =>
            array_values(
                $outputs
            ),
        ];
    }

    private function buildCandidates(
        array $groups
    ): array {
        $candidates = [];

        foreach (
            $groups
            as
            $group
        ) {
            for (
                $position = 1;
                $position <= $group['size'];
                $position++
            ) {
                $candidates[] = [
                    'key' =>
                    'G'
                        .
                        $group['index']
                        .
                        ':P'
                        .
                        $position,

                    'group_index' =>
                    $group['index'],

                    'group_definition_id' =>
                    $group['definition_id'],

                    'group_name' =>
                    $group['name'],

                    'position' =>
                    $position,
                ];
            }
        }

        return $candidates;
    }

    private function selectForRule(
        array $eligible,
        array $groups,
        object $rule
    ): array {
        return match ($rule->rule_type) {
            'EACH_GROUP_TOP_N' =>
            $this->eachGroupTop(
                $eligible,
                (int)
                $rule->take
            ),

            'EACH_GROUP_BOTTOM_N' =>
            $this->eachGroupBottom(
                $eligible,
                $groups,
                (int)
                $rule->take
            ),

            'EACH_GROUP_POSITION' =>
            $this->eachGroupPosition(
                $eligible,
                (int)
                $rule->position_from
            ),

            'EACH_GROUP_RANGE' =>
            $this->eachGroupRange(
                $eligible,
                (int)
                $rule->position_from,
                (int)
                $rule->position_to
            ),

            'CROSS_GROUP_POSITION_TOP_N' =>
            $this->crossGroupPosition(
                $eligible,
                (int)
                $rule->position_from,
                (int)
                $rule->take,
                false
            ),

            'CROSS_GROUP_POSITION_BOTTOM_N' =>
            $this->crossGroupPosition(
                $eligible,
                (int)
                $rule->position_from,
                (int)
                $rule->take,
                true
            ),

            'BEST_REMAINING' =>
            $this->remainingSelection(
                $eligible,
                (int)
                $rule->take,
                false
            ),

            'WORST_REMAINING' =>
            $this->remainingSelection(
                $eligible,
                (int)
                $rule->take,
                true
            ),

            'SPECIFIC_GROUP_POSITION' =>
            $this->specificGroupPosition(
                $eligible,
                $rule
                    ->phase_group_stage_group_id,
                (int)
                $rule->position_from
            ),

            'SPECIFIC_GROUP_RANGE' =>
            $this->specificGroupRange(
                $eligible,
                $rule
                    ->phase_group_stage_group_id,
                (int)
                $rule->position_from,
                (int)
                $rule->position_to
            ),

            'REMAINING' =>
            $eligible,

            default =>
            [],
        };
    }

    private function eachGroupTop(
        array $eligible,
        int $take
    ): array {
        return array_values(
            array_filter(
                $eligible,
                fn($candidate) =>
                $candidate['position']
                    <=
                    $take
            )
        );
    }

    private function eachGroupBottom(
        array $eligible,
        array $groups,
        int $take
    ): array {
        $groupSizes = [];

        foreach (
            $groups
            as
            $group
        ) {
            $groupSizes[$group['index']] =
                $group['size'];
        }

        return array_values(
            array_filter(
                $eligible,
                function (
                    $candidate
                ) use (
                    $groupSizes,
                    $take
                ) {
                    $size =
                        $groupSizes[$candidate['group_index']];

                    return
                        $candidate['position']
                        >
                        $size
                        -
                        $take;
                }
            )
        );
    }

    private function eachGroupPosition(
        array $eligible,
        int $position
    ): array {
        return array_values(
            array_filter(
                $eligible,
                fn($candidate) =>
                $candidate['position']
                    ===
                    $position
            )
        );
    }

    private function eachGroupRange(
        array $eligible,
        int $from,
        int $to
    ): array {
        return array_values(
            array_filter(
                $eligible,
                fn($candidate) =>
                $candidate['position']
                    >=
                    $from
                    &&
                    $candidate['position']
                    <=
                    $to
            )
        );
    }

    private function crossGroupPosition(
        array $eligible,
        int $position,
        int $take,
        bool $reverse
    ): array {
        $candidates =
            array_values(
                array_filter(
                    $eligible,
                    fn($candidate) =>
                    $candidate['position']
                        ===
                        $position
                )
            );

        usort(
            $candidates,
            fn($left, $right) =>
            $left['group_index']
                <=>
                $right['group_index']
        );

        if ($reverse) {
            $candidates =
                array_reverse(
                    $candidates
                );
        }

        return array_slice(
            $candidates,
            0,
            $take
        );
    }

    private function remainingSelection(
        array $eligible,
        int $take,
        bool $reverse
    ): array {
        usort(
            $eligible,
            function (
                $left,
                $right
            ) {
                $positionComparison =
                    $left['position']
                    <=>
                    $right['position'];

                if (
                    $positionComparison
                    !==
                    0
                ) {
                    return $positionComparison;
                }

                return
                    $left['group_index']
                    <=>
                    $right['group_index'];
            }
        );

        if ($reverse) {
            $eligible =
                array_reverse(
                    $eligible
                );
        }

        return array_slice(
            $eligible,
            0,
            $take
        );
    }

    private function specificGroupPosition(
        array $eligible,
        ?int $groupId,
        int $position
    ): array {
        return array_values(
            array_filter(
                $eligible,
                fn($candidate) =>
                $candidate['group_definition_id']
                    ===
                    $groupId
                    &&
                    $candidate['position']
                    ===
                    $position
            )
        );
    }

    private function specificGroupRange(
        array $eligible,
        ?int $groupId,
        int $from,
        int $to
    ): array {
        return array_values(
            array_filter(
                $eligible,
                fn($candidate) =>
                $candidate['group_definition_id']
                    ===
                    $groupId
                    &&
                    $candidate['position']
                    >=
                    $from
                    &&
                    $candidate['position']
                    <=
                    $to
            )
        );
    }
}
