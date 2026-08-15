<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationRoundAvailabilityService;
use PHPUnit\Framework\TestCase;

class SingleEliminationRoundAvailabilityServiceTest extends TestCase
{
    private SingleEliminationRoundAvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service =
            new SingleEliminationRoundAvailabilityService();
    }

    public function test_exact_eight_to_winner_has_three_round_sizes(): void
    {
        $phase =
            $this->phase(
                8,
                false
            );

        $settings =
            $this->settings(
                1
            );

        $this->assertSame(
            [
                8,
                4,
                2,
            ],
            $this->service
                ->possibleRoundSizes(
                    $phase,
                    $settings
                )
        );
    }

    public function test_exact_eight_to_two_survivors_excludes_final(): void
    {
        $phase =
            $this->phase(
                8,
                false
            );

        $settings =
            $this->settings(
                2
            );

        $this->assertSame(
            [
                8,
                4,
            ],
            $this->service
                ->possibleRoundSizes(
                    $phase,
                    $settings
                )
        );
    }

    public function test_fourteen_with_byes_uses_bracket_of_sixteen(): void
    {
        $phase =
            $this->phase(
                14,
                true
            );

        $settings =
            $this->settings(
                1
            );

        $this->assertSame(
            [
                16,
                8,
                4,
                2,
            ],
            $this->service
                ->possibleRoundSizes(
                    $phase,
                    $settings
                )
        );
    }

    public function test_fourteen_without_byes_has_no_possible_rounds(): void
    {
        $phase =
            $this->phase(
                14,
                false
            );

        $settings =
            $this->settings(
                1
            );

        $this->assertSame(
            [],
            $this->service
                ->possibleRoundSizes(
                    $phase,
                    $settings
                )
        );
    }

    private function phase(
        int $exactParticipants,
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
            $exactParticipants,

            'exact_participants' =>
            $exactParticipants,

            'participant_multiple' =>
            null,

            'allow_byes' =>
            $allowByes,
        ]);

        return $phase;
    }

    private function settings(
        int $target
    ): PhaseSingleEliminationSetting {
        $settings =
            new PhaseSingleEliminationSetting();

        $settings->forceFill([
            'completion_mode' =>
            $target === 1
                ? 'WINNER'
                : 'SURVIVORS',

            'target_survivors' =>
            $target,
        ]);

        return $settings;
    }
}
