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
                $settings,
                $roundRules
            );

        $obsoleteRules = [];
        $redundantRules = [];
        $invalidRules = [];

        foreach (
            $roundRules
            as
            $roundRule
        ) {
            $ruleErrors =
                $this->validator
                ->validateSeriesConfiguration(
                    (string)
                    (
                        $roundRule->series_format
                        ?:
                        'BEST_OF'
                    ),
                    $roundRule->best_of,
                    $roundRule->fixed_games,
                    'La regla de '
                        .
                        $roundRule->round_label
                );

            if ($ruleErrors !== []) {
                $invalidRules[] = [
                    'id' =>
                    (int)
                    $roundRule->id,

                    'round_size' =>
                    (int)
                    $roundRule->participants_in_round,

                    'label' =>
                    $roundRule->round_label,

                    'errors' =>
                    $ruleErrors,
                ];
            }

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

            if ($ruleErrors !== []) {
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
                        ' utiliza la misma configuración que la regla general.',
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

        $warnings = [];

        if (
            $settings->configuration_mode
            ===
            'ADVANCED'
        ) {
            $calculator =
                new SingleEliminationAdvancedCalculator();

            $calculation =
                $calculator->calculate(
                    $phaseTemplate,
                    $settings,
                    (int)
                    $previewParticipants,
                    $roundRules
                );

            $errors =
                array_merge(
                    $errors,
                    $calculation['errors']
                        ??
                        []
                );

            $warnings =
                array_merge(
                    $warnings,
                    $calculation['warnings']
                        ??
                        []
                );

            if (
                ($calculation['valid'] ?? false)
                &&
                ! ($calculation['complete'] ?? true)
            ) {
                $warnings[] =
                    'La definición es válida, pero necesita una resolución manual para completar todas sus rondas.';
            }
        }

        foreach (
            $obsoleteRules
            as
            $obsoleteRule
        ) {
            $errors[] =
                $obsoleteRule['message'];
        }

        foreach (
            $invalidRules
            as
            $invalidRule
        ) {
            $errors =
                array_merge(
                    $errors,
                    $invalidRule['errors']
                );
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
                array_unique(
                    $warnings
                )
            ),

            /*
             * Las reglas redundantes no bloquean ni representan
             * un riesgo de ejecución. Se exponen por separado para
             * que la interfaz no las presente como advertencias.
             */
            'recommendations' =>
            array_values(
                array_unique(
                    array_column(
                        $redundantRules,
                        'message'
                    )
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

            'invalid_rules' =>
            $invalidRules,

            'invalid_rule_ids' =>
            array_column(
                $invalidRules,
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
        if (
            $settings->configuration_mode
            ===
            'ADVANCED'
        ) {
            $ruleEntrants =
                (int) (
                    $roundRule->entrants_per_match
                    ??
                    $settings->entrants_per_match
                );

            $ruleQualifiers =
                (int) (
                    $roundRule->qualifiers_per_match
                    ??
                    $settings->qualifiers_per_match
                );

            $ruleProfile =
                (string) (
                    $roundRule->encounter_profile
                    ??
                    $settings->encounter_profile
                );

            if (
                $ruleEntrants
                !==
                (int)
                $settings->entrants_per_match
                ||
                $ruleQualifiers
                !==
                (int)
                $settings->qualifiers_per_match
                ||
                $ruleProfile
                !==
                (string)
                $settings->encounter_profile
            ) {
                return false;
            }
        }

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
