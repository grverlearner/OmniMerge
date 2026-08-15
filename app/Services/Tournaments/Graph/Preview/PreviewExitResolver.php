<?php

namespace App\Services\Tournaments\Graph\Preview;

use App\Models\PhaseExit;
use App\Models\PhaseSingleEliminationSetting;
use Illuminate\Support\Collection;

class PreviewExitResolver
{
    public function resolve(
        array $participants,
        Collection $exits,
        string $strategy,
        int $seed,
        ?PhaseSingleEliminationSetting $singleEliminationSettings = null
    ): array {
        $ordered =
            $this->orderParticipants(
                $participants,
                $strategy,
                $seed
            );

        $exits =
            $exits
            ->where(
                'status',
                'ACTIVE'
            )
            ->sortBy(
                fn($exit) =>
                sprintf(
                    '%010d-%010d-%010d',
                    $exit->priority,
                    $exit->sort_order,
                    $exit->id
                )
            )
            ->values();

        $remaining =
            $ordered;

        $assignments = [];
        $warnings = [];

        foreach (
            $exits
            as
            $exit
        ) {
            $selected =
                $this->select(
                    $ordered,
                    $remaining,
                    $exit,
                    $singleEliminationSettings
                );

            $selectedIds =
                array_column(
                    $selected,
                    'preview_id'
                );

            $remaining =
                array_values(
                    array_filter(
                        $remaining,
                        fn($participant) =>
                        ! in_array(
                            $participant['preview_id'],
                            $selectedIds,
                            true
                        )
                    )
                );

            $provisional =
                $this->isProvisional(
                    $exit
                );

            if ($provisional) {
                $warnings[] = [
                    'code' =>
                    'PROVISIONAL_EXIT_RESOLUTION',

                    'message' =>
                    'La salida “'
                        .
                        $exit->name
                        .
                        '” utiliza una resolución provisional porque depende de resultados competitivos.',
                ];
            }

            $assignments[$exit->id] = [
                'exit_id' =>
                (int) $exit->id,

                'exit_code' =>
                $exit->code,

                'exit_name' =>
                $exit->name,

                'selector_type' =>
                $exit->selector_type,

                'selector_label' =>
                $exit->selector_label,

                'provisional' =>
                $provisional,

                'participants' =>
                $selected,

                'participant_ids' =>
                $selectedIds,

                'count' =>
                count($selected),
            ];
        }

        return [
            'assignments' =>
            $assignments,

            'remaining' =>
            $remaining,

            'remaining_ids' =>
            array_column(
                $remaining,
                'preview_id'
            ),

            'warnings' =>
            $warnings,
        ];
    }

    private function select(
        array $ordered,
        array $remaining,
        PhaseExit $exit,
        ?PhaseSingleEliminationSetting $singleEliminationSettings = null
    ): array {
        return match ($exit->selector_type) {
            'TOP_N' =>
            array_slice(
                $remaining,
                0,
                max(
                    0,
                    (int) $exit->selector_from
                )
            ),

            'BOTTOM_N' =>
            $this->bottomN(
                $remaining,
                (int) $exit->selector_from
            ),

            'RANK_POSITION' =>
            $this->rankPosition(
                $remaining,
                (int) $exit->selector_from
            ),

            'RANK_RANGE' =>
            $this->rankRange(
                $remaining,
                $exit->selector_from,
                $exit->selector_to
            ),

            'ALL' =>
            array_values(
                $remaining
            ),

            'REMAINING',
            'ELIMINATED',
            'MATCH_LOSERS' =>
            array_values(
                $remaining
            ),

            'SURVIVORS',
            'MATCH_WINNERS' =>
            array_slice(
                $remaining,
                0,
                $this->winnerQuantity(
                    count($remaining),

                    $exit->selector_type
                        ===
                        'SURVIVORS'
                        ? $singleEliminationSettings
                        : null
                )
            ),

            'ELIMINATED_IN_ROUND' =>
            array_slice(
                $remaining,
                0,
                $this->eliminatedInRoundQuantity(
                    count($remaining),
                    $exit->selector_round_size
                )
            ),

            'ENGINE_RULES' =>
            $this->engineRuleSelection(
                $remaining,
                $exit,
                $singleEliminationSettings
            ),

            default =>
            [],
        };
    }

    private function bottomN(
        array $participants,
        int $quantity
    ): array {
        if ($quantity <= 0) {
            return [];
        }

        return array_slice(
            $participants,
            -$quantity
        );
    }

    private function rankPosition(
        array $participants,
        int $position
    ): array {
        if ($position <= 0) {
            return [];
        }

        $participant =
            $participants[$position - 1]
            ??
            null;

        return $participant
            ? [$participant]
            : [];
    }

    private function rankRange(
        array $participants,
        ?int $from,
        ?int $to
    ): array {
        if (
            $from === null
            ||
            $to === null
            ||
            $from <= 0
            ||
            $to < $from
        ) {
            return [];
        }

        return array_slice(
            $participants,
            $from - 1,
            ($to - $from) + 1
        );
    }

    private function winnerQuantity(
        int $participants,
        ?PhaseSingleEliminationSetting $singleEliminationSettings = null
    ): int {
        if ($singleEliminationSettings) {
            return min(
                $participants,

                max(
                    1,
                    (int)
                    $singleEliminationSettings
                        ->target_survivors
                )
            );
        }

        if ($participants <= 1) {
            return $participants;
        }

        return (int) ceil(
            $participants
                /
                2
        );
    }

    private function eliminatedInRoundQuantity(
        int $participants,
        ?int $roundSize
    ): int {
        if (
            $roundSize === null
            ||
            $roundSize <= 1
        ) {
            return 0;
        }

        return min(
            $participants,
            intdiv(
                $roundSize,
                2
            )
        );
    }

    private function engineRuleSelection(
        array $participants,
        PhaseExit $exit,
        ?PhaseSingleEliminationSetting $singleEliminationSettings = null
    ): array {
        if (
            $exit->selector_from
            !==
            null
        ) {
            return array_slice(
                $participants,
                0,
                max(
                    0,
                    (int) $exit->selector_from
                )
            );
        }

        return array_slice(
            $participants,
            0,
            $this->winnerQuantity(
                count($participants),
                $singleEliminationSettings
            )
        );
    }

    private function orderParticipants(
        array $participants,
        string $strategy,
        int $seed
    ): array {
        $participants =
            array_values(
                $participants
            );

        if (
            $strategy
            !==
            'SEEDED_RANDOM'
        ) {
            return $participants;
        }

        usort(
            $participants,
            fn($left, $right) =>
            strcmp(
                hash(
                    'sha256',
                    $seed
                        .
                        ':'
                        .
                        $left['preview_id']
                ),
                hash(
                    'sha256',
                    $seed
                        .
                        ':'
                        .
                        $right['preview_id']
                )
            )
        );

        return $participants;
    }

    private function isProvisional(
        PhaseExit $exit
    ): bool {
        return in_array(
            $exit->selector_type,
            [
                'MATCH_WINNERS',
                'MATCH_LOSERS',
                'SURVIVORS',
                'ELIMINATED',
                'ELIMINATED_IN_ROUND',
                'ENGINE_RULES',
            ],
            true
        );
    }
}
