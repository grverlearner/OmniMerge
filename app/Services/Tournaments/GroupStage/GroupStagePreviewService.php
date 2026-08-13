<?php

namespace App\Services\Tournaments\GroupStage;

use App\Models\PhaseGroupStageSetting;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\RoundRobin\RoundRobinScheduleCalculator;
use Illuminate\Support\Collection;

class GroupStagePreviewService
{
    public function __construct(
        private readonly
        GroupStageAllocator $allocator,

        private readonly
        GroupStageAdvancementCalculator $advancementCalculator,

        private readonly
        RoundRobinScheduleCalculator $roundRobinCalculator
    ) {}

    public function preview(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageSetting $settings,
        Collection $groups,
        Collection $advancementRules,
        int $participants
    ): array {
        $allocation =
            $this
            ->allocator
            ->allocate(
                $phaseTemplate,
                $settings,
                $groups,
                $participants
            );

        if (! $allocation['valid']) {
            return $allocation;
        }

        $groupPreviews = [];

        $totalSeries = 0;
        $totalGroupRounds = 0;
        $parallelRoundWindows = 0;
        $totalRestAssignments = 0;

        foreach (
            $allocation['groups']
            as
            $group
        ) {
            $schedule =
                $this
                ->roundRobinCalculator
                ->calculateStructure(
                    $group['size'],
                    $settings->internal_cycles,
                    $settings->internal_best_of,
                    $settings->internal_allow_draws,
                    3
                );

            $totalSeries +=
                $schedule['total_series']
                ??
                0;

            $totalGroupRounds +=
                $schedule['total_rounds']
                ??
                0;

            $parallelRoundWindows =
                max(
                    $parallelRoundWindows,
                    $schedule['total_rounds']
                        ??
                        0
                );

            $totalRestAssignments +=
                $schedule['total_rest_assignments']
                ??
                0;

            $groupPreviews[] = [
                ...$group,

                'schedule' =>
                $schedule,
            ];
        }

        $advancement =
            $this
            ->advancementCalculator
            ->forecast(
                $allocation['groups'],
                $advancementRules,
                $settings->cutoff_tie_policy
            );

        return [
            ...$allocation,

            'groups' =>
            $groupPreviews,

            'total_series' =>
            $totalSeries,

            'total_group_rounds' =>
            $totalGroupRounds,

            'parallel_round_windows' =>
            $parallelRoundWindows,

            'total_rest_assignments' =>
            $totalRestAssignments,

            'advancement' =>
            $advancement,
        ];
    }
}
