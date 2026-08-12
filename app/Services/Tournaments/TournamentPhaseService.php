<?php

namespace App\Services\Tournaments;

use App\Models\TournamentPhase;
use App\Models\TournamentTemplate;
use Illuminate\Support\Facades\DB;


class TournamentPhaseService
{
    public function create(
        TournamentTemplate $template,
        array $data
    ): TournamentPhase {

        return DB::transaction(
            function () use (
                $template,
                $data
            ) {

                /*
                 * Bloqueamos la plantilla para evitar
                 * secuencias repetidas.
                 */
                TournamentTemplate::query()
                    ->whereKey(
                        $template->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                $sequence =
                    $this->nextSequence(
                        $template
                    );


                $sortOrder =
                    (
                        (int)
                        TournamentPhase::withTrashed()
                            ->where(
                                'tournament_template_id',
                                $template->id
                            )
                            ->max(
                                'sort_order'
                            )
                    )
                    +
                    10;


                $data['sequence_number'] =
                    $sequence;


                $data['code'] =
                    TournamentPhase::formatCode(
                        $sequence
                    );


                $data['sort_order'] =
                    $sortOrder;


                return $template
                    ->phases()
                    ->create(
                        $data
                    );
            }
        );
    }


    public function update(
        TournamentPhase $phase,
        array $data
    ): TournamentPhase {

        $phase->update(
            $data
        );


        return $phase->fresh();
    }


    public function delete(
        TournamentPhase $phase
    ): void {

        $phase->delete();
    }


    private function nextSequence(
        TournamentTemplate $template
    ): int {

        return (
                (int)
                TournamentPhase::withTrashed()
                    ->where(
                        'tournament_template_id',
                        $template->id
                    )
                    ->max(
                        'sequence_number'
                    )
            )
            +
            1;
    }
}
