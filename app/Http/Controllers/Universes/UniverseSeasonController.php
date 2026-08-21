<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Universes\StoreUniverseSeasonRequest;
use App\Http\Requests\Universes\UpdateUniverseSeasonRequest;
use App\Models\Universe;
use App\Models\UniverseSeason;
use App\Services\Universes\UniverseSeasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UniverseSeasonController extends Controller
{
    public function __construct(
        private readonly
        UniverseSeasonService $service
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(
        Universe $universe
    ): View {

        $this->authorize(
            'view',
            $universe
        );

        $seasons =
            $universe
            ->seasons()
            ->paginate(20);

        $statistics = [

            'total' =>
            $universe
                ->seasons()
                ->count(),

            'planned' =>
            $universe
                ->seasons()
                ->where(
                    'status',
                    'PLANNED'
                )
                ->count(),

            'completed' =>
            $universe
                ->seasons()
                ->where(
                    'status',
                    'COMPLETED'
                )
                ->count(),
        ];

        $activeSeason =
            $universe->activeSeason();

        return view(
            'universes.seasons.index',
            compact(
                'universe',
                'seasons',
                'statistics',
                'activeSeason'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(
        Universe $universe
    ): View {

        $this->authorize(
            'update',
            $universe
        );

        $nextNumber =
            $this->service
            ->nextNumber($universe);

        $activeSeason =
            $universe->activeSeason();

        return view(
            'universes.seasons.create',
            compact(
                'universe',
                'nextNumber',
                'activeSeason'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreUniverseSeasonRequest $request,
        Universe $universe
    ): RedirectResponse {

        $this->service
            ->create(
                $universe,

                $request->validated()
            );

        return redirect()
            ->route(
                'universes.seasons.index',
                $universe
            )
            ->with(
                'success',
                'Temporada creada correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        Universe $universe,
        UniverseSeason $season
    ): View {

        $this->authorize(
            'update',
            $universe
        );

        return view(
            'universes.seasons.edit',
            compact(
                'universe',
                'season'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateUniverseSeasonRequest $request,
        Universe $universe,
        UniverseSeason $season
    ): RedirectResponse {

        $this->service
            ->update(
                $season,

                $request->validated()
            );

        return redirect()
            ->route(
                'universes.seasons.index',
                $universe
            )
            ->with(
                'success',
                'Temporada actualizada correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Activar
    |--------------------------------------------------------------------------
    */

    public function activate(
        Universe $universe,
        UniverseSeason $season
    ): RedirectResponse {

        $this->authorize(
            'update',
            $universe
        );

        $this->service
            ->activate($season);

        return redirect()
            ->route(
                'universes.seasons.index',
                $universe
            )
            ->with(
                'success',
                "Temporada {$season->number} en curso."
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Finalizar
    |--------------------------------------------------------------------------
    */

    public function complete(
        Universe $universe,
        UniverseSeason $season
    ): RedirectResponse {

        $this->authorize(
            'update',
            $universe
        );

        $this->service
            ->complete($season);

        return redirect()
            ->route(
                'universes.seasons.index',
                $universe
            )
            ->with(
                'success',
                "Temporada {$season->number} finalizada."
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Archivar
    |--------------------------------------------------------------------------
    */

    public function archive(
        Universe $universe,
        UniverseSeason $season
    ): RedirectResponse {

        $this->authorize(
            'update',
            $universe
        );

        $this->service
            ->archive($season);

        return redirect()
            ->route(
                'universes.seasons.index',
                $universe
            )
            ->with(
                'success',
                "Temporada {$season->number} archivada."
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Universe $universe,
        UniverseSeason $season
    ): RedirectResponse {

        $this->authorize(
            'update',
            $universe
        );

        $this->service
            ->delete($season);

        return redirect()
            ->route(
                'universes.seasons.index',
                $universe
            )
            ->with(
                'success',
                'Temporada eliminada correctamente.'
            );
    }
}
