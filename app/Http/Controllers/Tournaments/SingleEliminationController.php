<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\PreviewSingleEliminationRequest;
use App\Http\Requests\Tournaments\UpdateSingleEliminationSettingsRequest;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationBracketCalculator;
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SingleEliminationController extends Controller
{
    public function __construct(
        private readonly
        SingleEliminationSettingsService $settingsService,

        private readonly
        SingleEliminationBracketCalculator $calculator
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Workspace
    |--------------------------------------------------------------------------
    */

    public function show(
        PreviewSingleEliminationRequest $request,
        PhaseTemplate $phaseTemplate
    ): View {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureCorrectType(
            $phaseTemplate
        );

        /*
        |--------------------------------------------------------------------------
        | Configuración
        |--------------------------------------------------------------------------
        */

        $settings =
            $this->settingsService
            ->ensure(
                $phaseTemplate
            );

        /*
        |--------------------------------------------------------------------------
        | Reglas especiales
        |--------------------------------------------------------------------------
        */

        $roundRules =
            $phaseTemplate
            ->singleEliminationRoundRules()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Participantes para preview
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validated();

        $previewParticipants =
            isset(
                $validated['participants']
            )
            ? (int)
            $validated['participants']

            : (
                $phaseTemplate->exact_participants
                ??
                $phaseTemplate->min_participants
            );

        /*
        |--------------------------------------------------------------------------
        | Preview
        |--------------------------------------------------------------------------
        */

        $preview =
            $this->calculator
            ->calculate(
                $phaseTemplate,
                $settings,
                $previewParticipants,
                $roundRules
            );

        /*
        |--------------------------------------------------------------------------
        | Tamaños de ronda
        |--------------------------------------------------------------------------
        */

        $roundSizes = [
            512,
            256,
            128,
            64,
            32,
            16,
            8,
            4,
            2,
        ];

        $usedRoundSizes =
            $roundRules
            ->pluck(
                'participants_in_round'
            )
            ->map(
                fn($value) =>
                (int) $value
            )
            ->all();

        $availableRoundSizes =
            array_values(
                array_filter(
                    $roundSizes,

                    fn($size) =>
                    ! in_array(
                        $size,
                        $usedRoundSizes,
                        true
                    )
                )
            );

        return view(
            'tournaments.phase-templates.single-elimination',
            compact(
                'phaseTemplate',
                'settings',
                'roundRules',
                'roundSizes',
                'availableRoundSizes',
                'previewParticipants',
                'preview'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateSingleEliminationSettingsRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->settingsService
            ->update(
                $phaseTemplate,
                $request->validated()
            );

        return redirect()
            ->route(
                'tournaments.single-elimination.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Configuración de Eliminación directa actualizada.'
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
