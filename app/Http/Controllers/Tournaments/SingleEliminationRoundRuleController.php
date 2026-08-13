<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreSingleEliminationRoundRuleRequest;
use App\Http\Requests\Tournaments\UpdateSingleEliminationRoundRuleRequest;
use App\Models\PhaseSingleEliminationRoundRule;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationRoundRuleService;
use Illuminate\Http\RedirectResponse;

class SingleEliminationRoundRuleController extends Controller
{
    public function __construct(
        private readonly
        SingleEliminationRoundRuleService $service
    ) {}

    public function store(
        StoreSingleEliminationRoundRuleRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->service
            ->create(
                $phaseTemplate,
                $request->validated()
            );

        return redirect()
            ->route(
                'tournaments.single-elimination.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Regla especial de ronda creada correctamente.'
            );
    }

    public function update(
        UpdateSingleEliminationRoundRuleRequest $request,
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationRoundRule $roundRule
    ): RedirectResponse {
        $this->ensureBelongsToPhase(
            $phaseTemplate,
            $roundRule
        );

        $this->service
            ->update(
                $roundRule,
                $request->validated()
            );

        return redirect()
            ->route(
                'tournaments.single-elimination.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Regla de ronda actualizada correctamente.'
            );
    }

    public function destroy(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationRoundRule $roundRule
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureBelongsToPhase(
            $phaseTemplate,
            $roundRule
        );

        $this->service
            ->delete(
                $roundRule
            );

        return redirect()
            ->route(
                'tournaments.single-elimination.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Override de ronda eliminado correctamente.'
            );
    }

    private function ensureBelongsToPhase(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationRoundRule $roundRule
    ): void {
        abort_unless(
            $roundRule->phase_template_id
                ===
                $phaseTemplate->id,
            404
        );
    }
}
