<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\ExecuteGroupStageSimulatorActionRequest;
use App\Http\Requests\Tournaments\InitializeGroupStageSimulatorRequest;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\CompetitionLab\PhaseSimulatorService;
use App\Services\Tournaments\GroupStage\GroupStageSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class GroupStageSimulatorController extends Controller
{
    public function __construct(
        private readonly PhaseSimulatorService $simulatorService,

        private readonly GroupStageSettingsService $settingsService
    ) {}

    public function show(PhaseTemplate $phaseTemplate): View
    {
        $this->authorize('update', $phaseTemplate);

        $this->ensureCorrectType($phaseTemplate);

        $settings = $this->settingsService->ensure($phaseTemplate);

        return view(
            'tournaments.phase-templates.group-stage-simulator',
            [
                'phaseTemplate' => $phaseTemplate,
                'settings' => $settings,
            ]
        );
    }

    public function initialize(
        InitializeGroupStageSimulatorRequest $request,
        PhaseTemplate $phaseTemplate
    ): JsonResponse {
        $this->ensureCorrectType($phaseTemplate);

        $result = $this->simulatorService->initialize(
            $phaseTemplate,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'ok' => true,
            ...$result,
        ]);
    }

    public function action(
        ExecuteGroupStageSimulatorActionRequest $request,
        PhaseTemplate $phaseTemplate
    ): JsonResponse {
        $this->ensureCorrectType($phaseTemplate);

        $result = $this->simulatorService->execute(
            $phaseTemplate,
            $request->user(),
            $request->string('state_token')->toString(),
            $request->string('action')->toString(),
            $request->safe()->except(['action', 'state_token'])
        );

        return response()->json([
            'ok' => true,
            ...$result,
        ]);
    }

    private function ensureCorrectType(PhaseTemplate $phaseTemplate): void
    {
        abort_unless($phaseTemplate->phase_type === 'GROUP_STAGE', 404);
    }
}
