<?php

namespace App\Services\Universes;

use App\Models\Universe;
use App\Models\UniverseTournament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
        array $data,
        ?UploadedFile $image = null
    ): UniverseTournament {

        if ($image) {
            $data['image'] = $image->store('universe-tournaments', 'public');
        }

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
        array $data,
        ?UploadedFile $image = null
    ): UniverseTournament {

        $old = $universeTournament->image;

        if ($image) {
            $data['image'] = $image->store('universe-tournaments', 'public');
        }

        $universeTournament->update($data);

        /*
         * La portada anterior se borra solo despues de guardar bien.
         */
        if ($image && $old) {
            Storage::disk('public')->delete($old);
        }

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
