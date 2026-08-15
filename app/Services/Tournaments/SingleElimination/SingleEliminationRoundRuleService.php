<?php

namespace App\Services\Tournaments\SingleElimination;

use App\Models\PhaseSingleEliminationRoundRule;
use App\Models\PhaseTemplate;

class SingleEliminationRoundRuleService
{
    public function create(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseSingleEliminationRoundRule {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        $data =
            $this->normalizeSeries(
                $data
            );

        $data['sort_order'] =
            $data['participants_in_round'];

        $roundRule =
            $phaseTemplate
            ->singleEliminationRoundRules()
            ->create(
                $data
            );

        $this->markStructureStale(
            $phaseTemplate
        );

        return $roundRule;
    }

    public function update(
        PhaseSingleEliminationRoundRule $roundRule,
        array $data
    ): PhaseSingleEliminationRoundRule {
        $roundRule->loadMissing(
            'phaseTemplate'
        );

        $data =
            $this->normalizeSeries(
                $data,
                $roundRule
            );

        $data['sort_order'] =
            $data['participants_in_round'];

        $roundRule->update(
            $data
        );

        $this->markStructureStale(
            $roundRule->phaseTemplate
        );

        return $roundRule->fresh();
    }

    public function delete(
        PhaseSingleEliminationRoundRule $roundRule
    ): void {
        $roundRule->loadMissing(
            'phaseTemplate'
        );

        $phaseTemplate =
            $roundRule->phaseTemplate;

        $roundRule->delete();

        if ($phaseTemplate) {
            $this->markStructureStale(
                $phaseTemplate
            );
        }
    }

    private function markStructureStale(
        PhaseTemplate $phaseTemplate
    ): void {
        $settings =
            $phaseTemplate
            ->singleEliminationSetting;

        if (
            ! $settings
            ||
            (int)
            $settings->structure_version
            < 1
        ) {
            return;
        }

        $settings->update([
            'structure_status' =>
            'STALE',

            'structure_validated_at' =>
            null,
        ]);
    }

    private function ensureCorrectType(
        PhaseTemplate $phaseTemplate
    ): void {
        abort_unless(
            $phaseTemplate->phase_type
                ===
                'SINGLE_ELIMINATION',
            404
        );
    }

    private function normalizeSeries(
        array $data,
        ?PhaseSingleEliminationRoundRule $current = null
    ): array {
        $data['series_format'] =
            $data['series_format']
            ??
            $current?->series_format
            ??
            'BEST_OF';

        $data['best_of'] =
            $data['best_of']
            ??
            $current?->best_of
            ??
            1;

        $data['fixed_games'] =
            $data['fixed_games']
            ??
            $current?->fixed_games
            ??
            1;

        foreach (
            [
                'entrants_per_match',
                'qualifiers_per_match',
                'encounter_profile',
            ]
            as
            $advancedField
        ) {
            if (
                ! array_key_exists(
                    $advancedField,
                    $data
                )
            ) {
                $data[$advancedField] =
                    $current?->{$advancedField};
            }
        }

        return $data;
    }
}
