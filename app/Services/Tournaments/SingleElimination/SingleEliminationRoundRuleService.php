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
}
