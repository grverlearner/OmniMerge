<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreGroupStageAdvancementRuleRequest;
use App\Http\Requests\Tournaments\UpdateGroupStageAdvancementRuleRequest;
use App\Models\PhaseGroupStageAdvancementRule;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\GroupStage\GroupStageAdvancementRuleService;
use Illuminate\Http\RedirectResponse;

class GroupStageAdvancementRuleController extends Controller
{
    public function __construct(
        private readonly
        GroupStageAdvancementRuleService $service
    ) {}

    public function store(
        StoreGroupStageAdvancementRuleRequest $request,
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
                'Regla de clasificación agregada.'
            );
    }

    public function update(
        UpdateGroupStageAdvancementRuleRequest $request,
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageAdvancementRule $advancementRule
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
                'Regla actualizada.'
            );
    }

    public function destroy(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageAdvancementRule $advancementRule
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
                'Regla eliminada.'
            );
    }

    public function moveUp(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageAdvancementRule $advancementRule
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
        PhaseGroupStageAdvancementRule $advancementRule
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
        PhaseGroupStageAdvancementRule $rule
    ): void {
        abort_unless(
            $rule->phase_template_id
                ===
                $phaseTemplate->id,
            404
        );
    }
}
