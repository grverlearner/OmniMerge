<?php

namespace Tests\Unit\Tournaments;

use App\Services\Tournaments\CompetitionLab\Runtime\CutoffPolicyResolver;
use App\Services\Tournaments\CompetitionLab\Runtime\MatchSeriesRuntime;
use PHPUnit\Framework\TestCase;

class PriorityTwoFunctionalityTest extends TestCase
{
    public function test_best_of_three_requires_two_game_wins(): void
    {
        $series = new MatchSeriesRuntime();
        $runtime = $this->runtimeWithMatch('BEST_OF', 3, 1);

        $first = $series->submitGame($runtime, 'M1', 1, 0, true);
        $this->assertFalse($first['completed']);
        $this->assertSame('RUNNING', $first['runtime']['series']['M1']['status']);

        $second = $series->submitGame($first['runtime'], 'M1', 2, 1, true);
        $this->assertTrue($second['completed']);
        $this->assertSame(2, $second['engine_score_a']);
        $this->assertSame(0, $second['engine_score_b']);
        $this->assertSame('A', $second['runtime']['series']['M1']['winner_id']);
    }

    public function test_bo1_draw_completes_for_non_elimination_engines(): void
    {
        $series = new MatchSeriesRuntime();
        $runtime = $this->runtimeWithMatch('BEST_OF', 1, 1);

        $result = $series->submitGame($runtime, 'M1', 1, 1, false);

        $this->assertTrue($result['completed']);
        $this->assertSame(1, $result['engine_score_a']);
        $this->assertSame(1, $result['engine_score_b']);
    }

    public function test_fixed_games_plays_every_configured_game(): void
    {
        $series = new MatchSeriesRuntime();
        $runtime = $this->runtimeWithMatch('FIXED_GAMES', 1, 3);

        $one = $series->submitGame($runtime, 'M1', 3, 0, false);
        $two = $series->submitGame($one['runtime'], 'M1', 2, 0, false);
        $this->assertFalse($two['completed']);

        $three = $series->submitGame($two['runtime'], 'M1', 1, 0, false);
        $this->assertTrue($three['completed']);
        $this->assertCount(3, $three['runtime']['series']['M1']['games']);
    }

    public function test_include_all_tied_can_expand_a_cutoff(): void
    {
        $resolver = new CutoffPolicyResolver();
        $rows = [
            ['participant_id' => 'A', 'points' => 10],
            ['participant_id' => 'B', 'points' => 8],
            ['participant_id' => 'C', 'points' => 8],
            ['participant_id' => 'D', 'points' => 8],
        ];

        $result = $resolver->resolve(
            $rows,
            2,
            'INCLUDE_ALL_TIED',
            fn(array $left, array $right): bool => $left['points'] === $right['points'],
            'TEST:CUTOFF'
        );

        $this->assertSame(['A', 'B', 'C', 'D'], array_column($result['selected'], 'participant_id'));
        $this->assertNull($result['decision']);
    }

    public function test_manual_cutoff_returns_a_runtime_decision(): void
    {
        $resolver = new CutoffPolicyResolver();
        $rows = [
            ['participant_id' => 'A', 'points' => 10],
            ['participant_id' => 'B', 'points' => 8],
            ['participant_id' => 'C', 'points' => 8],
        ];

        $result = $resolver->resolve(
            $rows,
            2,
            'MANUAL_RESOLUTION',
            fn(array $left, array $right): bool => $left['points'] === $right['points'],
            'TEST:MANUAL'
        );

        $this->assertSame(['A'], array_column($result['selected'], 'participant_id'));
        $this->assertSame('CUTOFF_SELECTION', $result['decision']['type']);
        $this->assertSame(1, $result['decision']['required_selection_count']);
        $this->assertSame(['B', 'C'], $result['decision']['eligible_participant_ids']);
    }

    private function runtimeWithMatch(
        string $format,
        int $bestOf,
        int $fixedGames
    ): array {
        return [
            'rounds' => [[
                'number' => 1,
                'matches' => [[
                    'id' => 'M1',
                    'participant_a_id' => 'A',
                    'participant_b_id' => 'B',
                    'series_format' => $format,
                    'best_of' => $bestOf,
                    'fixed_games' => $fixedGames,
                    'status' => 'PENDING',
                ]],
            ]],
        ];
    }
}
