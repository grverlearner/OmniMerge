<?php

namespace App\Services\Universes;

use App\Models\Universe;
use App\Models\UniverseSeason;
use Illuminate\Support\Facades\DB;

class UniverseSeasonService
{
    /*
    |--------------------------------------------------------------------------
    | Numeración correlativa
    |--------------------------------------------------------------------------
    */

    public function nextNumber(
        Universe $universe
    ): int {

        return (
            (int)
            UniverseSeason::withTrashed()
                ->where(
                    'universe_id',
                    $universe->id
                )
                ->max('number')
        )
            +
            1;
    }

    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    */

    public function create(
        Universe $universe,
        array $data
    ): UniverseSeason {

        return DB::transaction(
            function () use (
                $universe,
                $data
            ) {

                $data['number'] =
                    $this->nextNumber(
                        $universe
                    );

                $season =
                    $universe
                    ->seasons()
                    ->create($data);

                if (
                    $season->status === 'ACTIVE'
                ) {

                    $this->demoteOtherActiveSeasons(
                        $universe,
                        $season->id
                    );
                }

                return $season;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function update(
        UniverseSeason $season,
        array $data
    ): UniverseSeason {

        return DB::transaction(
            function () use (
                $season,
                $data
            ) {

                $season->update($data);

                if (
                    $season->status === 'ACTIVE'
                ) {

                    $this->demoteOtherActiveSeasons(
                        $season->universe,
                        $season->id
                    );
                }

                return $season->fresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Activar
    |--------------------------------------------------------------------------
    |
    | Un Universo solo puede tener una temporada en curso. Activar
    | una temporada finaliza la anterior.
    |
    */

    public function activate(
        UniverseSeason $season
    ): void {

        DB::transaction(
            function () use ($season) {

                $this->demoteOtherActiveSeasons(
                    $season->universe,
                    $season->id
                );

                $season->update([

                    'status' =>
                    'ACTIVE',
                ]);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Finalizar
    |--------------------------------------------------------------------------
    */

    public function complete(
        UniverseSeason $season
    ): void {

        $season->update([

            'status' =>
            'COMPLETED',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Archivar
    |--------------------------------------------------------------------------
    */

    public function archive(
        UniverseSeason $season
    ): void {

        $season->update([

            'status' =>
            'ARCHIVED',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    public function delete(
        UniverseSeason $season
    ): void {

        $season->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Regla de temporada única en curso
    |--------------------------------------------------------------------------
    */

    private function demoteOtherActiveSeasons(
        Universe $universe,
        int $keepSeasonId
    ): void {

        $universe
            ->seasons()
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereKeyNot(
                $keepSeasonId
            )
            ->update([

                'status' =>
                'COMPLETED',
            ]);
    }
}
