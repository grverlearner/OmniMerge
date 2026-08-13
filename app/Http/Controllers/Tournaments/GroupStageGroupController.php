<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreGroupStageGroupRequest;
use App\Http\Requests\Tournaments\UpdateGroupStageGroupRequest;
use App\Models\PhaseGroupStageGroup;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\GroupStage\GroupStageGroupService;
use Illuminate\Http\RedirectResponse;

class GroupStageGroupController extends Controller
{
    public function __construct(
        private readonly
        GroupStageGroupService $service
    ) {}

    public function store(
        StoreGroupStageGroupRequest $request,
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
                'Grupo agregado correctamente.'
            );
    }

    public function update(
        UpdateGroupStageGroupRequest $request,
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageGroup $group
    ): RedirectResponse {
        $this->ensureBelongs(
            $phaseTemplate,
            $group
        );

        $this->service
            ->update(
                $group,
                $request->validated()
            );

        return back()
            ->with(
                'success',
                'Grupo actualizado.'
            );
    }

    public function destroy(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageGroup $group
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureBelongs(
            $phaseTemplate,
            $group
        );

        $this->service
            ->delete(
                $phaseTemplate,
                $group
            );

        return back()
            ->with(
                'success',
                'Grupo eliminado.'
            );
    }

    private function ensureBelongs(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageGroup $group
    ): void {
        abort_unless(
            $group->phase_template_id
                ===
                $phaseTemplate->id,
            404
        );
    }
}
