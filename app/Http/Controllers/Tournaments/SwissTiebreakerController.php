<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreSwissTiebreakerRequest;
use App\Http\Requests\Tournaments\UpdateSwissTiebreakerRequest;
use App\Models\PhaseSwissTiebreaker;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\Swiss\SwissTiebreakerService;
use Illuminate\Http\RedirectResponse;

class SwissTiebreakerController extends Controller
{
    public function __construct(
        private readonly
        SwissTiebreakerService $service
    ) {}

    public function store(
        StoreSwissTiebreakerRequest $request,
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
                'Criterio Swiss agregado.'
            );
    }

    public function update(
        UpdateSwissTiebreakerRequest $request,
        PhaseTemplate $phaseTemplate,
        PhaseSwissTiebreaker $tiebreaker
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
                'Criterio Swiss actualizado.'
            );
    }

    public function destroy(
        PhaseTemplate $phaseTemplate,
        PhaseSwissTiebreaker $tiebreaker
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
        PhaseSwissTiebreaker $tiebreaker
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
        PhaseSwissTiebreaker $tiebreaker
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
        PhaseSwissTiebreaker $tiebreaker
    ): void {
        abort_unless(
            $tiebreaker->phase_template_id
                ===
                $phaseTemplate->id,
            404
        );
    }
}
