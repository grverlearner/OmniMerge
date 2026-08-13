<?php

namespace App\Services\Tournaments\Swiss;

use App\Models\PhaseSwissTiebreaker;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;

class SwissTiebreakerService
{
    public function create(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseSwissTiebreaker {
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

                $data['sort_order'] =
                    (
                        (int)
                        $phaseTemplate
                            ->swissTiebreakers()
                            ->max(
                                'sort_order'
                            )
                    )
                    +
                    10;

                return $phaseTemplate
                    ->swissTiebreakers()
                    ->create(
                        $data
                    );
            }
        );
    }

    public function update(
        PhaseSwissTiebreaker $tiebreaker,
        array $data
    ): PhaseSwissTiebreaker {
        $tiebreaker->update(
            $data
        );

        return $tiebreaker->fresh();
    }

    public function delete(
        PhaseSwissTiebreaker $tiebreaker
    ): void {
        $tiebreaker->delete();
    }

    public function move(
        PhaseTemplate $phaseTemplate,
        PhaseSwissTiebreaker $tiebreaker,
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
                    ->swissTiebreakers()
                    ->lockForUpdate()
                    ->get();

                $index =
                    $items->search(
                        fn($item) =>
                        $item->id
                            ===
                            $tiebreaker->id
                    );

                if ($index === false) {
                    return;
                }

                $targetIndex =
                    $direction
                    ===
                    'UP'
                    ? $index - 1
                    : $index + 1;

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
                    $items[$index];

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
