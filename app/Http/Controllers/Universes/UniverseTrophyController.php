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

    public function store(
        Request $request,
        Universe $universe
    ): RedirectResponse {

        $this->authorize('update', $universe);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:16'],
            'tier' => ['required', Rule::in(array_keys(UniverseTrophy::TIERS))],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {

            $data['image'] =
                $request->file('image')
                ->store('universes/trophies', 'public');
        }

        $universe->trophies()->create($data);

        return back()->with(
            'success',
            'Trofeo creado. Ya puedes asignarlo a una recompensa de torneo.'
        );
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
