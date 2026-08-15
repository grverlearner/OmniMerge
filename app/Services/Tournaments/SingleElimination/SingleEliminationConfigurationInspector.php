<?php

namespace App\Services\Tournaments\SingleElimination;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use Illuminate\Support\Collection;

class SingleEliminationConfigurationInspector
{
    public function __construct(
        private readonly
        SingleEliminationRoundAvailabilityService $availability,

        private readonly
        SingleEliminationValidator $validator
    ) {}

    public function inspect(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationSetting $settings,
        Collection $roundRules,
        ?int $participants = null
    ): array {
        $possibleRoundSizes =
            $this->availability
            ->possibleRoundSizes(
                $phaseTemplate,
                $settings
            );

        $obsoleteRules = [];
        $redundantRules = [];

        foreach (
            $roundRules
            as
            $roundRule
        ) {
            if (
                ! in_array(
                    (int)
                    $roundRule->participants_in_round,
                    $possibleRoundSizes,
                    true
                )
            ) {
                $obsoleteRules[] = [
                    'id' =>
                    (int)
                    $roundRule->id,

                    'round_size' =>
                    (int)
                    $roundRule->participants_in_round,

                    'label' =>
                    $roundRule->round_label,

                    'message' =>
                    $roundRule->round_label
                        .
                        ' no puede existir con el contrato y objetivo actuales.',
                ];

                continue;
            }

            if (
                $this->ruleIsRedundant(
                    $settings,
                    $roundRule
                )
            ) {
                $redundantRules[] = [
                    'id' =>
                    (int)
                    $roundRule->id,

                    'round_size' =>
                    (int)
                    $roundRule->participants_in_round,

                    'label' =>
                    $roundRule->round_label,

                    'message' =>
                    'La regla de '
                        .
                        $roundRule->round_label
                        .
                        ' utiliza el mismo formato que la configuración general.',
                ];
            }
        }

        $previewParticipants =
            $participants
            ??
            $phaseTemplate->exact_participants
            ??
            $phaseTemplate->min_participants;

        $errors =
            $this->validator
            ->validate(
                $phaseTemplate,
                $settings,
                (int)
                $previewParticipants
            );

        foreach (
            $obsoleteRules
            as
            $obsoleteRule
        ) {
            $errors[] =
                $obsoleteRule['message'];
        }

        return [
            'valid' =>
            $errors === [],

            'errors' =>
            array_values(
                array_unique(
                    $errors
                )
            ),

            'warnings' =>
            array_values(
                array_column(
                    $redundantRules,
                    'message'
                )
            ),

            'possible_round_sizes' =>
            $possibleRoundSizes,

            'obsolete_rules' =>
            $obsoleteRules,

            'obsolete_rule_ids' =>
            array_column(
                $obsoleteRules,
                'id'
            ),

            'redundant_rules' =>
            $redundantRules,

            'redundant_rule_ids' =>
            array_column(
                $redundantRules,
                'id'
            ),
        ];
    }

    private function ruleIsRedundant(
        PhaseSingleEliminationSetting $settings,
        object $roundRule
    ): bool {
        $settingsFormat =
            $settings->series_format
            ?:
            'BEST_OF';

        $ruleFormat =
            $roundRule->series_format
            ?:
            'BEST_OF';

        if (
            $settingsFormat
            !==
            $ruleFormat
        ) {
            return false;
        }

        if (
            $ruleFormat
            ===
            'FIXED_GAMES'
        ) {
            return
                (int)
                $settings->fixed_games
                ===
                (int)
                $roundRule->fixed_games;
        }

        return
            (int)
            $settings->default_best_of
            ===
            (int)
            $roundRule->best_of;
    }
}
