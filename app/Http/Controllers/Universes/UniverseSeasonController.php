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
    | Show
    |--------------------------------------------------------------------------
    |
    | Que paso en esta temporada: sus competiciones, sus campeones y sus
    | cifras. Es lo que convierte una temporada en una etapa del mundo y
    | no en una fila de una tabla.
    |
    */

    public function show(
        Universe $universe,
        UniverseSeason $season
    ): View {

        $this->authorize('view', $universe);

        $competitions =
            $season
            ->competitions()
            ->with('universeTournament')
            ->orderByDesc('started_at')
            ->get();

        $champions =
            \App\Models\TournamentInstanceParticipant::query()
            ->whereIn(
                'tournament_instance_id',
                $competitions->pluck('id')
            )
            ->where('outcome', 'CHAMPION')
            ->with('universeEntity')
            ->get()
            ->keyBy('tournament_instance_id');

        $statistics = [

            'competitions' =>
            $competitions->count(),

            'completed' =>
            $competitions->where('status', 'COMPLETED')->count(),

            'participants' =>
            (int) $competitions->sum('participant_count'),
        ];

        /*
         * Torneos del Universo que, por su recurrencia, tocan en esta
         * temporada. Ayuda a ver que falta por jugar.
         */
        $scheduled =
            $universe
            ->universeTournaments()
            ->get()
            ->filter(
                fn($tournament) =>
                $tournament->occursInSeason($season->number)
            );

        return view(
            'universes.seasons.show',
            compact(
                'universe',
                'season',
                'competitions',
                'champions',
                'statistics',
                'scheduled'
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
