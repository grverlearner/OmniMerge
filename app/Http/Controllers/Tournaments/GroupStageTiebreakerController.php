<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreGroupStageTiebreakerRequest;
use App\Http\Requests\Tournaments\UpdateGroupStageTiebreakerRequest;
use App\Models\PhaseGroupStageTiebreaker;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\GroupStage\GroupStageTiebreakerService;
use Illuminate\Http\RedirectResponse;

class GroupStageTiebreakerController extends Controller
{
    public function __construct(
        private readonly
        GroupStageTiebreakerService $service
    ) {}

    public function store(
        StoreGroupStageTiebreakerRequest $request,
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
                'Criterio entre grupos agregado.'
            );
    }

    public function update(
        UpdateGroupStageTiebreakerRequest $request,
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageTiebreaker $tiebreaker
    ): RedirectResponse {
        $this->ensureBelongs(
            $phaseTemplate,
            $tiebreaker
        );

        $this->service
            ->update(
                $tiebreaker,
                $request->validated()
            );

        return back()
            ->with(
                'success',
                'Criterio actualizado.'
            );
    }

    public function destroy(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageTiebreaker $tiebreaker
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureBelongs(
            $phaseTemplate,
            $tiebreaker
        );

        $this->service
            ->delete(
                $tiebreaker
            );

        return back()
            ->with(
                'success',
                'Criterio eliminado.'
            );
    }

    public function moveUp(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageTiebreaker $tiebreaker
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureBelongs(
            $phaseTemplate,
            $tiebreaker
        );

        $this->service
            ->move(
                $phaseTemplate,
                $tiebreaker,
                'UP'
            );

        return back();
    }

    public function moveDown(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageTiebreaker $tiebreaker
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureBelongs(
            $phaseTemplate,
            $tiebreaker
        );

        $this->service
            ->move(
                $phaseTemplate,
                $tiebreaker,
                'DOWN'
            );

        return back();
    }

    private function ensureBelongs(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageTiebreaker $tiebreaker
    ): void {
        abort_unless(
            $tiebreaker->phase_template_id
                ===
                $phaseTemplate->id,
            404
        );
    }
}
