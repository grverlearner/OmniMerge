<?php

namespace Tests\Unit\Tournaments\CompetitionLab\Runtime;

use App\Services\Tournaments\CompetitionLab\Runtime\MatchSeriesRuntime;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class MatchSeriesRuntimeTest extends TestCase
{
    private MatchSeriesRuntime $runtime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runtime =
            new MatchSeriesRuntime();
    }

    public function test_bo1_completes_and_preserves_raw_game_score_for_engine(): void
    {
        $result =
            $this->runtime
            ->submitGame(
                $this->matchRuntime(
                    'BEST_OF',
                    1,
                    1
                ),
                'M1',
                10,
                8,
                true
            );

        $this->assertTrue(
            $result['completed']
        );

        $this->assertSame(
            10,
            $result['engine_score_a']
        );

        $this->assertSame(
            8,
            $result['engine_score_b']
        );

        $series =
            $result['series'];

        $this->assertSame(
            'COMPLETED',
            $series['status']
        );

        $this->assertSame(
            'A',
            $series['winner_id']
        );

        $this->assertSame(
            1,
            $series['games_played']
        );

        $this->assertSame(
            1,
            $series['wins_required']
        );

        $this->assertNull(
            $series['next_game_number']
        );
    }

    public function test_bo3_stays_running_after_one_win_and_closes_two_zero(): void
    {
        $first =
            $this->runtime
            ->submitGame(
                $this->matchRuntime(
                    'BEST_OF',
                    3,
                    1
                ),
                'M1',
                3,
                1,
                true
            );

        $this->assertFalse(
            $first['completed']
        );

        $this->assertSame(
            1,
            $first['series']['game_wins_a']
        );

        $this->assertSame(
            0,
            $first['series']['game_wins_b']
        );

        $this->assertSame(
            2,
            $first['series']['wins_required']
        );

        $this->assertSame(
            2,
            $first['series']['next_game_number']
        );

        $second =
            $this->runtime
            ->submitGame(
                $first['runtime'],
                'M1',
                7,
                4,
                true
            );

        $this->assertTrue(
            $second['completed']
        );

        $this->assertSame(
            2,
            $second['engine_score_a']
        );

        $this->assertSame(
            0,
            $second['engine_score_b']
        );

        $this->assertSame(
            2,
            $second['series']['games_played']
        );

        $this->assertSame(
            2,
            $second['series']['series_score_a']
        );
    }

    public function test_bo5_can_close_three_zero_without_playing_unnecessary_games(): void
    {
        $state =
            $this->matchRuntime(
                'BEST_OF',
                5,
                1
            );

        foreach (
            [
                [4, 1],
                [2, 0],
                [9, 8],
            ]
            as
            [$scoreA, $scoreB]
        ) {
            $result =
                $this->runtime
                ->submitGame(
                    $state,
                    'M1',
                    $scoreA,
                    $scoreB,
                    true
                );

            $state =
                $result['runtime'];
        }

        $this->assertTrue(
            $result['completed']
        );

        $this->assertSame(
            3,
            $result['series']['games_played']
        );

        $this->assertSame(
            3,
            $result['series']['game_wins_a']
        );

        $this->assertSame(
            3,
            $result['series']['wins_required']
        );
    }

    public function test_bo7_closes_when_one_side_reaches_four_wins(): void
    {
        $state =
            $this->matchRuntime(
                'BEST_OF',
                7,
                1
            );

        foreach (
            [
                [1, 0],
                [0, 1],
                [2, 0],
                [3, 1],
                [0, 2],
                [4, 2],
            ]
            as
            [$scoreA, $scoreB]
        ) {
            $result =
                $this->runtime
                ->submitGame(
                    $state,
                    'M1',
                    $scoreA,
                    $scoreB,
                    true
                );

            $state =
                $result['runtime'];
        }

        $this->assertTrue(
            $result['completed']
        );
        $this->assertSame(4, $result['series']['game_wins_a']);
        $this->assertSame(4, $result['series']['wins_required']);
    }

    public function test_bo9_closes_when_one_side_reaches_five_wins(): void
    {
        $state =
            $this->matchRuntime(
                'BEST_OF',
                9,
                1
            );

        foreach (
            [
                [1, 0],
                [0, 1],
                [2, 1],
                [0, 3],
                [4, 0],
                [5, 2],
                [0, 1],
                [6, 3],
            ]
            as
            [$scoreA, $scoreB]
        ) {
            $result =
                $this->runtime
                ->submitGame(
                    $state,
                    'M1',
                    $scoreA,
                    $scoreB,
                    true
                );

            $state =
                $result['runtime'];
        }

        $this->assertTrue(
            $result['completed']
        );
        $this->assertSame(5, $result['series']['game_wins_a']);
        $this->assertSame(5, $result['series']['wins_required']);
    }

    public function test_fixed_games_plays_all_nominal_games_even_when_one_side_leads_early(): void
    {
        $state =
            $this->matchRuntime(
                'FIXED_GAMES',
                1,
                3
            );

        $first =
            $this->runtime
            ->submitGame(
                $state,
                'M1',
                1,
                0,
                true
            );

        $second =
            $this->runtime
            ->submitGame(
                $first['runtime'],
                'M1',
                5,
                2,
                true
            );

        $this->assertFalse(
            $second['completed']
        );

        $this->assertSame(
            1,
            $second['series']['nominal_games_remaining']
        );

        $third =
            $this->runtime
            ->submitGame(
                $second['runtime'],
                'M1',
                0,
                3,
                true
            );

        $this->assertTrue(
            $third['completed']
        );

        $this->assertSame(
            3,
            $third['series']['games_played']
        );

        $this->assertSame(
            2,
            $third['engine_score_a']
        );

        $this->assertSame(
            1,
            $third['engine_score_b']
        );
    }

    public function test_even_fixed_games_tie_requires_and_then_resolves_sudden_death(): void
    {
        $state =
            $this->matchRuntime(
                'FIXED_GAMES',
                1,
                4
            );

        foreach (
            [
                [1, 0],
                [0, 1],
                [2, 1],
                [1, 2],
            ]
            as
            [$scoreA, $scoreB]
        ) {
            $result =
                $this->runtime
                ->submitGame(
                    $state,
                    'M1',
                    $scoreA,
                    $scoreB,
                    true
                );

            $state =
                $result['runtime'];
        }

        $this->assertFalse(
            $result['completed']
        );

        $this->assertTrue(
            $result['series']['tiebreak_required']
        );

        $this->assertSame(
            5,
            $result['series']['next_game_number']
        );

        $this->assertSame(
            0,
            $result['series']['tiebreak_games']
        );

        $tiebreak =
            $this->runtime
            ->submitGame(
                $result['runtime'],
                'M1',
                4,
                2,
                true
            );

        $this->assertTrue(
            $tiebreak['completed']
        );

        $this->assertFalse(
            $tiebreak['series']['tiebreak_required']
        );

        $this->assertSame(
            1,
            $tiebreak['series']['tiebreak_games']
        );

        $this->assertTrue(
            $tiebreak['series']['games'][4]['is_tiebreak']
        );

        $this->assertSame(
            5,
            $tiebreak['series']['games_played']
        );
    }

    public function test_single_elimination_rejects_a_tied_game(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'necesita un ganador en cada juego'
        );

        $this->runtime
            ->submitGame(
                $this->matchRuntime(
                    'BEST_OF',
                    3,
                    1
                ),
                'M1',
                2,
                2,
                true
            );
    }

    public function test_bo1_can_finish_as_draw_for_an_engine_that_allows_draws(): void
    {
        $result =
            $this->runtime
            ->submitGame(
                $this->matchRuntime(
                    'BEST_OF',
                    1,
                    1
                ),
                'M1',
                2,
                2,
                false
            );

        $this->assertTrue(
            $result['completed']
        );

        $this->assertNull(
            $result['series']['winner_id']
        );

        $this->assertSame(
            1,
            $result['series']['game_draws']
        );
    }

    public function test_negative_score_is_rejected(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'no pueden ser negativos'
        );

        $this->runtime
            ->submitGame(
                $this->matchRuntime(),
                'M1',
                -1,
                0,
                true
            );
    }

    public function test_unsupported_series_format_is_rejected_instead_of_falling_back(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'formato de serie no soportado'
        );

        $this->runtime
            ->submitGame(
                $this->matchRuntime(
                    'CUSTOM_SERIES',
                    1,
                    1
                ),
                'M1',
                1,
                0,
                true
            );
    }

    public function test_even_best_of_is_rejected_instead_of_being_normalized(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'BO1, BO3, BO5, BO7 o BO9'
        );

        $this->runtime
            ->submitGame(
                $this->matchRuntime(
                    'BEST_OF',
                    4,
                    1
                ),
                'M1',
                1,
                0,
                true
            );
    }

    public function test_zero_fixed_games_is_rejected_instead_of_being_normalized_to_one(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'entre 1 y 99 juegos'
        );

        $this->runtime
            ->submitGame(
                $this->matchRuntime(
                    'FIXED_GAMES',
                    1,
                    0
                ),
                'M1',
                1,
                0,
                true
            );
    }

    public function test_completed_series_rejects_an_extra_game(): void
    {
        $completed =
            $this->runtime
            ->submitGame(
                $this->matchRuntime(),
                'M1',
                1,
                0,
                true
            );

        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'serie ya fue completada'
        );

        $this->runtime
            ->submitGame(
                $completed['runtime'],
                'M1',
                1,
                0,
                true
            );
    }

    public function test_series_configuration_cannot_change_after_first_game(): void
    {
        $first =
            $this->runtime
            ->submitGame(
                $this->matchRuntime(
                    'BEST_OF',
                    3,
                    1
                ),
                'M1',
                1,
                0,
                true
            );

        $changed =
            $first['runtime'];

        $changed['rounds'][0]['matches'][0]['best_of'] =
            5;

        $this->expectException(
            ValidationException::class
        );
        $this->expectExceptionMessage(
            'Best of de la serie cambió'
        );

        $this->runtime
            ->submitGame(
                $changed,
                'M1',
                1,
                0,
                true
            );
    }

    public function test_participant_metrics_include_games_and_raw_score_differences(): void
    {
        $runtime =
            $this->matchRuntime(
                'BEST_OF',
                3,
                1
            );

        $first =
            $this->runtime
            ->submitGame(
                $runtime,
                'M1',
                5,
                3,
                true
            );

        $second =
            $this->runtime
            ->submitGame(
                $first['runtime'],
                'M1',
                2,
                4,
                true
            );

        $third =
            $this->runtime
            ->submitGame(
                $second['runtime'],
                'M1',
                7,
                1,
                true
            );

        $metrics =
            $this->runtime
            ->participantMetrics(
                $third['runtime'],
                'A'
            );

        $this->assertSame(
            [
                'games_played' => 3,
                'game_wins' => 2,
                'game_losses' => 1,
                'game_draws' => 0,
                'game_difference' => 1,
                'score_for' => 14,
                'score_against' => 8,
                'score_difference' => 6,
            ],
            $metrics
        );
    }

    private function matchRuntime(
        string $format = 'BEST_OF',
        int $bestOf = 1,
        int $fixedGames = 1
    ): array {
        return [
            'engine' =>
            'SINGLE_ELIMINATION',

            'status' =>
            'RUNNING',

            'rounds' => [
                [
                    'number' =>
                    1,

                    'status' =>
                    'RUNNING',

                    'matches' => [
                        [
                            'id' =>
                            'M1',

                            'participant_a_id' =>
                            'A',

                            'participant_b_id' =>
                            'B',

                            'series_format' =>
                            $format,

                            'best_of' =>
                            $bestOf,

                            'fixed_games' =>
                            $fixedGames,

                            'status' =>
                            'PENDING',
                        ],
                    ],
                ],
            ],
        ];
    }
}
