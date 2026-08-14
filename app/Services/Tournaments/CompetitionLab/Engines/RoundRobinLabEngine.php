<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\RoundRobin\RoundRobinValidator;
use Illuminate\Validation\ValidationException;

class RoundRobinLabEngine
implements LabPhaseEngine
{
    public function __construct(
        private readonly
        RoundRobinValidator $validator
    ) {}

    public function supports(
        string $phaseType
    ): bool {
        return
            $phaseType
            ===
            'ROUND_ROBIN';
    }

    public function prepare(
        PhaseTemplate $phase,
        array $participantIds,
        array $participants
    ): array {
        $phase->loadMissing([
            'roundRobinSetting',
            'roundRobinTiebreakers',
        ]);

        $settings =
            $phase->roundRobinSetting;

        if (! $settings) {
            $this->fail(
                'La fase no tiene una configuración Round Robin.'
            );
        }

        $participantIds =
            array_values(
                array_unique(
                    $participantIds
                )
            );

        foreach (
            $participantIds
            as
            $participantId
        ) {
            if (
                ! isset(
                    $participants[$participantId]
                )
            ) {
                $this->fail(
                    "El participante {$participantId} no pertenece al Lab."
                );
            }
        }

        $errors =
            $this->validator
            ->validate(
                $phase,
                $settings,
                count($participantIds)
            );

        if ($errors !== []) {
            $this->fail(
                implode(
                    ' ',
                    $errors
                )
            );
        }

        if (
            $settings->initial_order_mode
            ===
            'RANDOM'
        ) {
            shuffle(
                $participantIds
            );
        }

        $standings =
            [];

        foreach (
            $participantIds
            as
            $index =>
            $participantId
        ) {
            $standings[$participantId] = [
                'participant_id' =>
                $participantId,

                'seed' =>
                $index + 1,

                'played' =>
                0,

                'wins' =>
                0,

                'draws' =>
                0,

                'losses' =>
                0,

                'points' =>
                0,

                'score_for' =>
                0,

                'score_against' =>
                0,

                'score_difference' =>
                0,

                'position' =>
                $index + 1,
            ];
        }

        $rounds =
            $this->schedule(
                $participantIds,
                (int)
                $settings->cycles,
                (int)
                $settings->default_best_of
            );

        return [
            'engine' =>
            'ROUND_ROBIN',

            'status' =>
            'RUNNING',

            'allow_draws' =>
            (bool)
            $settings->allow_draws,

            'points' => [
                'win' =>
                (float)
                $settings->win_points,

                'draw' =>
                (float)
                $settings->draw_points,

                'loss' =>
                (float)
                $settings->loss_points,
            ],

            'tiebreakers' =>
            $phase
                ->roundRobinTiebreakers
                ->map(
                    fn($tiebreaker) => [
                        'criterion' =>
                        $tiebreaker->criterion,

                        'direction' =>
                        $tiebreaker
                            ->effective_direction,
                    ]
                )
                ->all(),

            'rounds' =>
            $rounds,

            'standings' =>
            array_values(
                $standings
            ),

            'matches_total' =>
            collect($rounds)
                ->sum(
                    fn($round) =>
                    count(
                        $round['matches']
                    )
                ),

            'matches_completed' =>
            0,

            'current_round' =>
            1,

            'survivor_ids' =>
            [],

            'eliminated_ids' =>
            [],
        ];
    }

    public function submit(
        array $runtime,
        string $matchId,
        int $scoreA,
        int $scoreB
    ): array {
        if (
            ($runtime['status'] ?? null)
            !==
            'RUNNING'
        ) {
            $this->fail(
                'La fase ya está completada.'
            );
        }

        if (
            $scoreA < 0
            ||
            $scoreB < 0
        ) {
            $this->fail(
                'Los scores no pueden ser negativos.'
            );
        }

        if (
            $scoreA === $scoreB
            &&
            ! $runtime['allow_draws']
        ) {
            $this->fail(
                'Esta fase Round Robin no permite empates.'
            );
        }

        $found =
            false;

        foreach (
            $runtime['rounds']
            as
            &$round
        ) {
            foreach (
                $round['matches']
                as
                &$match
            ) {
                if (
                    $match['id']
                    !==
                    $matchId
                ) {
                    continue;
                }

                if (
                    $match['status']
                    ===
                    'COMPLETED'
                ) {
                    $this->fail(
                        'El encuentro ya tiene resultado.'
                    );
                }

                $match['score_a'] =
                    $scoreA;

                $match['score_b'] =
                    $scoreB;

                $match['winner_id'] =
                    $scoreA === $scoreB
                    ? null
                    : (
                        $scoreA > $scoreB
                        ? $match['participant_a_id']
                        : $match['participant_b_id']
                    );

                $match['status'] =
                    'COMPLETED';

                $runtime['matches_completed']++;

                $found =
                    true;

                break 2;
            }
        }

        unset(
            $match,
            $round
        );

        if (! $found) {
            $this->fail(
                'El encuentro no existe en esta fase.'
            );
        }

        $runtime =
            $this->rank(
                $runtime
            );

        foreach (
            $runtime['rounds']
            as
            &$round
        ) {
            $hasPending =
                collect(
                    $round['matches']
                )
                ->contains(
                    fn($match) =>
                    $match['status']
                        !==
                        'COMPLETED'
                );

            if (! $hasPending) {
                $round['status'] =
                    'COMPLETED';
            }
        }

        unset($round);

        $pendingRound =
            collect(
                $runtime['rounds']
            )
            ->first(
                fn($round) =>
                $round['status']
                    !==
                    'COMPLETED'
            );

        $runtime['current_round'] =
            $pendingRound['number']
            ??
            count(
                $runtime['rounds']
            );

        if (
            $runtime['matches_completed']
            ===
            $runtime['matches_total']
        ) {
            $runtime['status'] =
                'COMPLETED';

            $runtime['survivor_ids'] =
                collect(
                    $runtime['standings']
                )
                ->pluck(
                    'participant_id'
                )
                ->all();
        }

        return $runtime;
    }

    private function schedule(
        array $participantIds,
        int $cycles,
        int $bestOf
    ): array {
        if (
            count($participantIds)
            %
            2
            !==
            0
        ) {
            $participantIds[] =
                null;
        }

        $rounds =
            [];

        $globalRound =
            1;

        $slotCount =
            count(
                $participantIds
            );

        for (
            $cycle = 1;
            $cycle <= max(1, $cycles);
            $cycle++
        ) {
            $rotation =
                $participantIds;

            for (
                $turn = 1;
                $turn < $slotCount;
                $turn++
            ) {
                $matches =
                    [];

                for (
                    $index = 0;
                    $index < $slotCount / 2;
                    $index++
                ) {
                    $participantA =
                        $rotation[$index];

                    $participantB =
                        $rotation[$slotCount
                            -
                            1
                            -
                            $index];

                    if (
                        ! $participantA
                        ||
                        ! $participantB
                    ) {
                        continue;
                    }

                    if (
                        $cycle
                        %
                        2
                        ===
                        0
                    ) {
                        [
                            $participantA,
                            $participantB,
                        ] = [
                            $participantB,
                            $participantA,
                        ];
                    }

                    $matches[] = [
                        'id' =>
                        'RR-R'
                            .
                            $globalRound
                            .
                            '-M'
                            .
                            (
                                count($matches)
                                +
                                1
                            ),

                        'number' =>
                        count($matches)
                            +
                            1,

                        'participant_a_id' =>
                        $participantA,

                        'participant_b_id' =>
                        $participantB,

                        'score_a' =>
                        null,

                        'score_b' =>
                        null,

                        'winner_id' =>
                        null,

                        'best_of' =>
                        $bestOf,

                        'status' =>
                        'PENDING',
                    ];
                }

                $rounds[] = [
                    'number' =>
                    $globalRound,

                    'cycle' =>
                    $cycle,

                    'label' =>
                    'Jornada '
                        .
                        $globalRound,

                    'status' =>
                    'PENDING',

                    'matches' =>
                    $matches,
                ];

                $fixed =
                    array_shift(
                        $rotation
                    );

                $last =
                    array_pop(
                        $rotation
                    );

                array_unshift(
                    $rotation,
                    $fixed,
                    $last
                );

                $globalRound++;
            }
        }

        return $rounds;
    }

    private function rank(
        array $runtime
    ): array {
        $rows =
            [];

        foreach (
            $runtime['standings']
            as
            $row
        ) {
            foreach (
                [
                    'played',
                    'wins',
                    'draws',
                    'losses',
                    'points',
                    'score_for',
                    'score_against',
                    'score_difference',
                ]
                as
                $key
            ) {
                $row[$key] =
                    0;
            }

            $rows[$row['participant_id']] = $row;
        }

        foreach (
            $runtime['rounds']
            as
            $round
        ) {
            foreach (
                $round['matches']
                as
                $match
            ) {
                if (
                    $match['status']
                    !==
                    'COMPLETED'
                ) {
                    continue;
                }

                $participantA =
                    &$rows[$match['participant_a_id']];

                $participantB =
                    &$rows[$match['participant_b_id']];

                $participantA['played']++;
                $participantB['played']++;

                $participantA['score_for'] +=
                    $match['score_a'];

                $participantA['score_against'] +=
                    $match['score_b'];

                $participantB['score_for'] +=
                    $match['score_b'];

                $participantB['score_against'] +=
                    $match['score_a'];

                if (
                    $match['score_a']
                    ===
                    $match['score_b']
                ) {
                    $participantA['draws']++;
                    $participantB['draws']++;

                    $participantA['points'] +=
                        $runtime['points']['draw'];

                    $participantB['points'] +=
                        $runtime['points']['draw'];
                } elseif (
                    $match['score_a']
                    >
                    $match['score_b']
                ) {
                    $participantA['wins']++;
                    $participantB['losses']++;

                    $participantA['points'] +=
                        $runtime['points']['win'];

                    $participantB['points'] +=
                        $runtime['points']['loss'];
                } else {
                    $participantB['wins']++;
                    $participantA['losses']++;

                    $participantB['points'] +=
                        $runtime['points']['win'];

                    $participantA['points'] +=
                        $runtime['points']['loss'];
                }

                unset(
                    $participantA,
                    $participantB
                );
            }
        }

        foreach (
            $rows
            as
            &$row
        ) {
            $row['score_difference'] =
                $row['score_for']
                -
                $row['score_against'];
        }

        unset($row);

        $criteria = [
            [
                'criterion' =>
                'POINTS',

                'direction' =>
                'DESC',
            ],

            ...$runtime['tiebreakers'],

            [
                'criterion' =>
                'SEED',

                'direction' =>
                'ASC',
            ],
        ];

        $fieldMap = [
            'POINTS' =>
            'points',

            'WINS' =>
            'wins',

            'FEWEST_LOSSES' =>
            'losses',

            'SCORE_DIFFERENCE' =>
            'score_difference',

            'SCORE_FOR' =>
            'score_for',

            'GAME_DIFFERENCE' =>
            'score_difference',

            'GAME_WINS' =>
            'score_for',

            'SEED' =>
            'seed',
        ];

        uasort(
            $rows,
            function (
                array $left,
                array $right
            ) use (
                $criteria,
                $fieldMap
            ): int {
                foreach (
                    $criteria
                    as
                    $criterion
                ) {
                    $field =
                        $fieldMap[$criterion['criterion']]
                        ??
                        null;

                    if (
                        ! $field
                        ||
                        $left[$field]
                        ==
                        $right[$field]
                    ) {
                        continue;
                    }

                    return
                        $criterion['direction']
                        ===
                        'ASC'
                        ? $left[$field]
                        <=>
                        $right[$field]
                        : $right[$field]
                        <=>
                        $left[$field];
                }

                return strcmp(
                    $left['participant_id'],
                    $right['participant_id']
                );
            }
        );

        $position =
            1;

        foreach (
            $rows
            as
            &$row
        ) {
            $row['position'] =
                $position++;
        }

        unset($row);

        $runtime['standings'] =
            array_values(
                $rows
            );

        return $runtime;
    }

    private function fail(
        string $message
    ): never {
        throw ValidationException::withMessages([
            'engine' => [
                $message,
            ],
        ]);
    }
}
