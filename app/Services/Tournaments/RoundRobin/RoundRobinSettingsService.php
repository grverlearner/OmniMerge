<?php

namespace App\Services\Tournaments\RoundRobin;

use App\Models\PhaseRoundRobinSetting;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;

class RoundRobinSettingsService
{
    public function __construct(
        private readonly
        RoundRobinRankingDefinitionService $rankingDefinition
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Obtener o crear
    |--------------------------------------------------------------------------
    */

    public function ensure(
        PhaseTemplate $phaseTemplate
    ): PhaseRoundRobinSetting {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        return DB::transaction(
            function () use (
                $phaseTemplate
            ) {
                $setting =
                    $phaseTemplate
                    ->roundRobinSetting()
                    ->firstOrCreate(
                        [],
                        [
                            'cycles' =>
                            1,

                            'initial_order_mode' =>
                            'INPUT_ORDER',

                            'schedule_mode' =>
                            'BALANCED',

                            'allow_draws' =>
                            true,

                            'win_points' =>
                            3,

                            'draw_points' =>
                            1,

                            'loss_points' =>
                            0,

                            'default_best_of' =>
                            $phaseTemplate->best_of
                                ?: 1,

                            'cutoff_tie_policy' =>
                            'USE_TIEBREAKERS',
                        ]
                    );

                /*
                |--------------------------------------------------------------------------
                | Desempates iniciales
                |--------------------------------------------------------------------------
                |
                | Se crean solo la primera vez.
                |
                | Si después el usuario los elimina, no vuelven
                | a aparecer automáticamente.
                |
                */

                if (
                    $setting->wasRecentlyCreated
                    &&
                    ! $phaseTemplate
                        ->roundRobinTiebreakers()
                        ->exists()
                ) {
                    $sortOrder =
                        10;

                    foreach (
                        $this
                            ->rankingDefinition
                            ->defaultCriteria()
                        as
                        $criterion
                    ) {
                        $phaseTemplate
                            ->roundRobinTiebreakers()
                            ->create([
                                'criterion' =>
                                $criterion,

                                'direction' =>
                                'AUTO',

                                'sort_order' =>
                                $sortOrder,
                            ]);

                        $sortOrder += 10;
                    }
                }

                return $setting->fresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function update(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseRoundRobinSetting {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        return DB::transaction(
            function () use (
                $phaseTemplate,
                $data
            ) {
                $lockedPhase =
                    PhaseTemplate::query()
                    ->whereKey(
                        $phaseTemplate->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $setting =
                    $lockedPhase
                    ->roundRobinSetting()
                    ->firstOrCreate(
                        [],
                        [
                            'cycles' =>
                            1,

                            'initial_order_mode' =>
                            'INPUT_ORDER',

                            'schedule_mode' =>
                            'BALANCED',

                            'allow_draws' =>
                            true,

                            'win_points' =>
                            3,

                            'draw_points' =>
                            1,

                            'loss_points' =>
                            0,

                            'default_best_of' =>
                            $lockedPhase->best_of
                                ?: 1,

                            'cutoff_tie_policy' =>
                            'USE_TIEBREAKERS',
                        ]
                    );

                $setting->update(
                    $data
                );

                /*
                |--------------------------------------------------------------------------
                | Compatibilidad con T1
                |--------------------------------------------------------------------------
                */

                $lockedPhase->update([
                    'best_of' =>
                    $data['default_best_of'],
                ]);

                return $setting->fresh();
            }
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
