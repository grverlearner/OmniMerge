<?php

namespace App\Services\Tournaments\SingleElimination;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use Illuminate\Support\Collection;

class SingleEliminationRoundAvailabilityService
{
    public function possibleRoundSizes(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationSetting $settings,
        ?Collection $roundRules = null
    ): array {
        $target =
            max(
                1,
                (int)
                $settings->target_survivors
            );

        if (
            $settings->configuration_mode
            ===
            'ADVANCED'
        ) {
            return $this->advancedRoundSizes(
                $phaseTemplate,
                $settings,
                $roundRules
                    ??
                    collect()
            );
        }

        $roundSizes = [];

        foreach (
            $this->acceptedParticipantCounts(
                $phaseTemplate,
                $settings
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
        PhaseTemplate $phaseTemplate,
        ?PhaseSingleEliminationSetting $settings = null
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
                    $exact,
                    $settings
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
                    $count,
                    $settings
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
        int $participants,
        ?PhaseSingleEliminationSetting $settings = null
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

        /*
         * En modo avanzado una fase sin BYEs puede aceptar
         * cantidades no potencia de 2 si otra política de
         * sobrantes logra distribuirlas correctamente.
         */

        if (
            $settings?->configuration_mode
            !==
            'ADVANCED'
            &&
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

    private function advancedRoundSizes(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationSetting $settings,
        Collection $roundRules
    ): array {
        $target =
            max(
                1,
                (int)
                $settings->target_survivors
            );

        $rulesByRound =
            $roundRules->keyBy(
                'participants_in_round'
            );

        $roundSizes = [];

        foreach (
            $this->acceptedParticipantCounts(
                $phaseTemplate,
                $settings
            )
            as
            $participants
        ) {
            $current =
                $participants;

            for (
                $guard = 0;
                $guard < 64
                    &&
                    $current > $target;
                $guard++
            ) {
                /*
                 * Se registra antes de calcular la siguiente ronda.
                 * Así el usuario puede crear un override que resuelva
                 * una ronda actualmente incompatible.
                 */

                $roundSizes[$current] =
                    $current;

                $rule =
                    $rulesByRound->get(
                        $current
                    );

                $entrants =
                    (int) (
                        $rule?->entrants_per_match
                        ??
                        $settings->entrants_per_match
                    );

                $qualifiers =
                    (int) (
                        $rule?->qualifiers_per_match
                        ??
                        $settings->qualifiers_per_match
                    );

                if (
                    $entrants < 2
                    ||
                    $qualifiers < 1
                    ||
                    $qualifiers >= $entrants
                ) {
                    break;
                }

                $next =
                    $this->advancedNextCount(
                        $phaseTemplate,
                        (string)
                        $settings->remainder_policy,
                        $current,
                        $target,
                        $entrants,
                        $qualifiers
                    );

                if (
                    $next === null
                    ||
                    $next >= $current
                    ||
                    $next < $target
                ) {
                    break;
                }

                $current =
                    $next;
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

    private function advancedNextCount(
        PhaseTemplate $phaseTemplate,
        string $policy,
        int $participants,
        int $target,
        int $entrants,
        int $qualifiers
    ): ?int {
        $fullSeries =
            intdiv(
                $participants,
                $entrants
            );

        $remainder =
            $participants
            %
            $entrants;

        if ($remainder === 0) {
            return
                $fullSeries
                *
                $qualifiers;
        }

        if (
            $policy === 'BYE'
            &&
            $phaseTemplate->allow_byes
        ) {
            return (
                    $fullSeries
                    *
                    $qualifiers
                )
                +
                $remainder;
        }

        if (
            $policy === 'INCOMPLETE_MATCH'
            &&
            $remainder >= 2
            &&
            $qualifiers < $remainder
        ) {
            return (
                    $fullSeries + 1
                )
                *
                $qualifiers;
        }

        if ($policy === 'BALANCED') {
            $series =
                (int)
                ceil(
                    $participants
                        /
                        $entrants
                );

            $minimumSize =
                intdiv(
                    $participants,
                    $series
                );

            return
                $minimumSize >= 2
                &&
                $qualifiers < $minimumSize
                ? $series
                *
                $qualifiers
                : null;
        }

        if ($policy === 'PRELIMINARY') {
            for (
                $series = 1;
                $series <= $fullSeries;
                $series++
            ) {
                $next =
                    $participants
                    -
                    (
                        $series
                        *
                        (
                            $entrants
                            -
                            $qualifiers
                        )
                    );

                if (
                    $next >= $target
                    &&
                    $next < $participants
                    &&
                    $next % $entrants === 0
                    &&
                    (
                        $series
                        *
                        $entrants
                    ) <= $participants
                ) {
                    return $next;
                }
            }
        }

        /*
         * MANUAL y REJECT no producen automáticamente
         * la siguiente cantidad.
         */

        return null;
    }

    public function nextPowerOfTwo(
        int $value
    ): int {
        if ($value <= 1) {
            return 1;
        }

        $power =
            1;

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
