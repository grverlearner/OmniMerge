<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StorePhaseExitRequest;
use App\Http\Requests\Tournaments\UpdatePhaseExitRequest;
use App\Models\PhaseExit;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\PhaseExitService;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class PhaseExitController extends Controller
{
    public function __construct(
        private readonly
        PhaseExitService $service,

        private readonly
        SingleEliminationStructureService $structureService
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

        $this->validateInternalStructureWhenNeeded(
            $request,
            $phaseTemplate
        );

        return $this->redirectAfterMutation(
            $request,
            $phaseTemplate
        )->with(
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

        $this->validateInternalStructureWhenNeeded(
            $request,
            $phaseTemplate
        );

        return $this->redirectAfterMutation(
            $request,
            $phaseTemplate
        )->with(
            'success',
            'Puerta de salida actualizada correctamente.'
        );
    }

    public function destroy(
        Request $request,
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

        $this->validateInternalStructureWhenNeeded(
            $request,
            $phaseTemplate
        );

        return $this->redirectAfterMutation(
            $request,
            $phaseTemplate
        )->with(
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

    private function redirectAfterMutation(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        if (
            in_array(
                $request->input('return_to'),
                [
                    'structure',
                    'structure_io',
                ],
                true
            )
            &&
            $phaseTemplate->phase_type
            ===
            'SINGLE_ELIMINATION'
        ) {
            return redirect()->to(
                route(
                    'tournaments.single-elimination.structure.io',
                    $phaseTemplate
                )
                    .
                    '#output-gates'
            );
        }

        if (
            $request->input('return_to')
            ===
            'round_robin_io'
            &&
            $phaseTemplate->phase_type
            ===
            'ROUND_ROBIN'
        ) {
            return redirect()->route(
                'tournaments.round-robin.io',
                $phaseTemplate
            );
        }

        return redirect()->route(
            'tournaments.phase-templates.show',
            $phaseTemplate
        );
    }

    private function validateInternalStructureWhenNeeded(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): void {
        if (
            ! in_array(
                $request->input('return_to'),
                [
                    'structure',
                    'structure_io',
                ],
                true
            )
            ||
            $phaseTemplate->phase_type
            !==
            'SINGLE_ELIMINATION'
        ) {
            return;
        }

        $this->structureService
            ->validateAndPersist(
                $phaseTemplate->fresh()
            );
    }
}
