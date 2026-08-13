<?php

namespace App\Services\Tournaments\Swiss;

use App\Models\PhaseSwissSetting;

class SwissRecordMapService
{
    public function build(
        PhaseSwissSetting $settings
    ): array {
        if (
            $settings->completion_mode
            !==
            'RECORD_THRESHOLDS'
        ) {
            return [];
        }

        $maxRounds =
            max(
                1,
                (int)
                $settings->max_rounds
            );

        $rounds = [
            0 => [
                [
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'status' => 'ACTIVE',
                    'label' => '0W · 0D · 0L',
                ],
            ],
        ];

        for (
            $round = 1;
            $round <= $maxRounds;
            $round++
        ) {
            $nextStates = [];

            foreach (
                $rounds[$round - 1]
                as
                $state
            ) {
                if (
                    $state['status']
                    !==
                    'ACTIVE'
                ) {
                    continue;
                }

                $transitions = [
                    [
                        'wins' => 1,
                        'draws' => 0,
                        'losses' => 0,
                    ],

                    [
                        'wins' => 0,
                        'draws' => 0,
                        'losses' => 1,
                    ],
                ];

                if (
                    $settings->allow_draws
                ) {
                    $transitions[] = [
                        'wins' => 0,
                        'draws' => 1,
                        'losses' => 0,
                    ];
                }

                foreach (
                    $transitions
                    as
                    $transition
                ) {
                    $wins =
                        $state['wins']
                        +
                        $transition['wins'];

                    $draws =
                        $state['draws']
                        +
                        $transition['draws'];

                    $losses =
                        $state['losses']
                        +
                        $transition['losses'];

                    $status =
                        $this->status(
                            $wins,
                            $losses,
                            $round,
                            $settings
                        );

                    $key =
                        $wins
                        .
                        ':'
                        .
                        $draws
                        .
                        ':'
                        .
                        $losses;

                    $nextStates[$key] = [
                        'wins' =>
                        $wins,

                        'draws' =>
                        $draws,

                        'losses' =>
                        $losses,

                        'status' =>
                        $status,

                        'label' =>
                        $wins
                            .
                            'W · '
                            .
                            $draws
                            .
                            'D · '
                            .
                            $losses
                            .
                            'L',
                    ];
                }
            }

            $values =
                array_values(
                    $nextStates
                );

            usort(
                $values,
                function (
                    array $left,
                    array $right
                ) {
                    if (
                        $left['wins']
                        !==
                        $right['wins']
                    ) {
                        return
                            $right['wins']
                            <=>
                            $left['wins'];
                    }

                    if (
                        $left['losses']
                        !==
                        $right['losses']
                    ) {
                        return
                            $left['losses']
                            <=>
                            $right['losses'];
                    }

                    return
                        $right['draws']
                        <=>
                        $left['draws'];
                }
            );

            $rounds[$round] =
                $values;
        }

        return $rounds;
    }

    private function status(
        int $wins,
        int $losses,
        int $round,
        PhaseSwissSetting $settings
    ): string {
        if (
            $wins
            >=
            $settings->qualification_wins
        ) {
            return 'QUALIFIED';
        }

        if (
            $losses
            >=
            $settings->elimination_losses
        ) {
            return 'ELIMINATED';
        }

        if (
            $round
            >=
            $settings->max_rounds
        ) {
            return 'FALLBACK';
        }

        return 'ACTIVE';
    }
}
