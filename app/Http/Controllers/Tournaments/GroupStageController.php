<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\PreviewGroupStageRequest;
use App\Http\Requests\Tournaments\UpdateGroupStageSettingsRequest;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\GroupStage\GroupStageDefinitionService;
use App\Services\Tournaments\GroupStage\GroupStagePreviewService;
use App\Services\Tournaments\GroupStage\GroupStageSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GroupStageController extends Controller
{
    public function __construct(
        private readonly
        GroupStageSettingsService $settingsService,

        private readonly
        GroupStagePreviewService $previewService,

        private readonly
        GroupStageDefinitionService $definitionService
    ) {}

    public function show(
        PreviewGroupStageRequest $request,
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

        $groupDefinitions =
            $phaseTemplate
            ->groupStageGroups()
            ->get();

        $activeGroupDefinitions =
            $groupDefinitions
            ->where(
                'is_active',
                true
            )
            ->values();

        $advancementRules =
            $phaseTemplate
            ->groupStageAdvancementRules()
            ->with([
                'phaseExit',
                'group',
            ])
            ->get();

        $tiebreakers =
            $phaseTemplate
            ->groupStageTiebreakers()
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
                $groupDefinitions,
                $advancementRules,
                $previewParticipants
            );

        $groupCountModes =
            $this
            ->definitionService
            ->groupCountModes();

        $remainderPolicies =
            $this
            ->definitionService
            ->remainderPolicies();

        $distributionModes =
            $this
            ->definitionService
            ->distributionModes();

        $ruleTypes =
            $this
            ->definitionService
            ->ruleTypes();

        $crossGroupCriteria =
            $this
            ->definitionService
            ->crossGroupCriteria();

        $cutoffPolicies =
            $this
            ->definitionService
            ->cutoffPolicies();

        return view(
            'tournaments.phase-templates.group-stage',
            compact(
                'phaseTemplate',
                'settings',

                'groupDefinitions',
                'activeGroupDefinitions',

                'advancementRules',
                'tiebreakers',
                'phaseExits',

                'previewParticipants',
                'preview',

                'groupCountModes',
                'remainderPolicies',
                'distributionModes',
                'ruleTypes',
                'crossGroupCriteria',
                'cutoffPolicies'
            )
        );
    }

    public function update(
        UpdateGroupStageSettingsRequest $request,
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
                'tournaments.group-stage.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Configuración de Fase de grupos actualizada.'
            );
    }

    private function ensureCorrectType(
        PhaseTemplate $phaseTemplate
    ): void {
        abort_unless(
            $phaseTemplate->phase_type
                ===
                'GROUP_STAGE',
            404
        );
    }
}
