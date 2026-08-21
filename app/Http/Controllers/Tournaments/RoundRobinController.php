<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\PreviewRoundRobinRequest;
use App\Http\Requests\Tournaments\UpdateRoundRobinSettingsRequest;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\RoundRobin\RoundRobinRankingDefinitionService;
use App\Services\Tournaments\RoundRobin\RoundRobinScheduleCalculator;
use App\Services\Tournaments\RoundRobin\RoundRobinSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoundRobinController extends Controller
{
    public function __construct(
        private readonly
        RoundRobinSettingsService $settingsService,

        private readonly
        RoundRobinScheduleCalculator $scheduleCalculator,

        private readonly
        RoundRobinRankingDefinitionService $rankingDefinition
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Workspace
    |--------------------------------------------------------------------------
    */

    public function show(
        PreviewRoundRobinRequest $request,
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
        | Desempates
        |--------------------------------------------------------------------------
        */

        $tiebreakers =
            $phaseTemplate
            ->roundRobinTiebreakers()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Preview
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
                $phaseTemplate
                ->exact_participants
                ??
                $phaseTemplate
                ->min_participants
            );

        $preview =
            $this
            ->scheduleCalculator
            ->calculate(
                $phaseTemplate,
                $settings,
                $previewParticipants
            );

        /*
        |--------------------------------------------------------------------------
        | Ranking Definition
        |--------------------------------------------------------------------------
        */

        $primaryCriterion =
            $this
            ->rankingDefinition
            ->primaryCriterion();

        $criteria =
            $this
            ->rankingDefinition
            ->criteria();

        $cutoffPolicies =
            $this
            ->rankingDefinition
            ->cutoffPolicies();

        $standingsColumns =
            $this
            ->rankingDefinition
            ->standingsColumns();

        /*
        |--------------------------------------------------------------------------
        | Criterios todavía disponibles
        |--------------------------------------------------------------------------
        */

        $usedCriteria =
            $tiebreakers
            ->pluck(
                'criterion'
            )
            ->all();

        $availableCriteria =
            array_filter(
                $criteria,
                fn(
                    $definition,
                    $key
                ) =>
                ! in_array(
                    $key,
                    $usedCriteria,
                    true
                ),
                ARRAY_FILTER_USE_BOTH
            );

        return view(
            'tournaments.phase-templates.round-robin',
            compact(
                'phaseTemplate',
                'settings',
                'tiebreakers',
                'previewParticipants',
                'preview',
                'primaryCriterion',
                'criteria',
                'availableCriteria',
                'cutoffPolicies',
                'standingsColumns'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Entrada y salida
    |--------------------------------------------------------------------------
    */

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

        $exits =
            $phaseTemplate
            ->exits()
            ->get();

        return view(
            'tournaments.phase-templates.round-robin-io',
            compact(
                'phaseTemplate',
                'exits'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateRoundRobinSettingsRequest $request,
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
                'tournaments.round-robin.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Configuración Round Robin actualizada correctamente.'
            );
    }

    private function ensureCorrectType(
        PhaseTemplate $phaseTemplate
    ): void {
        abort_unless(
            $phaseTemplate->phase_type
                ===
                'ROUND_ROBIN',
            404
        );
    }
}
