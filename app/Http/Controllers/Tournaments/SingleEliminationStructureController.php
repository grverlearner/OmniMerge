<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\GenerateSingleEliminationStructureRequest;
use App\Http\Requests\Tournaments\UpdateSingleEliminationStructureElementRequest;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureService;
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SingleEliminationStructureController extends Controller
{
    public function __construct(
        private readonly
        SingleEliminationStructureService $service,

        private readonly
        SingleEliminationSettingsService $settingsService
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

        /*
        * Recordar la cantidad introducida antes de generar.
        * Incluso si la estructura necesita alguna corrección,
        * el campo conservará este número.
        */
        $this->settingsService
            ->rememberParticipants(
                $phaseTemplate,
                $participants
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

        [$flashType, $message] =
            $this->statusFeedback(
                $validation,
                'GENERATE'
            );

        return redirect()
            ->route(
                'tournaments.single-elimination.structure.show',
                $phaseTemplate
            )
            ->with(
                $flashType,
                $message
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

        [$flashType, $message] =
            $this->statusFeedback(
                $validation,
                'VALIDATE'
            );

        return redirect()
            ->route(
                'tournaments.single-elimination.structure.show',
                $phaseTemplate
            )
            ->with(
                $flashType,
                $message
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

        [$flashType, $message] =
            $this->statusFeedback(
                $validation,
                'UPDATE'
            );

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
                $flashType,
                $message
            );
    }

    private function statusFeedback(
        array $validation,
        string $action
    ): array {
        $status =
            (string) (
                $validation['structure_status']
                ?? 'INVALID'
            );

        return match ($status) {
            'VALID' => [
                'success',

                $action === 'GENERATE'
                    ? 'La estructura fue generada, validada y está lista para ejecutar.'
                    : (
                        $action === 'UPDATE'
                        ? 'El elemento fue actualizado y la estructura quedó validada y ejecutable.'
                        : 'El grafo interno está validado y listo para ejecutar.'
                    ),
            ],

            'BLOCKED' => [
                'warning',

                'La estructura es coherente, pero no es ejecutable todavía. Revisa los bloqueos del diagnóstico antes de continuar.',
            ],

            'NOT_GENERATED' => [
                'warning',

                'Todavía no existe una estructura interna. Genérala o constrúyela antes de ejecutar la validación.',
            ],

            'STALE' => [
                'warning',

                'La estructura está desactualizada respecto del último snapshot validado. Revísala y vuelve a validar o regenerar.',
            ],

            'GENERATED' => [
                'warning',

                'La estructura existe, pero está pendiente de validación.',
            ],

            default => [
                'warning',

                'La estructura contiene errores que deben corregirse antes de ejecutarla.',
            ],
        };
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
