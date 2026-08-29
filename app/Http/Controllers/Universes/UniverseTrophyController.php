<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Models\Universe;
use App\Models\UniverseTrophy;
use App\Models\UniverseTrophyAward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| UniverseTrophyController
|--------------------------------------------------------------------------
|
| La vitrina del Universo: qué trofeos existen y quién los tiene.
|
| Un trofeo pertenece al mundo. La Entity de la Biblioteca no sabe nada
| de esto, igual que no sabe de estadísticas ni de historial.
|
*/

class UniverseTrophyController extends Controller
{
    public function index(
        Universe $universe
    ): View {

        $this->authorize('view', $universe);

        $trophies =
            $universe->trophies()
            ->withCount('awards')
            ->orderBy('name')
            ->get();

        /* La vitrina: lo último conquistado en este mundo */
        $recentAwards =
            UniverseTrophyAward::query()
            ->where('universe_id', $universe->id)
            ->with([
                'trophy',
                'universeEntity',
                'tournamentInstance',
                'season',
            ])
            ->orderByDesc('awarded_at')
            ->limit(12)
            ->get();

        return view(
            'universes.trophies.index',
            compact('universe', 'trophies', 'recentAwards')
        );
    }

    /*
     * Sin tipo de retorno fijo: devuelve una redireccion desde su pantalla,
     * y JSON cuando lo llama el disenador de un torneo sin recargar.
     */
    public function store(
        Request $request,
        Universe $universe
    ) {

        $this->authorize('update', $universe);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:16'],
            'tier' => ['required', Rule::in(array_keys(UniverseTrophy::TIERS))],
            'image' => ['nullable', 'image', 'max:2048'],

            /*
             * Para que edicion se inventa.
             *
             * Vacio = trofeo del universo, visible para todos sus torneos.
             * Con valor = nace dentro de una edicion concreta y solo ella
             * puede corregirlo. Es lo que permite crear un trofeo de
             * aniversario sin ensuciar la vitrina permanente.
             */
            'tournament_instance_id' => [
                'nullable',
                'integer',
                Rule::exists('tournament_instances', 'id')
                    ->where('universe_id', $universe->id)
                    ->whereNull('deleted_at'),
            ],
        ]);

        if ($request->hasFile('image')) {

            $data['image'] =
                $request->file('image')
                ->store('universes/trophies', 'public');
        }

        $trophy = $universe->trophies()->create($data);

        /*
         * Desde el disenador de un torneo esto se llama sin recargar: un
         * trofeo se crea ahi mismo, en medio de configurar los premios, y
         * mandar al usuario a otra pantalla en ese momento le haria perder
         * todo lo que llevaba escrito.
         */
        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'trophy' => $this->asJson($trophy),
            ]);
        }

        return back()->with(
            'success',
            'Trofeo creado. Ya puedes asignarlo a una recompensa de torneo.'
        );
    }

    /*
     * Editar un trofeo.
     *
     * No existia: un trofeo se creaba y se borraba, pero no se corregia.
     * Cambiar una imagen obligaba a borrarlo, y borrarlo esta prohibido en
     * cuanto alguien lo ha ganado, asi que un trofeo con la foto mal puesta
     * se quedaba asi para siempre.
     */
    public function update(
        Request $request,
        Universe $universe,
        UniverseTrophy $trophy
    ) {
        $this->authorize('update', $universe);

        abort_unless($trophy->universe_id === $universe->id, 404);

        /*
         * Desde dentro de una edicion solo se tocan LOS SUYOS.
         *
         * Un trofeo del torneo lo heredan todas sus ediciones: corregirlo
         * desde una sola las cambiaria todas, incluidas las que ya se
         * jugaron y ya lo entregaron. Se dice, y no se hace en silencio.
         */
        $scope = (int) $request->input('tournament_instance_id');

        if ($scope > 0 && (int) $trophy->tournament_instance_id !== $scope) {

            $mensaje = 'Este trofeo es del torneo, no de esta edición: '
                . 'se hereda tal cual. Para uno propio, crea uno nuevo aquí.';

            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => $mensaje], 422)
                : back()->with('error', $mensaje);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:16'],
            'tier' => ['required', Rule::in(array_keys(UniverseTrophy::TIERS))],
            'image' => ['nullable', 'image', 'max:2048'],
            'tournament_instance_id' => ['nullable', 'integer'],
        ]);

        /* El ambito no se cambia al editar: se decidio al crearlo */
        unset($data['tournament_instance_id']);

        if ($request->hasFile('image')) {

            /* La anterior se borra: dejarla seria basura que nadie mira */
            $previous = $trophy->image;

            $data['image'] = $request->file('image')
                ->store('universes/trophies', 'public');

            if ($previous) {
                Storage::disk('public')->delete($previous);
            }
        } else {
            unset($data['image']);
        }

        $trophy->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'trophy' => $this->asJson($trophy->fresh()),
            ]);
        }

        return back()->with('success', 'Trofeo actualizado.');
    }

    /*
     * Un trofeo como lo espera la pantalla que lo acaba de crear.
     */
    private function asJson(UniverseTrophy $trophy): array
    {
        return [
            'id' => $trophy->id,
            'name' => $trophy->name,
            'description' => $trophy->description,
            'icon' => $trophy->icon,
            'tier' => $trophy->tier,
            'tier_label' => UniverseTrophy::TIERS[$trophy->tier] ?? $trophy->tier,
            'image_url' => $trophy->image_url,

            /* Si es de una edicion concreta, y por tanto editable ahi */
            'own' => $trophy->tournament_instance_id !== null,
            'tournament_instance_id' => $trophy->tournament_instance_id,
        ];
    }

    public function destroy(
        Universe $universe,
        UniverseTrophy $trophy
    ): RedirectResponse {

        $this->authorize('update', $universe);

        abort_unless(
            $trophy->universe_id === $universe->id,
            404
        );

        $awarded = $trophy->awards()->count();

        if ($awarded > 0) {

            return back()->withErrors([
                'trophy' =>
                'No se puede borrar: ' . $awarded . ' competidor(es) ya lo '
                    . 'ganaron y borrarlo les quitaría un título de su palmarés.',
            ]);
        }

        if ($trophy->image) {
            Storage::disk('public')->delete($trophy->image);
        }

        $trophy->delete();

        return back()->with('success', 'Trofeo eliminado.');
    }
}
