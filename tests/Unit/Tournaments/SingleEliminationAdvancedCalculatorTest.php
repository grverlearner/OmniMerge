<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseSingleEliminationRoundRule;
use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationAdvancedCalculator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class SingleEliminationAdvancedCalculatorTest extends TestCase
{
    public function test_four_to_two_can_close_with_a_duel_override(): void
    {
        $rule =
            new PhaseSingleEliminationRoundRule();

        $rule->forceFill([
            'participants_in_round' =>
            2,

            'entrants_per_match' =>
            2,

            'qualifiers_per_match' =>
            1,

            'encounter_profile' =>
            'DUEL',

            'series_format' =>
            'BEST_OF',

            'best_of' =>
            3,

            'fixed_games' =>
            1,
        ]);

        $result =
            $this->calculator()
            ->calculate(
                $this->phase(
                    true
                ),

                $this->settings(
                    4,
                    2,
                    'MULTI_COMPETITOR',
                    'BYE'
                ),

                16,

                new Collection([
                    $rule,
                ])
            );

        $this->assertTrue(
            $result['valid']
        );

        $this->assertTrue(
            $result['complete']
        );

        $this->assertSame(
            1,
            $result['survivors_count']
        );

        $this->assertSame(
            4,
            $result['round_count']
        );

        $this->assertSame(
            8,
            $result['total_series']
        );

        $this->assertSame(
            2,
            $result['rounds'][3]['entrants_per_match']
        );

        $this->assertTrue(
            $result['rounds'][3]['has_override']
        );
    }

    public function test_balanced_policy_spreads_remainders_without_byes(): void
    {
        $result =
            $this->calculator()
            ->calculate(
                $this->phase(
                    false
                ),

                $this->settings(
                    3,
                    1,
                    'MULTI_COMPETITOR',
                    'BALANCED'
                ),

                10,

                new Collection()
            );

        $this->assertTrue(
            $result['valid']
        );

        $this->assertSame(
            [
                3,
                3,
                2,
                2,
            ],
            $result['rounds'][0]['distribution']
        );

        $this->assertSame(
            0,
            $result['initial_byes']
        );

        $this->assertSame(
            1,
            $result['survivors_count']
        );
    }

    public function test_reject_policy_explains_an_incompatible_remainder(): void
    {
        $result =
            $this->calculator()
            ->calculate(
                $this->phase(
                    false
                ),

                $this->settings(
                    4,
                    2,
                    'MULTI_COMPETITOR',
                    'REJECT'
                ),

                14,

                new Collection()
            );

        $this->assertFalse(
            $result['valid']
        );

        $this->assertStringContainsString(
            'sobrante',
            implode(
                ' ',
                $result['errors']
            )
        );
    }

    private function calculator(): SingleEliminationAdvancedCalculator
    {
        return
            new SingleEliminationAdvancedCalculator();
    }

    private function phase(
        bool $allowByes
    ): PhaseTemplate {
        $phase =
            new PhaseTemplate();

        $phase->forceFill([
            'phase_type' =>
            'SINGLE_ELIMINATION',

            'min_participants' =>
            2,

            'max_participants' =>
            512,

            'allow_byes' =>
            $allowByes,
        ]);

        return $phase;
    }

    private function settings(
        int $entrants,
        int $qualifiers,
        string $profile,
        string $remainderPolicy
    ): PhaseSingleEliminationSetting {
        $settings =
            new PhaseSingleEliminationSetting();

        $settings->forceFill([
            'configuration_mode' =>
            'ADVANCED',

            'completion_mode' =>
            'WINNER',

            'target_survivors' =>
            1,

            'entrants_per_match' =>
            $entrants,

            'qualifiers_per_match' =>
            $qualifiers,

            'encounter_profile' =>
            $profile,

            'remainder_policy' =>
            $remainderPolicy,

            'series_format' =>
            'BEST_OF',

            'default_best_of' =>
            1,

            'fixed_games' =>
            1,
        ]);

        return $settings;
    }
}
