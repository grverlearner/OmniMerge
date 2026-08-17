<?php

namespace App\Services\Tournaments\CompetitionLab\Runtime;

use Illuminate\Support\Collection;

class RuntimeOutcomeResolver
{
    public function resolve(
        Collection $phaseExits,
        array $runtime,
        array $participantIds
    ): array {
        $standings =
            collect(
                $runtime['standings']
                    ??
                    []
            )
            ->sortBy(
                'position'
            )
            ->values();

        $outcomes =
            [];

        $selected =
            [];

        /*
        |--------------------------------------------------------------------------
        | Outcomes producidos directamente por el Engine
        |--------------------------------------------------------------------------
        */

        $directOutcomes = array_merge(
            $runtime['outcomes'] ?? [],
            array_values($runtime['timed_outcomes'] ?? [])
        );

        foreach (
            $directOutcomes
            as
            $engineOutcome
        ) {
            $exitId =
                $engineOutcome['exit_id']
                ??
                null;

            if (
                $exitId === null
                ||
                ! $phaseExits->contains(
                    fn($exit) => (int) $exit->id === (int) $exitId
                )
            ) {
                continue;
            }

            $ids =
                array_values(
                    array_unique(
                        $engineOutcome['participant_ids']
                            ??
                            []
                    )
                );

            if (
                $exitId === null
                ||
                $ids === []
            ) {
                continue;
            }

            $outcomes[$exitId] ??= [
                'exit_id' =>
                (int)
                $exitId,

                'exit_name' =>
                $engineOutcome['exit_name']
                    ??
                    $phaseExits
                    ->firstWhere(
                        'id',
                        (int)
                        $exitId
                    )
                    ?->name
                    ??
                    'Salida',

                'selector_type' =>
                $phaseExits
                    ->firstWhere('id', (int) $exitId)
                    ?->selector_type
                    ?? 'ENGINE_RULES',

                'participant_ids' =>
                [],
            ];

            foreach (
                $ids
                as
                $participantId
            ) {
                if (
                    ! in_array(
                        $participantId,
                        $participantIds,
                        true
                    )
                ) {
                    continue;
                }

                if (
                    ! in_array(
                        $participantId,
                        $outcomes[$exitId]['participant_ids'],
                        true
                    )
                ) {
                    $outcomes[$exitId]['participant_ids'][] =
                        $participantId;
                }

                $selected[$participantId] =
                    true;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Phase Exits genéricos
        |--------------------------------------------------------------------------
        */

        foreach (
            $phaseExits
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
            as
            $exit
        ) {
            if (
                isset(
                    $outcomes[$exit->id]
                )
            ) {
                continue;
            }

            $available =
                array_values(
                    array_filter(
                        $participantIds,
                        fn($participantId) =>
                        ! isset(
                            $selected[$participantId]
                        )
                    )
                );

            $selection =
                $this->select(
                    $exit->selector_type,
                    $exit->selector_from,
                    $exit->selector_to,
                    $exit->selector_round_size,
                    $runtime,
                    $standings,
                    $available
                );

            $selection =
                array_values(
                    array_unique(
                        array_filter(
                            $selection,
                            fn($participantId) =>
                            in_array(
                                $participantId,
                                $available,
                                true
                            )
                        )
                    )
                );

            foreach (
                $selection
                as
                $participantId
            ) {
                $selected[$participantId] =
                    true;
            }

            $outcomes[$exit->id] = [
                'exit_id' =>
                (int)
                $exit->id,

                'exit_name' =>
                $exit->name,

                'selector_type' =>
                $exit->selector_type,

                'participant_ids' =>
                $selection,
            ];
        }

        $unassigned =
            array_values(
                array_filter(
                    $participantIds,
                    fn($participantId) =>
                    ! isset(
                        $selected[$participantId]
                    )
                )
            );

        return [
            'outcomes' =>
            array_values(
                $outcomes
            ),

            'selected_ids' =>
            array_values(
                array_keys(
                    $selected
                )
            ),

            'unassigned_ids' =>
            $unassigned,
        ];
    }

    private function select(
        string $selectorType,
        ?int $from,
        ?int $to,
        ?int $roundSize,
        array $runtime,
        Collection $standings,
        array $available
    ): array {
        $availableMap =
            array_fill_keys(
                $available,
                true
            );

        $ranked =
            $standings
            ->filter(
                fn($row) =>
                isset(
                    $availableMap[$row['participant_id']]
                )
            )
            ->values();

        return match ($selectorType) {
            'SURVIVORS' =>
            array_values(
                array_intersect(
                    $runtime['survivor_ids']
                        ??
                        [],
                    $available
                )
            ),

            'ELIMINATED' =>
            array_values(
                array_intersect(
                    $runtime['eliminated_ids']
                        ??
                        [],
                    $available
                )
            ),

            'ELIMINATED_IN_ROUND' =>
            $this->eliminatedInRoundIds(
                $runtime,
                $roundSize,
                $availableMap
            ),

            'TOP_N' =>
            $ranked
                ->take(
                    max(
                        0,
                        (int)
                        $from
                    )
                )
                ->pluck(
                    'participant_id'
                )
                ->all(),

            'BOTTOM_N' =>
            $ranked
                ->reverse()
                ->take(
                    max(
                        0,
                        (int)
                        $from
                    )
                )
                ->pluck(
                    'participant_id'
                )
                ->all(),

            'RANK_POSITION' =>
            $ranked
                ->where(
                    'position',
                    (int)
                    $from
                )
                ->pluck(
                    'participant_id'
                )
                ->all(),

            'RANK_RANGE' =>
            $ranked
                ->filter(
                    fn($row) =>
                    $row['position']
                        >=
                        (int)
                        $from
                        &&
                        $row['position']
                        <=
                        (int)
                        $to
                )
                ->pluck(
                    'participant_id'
                )
                ->all(),

            'MATCH_WINNERS' =>
            $this->matchResultIds(
                $runtime,
                'winner_id',
                $availableMap
            ),

            'MATCH_LOSERS' =>
            $this->matchLoserIds(
                $runtime,
                $availableMap
            ),

            'ALL',
            'REMAINING' =>
            $available,

            /*
             * ENGINE_RULES ya fue procesado utilizando runtime.outcomes.
             */
            'ENGINE_RULES' =>
            [],

            default =>
            [],
        };
    }

    private function eliminatedInRoundIds(
        array $runtime,
        ?int $roundSize,
        array $availableMap
    ): array {
        if ($roundSize === null || $roundSize <= 1) {
            return [];
        }

        $eliminated = [];

        foreach ($runtime['rounds'] ?? [] as $round) {
            if ((int) ($round['participants_in_round'] ?? 0) !== $roundSize) {
                continue;
            }

            foreach ($round['matches'] ?? [] as $match) {
                foreach (
                    array_values(array_unique([
                        ...($match['eliminated_ids'] ?? []),
                        ...(($match['loser_id'] ?? null) !== null
                            ? [$match['loser_id']]
                            : []),
                    ]))
                    as $participantId
                ) {
                    if (isset($availableMap[$participantId])) {
                        $eliminated[] = $participantId;
                    }
                }
            }
        }

        return array_values(array_unique($eliminated));
    }

    private function matchResultIds(
        array $runtime,
        string $field,
        array $availableMap
    ): array {
        return collect(
            $runtime['rounds']
                ??
                []
        )
            ->flatMap(
                fn($round) =>
                $round['matches']
                    ??
                    []
            )
            ->pluck(
                $field
            )
            ->filter(
                fn($participantId) =>
                isset(
                    $availableMap[$participantId]
                )
            )
            ->unique()
            ->values()
            ->all();
    }

    private function matchLoserIds(
        array $runtime,
        array $availableMap
    ): array {
        $losers =
            [];

        foreach (
            $runtime['rounds']
                ??
                []
            as
            $round
        ) {
            foreach (
                $round['matches']
                    ??
                    []
                as
                $match
            ) {
                if (
                    ($match['status'] ?? null)
                    !==
                    'COMPLETED'
                ) {
                    continue;
                }

                $winnerId =
                    $match['winner_id']
                    ??
                    null;

                foreach (
                    [
                        $match['participant_a_id']
                            ??
                            null,

                        $match['participant_b_id']
                            ??
                            null,
                    ]
                    as
                    $participantId
                ) {
                    if (
                        ! $participantId
                        ||
                        $participantId
                        ===
                        $winnerId
                        ||
                        ! isset(
                            $availableMap[$participantId]
                        )
                    ) {
                        continue;
                    }

                    $losers[] =
                        $participantId;
                }
            }
        }

        return array_values(
            array_unique(
                $losers
            )
        );
    }
}
