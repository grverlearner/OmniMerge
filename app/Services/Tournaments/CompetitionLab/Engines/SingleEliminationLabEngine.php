<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationValidator;
use App\Services\Tournaments\SingleElimination\SingleEliminationConfigurationInspector;
use Illuminate\Validation\ValidationException;

class SingleEliminationLabEngine
implements LabPhaseEngine
{
    public function __construct(
        private readonly
        SingleEliminationValidator $validator,

        private readonly
        SingleEliminationConfigurationInspector $inspector
    ) {}

    public function supports(
        string $phaseType
    ): bool {
        return
            $phaseType
            ===
            'SINGLE_ELIMINATION';
    }

    public function prepare(
        PhaseTemplate $phase,
        array $participantIds,
        array $participants
    ): array {
        $phase->loadMissing([
            'singleEliminationSetting',
            'singleEliminationRoundRules',
        ]);

        $settings =
            $phase->singleEliminationSetting;

        if (! $settings) {
            $this->fail(
                'La fase no tiene una configuración Single Elimination.'
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

        $diagnostic =
            $this->inspector
            ->inspect(
                $phase,
                $settings,
                $phase->singleEliminationRoundRules,
                count($participantIds)
            );

        $errors =
            array_values(
                array_unique([
                    ...$errors,
                    ...$diagnostic['errors'],
                ])
            );

        if ($errors !== []) {
            $this->fail(
                implode(
                    ' ',
                    $errors
                )
            );
        }

        $seedMap = [];

        foreach (
            $participantIds
            as
            $index =>
            $participantId
        ) {
            $seedMap[$participantId] =
                $index + 1;
        }

        if (
            $settings->seeding_mode
            ===
            'RANDOM'
            ||
            $settings->pairing_mode
            ===
            'RANDOM'
        ) {
            shuffle(
                $participantIds
            );
        }

        $roundSeriesRules =
            $phase
            ->singleEliminationRoundRules
            ->mapWithKeys(
                fn($rule) => [
                    (int)
                    $rule->participants_in_round
                    =>
                    [
                        'series_format' =>
                        $rule->series_format
                            ?:
                            'BEST_OF',

                        'best_of' =>
                        (int)
                        $rule->best_of,

                        'fixed_games' =>
                        (int)
                        $rule->fixed_games,
                    ],
                ]
            )
            ->all();

        $runtime = [
            'engine' =>
            'SINGLE_ELIMINATION',

            'status' =>
            'RUNNING',

            'target_survivors' =>
            max(
                1,
                (int)
                $settings->target_survivors
            ),

            'default_series_format' =>
            $settings->series_format
                ?:
                'BEST_OF',

            'default_best_of' =>
            (int)
            $settings->default_best_of,

            'default_fixed_games' =>
            (int)
            $settings->fixed_games,

            'round_series_rules' =>
            $roundSeriesRules,

            'reseed_each_round' =>
            (bool)
            $settings->reseed_each_round,

            'seed' =>
            $seedMap,

            'rounds' =>
            [],

            'standings' =>
            [],

            'survivor_ids' =>
            [],

            'eliminated_ids' =>
            [],

            'current_round' =>
            1,

            'matches_total' =>
            0,

            'matches_completed' =>
            0,
        ];

        $runtime['rounds'][] =
            $this->makeRound(
                $participantIds,
                1,
                $runtime
            );

        return $this->advanceAutomatic(
            $runtime
        );
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
            $scoreA
            ===
            $scoreB
        ) {
            $this->fail(
                'Single Elimination no permite empates.'
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

                if (
                    ! $match['participant_a_id']
                    ||
                    ! $match['participant_b_id']
                ) {
                    $this->fail(
                        'No se puede registrar un resultado en un BYE.'
                    );
                }

                $match['score_a'] =
                    $scoreA;

                $match['score_b'] =
                    $scoreB;

                $match['winner_id'] =
                    $scoreA > $scoreB
                    ? $match['participant_a_id']
                    : $match['participant_b_id'];

                $match['loser_id'] =
                    $scoreA > $scoreB
                    ? $match['participant_b_id']
                    : $match['participant_a_id'];

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

        return $this->advanceAutomatic(
            $runtime
        );
    }

    private function advanceAutomatic(
        array $runtime
    ): array {
        while (true) {
            $roundIndex =
                count(
                    $runtime['rounds']
                ) - 1;

            $round =
                &$runtime['rounds'][$roundIndex];

            foreach (
                $round['matches']
                as
                &$match
            ) {
                if (
                    $match['status']
                    !==
                    'PENDING'
                ) {
                    continue;
                }

                if (
                    $match['participant_a_id']
                    &&
                    ! $match['participant_b_id']
                ) {
                    $match['winner_id'] =
                        $match['participant_a_id'];

                    $match['status'] =
                        'BYE';
                } elseif (
                    ! $match['participant_a_id']
                    &&
                    $match['participant_b_id']
                ) {
                    $match['winner_id'] =
                        $match['participant_b_id'];

                    $match['status'] =
                        'BYE';
                }
            }

            unset($match);

            $hasPending =
                collect(
                    $round['matches']
                )
                ->contains(
                    fn($match) =>
                    $match['status']
                        ===
                        'PENDING'
                );

            if ($hasPending) {
                return $runtime;
            }

            $round['status'] =
                'COMPLETED';

            $winners =
                collect(
                    $round['matches']
                )
                ->pluck(
                    'winner_id'
                )
                ->filter()
                ->values()
                ->all();

            $losers =
                collect(
                    $round['matches']
                )
                ->pluck(
                    'loser_id'
                )
                ->filter()
                ->values()
                ->all();

            $runtime['eliminated_ids'] =
                array_values(
                    array_unique([
                        ...$runtime['eliminated_ids'],
                        ...$losers,
                    ])
                );

            if (
                count($winners)
                <=
                $runtime['target_survivors']
            ) {
                $runtime['status'] =
                    'COMPLETED';

                $runtime['survivor_ids'] =
                    $winners;

                $runtime['standings'] =
                    $this->standings(
                        $runtime,
                        $winners
                    );

                return $runtime;
            }

            if (
                $runtime['reseed_each_round']
            ) {
                usort(
                    $winners,
                    fn($left, $right) =>
                    $runtime['seed'][$left]
                        <=>
                        $runtime['seed'][$right]
                );
            }

            $roundNumber =
                count(
                    $runtime['rounds']
                ) + 1;

            $runtime['rounds'][] =
                $this->makeRound(
                    $winners,
                    $roundNumber,
                    $runtime
                );

            $runtime['current_round'] =
                $roundNumber;
        }
    }

    private function makeRound(
        array $participantIds,
        int $roundNumber,
        array &$runtime
    ): array {
        $matches = [];
        $roundSeries =
            $runtime['round_series_rules'][count($participantIds)]
            ??
            [
                'series_format' =>
                $runtime['default_series_format'],

                'best_of' =>
                $runtime['default_best_of'],

                'fixed_games' =>
                $runtime['default_fixed_games'],
            ];

        $left =
            0;

        $right =
            count(
                $participantIds
            ) - 1;

        while (
            $left
            <=
            $right
        ) {
            $participantA =
                $participantIds[$left++]
                ??
                null;

            $participantB =
                $left <= $right
                ? (
                    $participantIds[$right--]
                    ??
                    null
                )
                : null;

            $matches[] = [
                'id' =>
                'SE-R'
                    .
                    $roundNumber
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

                'loser_id' =>
                null,

                'series_format' =>
                $roundSeries['series_format'],

                'best_of' =>
                (int)
                $roundSeries['best_of'],

                'fixed_games' =>
                (int)
                $roundSeries['fixed_games'],

                'series_label' =>
                $roundSeries['series_format']
                    ===
                    'FIXED_GAMES'
                    ? $roundSeries['fixed_games']
                    .
                    ' '
                    .
                    (
                        $roundSeries['fixed_games'] === 1
                        ? 'enfrentamiento fijo'
                        : 'enfrentamientos fijos'
                    )
                    : 'BO'
                    .
                    $roundSeries['best_of'],

                'status' =>
                'PENDING',
            ];
        }

        $runtime['matches_total'] +=
            count(
                $matches
            );

        return [
            'number' =>
            $roundNumber,

            'label' =>
            count($participantIds)
                ===
                2
                ? 'Final'
                : 'Ronda '
                .
                $roundNumber,

            'status' =>
            'RUNNING',

            'matches' =>
            $matches,
        ];
    }

    private function standings(
        array $runtime,
        array $survivors
    ): array {
        $standings =
            [];

        $position =
            1;

        foreach (
            $survivors
            as
            $participantId
        ) {
            $standings[] = [
                'position' =>
                $position++,

                'participant_id' =>
                $participantId,

                'status' =>
                'SURVIVOR',
            ];
        }

        foreach (
            array_reverse(
                $runtime['eliminated_ids']
            )
            as
            $participantId
        ) {
            $standings[] = [
                'position' =>
                $position++,

                'participant_id' =>
                $participantId,

                'status' =>
                'ELIMINATED',
            ];
        }

        return $standings;
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
