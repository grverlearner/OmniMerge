<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationRoundAvailabilityService;
use PHPUnit\Framework\TestCase;

class SingleEliminationRoundAvailabilityContractTest extends TestCase
{
    public function test_basic_reject_policy_excludes_irregular_participant_counts(): void
    {
        $service = new SingleEliminationRoundAvailabilityService();
        $phase = $this->phase(5, true);
        $settings = $this->settings('REJECT');

        $this->assertFalse(
            $service->participantCountIsAccepted($phase, 5, $settings)
        );
        $this->assertSame([], $service->possibleRoundSizes($phase, $settings));
    }

    public function test_basic_bye_policy_keeps_irregular_participant_counts_available(): void
    {
        $service = new SingleEliminationRoundAvailabilityService();
        $phase = $this->phase(5, true);
        $settings = $this->settings('BYE');

        $this->assertTrue(
            $service->participantCountIsAccepted($phase, 5, $settings)
        );
        $this->assertSame(
            [8, 4, 2],
            $service->possibleRoundSizes($phase, $settings)
        );
    }

    private function phase(
        int $exactParticipants,
        bool $allowByes
    ): PhaseTemplate {
        $phase = new PhaseTemplate();
        $phase->forceFill([
            'phase_type' => 'SINGLE_ELIMINATION',
            'min_participants' => 2,
            'max_participants' => $exactParticipants,
            'exact_participants' => $exactParticipants,
            'participant_multiple' => null,
            'allow_byes' => $allowByes,
        ]);

        return $phase;
    }

    private function settings(string $remainderPolicy): PhaseSingleEliminationSetting
    {
        $settings = new PhaseSingleEliminationSetting();
        $settings->forceFill([
            'configuration_mode' => 'BASIC',
            'target_survivors' => 1,
            'remainder_policy' => $remainderPolicy,
        ]);

        return $settings;
    }
}
