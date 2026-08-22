<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Models\Universe;
use App\Services\Universes\UniverseRankingService;
use App\Support\Universes\UniverseSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| UniverseRankingController
|--------------------------------------------------------------------------
|
| Clasificación del Universo. Contextual por definición: la misma Entidad
| de Biblioteca puede ser #1 aquí y #18 en otro Universo.
|
*/

class UniverseRankingController extends Controller
{
    public function __construct(
        private readonly
        UniverseRankingService $ranking
    ) {}

    public function index(
        Request $request,
        Universe $universe
    ): View {

        $this->authorize('view', $universe);

        $seasonId =
            (int) $request->input('season', 0);

        $ranking =
            $this->ranking
            ->ranking(
                $universe,
                $seasonId ?: null
            );

        $seasons =
            $universe->seasons()->get();

        $settings =
            (new UniverseSettings($universe))->all();

        return view(
            'universes.ranking.index',
            compact(
                'universe',
                'ranking',
                'seasons',
                'seasonId',
                'settings'
            )
        );
    }

    /*
     * El sistema de puntos es lo único configurable que tiene efecto
     * real hoy, así que vive junto al ranking y no en un panel aparte.
     */
    public function updatePoints(
        Request $request,
        Universe $universe
    ): RedirectResponse {

        $this->authorize('update', $universe);

        $data = $request->validate([
            'points_champion' => ['required', 'integer', 'min:0', 'max:1000'],
            'points_win' => ['required', 'integer', 'min:0', 'max:1000'],
            'points_draw' => ['required', 'integer', 'min:0', 'max:1000'],
            'points_loss' => ['required', 'integer', 'min:0', 'max:1000'],
            'points_participation' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        (new UniverseSettings($universe))->save($data);

        return back()->with(
            'success',
            'Sistema de puntos actualizado. La clasificación se recalcula sola.'
        );
    }
}
