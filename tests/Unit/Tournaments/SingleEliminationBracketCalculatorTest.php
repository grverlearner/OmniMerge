<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationBracketCalculator;
use App\Services\Tournaments\SingleElimination\SingleEliminationValidator;
use App\Services\Tournaments\SingleElimination\SingleEliminationAdvancedCalculator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class SingleEliminationBracketCalculatorTest extends TestCase
{
    private function calculator(): SingleEliminationBracketCalculator
    {
        return new SingleEliminationBracketCalculator(
            new SingleEliminationValidator(),
            new SingleEliminationAdvancedCalculator()
        );
    }

    private function phase(
        bool $allowByes = true
    ): PhaseTemplate {
        return new PhaseTemplate([
            'phase_type' =>
            'SINGLE_ELIMINATION',

            'min_participants' =>
            2,

            'max_participants' =>
            512,

            'allow_byes' =>
            $allowByes,
        ]);
    }

    private function settings(
        int $target = 1
    ): PhaseSingleEliminationSetting {
        return new PhaseSingleEliminationSetting([
            'completion_mode' =>
            $target === 1
                ? 'WINNER'
                : 'SURVIVORS',

            'target_survivors' =>
            $target,

            'default_best_of' =>
            1,

            'seeding_mode' =>
            'INPUT_ORDER',

            'pairing_mode' =>
            'STANDARD_SEEDED',

            'bye_assignment' =>
            'TOP_SEEDS',

            'reseed_each_round' =>
            false,
        ]);
    }

    public function test_sixteen_participants_generate_four_rounds(): void
    {
        $result =
            $this->calculator()
            ->calculate(
                $this->phase(),
                $this->settings(),
                16,
                new Collection()
            );

        $this->assertTrue(
            $result['valid']
        );

        $this->assertSame(
            16,
            $result['bracket_size']
        );

        $this->assertSame(
            0,
            $result['initial_byes']
        );

        $this->assertSame(
            4,
            $result['round_count']
        );

        $this->assertSame(
            15,
            $result['total_series']
        );
    }

    public function test_fourteen_participants_generate_two_byes(): void
    {
        $result =
            $this->calculator()
            ->calculate(
                $this->phase(),
                $this->settings(),
                14,
                new Collection()
            );

        $this->assertTrue(
            $result['valid']
        );

        $this->assertSame(
            16,
            $result['bracket_size']
        );

        $this->assertSame(
            2,
            $result['initial_byes']
        );

        $this->assertSame(
            13,
            $result['total_series']
        );

        $this->assertSame(
            6,
            $result['rounds'][0]['series']
        );

        $this->assertSame(
            2,
            $result['rounds'][0]['byes']
        );
    }

    public function test_twenty_two_can_reduce_to_eight_survivors(): void
    {
        $result =
            $this->calculator()
            ->calculate(
                $this->phase(),
                $this->settings(8),
                22,
                new Collection()
            );

        $this->assertTrue(
            $result['valid']
        );

        $this->assertSame(
            32,
            $result['bracket_size']
        );

        $this->assertSame(
            10,
            $result['initial_byes']
        );

        $this->assertSame(
            2,
            $result['round_count']
        );

        $this->assertSame(
            14,
            $result['total_series']
        );

        $this->assertSame(
            8,
            $result['survivors_count']
        );
    }

    public function test_non_power_of_two_is_invalid_without_byes(): void
    {
        $result =
            $this->calculator()
            ->calculate(
                $this->phase(false),
                $this->settings(),
                14,
                new Collection()
            );

        $this->assertFalse(
            $result['valid']
        );

        $this->assertNotEmpty(
            $result['errors']
        );
    }
}
