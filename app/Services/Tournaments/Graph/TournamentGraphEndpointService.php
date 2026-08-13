<?php

namespace App\Services\Tournaments\Graph;

use App\Models\TournamentStart;
use App\Models\TournamentTemplate;
use App\Models\TournamentTerminal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TournamentGraphEndpointService
{
    /*
    |--------------------------------------------------------------------------
    | START
    |--------------------------------------------------------------------------
    */

    public function createStart(
        TournamentTemplate $template,
        array $data
    ): TournamentStart {
        return DB::transaction(
            function () use (
                $template,
                $data
            ) {
                TournamentTemplate::query()
                    ->whereKey(
                        $template->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                $sequence =
                    (
                        (int)
                        $template
                            ->graphStarts()
                            ->max(
                                'sequence_number'
                            )
                    )
                    +
                    1;


                $data['sequence_number'] =
                    $sequence;

                $data['code'] =
                    TournamentStart::formatCode(
                        $sequence
                    );

                $data['x_position'] =
                    $data['x_position']
                    ??
                    80;

                $data['y_position'] =
                    $data['y_position']
                    ??
                    (
                        120
                        +
                        (($sequence - 1) * 170)
                    );


                return $template
                    ->graphStarts()
                    ->create(
                        $data
                    );
            }
        );
    }


    public function updateStart(
        TournamentStart $start,
        array $data
    ): TournamentStart {
        $start->update(
            $data
        );

        return $start->fresh();
    }


    public function deleteStart(
        TournamentStart $start
    ): void {
        $start->delete();
    }


    /*
    |--------------------------------------------------------------------------
    | TERMINAL
    |--------------------------------------------------------------------------
    */

    public function createTerminal(
        TournamentTemplate $template,
        array $data
    ): TournamentTerminal {
        return DB::transaction(
            function () use (
                $template,
                $data
            ) {
                TournamentTemplate::query()
                    ->whereKey(
                        $template->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                $sequence =
                    (
                        (int)
                        $template
                            ->graphTerminals()
                            ->max(
                                'sequence_number'
                            )
                    )
                    +
                    1;


                $data['sequence_number'] =
                    $sequence;

                $data['code'] =
                    TournamentTerminal::formatCode(
                        $sequence
                    );

                $data['x_position'] =
                    $data['x_position']
                    ??
                    1900;

                $data['y_position'] =
                    $data['y_position']
                    ??
                    (
                        120
                        +
                        (($sequence - 1) * 170)
                    );


                return $template
                    ->graphTerminals()
                    ->create(
                        $data
                    );
            }
        );
    }


    public function updateTerminal(
        TournamentTerminal $terminal,
        array $data
    ): TournamentTerminal {
        $terminal->update(
            $data
        );

        return $terminal->fresh();
    }


    public function deleteTerminal(
        TournamentTerminal $terminal
    ): void {
        $terminal->delete();
    }


    /*
    |--------------------------------------------------------------------------
    | POSITION
    |--------------------------------------------------------------------------
    */

    public function updatePosition(
        Model $endpoint,
        int $x,
        int $y
    ): Model {
        $endpoint->update([
            'x_position' =>
            max(
                0,
                $x
            ),

            'y_position' =>
            max(
                0,
                $y
            ),
        ]);

        return $endpoint->fresh();
    }
}
