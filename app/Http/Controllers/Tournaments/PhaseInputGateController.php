<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StorePhaseInputGateRequest;
use App\Http\Requests\Tournaments\UpdatePhaseInputGateRequest;
use App\Models\PhaseInputGate;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\PhaseInputGateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PhaseInputGateController extends Controller
{
    public function __construct(
        private readonly
        PhaseInputGateService $service
    ) {}

    public function store(
        StorePhaseInputGateRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        $result = $this->service
            ->create(
                $phaseTemplate,
                $request->validated()
            );

        return $this->redirectWithValidation(
            $phaseTemplate,
            $result,
            'Puerta de entrada creada correctamente.',
            'La puerta fue creada, pero el grafo necesita correcciones.'
        );
    }

    public function update(
        UpdatePhaseInputGateRequest $request,
        PhaseTemplate $phaseTemplate,
        PhaseInputGate $phaseInputGate
    ): RedirectResponse {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        $this->ensureBelongsToPhase(
            $phaseTemplate,
            $phaseInputGate
        );
        $result = $this->service
            ->update(
                $phaseTemplate,
                $phaseInputGate,
                $request->validated()
            );

        return $this->redirectWithValidation(
            $phaseTemplate,
            $result,
            'Puerta de entrada y destinos actualizados correctamente.',
            'Los cambios se guardaron, pero el grafo necesita correcciones.'
        );
    }

    public function duplicate(
        Request $request,
        PhaseTemplate $phaseTemplate,
        PhaseInputGate $phaseInputGate
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureCorrectType(
            $phaseTemplate
        );

        $this->ensureBelongsToPhase(
            $phaseTemplate,
            $phaseInputGate
        );

        $result = $this->service
            ->duplicate(
                $phaseTemplate,
                $phaseInputGate
            );

        return $this->redirectWithValidation(
            $phaseTemplate,
            $result,
            'Puerta duplicada. Ahora asigna sus slots de destino.',
            'La puerta fue duplicada sin destinos; completa su mapeo para validar el grafo.'
        );
    }

    public function destroy(
        Request $request,
        PhaseTemplate $phaseTemplate,
        PhaseInputGate $phaseInputGate
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureCorrectType(
            $phaseTemplate
        );

        $this->ensureBelongsToPhase(
            $phaseTemplate,
            $phaseInputGate
        );

        $result = $this->service
            ->delete(
                $phaseTemplate,
                $phaseInputGate
            );

        $validation =
            $result['validation'];

        return redirect()
            ->to(
                route(
                    'tournaments.single-elimination.structure.show',
                    $phaseTemplate
                )
                    .
                    '#input-gates'
            )
            ->with(
                $validation['valid']
                    ? 'success'
                    : 'warning',

                $validation['valid']
                    ? 'Puerta de entrada eliminada correctamente.'
                    : 'La puerta fue eliminada, pero el grafo necesita correcciones.'
            );
    }

    private function redirectWithValidation(
        PhaseTemplate $phaseTemplate,
        array $result,
        string $successMessage,
        string $warningMessage
    ): RedirectResponse {
        $validation =
            $result['validation'];

        $gate =
            $result['gate'];

        return redirect()
            ->to(
                route(
                    'tournaments.single-elimination.structure.show',
                    [
                        'phaseTemplate' =>
                        $phaseTemplate,

                        'selected' =>
                        'INPUT_GATE:'
                            .
                            $gate->id,

                        'view' =>
                        'blocks',
                    ]
                )
                    .
                    '#input-gates'
            )
            ->with(
                $validation['valid']
                    ? 'success'
                    : 'warning',

                $validation['valid']
                    ? $successMessage
                    : $warningMessage
            );
    }

    private function ensureBelongsToPhase(
        PhaseTemplate $phaseTemplate,
        PhaseInputGate $phaseInputGate
    ): void {
        abort_unless(
            $phaseInputGate->phase_template_id
                ===
                $phaseTemplate->id,
            404
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
