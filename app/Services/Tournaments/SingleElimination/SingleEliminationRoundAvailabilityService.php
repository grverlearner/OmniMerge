<?php

namespace App\Services\Tournaments\SingleElimination;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;

class SingleEliminationRoundAvailabilityService
{
    public function possibleRoundSizes(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationSetting $settings
    ): array {
        $target =
            max(
                1,
                (int) $settings->target_survivors
            );

        $roundSizes = [];

        foreach (
            $this->acceptedParticipantCounts(
                $phaseTemplate
            )
            as
            $participants
        ) {
            $slots =
                $this->nextPowerOfTwo(
                    $participants
                );

            while ($slots > $target) {
                $roundSizes[$slots] =
                    $slots;

                $slots =
                    intdiv(
                        $slots,
                        2
                    );
            }
        }

        rsort(
            $roundSizes,
            SORT_NUMERIC
        );

        return array_values(
            $roundSizes
        );
    }

    public function acceptedParticipantCounts(
        PhaseTemplate $phaseTemplate
    ): array {
        if (
            $phaseTemplate->exact_participants
            !==
            null
        ) {
            $exact =
                (int)
                $phaseTemplate->exact_participants;

            return
                $this->participantCountIsAccepted(
                    $phaseTemplate,
                    $exact
                )
                ? [$exact]
                : [];
        }

        $minimum =
            max(
                2,
                (int)
                $phaseTemplate->min_participants
            );

        $maximum =
            min(
                512,
                (int) (
                    $phaseTemplate->max_participants
                    ??
                    512
                )
            );

        if ($minimum > $maximum) {
            return [];
        }

        $counts = [];

        for (
            $count = $minimum;
            $count <= $maximum;
            $count++
        ) {
            if (
                $this->participantCountIsAccepted(
                    $phaseTemplate,
                    $count
                )
            ) {
                $counts[] =
                    $count;
            }
        }

        return $counts;
    }

    public function participantCountIsAccepted(
        PhaseTemplate $phaseTemplate,
        int $participants
    ): bool {
        if (
            $participants < 2
            ||
            $participants > 512
        ) {
            return false;
        }

        if (
            $phaseTemplate->exact_participants
            !==
            null
            &&
            $participants
            !==
            (int)
            $phaseTemplate->exact_participants
        ) {
            return false;
        }

        if (
            $participants
            <
            (int)
            $phaseTemplate->min_participants
        ) {
            return false;
        }

        if (
            $phaseTemplate->max_participants
            !==
            null
            &&
            $participants
            >
            (int)
            $phaseTemplate->max_participants
        ) {
            return false;
        }

        if (
            $phaseTemplate->participant_multiple
            !==
            null
            &&
            $participants
            %
            (int)
            $phaseTemplate->participant_multiple
            !==
            0
        ) {
            return false;
        }

        if (
            ! $phaseTemplate->allow_byes
            &&
            ! $this->isPowerOfTwo(
                $participants
            )
        ) {
            return false;
        }

        return true;
    }

    public function nextPowerOfTwo(
        int $value
    ): int {
        if ($value <= 1) {
            return 1;
        }

        $power = 1;

        while ($power < $value) {
            $power *= 2;
        }

        return $power;
    }

    public function isPowerOfTwo(
        int $value
    ): bool {
        return
            $value > 0
            &&
            (
                $value
                &
                ($value - 1)
            )
            ===
            0;
    }
}
