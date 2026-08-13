<?php

namespace App\Services\Tournaments\RoundRobin;

use App\Models\PhaseRoundRobinTiebreaker;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;

class RoundRobinTiebreakerService
{
    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    */

    public function create(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseRoundRobinTiebreaker {
        $this->ensureCorrectType(
            $phaseTemplate
        );

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
                            ->roundRobinTiebreakers()
                            ->max(
                                'sort_order'
                            )
                    )
                    +
                    10;

                $data['sort_order'] =
                    $sortOrder;

                return $phaseTemplate
                    ->roundRobinTiebreakers()
                    ->create(
                        $data
                    );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function update(
        PhaseRoundRobinTiebreaker $tiebreaker,
        array $data
    ): PhaseRoundRobinTiebreaker {
        $tiebreaker->update(
            $data
        );

        return $tiebreaker->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    public function delete(
        PhaseRoundRobinTiebreaker $tiebreaker
    ): void {
        $tiebreaker->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Mover
    |--------------------------------------------------------------------------
    */

    public function move(
        PhaseTemplate $phaseTemplate,
        PhaseRoundRobinTiebreaker $tiebreaker,
        string $direction
    ): void {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        DB::transaction(
            function () use (
                $phaseTemplate,
                $tiebreaker,
                $direction
            ) {
                $items =
                    $phaseTemplate
                    ->roundRobinTiebreakers()
                    ->lockForUpdate()
                    ->get();

                $currentIndex =
                    $items->search(
                        fn($item) =>
                        $item->id
                            ===
                            $tiebreaker->id
                    );

                if (
                    $currentIndex === false
                ) {
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

                $targetOrder =
                    $target->sort_order;

                $current->update([
                    'sort_order' =>
                    $targetOrder,
                ]);

                $target->update([
                    'sort_order' =>
                    $currentOrder,
                ]);
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
