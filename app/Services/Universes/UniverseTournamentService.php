<?php

namespace App\Services\Universes;

use App\Models\Universe;
use App\Models\UniverseTournament;

class UniverseTournamentService
{
    /*
    |--------------------------------------------------------------------------
    | Adoptar una plantilla
    |--------------------------------------------------------------------------
    |
    | La TournamentTemplate no se copia ni se modifica: sigue siendo
    | un diseño reutilizable de la Biblioteca de Torneos. Este registro
    | solo describe cómo la usa este Universo.
    |
    */

    public function create(
        Universe $universe,
        array $data
    ): UniverseTournament {

        return $universe
            ->universeTournaments()
            ->create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function update(
        UniverseTournament $universeTournament,
        array $data
    ): UniverseTournament {

        $universeTournament->update(
            $data
        );

        return $universeTournament->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Archivar
    |--------------------------------------------------------------------------
    */

    public function archive(
        UniverseTournament $universeTournament
    ): void {

        $universeTournament->update([

            'status' =>
            'ARCHIVED',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    |
    | Soft Delete de la adopción. La plantilla original permanece
    | intacta en la Biblioteca de Torneos.
    |
    */

    public function delete(
        UniverseTournament $universeTournament
    ): void {

        $universeTournament->delete();
    }
}
