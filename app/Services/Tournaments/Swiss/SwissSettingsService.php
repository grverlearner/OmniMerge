<?php

namespace App\Services\Tournaments\Swiss;

use App\Models\PhaseSwissSetting;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;

class SwissSettingsService
{
    public function __construct(
        private readonly
        SwissDefinitionService $definitionService
    ) {}

    public function ensure(
        PhaseTemplate $phaseTemplate
    ): PhaseSwissSetting {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        return DB::transaction(
            function () use (
                $phaseTemplate
            ) {
                $setting =
                    $phaseTemplate
                    ->swissSetting()
                    ->firstOrCreate(
                        [],
                        $this->defaults(
                            $phaseTemplate
                        )
                    );

                if (
                    $setting->wasRecentlyCreated
                    &&
                    ! $phaseTemplate
                        ->swissTiebreakers()
                        ->exists()
                ) {
                    $sortOrder = 10;

                    foreach (
                        $this
                            ->definitionService
                            ->defaultTiebreakers()
                        as
                        $criterion
                    ) {
                        $phaseTemplate
                            ->swissTiebreakers()
                            ->create([
                                'criterion' =>
                                $criterion,

                                'parameter_int' =>
                                null,

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

    public function update(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseSwissSetting {
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
                    ->swissSetting()
                    ->firstOrCreate(
                        [],
                        $this->defaults(
                            $lockedPhase
                        )
                    );

                $setting->update(
                    $data
                );

                /*
                |--------------------------------------------------------------------------
                | Sincronización genérica PhaseTemplate
                |--------------------------------------------------------------------------
                */

                $lockedPhase->update([
                    'best_of' =>
                    (int)
                    $data['default_best_of'],

                    'allow_byes' =>
                    $data['bye_policy']
                        !==
                        'DISABLED',
                ]);

                return $setting->fresh();
            }
        );
    }

    private function defaults(
        PhaseTemplate $phaseTemplate
    ): array {
        return [
            'completion_mode' =>
            'FIXED_ROUNDS',

            'fixed_rounds' =>
            5,

            'qualification_wins' =>
            3,

            'elimination_losses' =>
            3,

            'max_rounds' =>
            5,

            'pairing_algorithm' =>
            'OMNIMERGE_SCORE_GROUP',

            'pairing_basis' =>
            'MATCH_POINTS',

            'first_round_mode' =>
            'SEEDED_HALVES',

            'rematch_policy' =>
            'STRICT_NO_REMATCH',

            'floater_policy' =>
            'MINIMIZE_SCORE_GAP',

            'side_balance_policy' =>
            'PREFER_BALANCE',

            'allow_draws' =>
            true,

            'win_points' =>
            1,

            'draw_points' =>
            0.5,

            'loss_points' =>
            0,

            'default_best_of' =>
            $phaseTemplate->best_of
                ?: 1,

            'bye_policy' =>
            $phaseTemplate->allow_byes
                ? 'LOWEST_STANDING_WITHOUT_BYE'
                : 'DISABLED',

            'bye_points' =>
            1,

            'max_byes_per_participant' =>
            1,

            'initial_pairing_score_mode' =>
            'ZERO',

            'acceleration_mode' =>
            'NONE',

            'acceleration_rounds' =>
            null,

            'acceleration_seed_count' =>
            null,

            'acceleration_virtual_points' =>
            null,

            'cutoff_tie_policy' =>
            'USE_TIEBREAKERS',

            'fallback_policy' =>
            'FINAL_RANKING',
        ];
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
