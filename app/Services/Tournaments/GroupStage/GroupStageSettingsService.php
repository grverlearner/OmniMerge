<?php

namespace App\Services\Tournaments\GroupStage;

use App\Models\PhaseGroupStageGroup;
use App\Models\PhaseGroupStageSetting;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;

class GroupStageSettingsService
{
    public function __construct(
        private readonly
        GroupStageDefinitionService $definitionService
    ) {}

    public function ensure(
        PhaseTemplate $phaseTemplate
    ): PhaseGroupStageSetting {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        return DB::transaction(
            function () use (
                $phaseTemplate
            ) {
                $setting =
                    $phaseTemplate
                    ->groupStageSetting()
                    ->firstOrCreate(
                        [],
                        [
                            'group_count_mode' =>
                            'FIXED_GROUP_COUNT',

                            'group_count' =>
                            4,

                            'target_group_size' =>
                            4,

                            'min_group_size' =>
                            2,

                            'max_group_size' =>
                            8,

                            'remainder_policy' =>
                            'BALANCED',

                            'distribution_mode' =>
                            'SNAKE_SEEDED',

                            'pot_count' =>
                            null,

                            'internal_engine_type' =>
                            'ROUND_ROBIN',

                            'internal_cycles' =>
                            1,

                            'internal_schedule_mode' =>
                            'BALANCED',

                            'internal_allow_draws' =>
                            true,

                            'internal_win_points' =>
                            3,

                            'internal_draw_points' =>
                            1,

                            'internal_loss_points' =>
                            0,

                            'internal_best_of' =>
                            $phaseTemplate->best_of
                                ?: 1,

                            'cross_group_normalization' =>
                            'RAW',

                            'cutoff_tie_policy' =>
                            'USE_TIEBREAKERS',

                            'completion_mode' =>
                            'ALL_GROUPS_COMPLETE',
                        ]
                    );

                $this->syncGroupsForMode(
                    $phaseTemplate,
                    $setting
                );

                if (
                    $setting->wasRecentlyCreated
                    &&
                    ! $phaseTemplate
                        ->groupStageTiebreakers()
                        ->exists()
                ) {
                    $sortOrder = 10;

                    foreach (
                        $this
                            ->definitionService
                            ->defaultCrossGroupCriteria()
                        as
                        $definition
                    ) {
                        $phaseTemplate
                            ->groupStageTiebreakers()
                            ->create([
                                'criterion' =>
                                $definition['criterion'],

                                'normalization' =>
                                $definition['normalization'],

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
    ): PhaseGroupStageSetting {
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
                    ->groupStageSetting()
                    ->firstOrCreate(
                        [],
                        [
                            'group_count_mode' =>
                            'FIXED_GROUP_COUNT',

                            'group_count' =>
                            4,

                            'target_group_size' =>
                            4,

                            'min_group_size' =>
                            2,

                            'max_group_size' =>
                            8,

                            'remainder_policy' =>
                            'BALANCED',

                            'distribution_mode' =>
                            'SNAKE_SEEDED',

                            'internal_engine_type' =>
                            'ROUND_ROBIN',

                            'internal_cycles' =>
                            1,

                            'internal_schedule_mode' =>
                            'BALANCED',

                            'internal_allow_draws' =>
                            true,

                            'internal_win_points' =>
                            3,

                            'internal_draw_points' =>
                            1,

                            'internal_loss_points' =>
                            0,

                            'internal_best_of' =>
                            $lockedPhase->best_of
                                ?: 1,

                            'cross_group_normalization' =>
                            'RAW',

                            'cutoff_tie_policy' =>
                            'USE_TIEBREAKERS',

                            'completion_mode' =>
                            'ALL_GROUPS_COMPLETE',
                        ]
                    );

                $data['completion_mode'] =
                    'ALL_GROUPS_COMPLETE';

                $data['internal_engine_type'] =
                    'ROUND_ROBIN';

                $data['internal_schedule_mode'] =
                    'BALANCED';

                $setting->update(
                    $data
                );

                /*
                |--------------------------------------------------------------------------
                | Compatibilidad con la propiedad general de PhaseTemplate
                |--------------------------------------------------------------------------
                */

                $lockedPhase->update([
                    'best_of' =>
                    $data['internal_best_of'],
                ]);

                $this->syncGroupsForMode(
                    $lockedPhase,
                    $setting->fresh()
                );

                return $setting->fresh();
            }
        );
    }

    private function syncGroupsForMode(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageSetting $setting
    ): void {
        if (
            $setting->group_count_mode
            ===
            'FIXED_GROUP_COUNT'
        ) {
            $count =
                max(
                    2,
                    (int)
                    $setting->group_count
                );

            for (
                $sequence = 1;
                $sequence <= $count;
                $sequence++
            ) {
                $group =
                    $phaseTemplate
                    ->groupStageGroups()
                    ->where(
                        'sequence_number',
                        $sequence
                    )
                    ->first();

                if (! $group) {
                    $group =
                        $phaseTemplate
                        ->groupStageGroups()
                        ->create([
                            'sequence_number' =>
                            $sequence,

                            'code' =>
                            PhaseGroupStageGroup::formatCode(
                                $sequence
                            ),

                            'name' =>
                            $this->defaultGroupName(
                                $sequence
                            ),

                            'capacity' =>
                            null,

                            'is_active' =>
                            true,

                            'sort_order' =>
                            $sequence * 10,
                        ]);
                } elseif (
                    ! $group->is_active
                ) {
                    $group->update([
                        'is_active' =>
                        true,
                    ]);
                }
            }

            $phaseTemplate
                ->groupStageGroups()
                ->where(
                    'sequence_number',
                    '>',
                    $count
                )
                ->update([
                    'is_active' =>
                    false,
                ]);

            return;
        }

        if (
            $setting->group_count_mode
            ===
            'TARGET_GROUP_SIZE'
        ) {
            /*
             * La cantidad real depende del número
             * utilizado durante la ejecución.
             */

            $phaseTemplate
                ->groupStageGroups()
                ->update([
                    'is_active' =>
                    false,
                ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CUSTOM_GROUPS
        |--------------------------------------------------------------------------
        */

        $activeCount =
            $phaseTemplate
            ->groupStageGroups()
            ->where(
                'is_active',
                true
            )
            ->count();

        if ($activeCount >= 2) {
            return;
        }

        $existing =
            $phaseTemplate
            ->groupStageGroups()
            ->orderBy(
                'sequence_number'
            )
            ->get();

        foreach (
            $existing
            as
            $group
        ) {
            if ($activeCount >= 2) {
                break;
            }

            if (! $group->is_active) {
                $group->update([
                    'is_active' =>
                    true,
                ]);

                $activeCount++;
            }
        }

        while ($activeCount < 2) {
            $sequence =
                (
                    (int)
                    $phaseTemplate
                        ->groupStageGroups()
                        ->max(
                            'sequence_number'
                        )
                )
                +
                1;

            $phaseTemplate
                ->groupStageGroups()
                ->create([
                    'sequence_number' =>
                    $sequence,

                    'code' =>
                    PhaseGroupStageGroup::formatCode(
                        $sequence
                    ),

                    'name' =>
                    $this->defaultGroupName(
                        $sequence
                    ),

                    'capacity' =>
                    2,

                    'is_active' =>
                    true,

                    'sort_order' =>
                    $sequence * 10,
                ]);

            $activeCount++;
        }
    }

    private function defaultGroupName(
        int $sequence
    ): string {
        return 'Grupo '
            .
            $this->alphabeticLabel(
                $sequence
            );
    }

    private function alphabeticLabel(
        int $number
    ): string {
        $label = '';

        while ($number > 0) {
            $number--;

            $label =
                chr(
                    65
                        +
                        ($number % 26)
                )
                .
                $label;

            $number =
                intdiv(
                    $number,
                    26
                );
        }

        return $label;
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
