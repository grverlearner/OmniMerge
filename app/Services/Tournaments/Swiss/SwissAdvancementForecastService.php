<?php

namespace App\Services\Tournaments\Swiss;

use Illuminate\Support\Collection;

class SwissAdvancementForecastService
{
    public function forecast(
        int $participants,
        Collection $rules
    ): array {
        $virtualRanks =
            range(
                1,
                $participants
            );

        $selected = [];

        $unknownSelectionOccurred =
            false;

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
            $expectedCount =
                null;

            $variable =
                false;

            /*
            |--------------------------------------------------------------------------
            | Dynamic rules
            |--------------------------------------------------------------------------
            */

            if (
                $rule->isDynamic()
            ) {
                $variable =
                    true;

                $unknownSelectionOccurred =
                    true;
            } elseif (
                $unknownSelectionOccurred
            ) {
                /*
                 * Una regla dinámica anterior puede haber retirado
                 * participantes, así que ya no podemos garantizar
                 * una cantidad exacta.
                 */
                $variable =
                    true;
            } else {
                $available =
                    array_values(
                        array_filter(
                            $virtualRanks,
                            fn($rank) =>
                            ! isset(
                                $selected[$rank]
                            )
                        )
                    );

                $selection =
                    $this->selectFinalRanks(
                        $available,
                        $rule
                    );

                foreach (
                    $selection
                    as
                    $rank
                ) {
                    $selected[$rank] =
                        true;
                }

                $expectedCount =
                    count(
                        $selection
                    );
            }

            $exitId =
                $rule->phase_exit_id;

            $exitName =
                $rule->phaseExit?->name
                ??
                'Sin puerta vinculada';

            $ruleResults[] = [
                'rule_id' =>
                $rule->id,

                'label' =>
                $rule->rule_type_label,

                'summary' =>
                $rule->rule_summary,

                'timing' =>
                $rule->timing_label,

                'exit_id' =>
                $exitId,

                'exit_name' =>
                $exitName,

                'expected_count' =>
                $expectedCount,

                'variable' =>
                $variable,
            ];

            $outputKey =
                $exitId !== null
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

                    'minimum_count' =>
                    0,

                    'expected_count' =>
                    0,

                    'variable' =>
                    false,
                ];
            }

            if (
                $expectedCount !== null
            ) {
                $outputs[$outputKey]['minimum_count'] +=
                    $expectedCount;

                $outputs[$outputKey]['expected_count'] +=
                    $expectedCount;
            }

            if ($variable) {
                $outputs[$outputKey]['variable'] =
                    true;

                $outputs[$outputKey]['expected_count'] =
                    null;
            }
        }

        return [
            'rules' =>
            $ruleResults,

            'outputs' =>
            array_values(
                $outputs
            ),

            'fully_deterministic' =>
            ! $unknownSelectionOccurred,

            'deterministically_selected' =>
            count(
                $selected
            ),

            'participants' =>
            $participants,
        ];
    }

    private function selectFinalRanks(
        array $available,
        object $rule
    ): array {
        return match ($rule->rule_type) {
            'FINAL_TOP_N' =>
            array_slice(
                $available,
                0,
                (int)
                $rule->take
            ),

            'FINAL_BOTTOM_N' =>
            array_slice(
                array_reverse(
                    $available
                ),
                0,
                (int)
                $rule->take
            ),

            'FINAL_RANK_POSITION' =>
            array_values(
                array_filter(
                    $available,
                    fn($rank) =>
                    $rank
                        ===
                        (int)
                        $rule->rank_from
                )
            ),

            'FINAL_RANK_RANGE' =>
            array_values(
                array_filter(
                    $available,
                    fn($rank) =>
                    $rank
                        >=
                        (int)
                        $rule->rank_from
                        &&
                        $rank
                        <=
                        (int)
                        $rule->rank_to
                )
            ),

            'REMAINING' =>
            $available,

            default =>
            [],
        };
    }
}
