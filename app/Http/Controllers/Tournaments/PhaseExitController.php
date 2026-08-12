<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StorePhaseExitRequest;
use App\Http\Requests\Tournaments\UpdatePhaseExitRequest;
use App\Models\PhaseExit;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\PhaseExitService;
use Illuminate\Http\RedirectResponse;

class PhaseExitController extends Controller
{
    public function __construct(
        private readonly
        PhaseExitService $service
    ) {}

    public function store(
        StorePhaseExitRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->service
            ->create(
                $phaseTemplate,
                $request->validated()
            );

        return redirect()
            ->route(
                'tournaments.phase-templates.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Puerta de salida creada correctamente.'
            );
    }

    public function update(
        UpdatePhaseExitRequest $request,
        PhaseTemplate $phaseTemplate,
        PhaseExit $phaseExit
    ): RedirectResponse {
        $this->ensureBelongsToPhase(
            $phaseTemplate,
            $phaseExit
        );

        $this->service
            ->update(
                $phaseExit,
                $request->validated()
            );

        return redirect()
            ->route(
                'tournaments.phase-templates.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Puerta de salida actualizada correctamente.'
            );
    }

    public function destroy(
        PhaseTemplate $phaseTemplate,
        PhaseExit $phaseExit
    ): RedirectResponse {
        $this->ensureBelongsToPhase(
            $phaseTemplate,
            $phaseExit
        );

        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->service
            ->delete(
                $phaseExit
            );

        return redirect()
            ->route(
                'tournaments.phase-templates.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Puerta de salida eliminada correctamente.'
            );
    }

    private function ensureBelongsToPhase(
        PhaseTemplate $phaseTemplate,
        PhaseExit $phaseExit
    ): void {
        abort_unless(
            $phaseExit->phase_template_id
                ===
                $phaseTemplate->id,
            404
        );
    }
}
