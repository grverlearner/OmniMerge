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

        return $phaseTemplate
            ->singleEliminationRoundRules()
            ->create(
                $data
            );
    }

    public function update(
        PhaseSingleEliminationRoundRule $roundRule,
        array $data
    ): PhaseSingleEliminationRoundRule {

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

        return $roundRule->fresh();
    }

    public function delete(
        PhaseSingleEliminationRoundRule $roundRule
    ): void {
        $roundRule->delete();
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

        return $data;
    }
}
