<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseRoundRobinSetting;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\RoundRobin\RoundRobinScheduleCalculator;
use App\Services\Tournaments\RoundRobin\RoundRobinValidator;
use PHPUnit\Framework\TestCase;

class RoundRobinScheduleCalculatorTest extends TestCase
{
    private function calculator(): RoundRobinScheduleCalculator
    {
        return new RoundRobinScheduleCalculator(
            new RoundRobinValidator()
        );
    }

    private function phase(): PhaseTemplate
    {
        return new PhaseTemplate([
            'phase_type' =>
            'ROUND_ROBIN',

            'min_participants' =>
            2,

            'max_participants' =>
            512,
        ]);
    }

    private function settings(
        int $cycles = 1
    ): PhaseRoundRobinSetting {
        return new PhaseRoundRobinSetting([
            'cycles' =>
            $cycles,

            'initial_order_mode' =>
            'INPUT_ORDER',

            'schedule_mode' =>
            'BALANCED',

            'allow_draws' =>
            true,

            'win_points' =>
            3,

            'draw_points' =>
            1,

            'loss_points' =>
            0,

            'default_best_of' =>
            1,

            'cutoff_tie_policy' =>
            'USE_TIEBREAKERS',
        ]);
    }

    public function test_four_participants_generate_three_rounds(): void
    {
        $result =
            $this->calculator()
            ->calculate(
                $this->phase(),
                $this->settings(),
                4
            );

        $this->assertTrue(
            $result['valid']
        );

        $this->assertSame(
            3,
            $result['rounds_per_cycle']
        );

        $this->assertSame(
            3,
            $result['total_rounds']
        );

        $this->assertSame(
            6,
            $result['total_series']
        );

        $this->assertSame(
            2,
            $result['series_per_round']
        );

        $this->assertSame(
            0,
            $result['total_rest_assignments']
        );
    }

    public function test_five_participants_generate_rest_each_round(): void
    {
        $result =
            $this->calculator()
            ->calculate(
                $this->phase(),
                $this->settings(),
                5
            );

        $this->assertTrue(
            $result['valid']
        );

        $this->assertSame(
            5,
            $result['rounds_per_cycle']
        );

        $this->assertSame(
            10,
            $result['total_series']
        );

        $this->assertSame(
            2,
            $result['series_per_round']
        );

        $this->assertSame(
            1,
            $result['rests_per_round']
        );

        $this->assertSame(
            5,
            $result['total_rest_assignments']
        );

        foreach (
            $result['rounds']
            as
            $round
        ) {
            $this->assertNotNull(
                $round['rest_seed']
            );
        }
    }

    public function test_double_round_robin_doubles_schedule(): void
    {
        $result =
            $this->calculator()
            ->calculate(
                $this->phase(),
                $this->settings(2),
                4
            );

        $this->assertTrue(
            $result['valid']
        );

        $this->assertSame(
            6,
            $result['total_rounds']
        );

        $this->assertSame(
            12,
            $result['total_series']
        );
    }

    public function test_every_pair_appears_once_in_single_cycle(): void
    {
        $result =
            $this->calculator()
            ->calculate(
                $this->phase(),
                $this->settings(),
                4
            );

        $pairs = [];

        foreach (
            $result['rounds']
            as
            $round
        ) {
            foreach (
                $round['pairings']
                as
                $pairing
            ) {
                $pair = [
                    $pairing['seed_a'],
                    $pairing['seed_b'],
                ];

                sort(
                    $pair
                );

                $pairs[] =
                    implode(
                        '-',
                        $pair
                    );
            }
        }

        $this->assertCount(
            6,
            $pairs
        );

        $this->assertCount(
            6,
            array_unique(
                $pairs
            )
        );
    }

    public function test_exact_participant_contract_is_respected(): void
    {
        $phase =
            new PhaseTemplate([
                'phase_type' =>
                'ROUND_ROBIN',

                'min_participants' =>
                8,

                'max_participants' =>
                8,

                'exact_participants' =>
                8,
            ]);

        $result =
            $this->calculator()
            ->calculate(
                $phase,
                $this->settings(),
                7
            );

        $this->assertFalse(
            $result['valid']
        );

        $this->assertNotEmpty(
            $result['errors']
        );
    }
}
