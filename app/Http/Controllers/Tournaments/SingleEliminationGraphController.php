<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\InitializeSingleEliminationGraphRequest;
use App\Http\Requests\Tournaments\StoreSingleEliminationGraphEncounterRequest;
use App\Http\Requests\Tournaments\StoreSingleEliminationGraphRouteRequest;
use App\Http\Requests\Tournaments\StoreSingleEliminationGraphStageRequest;
use App\Http\Requests\Tournaments\UpdateSingleEliminationGraphEncounterRequest;
use App\Models\PhaseSingleEliminationConnection;
use App\Models\PhaseSingleEliminationEncounter;
use App\Models\PhaseSingleEliminationRound;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationGraphEditor;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureService;
use Illuminate\Http\RedirectResponse;

class SingleEliminationGraphController extends Controller
{
    public function __construct(
        private readonly SingleEliminationGraphEditor $editor,
        private readonly SingleEliminationStructureService $structureService
    ) {}

    public function initialize(
        InitializeSingleEliminationGraphRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $data = $request->validated();
        $this->editor->initialize(
            $phaseTemplate,
            (int) $data['participants'],
            (bool) ($data['replace_structure'] ?? false)
        );

        return $this->redirectWithValidation(
            $phaseTemplate,
            'El grafo personalizado fue inicializado. Ahora crea sus etapas y encuentros.'
        );
    }

    public function storeStage(
        StoreSingleEliminationGraphStageRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->editor->createStage($phaseTemplate, $request->validated());

        return $this->redirectWithValidation($phaseTemplate, 'La etapa fue creada.');
    }

    public function destroyStage(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationRound $round
    ): RedirectResponse {
        $this->authorize('update', $phaseTemplate);
        $this->editor->deleteStage($phaseTemplate, $round);

        return $this->redirectWithValidation($phaseTemplate, 'La etapa fue eliminada.');
    }

    public function storeEncounter(
        StoreSingleEliminationGraphEncounterRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $encounter = $this->editor->createEncounter($phaseTemplate, $request->validated());

        return $this->redirectWithValidation(
            $phaseTemplate,
            'El encuentro fue creado con sus slots y resultados.',
            'ENCOUNTER:' . $encounter->id
        );
    }

    public function updateEncounter(
        UpdateSingleEliminationGraphEncounterRequest $request,
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationEncounter $encounter
    ): RedirectResponse {
        $encounter = $this->editor->updateEncounter(
            $phaseTemplate,
            $encounter,
            $request->validated()
        );

        return $this->redirectWithValidation(
            $phaseTemplate,
            'El encuentro fue actualizado.',
            'ENCOUNTER:' . $encounter->id
        );
    }

    public function destroyEncounter(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationEncounter $encounter
    ): RedirectResponse {
        $this->authorize('update', $phaseTemplate);
        $this->editor->deleteEncounter($phaseTemplate, $encounter);

        return $this->redirectWithValidation($phaseTemplate, 'El encuentro fue eliminado.');
    }

    public function storeRoute(
        StoreSingleEliminationGraphRouteRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->editor->createRoute($phaseTemplate, $request->validated());

        return $this->redirectWithValidation($phaseTemplate, 'La ruta de clasificados fue creada.');
    }

    public function destroyRoute(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationConnection $connection
    ): RedirectResponse {
        $this->authorize('update', $phaseTemplate);
        $this->editor->deleteRoute($phaseTemplate, $connection);

        return $this->redirectWithValidation($phaseTemplate, 'La ruta fue eliminada.');
    }

    private function redirectWithValidation(
        PhaseTemplate $phaseTemplate,
        string $message,
        ?string $selected = null
    ): RedirectResponse {
        $validation = $this->structureService->validateAndPersist($phaseTemplate->fresh());

        return redirect()
            ->route('tournaments.single-elimination.structure.show', array_filter([
                'phaseTemplate' => $phaseTemplate,
                'selected' => $selected,
            ]))
            ->with($validation['valid'] ? 'success' : 'warning',
                $validation['valid']
                    ? $message . ' El grafo es válido.'
                    : $message . ' El grafo todavía necesita completar conexiones.'
            );
    }
}
