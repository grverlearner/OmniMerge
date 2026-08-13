<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreSwissAdvancementRuleRequest;
use App\Http\Requests\Tournaments\UpdateSwissAdvancementRuleRequest;
use App\Models\PhaseSwissAdvancementRule;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\Swiss\SwissAdvancementRuleService;
use Illuminate\Http\RedirectResponse;

class SwissAdvancementRuleController extends Controller
{
    public function __construct(
        private readonly
        SwissAdvancementRuleService $service
    ) {}

    public function store(
        StoreSwissAdvancementRuleRequest $request,
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
                'Regla de salida Swiss agregada.'
            );
    }

    public function update(
        UpdateSwissAdvancementRuleRequest $request,
        PhaseTemplate $phaseTemplate,
        PhaseSwissAdvancementRule $advancementRule
    ): RedirectResponse {
        $this->ensureBelongs(
            $phaseTemplate,
            $advancementRule
        );

        $this->service
            ->update(
                $advancementRule,
                $request->validated()
            );

        return back()
            ->with(
                'success',
                'Regla de salida Swiss actualizada.'
            );
    }

    public function destroy(
        PhaseTemplate $phaseTemplate,
        PhaseSwissAdvancementRule $advancementRule
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureBelongs(
            $phaseTemplate,
            $advancementRule
        );

        $this->service
            ->delete(
                $advancementRule
            );

        return back()
            ->with(
                'success',
                'Regla de salida eliminada.'
            );
    }

    public function moveUp(
        PhaseTemplate $phaseTemplate,
        PhaseSwissAdvancementRule $advancementRule
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureBelongs(
            $phaseTemplate,
            $advancementRule
        );

        $this->service
            ->move(
                $phaseTemplate,
                $advancementRule,
                'UP'
            );

        return back();
    }

    public function moveDown(
        PhaseTemplate $phaseTemplate,
        PhaseSwissAdvancementRule $advancementRule
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureBelongs(
            $phaseTemplate,
            $advancementRule
        );

        $this->service
            ->move(
                $phaseTemplate,
                $advancementRule,
                'DOWN'
            );

        return back();
    }

    private function ensureBelongs(
        PhaseTemplate $phaseTemplate,
        PhaseSwissAdvancementRule $rule
    ): void {
        abort_unless(
            $rule->phase_template_id
                ===
                $phaseTemplate->id,
            404
        );
    }
}
