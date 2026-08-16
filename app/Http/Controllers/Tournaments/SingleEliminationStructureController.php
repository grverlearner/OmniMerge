<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\GenerateSingleEliminationStructureRequest;
use App\Http\Requests\Tournaments\UpdateSingleEliminationStructureElementRequest;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SingleEliminationStructureController extends Controller
{
    public function __construct(
        private readonly
        SingleEliminationStructureService $service
    ) {}

    public function show(
        PhaseTemplate $phaseTemplate
    ): View {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureCorrectType(
            $phaseTemplate
        );

        $payload =
            $this->service
            ->payload(
                $phaseTemplate
            );

        return view(
            'tournaments.phase-templates.single-elimination-structure',
            [
                'phaseTemplate' =>
                $phaseTemplate,

                'settings' =>
                $payload['settings'],

                'inputGates' =>
                $payload['inputGates'],

                'rounds' =>
                $payload['rounds'],

                'connections' =>
                $payload['connections'],

                'exits' =>
                $payload['exits'],

                'validation' =>
                $payload['validation'],

                'visualizer' =>
                $payload['visualizer'],
            ]
        );
    }

    public function io(
        PhaseTemplate $phaseTemplate
    ): View {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureCorrectType(
            $phaseTemplate
        );

        $payload =
            $this->service
            ->payload(
                $phaseTemplate
            );

        return view(
            'tournaments.phase-templates.single-elimination-io',
            [
                'phaseTemplate' =>
                $phaseTemplate,

                'settings' =>
                $payload['settings'],

                'inputGates' =>
                $payload['inputGates'],

                'rounds' =>
                $payload['rounds'],

                'connections' =>
                $payload['connections'],

                'exits' =>
                $payload['exits'],

                'validation' =>
                $payload['validation'],
            ]
        );
    }

    public function generate(
        GenerateSingleEliminationStructureRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        $validated =
            $request->validated();

        $participants =
            (int) (
                $validated['participants']
                ??
                $phaseTemplate
                ->exact_participants
                ??
                $phaseTemplate
                ->min_participants
            );

        $result =
            $this->service
            ->generate(
                $phaseTemplate,
                $participants,
                (bool) (
                    $validated['replace_manual']
                    ??
                    false
                )
            );

        $validation =
            $result['validation'];

        return redirect()
            ->route(
                'tournaments.single-elimination.structure.show',
                $phaseTemplate
            )
            ->with(
                $validation['valid']
                    ? 'success'
                    : 'warning',
                $validation['valid']
                    ? 'La estructura interna fue generada y validada correctamente.'
                    : 'La estructura fue generada, pero necesita correcciones.'
            );
    }

    public function validateStructure(
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureCorrectType(
            $phaseTemplate
        );

        $validation =
            $this->service
            ->validateAndPersist(
                $phaseTemplate
            );

        return redirect()
            ->route(
                'tournaments.single-elimination.structure.show',
                $phaseTemplate
            )
            ->with(
                $validation['valid']
                    ? 'success'
                    : 'warning',
                $validation['valid']
                    ? 'El grafo interno es válido.'
                    : 'El grafo interno contiene errores que deben corregirse.'
            );
    }

    public function updateElement(
        UpdateSingleEliminationStructureElementRequest $request,
        PhaseTemplate $phaseTemplate,
        string $elementType,
        int $element
    ): RedirectResponse {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        $result =
            $this->service
            ->updateElement(
                $phaseTemplate,
                $elementType,
                $element,
                $request->validated()
            );

        $validation =
            $result['validation'];

        return redirect()
            ->route(
                'tournaments.single-elimination.structure.show',
                [
                    'phaseTemplate' =>
                    $phaseTemplate,

                    'selected' =>
                    $elementType
                        .
                        ':'
                        .
                        $element,
                ]
            )
            ->with(
                $validation['valid']
                    ? 'success'
                    : 'warning',
                $validation['valid']
                    ? 'El elemento fue actualizado y la estructura continúa siendo válida.'
                    : 'El elemento fue actualizado, pero la estructura ahora necesita correcciones.'
            );
    }

    private function ensureCorrectType(
        PhaseTemplate $phaseTemplate
    ): void {
        abort_unless(
            $phaseTemplate->phase_type
                ===
                'SINGLE_ELIMINATION',
            404
        );
    }
}
