<?php

namespace App\Services\Tournaments\SingleElimination;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SingleEliminationPreviewService
{
    public function __construct(
        private readonly
        SingleEliminationBracketCalculator $calculator,

        private readonly
        SingleEliminationConfigurationInspector $inspector
    ) {}

    public function preview(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationSetting $storedSettings,
        Collection $roundRules,
        array $payload
    ): array {
        $temporarySettings =
            $storedSettings->replicate();

        $temporarySettings->fill(
            Arr::only(
                $payload,
                [
                    'configuration_mode',
                    'input_mode',
                    'routing_mode',
                    'entrants_per_match',
                    'qualifiers_per_match',
                    'encounter_profile',
                    'remainder_policy',
                    
                    'completion_mode',
                    'target_survivors',
                    'seeding_mode',
                    'pairing_mode',
                    'bye_assignment',
                    'reseed_each_round',
                    'series_format',
                    'default_best_of',
                    'fixed_games',
                ]
            )
        );

        if (
            $temporarySettings->completion_mode
            ===
            'WINNER'
        ) {
            $temporarySettings->target_survivors =
                1;
        }

        $participants =
            (int)
            $payload['participants'];

        return [
            'settings' =>
            $temporarySettings,

            'participants' =>
            $participants,

            'preview' =>
            $this->calculator
                ->calculate(
                    $phaseTemplate,
                    $temporarySettings,
                    $participants,
                    $roundRules
                ),

            'diagnostic' =>
            $this->inspector
                ->inspect(
                    $phaseTemplate,
                    $temporarySettings,
                    $roundRules,
                    $participants
                ),
        ];
    }
}
