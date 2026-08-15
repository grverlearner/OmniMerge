<?php

namespace App\Services\Tournaments\SingleElimination;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;

class SingleEliminationSettingsService
{
    /*
    |--------------------------------------------------------------------------
    | Obtener o crear
    |--------------------------------------------------------------------------
    */

    public function ensure(
        PhaseTemplate $phaseTemplate
    ): PhaseSingleEliminationSetting {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        return $phaseTemplate
            ->singleEliminationSetting()
            ->firstOrCreate(
                [],
                [
                    'configuration_mode' =>
                    'BASIC',

                    'input_mode' =>
                    'POOL',

                    'routing_mode' =>
                    'AUTOMATIC',

                    'entrants_per_match' =>
                    2,

                    'qualifiers_per_match' =>
                    1,

                    'encounter_profile' =>
                    'DUEL',

                    'remainder_policy' =>
                    $phaseTemplate->allow_byes
                        ? 'BYE'
                        : 'REJECT',
                    'completion_mode' =>
                    'WINNER',

                    'target_survivors' =>
                    1,

                    'seeding_mode' =>
                    'INPUT_ORDER',

                    'pairing_mode' =>
                    'STANDARD_SEEDED',

                    'bye_assignment' =>
                    'TOP_SEEDS',

                    'reseed_each_round' =>
                    false,

                    'series_format' =>
                    'BEST_OF',

                    /*
                     * Conservamos compatibilidad
                     * con T1.
                     */
                    'default_best_of' =>
                    $phaseTemplate->best_of
                        ?: 1,
                    'fixed_games' =>
                    1,
                ]
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
    ): PhaseSingleEliminationSetting {
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

                $settings =
                    $lockedPhase
                    ->singleEliminationSetting()
                    ->firstOrCreate(
                        [],
                        [
                            'configuration_mode' =>
                            'BASIC',

                            'input_mode' =>
                            'POOL',

                            'routing_mode' =>
                            'AUTOMATIC',

                            'entrants_per_match' =>
                            2,

                            'qualifiers_per_match' =>
                            1,

                            'encounter_profile' =>
                            'DUEL',

                            'remainder_policy' =>
                            $lockedPhase->allow_byes
                                ? 'BYE'
                                : 'REJECT',
                            'completion_mode' =>
                            'WINNER',

                            'target_survivors' =>
                            1,

                            'seeding_mode' =>
                            'INPUT_ORDER',

                            'pairing_mode' =>
                            'STANDARD_SEEDED',

                            'bye_assignment' =>
                            'TOP_SEEDS',

                            'reseed_each_round' =>
                            false,
                            'series_format' =>
                            'BEST_OF',
                            'default_best_of' =>
                            $lockedPhase->best_of
                                ?: 1,

                            'fixed_games' =>
                            1,
                        ]
                    );

                /*
                |--------------------------------------------------------------------------
                | WINNER siempre significa 1
                |--------------------------------------------------------------------------
                */

                if (
                    $data['completion_mode']
                    ===
                    'WINNER'
                ) {
                    $data['target_survivors'] =
                        1;
                }

                $data['series_format'] =
                    $data['series_format']
                    ??
                    $settings->series_format
                    ??
                    'BEST_OF';

                $data['default_best_of'] =
                    $data['default_best_of']
                    ??
                    $settings->default_best_of
                    ??
                    1;

                $data['fixed_games'] =
                    $data['fixed_games']
                    ??
                    $settings->fixed_games
                    ??
                    1;

                $settings->update(
                    $data
                );

                /*
                |--------------------------------------------------------------------------
                | Mantener best_of de T1 sincronizado
                |--------------------------------------------------------------------------
                */

                $lockedPhase->update([
                    'best_of' =>
                    $data['series_format']
                        ===
                        'BEST_OF'
                        ? $data['default_best_of']
                        : 1,
                ]);

                return $settings->fresh();
            }
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
