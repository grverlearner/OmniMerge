<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreSwissRoundRuleRequest;
use App\Http\Requests\Tournaments\UpdateSwissRoundRuleRequest;
use App\Models\PhaseSwissRoundRule;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\Swiss\SwissRoundRuleService;
use Illuminate\Http\RedirectResponse;

class SwissRoundRuleController extends Controller
{
    public function __construct(
        private readonly
        SwissRoundRuleService $service
    ) {}

    public function store(
        StoreSwissRoundRuleRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->service
            ->create(
                $phaseTemplate,
                $request->validated()
            );

        return back()
            ->with(
                'success',
                'Regla de encuentro Swiss agregada.'
            );
    }

    public function update(
        UpdateSwissRoundRuleRequest $request,
        PhaseTemplate $phaseTemplate,
        PhaseSwissRoundRule $roundRule
    ): RedirectResponse {
        $this->ensureBelongs(
            $phaseTemplate,
            $roundRule
        );

        $this->service
            ->update(
                $roundRule,
                $request->validated()
            );

        return back()
            ->with(
                'success',
                'Regla de encuentro actualizada.'
            );
    }

    public function destroy(
        PhaseTemplate $phaseTemplate,
        PhaseSwissRoundRule $roundRule
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureBelongs(
            $phaseTemplate,
            $roundRule
        );

        $this->service
            ->delete(
                $roundRule
            );

        return back()
            ->with(
                'success',
                'Regla de encuentro eliminada.'
            );
    }

    private function ensureBelongs(
        PhaseTemplate $phaseTemplate,
        PhaseSwissRoundRule $roundRule
    ): void {
        abort_unless(
            $roundRule->phase_template_id
                ===
                $phaseTemplate->id,
            404
        );
    }
}
