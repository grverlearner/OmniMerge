<?php

namespace App\Services\Tournaments\GroupStage;

use App\Models\PhaseGroupStageTiebreaker;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;

class GroupStageTiebreakerService
{
    public function create(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseGroupStageTiebreaker {
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

                $sortOrder =
                    (
                        (int)
                        $phaseTemplate
                            ->groupStageTiebreakers()
                            ->max(
                                'sort_order'
                            )
                    )
                    +
                    10;

                $data['sort_order'] =
                    $sortOrder;

                return $phaseTemplate
                    ->groupStageTiebreakers()
                    ->create(
                        $data
                    );
            }
        );
    }

    public function update(
        PhaseGroupStageTiebreaker $tiebreaker,
        array $data
    ): PhaseGroupStageTiebreaker {
        $tiebreaker->update(
            $data
        );

        return $tiebreaker->fresh();
    }

    public function delete(
        PhaseGroupStageTiebreaker $tiebreaker
    ): void {
        $tiebreaker->delete();
    }

    public function move(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageTiebreaker $tiebreaker,
        string $direction
    ): void {
        DB::transaction(
            function () use (
                $phaseTemplate,
                $tiebreaker,
                $direction
            ) {
                $items =
                    $phaseTemplate
                    ->groupStageTiebreakers()
                    ->lockForUpdate()
                    ->get();

                $currentIndex =
                    $items->search(
                        fn($item) =>
                        $item->id
                            ===
                            $tiebreaker->id
                    );

                if ($currentIndex === false) {
                    return;
                }

                $targetIndex =
                    $direction === 'UP'
                    ? $currentIndex - 1
                    : $currentIndex + 1;

                if (
                    $targetIndex < 0
                    ||
                    $targetIndex
                    >=
                    $items->count()
                ) {
                    return;
                }

                $current =
                    $items[$currentIndex];

                $target =
                    $items[$targetIndex];

                $currentOrder =
                    $current->sort_order;

                $current->update([
                    'sort_order' =>
                    $target->sort_order,
                ]);

                $target->update([
                    'sort_order' =>
                    $currentOrder,
                ]);
            }
        );
    }
}
