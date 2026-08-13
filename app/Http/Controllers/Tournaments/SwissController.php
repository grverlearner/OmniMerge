<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\PreviewSwissRequest;
use App\Http\Requests\Tournaments\UpdateSwissSettingsRequest;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\Swiss\SwissDefinitionService;
use App\Services\Tournaments\Swiss\SwissPreviewService;
use App\Services\Tournaments\Swiss\SwissSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SwissController extends Controller
{
    public function __construct(
        private readonly
        SwissSettingsService $settingsService,

        private readonly
        SwissPreviewService $previewService,

        private readonly
        SwissDefinitionService $definitionService
    ) {}

    public function show(
        PreviewSwissRequest $request,
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
            $this
            ->settingsService
            ->ensure(
                $phaseTemplate
            );

        $tiebreakers =
            $phaseTemplate
            ->swissTiebreakers()
            ->get();

        $roundRules =
            $phaseTemplate
            ->swissRoundRules()
            ->get();

        $advancementRules =
            $phaseTemplate
            ->swissAdvancementRules()
            ->with(
                'phaseExit'
            )
            ->get();

        $phaseExits =
            $phaseTemplate
            ->exits()
            ->where(
                'status',
                'ACTIVE'
            )
            ->get();

        $validated =
            $request->validated();

        $previewParticipants =
            isset(
                $validated['participants']
            )
            ? (int)
            $validated['participants']
            : (
                $phaseTemplate
                ->exact_participants
                ??
                $phaseTemplate
                ->min_participants
            );

        $preview =
            $this
            ->previewService
            ->preview(
                $phaseTemplate,
                $settings,
                $advancementRules,
                $roundRules,
                $previewParticipants
            );

        $completionModes =
            $this
            ->definitionService
            ->completionModes();

        $pairingAlgorithms =
            $this
            ->definitionService
            ->pairingAlgorithms();

        $pairingBases =
            $this
            ->definitionService
            ->pairingBases();

        $firstRoundModes =
            $this
            ->definitionService
            ->firstRoundModes();

        $rematchPolicies =
            $this
            ->definitionService
            ->rematchPolicies();

        $byePolicies =
            $this
            ->definitionService
            ->byePolicies();

        $floaterPolicies =
            $this
            ->definitionService
            ->floaterPolicies();

        $sidePolicies =
            $this
            ->definitionService
            ->sidePolicies();

        $accelerationModes =
            $this
            ->definitionService
            ->accelerationModes();

        $fallbackPolicies =
            $this
            ->definitionService
            ->fallbackPolicies();

        $cutoffPolicies =
            $this
            ->definitionService
            ->cutoffPolicies();

        $tiebreakerCriteria =
            $this
            ->definitionService
            ->tiebreakerCriteria();

        $roundRuleTypes =
            $this
            ->definitionService
            ->roundRuleTypes();

        $advancementRuleTypes =
            $this
            ->definitionService
            ->advancementRuleTypes();

        return view(
            'tournaments.phase-templates.swiss',
            compact(
                'phaseTemplate',
                'settings',

                'tiebreakers',
                'roundRules',
                'advancementRules',
                'phaseExits',

                'previewParticipants',
                'preview',

                'completionModes',
                'pairingAlgorithms',
                'pairingBases',
                'firstRoundModes',
                'rematchPolicies',
                'byePolicies',
                'floaterPolicies',
                'sidePolicies',
                'accelerationModes',
                'fallbackPolicies',
                'cutoffPolicies',

                'tiebreakerCriteria',
                'roundRuleTypes',
                'advancementRuleTypes'
            )
        );
    }

    public function update(
        UpdateSwissSettingsRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this
            ->settingsService
            ->update(
                $phaseTemplate,
                $request->validated()
            );

        return redirect()
            ->route(
                'tournaments.swiss.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Configuración Swiss actualizada correctamente.'
            );
    }

    private function ensureCorrectType(
        PhaseTemplate $phaseTemplate
    ): void {
        abort_unless(
            $phaseTemplate->phase_type
                ===
                'SWISS',
            404
        );
    }
}
