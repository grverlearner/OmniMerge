<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreRoundRobinTiebreakerRequest;
use App\Http\Requests\Tournaments\UpdateRoundRobinTiebreakerRequest;
use App\Models\PhaseRoundRobinTiebreaker;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\RoundRobin\RoundRobinTiebreakerService;
use Illuminate\Http\RedirectResponse;

class RoundRobinTiebreakerController extends Controller
{
    public function __construct(
        private readonly
        RoundRobinTiebreakerService $service
    ) {}

    public function store(
        StoreRoundRobinTiebreakerRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->service
            ->create(
                $phaseTemplate,
                $request->validated()
            );

        return redirect()
            ->route(
                'tournaments.round-robin.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Criterio de desempate agregado.'
            );
    }

    public function update(
        UpdateRoundRobinTiebreakerRequest $request,
        PhaseTemplate $phaseTemplate,
        PhaseRoundRobinTiebreaker $tiebreaker
    ): RedirectResponse {
        $this->ensureBelongsToPhase(
            $phaseTemplate,
            $tiebreaker
        );

        $this->service
            ->update(
                $tiebreaker,
                $request->validated()
            );

        return redirect()
            ->route(
                'tournaments.round-robin.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Criterio actualizado correctamente.'
            );
    }

    public function destroy(
        PhaseTemplate $phaseTemplate,
        PhaseRoundRobinTiebreaker $tiebreaker
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureBelongsToPhase(
            $phaseTemplate,
            $tiebreaker
        );

        $this->service
            ->delete(
                $tiebreaker
            );

        return redirect()
            ->route(
                'tournaments.round-robin.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Criterio eliminado.'
            );
    }

    public function moveUp(
        PhaseTemplate $phaseTemplate,
        PhaseRoundRobinTiebreaker $tiebreaker
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureBelongsToPhase(
            $phaseTemplate,
            $tiebreaker
        );

        $this->service
            ->move(
                $phaseTemplate,
                $tiebreaker,
                'UP'
            );

        return redirect()
            ->route(
                'tournaments.round-robin.show',
                $phaseTemplate
            );
    }

    public function moveDown(
        PhaseTemplate $phaseTemplate,
        PhaseRoundRobinTiebreaker $tiebreaker
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureBelongsToPhase(
            $phaseTemplate,
            $tiebreaker
        );

        $this->service
            ->move(
                $phaseTemplate,
                $tiebreaker,
                'DOWN'
            );

        return redirect()
            ->route(
                'tournaments.round-robin.show',
                $phaseTemplate
            );
    }

    private function ensureBelongsToPhase(
        PhaseTemplate $phaseTemplate,
        PhaseRoundRobinTiebreaker $tiebreaker
    ): void {
        abort_unless(
            $tiebreaker->phase_template_id
                ===
                $phaseTemplate->id,
            404
        );
    }
}
