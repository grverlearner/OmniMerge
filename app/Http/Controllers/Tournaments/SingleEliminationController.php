<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\PreviewSingleEliminationConfigurationRequest;
use App\Http\Requests\Tournaments\PreviewSingleEliminationRequest;
use App\Http\Requests\Tournaments\UpdateSingleEliminationSettingsRequest;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationBracketCalculator;
use App\Services\Tournaments\SingleElimination\SingleEliminationConfigurationInspector;
use App\Services\Tournaments\SingleElimination\SingleEliminationPreviewService;
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SingleEliminationController extends Controller
{
    public function __construct(
        private readonly
        SingleEliminationSettingsService $settingsService,

        private readonly
        SingleEliminationBracketCalculator $calculator,

        private readonly
        SingleEliminationConfigurationInspector $inspector,

        private readonly
        SingleEliminationPreviewService $previewService
    ) {}

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

        $settings =
            $this->settingsService
            ->ensure(
                $phaseTemplate
            );

        $roundRules =
            $phaseTemplate
            ->singleEliminationRoundRules()
            ->get();

        $validated =
            $request->validated();

        $rememberedParticipants =
            (int) data_get(
                $settings->settings,
                'working_participants',
                0
            );

        $previewParticipants =
            isset($validated['participants'])
            ? (int) $validated['participants']
            : (
                $rememberedParticipants >= 2
                ? $rememberedParticipants
                : (
                    $phaseTemplate->exact_participants
                    ??
                    $phaseTemplate->min_participants
                )
            );

        $preview =
            $this->calculator
            ->calculate(
                $phaseTemplate,
                $settings,
                $previewParticipants,
                $roundRules
            );

        $diagnostic =
            $this->inspector
            ->inspect(
                $phaseTemplate,
                $settings,
                $roundRules,
                $previewParticipants
            );

        $roundSizes =
            $diagnostic['possible_round_sizes'];

        $usedRoundSizes =
            $roundRules
            ->pluck(
                'participants_in_round'
            )
            ->map(
                fn($value) =>
                (int)
                $value
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
                'preview',
                'diagnostic'
            )
        );
    }

    public function preview(
        PreviewSingleEliminationConfigurationRequest $request,
        PhaseTemplate $phaseTemplate
    ): JsonResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->ensureCorrectType(
            $phaseTemplate
        );

        $validated =
            $request->validated();

        /*
     * El número queda recordado aunque solamente
     * se esté utilizando para la vista previa.
     */
        $settings =
            $this->settingsService
            ->rememberParticipants(
                $phaseTemplate,
                (int) $validated['participants']
            );

        $roundRules =
            $phaseTemplate
            ->singleEliminationRoundRules()
            ->get();

        $result =
            $this->previewService
            ->preview(
                $phaseTemplate,
                $settings,
                $roundRules,
                $validated
            );

        $html =
            view(
                'tournaments.phase-templates.partials.single-elimination-preview',
                [
                    'phaseTemplate' =>
                    $phaseTemplate,

                    'previewParticipants' =>
                    $result['participants'],

                    'preview' =>
                    $result['preview'],

                    'settings' =>
                    $result['settings'],
                ]
            )
            ->render();

        $diagnosticHtml =
            view(
                'tournaments.phase-templates.partials.single-elimination-diagnostic',
                [
                    'diagnostic' =>
                    $result['diagnostic'],
                ]
            )
            ->render();

        return response()
            ->json([
                'valid' =>
                $result['preview']['valid']
                    &&
                    $result['diagnostic']['valid'],

                'preview' =>
                $result['preview'],

                'diagnostic' =>
                $result['diagnostic'],

                'html' =>
                $html,

                'diagnostic_html' =>
                $diagnosticHtml,
            ]);
    }

    public function update(
        UpdateSingleEliminationSettingsRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $settings =
            $this->settingsService
            ->update(
                $phaseTemplate,
                $request->validated()
            );

        $message =
            $settings->structure_status
            ===
            'STALE'
                ? 'Configuración actualizada. La estructura existente quedó desactualizada; revísala o regénérala antes de ejecutar.'
                : 'Configuración de Eliminación directa actualizada.';

        return redirect()
            ->route(
                'tournaments.single-elimination.show',
                $phaseTemplate
            )
            ->with(
                'success',
                $message
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
