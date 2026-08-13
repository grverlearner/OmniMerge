<?php

namespace App\Services\Tournaments;

use App\Models\PhaseExit;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;

class PhaseExitService
{
    public function create(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseExit {
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
                    $this->nextSequence(
                        $phaseTemplate
                    );

                $sortOrder =
                    (
                        (int)
                        PhaseExit::withTrashed()
                            ->where(
                                'phase_template_id',
                                $phaseTemplate->id
                            )
                            ->max(
                                'sort_order'
                            )
                    )
                    +
                    10;

                $data =
                    $this->normalizeSelector(
                        $data
                    );

                $data['sequence_number'] =
                    $sequence;

                $data['code'] =
                    PhaseExit::formatCode(
                        $sequence
                    );

                $data['sort_order'] =
                    $sortOrder;

                return $phaseTemplate
                    ->exits()
                    ->create(
                        $data
                    );
            }
        );
    }

    public function update(
        PhaseExit $phaseExit,
        array $data
    ): PhaseExit {
        $data =
            $this->normalizeSelector(
                $data
            );

        $phaseExit->update(
            $data
        );

        return $phaseExit->fresh();
    }

    public function delete(
        PhaseExit $phaseExit
    ): void {
        $phaseExit->delete();
    }

    private function nextSequence(
        PhaseTemplate $phaseTemplate
    ): int {
        return (
            (int)
            PhaseExit::withTrashed()
                ->where(
                    'phase_template_id',
                    $phaseTemplate->id
                )
                ->max(
                    'sequence_number'
                )
        )
            +
            1;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalización
    |--------------------------------------------------------------------------
    */

    private function normalizeSelector(
        array $data
    ): array {
        $type =
            $data['selector_type'];

        /*
        |--------------------------------------------------------------------------
        | selector_from
        |--------------------------------------------------------------------------
        */

        if (
            ! in_array(
                $type,
                [
                    'TOP_N',
                    'BOTTOM_N',
                    'RANK_POSITION',
                    'RANK_RANGE',
                ],
                true
            )
        ) {
            $data['selector_from'] =
                null;
        }

        /*
        |--------------------------------------------------------------------------
        | selector_to
        |--------------------------------------------------------------------------
        */

        if (
            $type
            !==
            'RANK_RANGE'
        ) {
            $data['selector_to'] =
                null;
        }

        /*
        |--------------------------------------------------------------------------
        | Ronda
        |--------------------------------------------------------------------------
        */

        if (
            $type
            !==
            'ELIMINATED_IN_ROUND'
        ) {
            $data['selector_round_size'] =
                null;
        }

        /*
        |--------------------------------------------------------------------------
        | Timing obligatorio
        |--------------------------------------------------------------------------
        */

        if (
            $type
            ===
            'ELIMINATED_IN_ROUND'
        ) {
            $data['exit_timing'] =
                'ON_ELIMINATION';
        }

        if (
            in_array(
                $type,
                [
                    'SURVIVORS',
                    'ELIMINATED',
                ],
                true
            )
        ) {
            $data['exit_timing'] =
                'PHASE_END';
        }

        return $data;
    }
}
