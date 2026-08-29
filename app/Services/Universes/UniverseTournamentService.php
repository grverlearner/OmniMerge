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

        $rewards = $this->takeRewards($data);

        $tournament = $universe
            ->universeTournaments()
            ->create($data);

        $this->syncRewards($tournament, $rewards);

        return $tournament;
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

        $rewards = $this->takeRewards($data);

        $universeTournament->update($data);

        $this->syncRewards($universeTournament, $rewards);

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
    /*
     * Saca los premios del paquete de datos.
     *
     * Vienen en el mismo formulario que el torneo -al crearlo todavia no
     * hay fila a la que colgarlos- pero no son columnas suyas, asi que hay
     * que apartarlos antes de que Eloquent intente guardarlos.
     *
     * `null` significa "el formulario no hablo de premios", que no es lo
     * mismo que "no hay ninguno": un formulario parcial no deberia borrar
     * lo que no menciona.
     */
    private function takeRewards(array &$data): ?array
    {
        if (! array_key_exists('rewards', $data)) {
            return null;
        }

        $rewards = $data['rewards'];

        unset($data['rewards']);

        return is_array($rewards) ? $rewards : [];
    }

    /*
     * Deja los premios del torneo exactamente como llegaron.
     *
     * Se borran y se reescriben en vez de casarlos uno a uno: no tienen
     * identidad propia -nadie enlaza a "el premio numero 3"- y el orden en
     * que se escribieron ES su orden. Casarlos por id complicaria el codigo
     * para conservar algo que no significa nada.
     *
     * Lo que si tiene identidad son los TROFEOS, y esos no se tocan: viven
     * en el universo y sobreviven a cualquier cambio aqui.
     */
    private function syncRewards(UniverseTournament $tournament, ?array $rewards): void
    {
        if ($rewards === null) {
            return;
        }

        $tournament->rewards()->delete();

        foreach ($rewards as $reward) {
            $tournament->rewards()->create([
                ...$reward,
                'game_key' => $tournament->game_key,
                'is_active' => true,
            ]);
        }
    }

}
