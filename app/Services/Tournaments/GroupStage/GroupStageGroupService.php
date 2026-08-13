<?php

namespace App\Services\Tournaments\GroupStage;

use App\Models\PhaseGroupStageGroup;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GroupStageGroupService
{
    public function create(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseGroupStageGroup {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        $setting =
            $phaseTemplate
            ->groupStageSetting()
            ->firstOrFail();

        if (
            $setting->group_count_mode
            !==
            'CUSTOM_GROUPS'
        ) {
            throw ValidationException::withMessages([
                'group' =>
                'Solo puedes agregar grupos manualmente cuando el modo es Grupos personalizados.',
            ]);
        }

        return DB::transaction(
            function () use (
                $phaseTemplate,
                $data
            ) {
                PhaseTemplate::query()
                    ->whereKey(
                        $phaseTemplate->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

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

                return $phaseTemplate
                    ->groupStageGroups()
                    ->create([
                        'sequence_number' =>
                        $sequence,

                        'code' =>
                        PhaseGroupStageGroup::formatCode(
                            $sequence
                        ),

                        'name' =>
                        $data['name'],

                        'capacity' =>
                        $data['capacity']
                            ?? null,

                        'is_active' =>
                        true,

                        'sort_order' =>
                        $sequence * 10,
                    ]);
            }
        );
    }

    public function update(
        PhaseGroupStageGroup $group,
        array $data
    ): PhaseGroupStageGroup {
        $group->update([
            'name' =>
            $data['name'],

            'capacity' =>
            $data['capacity']
                ?? null,
        ]);

        return $group->fresh();
    }

    public function delete(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageGroup $group
    ): void {
        $setting =
            $phaseTemplate
            ->groupStageSetting()
            ->firstOrFail();

        if (
            $setting->group_count_mode
            !==
            'CUSTOM_GROUPS'
        ) {
            throw ValidationException::withMessages([
                'group' =>
                'Solo puedes eliminar grupos en el modo Grupos personalizados.',
            ]);
        }

        $activeCount =
            $phaseTemplate
            ->groupStageGroups()
            ->where(
                'is_active',
                true
            )
            ->count();

        if ($activeCount <= 2) {
            throw ValidationException::withMessages([
                'group' =>
                'Una Fase de grupos debe conservar al menos 2 grupos.',
            ]);
        }

        if (
            $group
            ->advancementRules()
            ->exists()
        ) {
            throw ValidationException::withMessages([
                'group' =>
                'Este grupo está utilizado por una regla de avance. Modifica o elimina esa regla antes.',
            ]);
        }

        $group->delete();
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
