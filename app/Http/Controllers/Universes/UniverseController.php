<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Universes\StoreUniverseRequest;
use App\Http\Requests\Universes\UpdateUniverseRequest;
use App\Models\Universe;
use App\Services\Universes\UniverseRankingService;
use App\Services\Universes\UniverseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;


class UniverseController extends Controller
{
    public function __construct(
        private readonly
        UniverseService $service,

        private readonly
        UniverseRankingService $ranking
    ) {}


    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */


    public function index(
        Request $request
    ): View {

        $this->authorize(
            'viewAny',
            Universe::class
        );

        $user =
            $request->user();

        $search =
            trim(
                (string)
                $request->input(
                    'search'
                )
            );

        $status =
            (string)
            $request->input(
                'status',
                ''
            );

        $sort =
            (string)
            $request->input(
                'sort',
                'newest'
            );

        $base =
            Universe::query()
            ->ownedBy(
                $user
            );

        $stats = [

            'total' => (clone $base)
                ->count(),

            'active' => (clone $base)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'draft' => (clone $base)
                ->where(
                    'status',
                    'DRAFT'
                )
                ->count(),
        ];

        $query =
            Universe::query()
            ->ownedBy(
                $user
            )
            ->withCount([
                'entities',
                'seasons',
                'universeTournaments',
            ])
            ->when(
                $search,

                fn($query) =>
                $query->where(
                    function ($subquery) use (
                        $search
                    ) {

                        $subquery
                            ->where(
                                'name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'code',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'description',
                                'like',
                                "%{$search}%"
                            );
                    }
                )
            )
            ->when(
                $status,

                fn($query) =>
                $query->where(
                    'status',
                    $status
                )
            );

        match ($sort) {

            'oldest' =>
            $query->orderBy(
                'created_at'
            ),

            'name_asc' =>
            $query->orderBy(
                'name'
            ),

            'name_desc' =>
            $query->orderByDesc(
                'name'
            ),

            'tournaments_desc' =>
            $query->orderByDesc(
                'universe_tournaments_count'
            ),

            default =>
            $query->orderByDesc(
                'created_at'
            ),
        };

        $universes =
            $query
            ->paginate(18)
            ->withQueryString();

        return view(
            'universes.index',
            compact(
                'universes',
                'stats',
                'search',
                'status',
                'sort'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */


    public function create(
        Request $request
    ): View {

        $this->authorize(
            'create',
            Universe::class
        );

        $previewCode =
            $this->service
            ->previewCode(
                $request->user()
            );

        return view(
            'universes.create',
            compact(
                'previewCode'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */


    public function store(
        StoreUniverseRequest $request
    ): RedirectResponse {

        $universe =
            $this->service
            ->create(

                $request->user(),

                $request->validated(),

                $request->file(
                    'image'
                )
            );

        return redirect()
            ->route(
                'universes.show',
                $universe
            )
            ->with(
                'success',
                'Universo creado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */


    public function show(
        Universe $universe
    ): View {

        $this->authorize(
            'view',
            $universe
        );

        /*
         * Panel de control del Universo (Fase 10): en una sola pantalla,
         * que temporada corre, quien manda, que se juega y que paso.
         */
        $statistics = [

            'entities' =>
            $universe->entities()->count(),

            'active_competitors' =>
            $universe->entities()->where('status', 'ACTIVE')->count(),

            'seasons' =>
            $universe->seasons()->count(),

            'tournaments' =>
            $universe->universeTournaments()->count(),

            'competitions' =>
            $universe->tournamentInstances()->count(),

            'competitions_running' =>
            $universe->tournamentInstances()
                ->whereIn('status', ['RUNNING', 'PAUSED'])
                ->count(),
        ];

        $activeSeason =
            $universe->activeSeason();

        $ranking =
            $this->ranking
            ->ranking($universe)
            ->take(5);

        $recentChampions =
            $this->ranking
            ->recentChampions($universe, 4);

        $activity =
            $universe
            ->activities()
            ->with('universeEntity')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $liveCompetitions =
            $universe
            ->tournamentInstances()
            ->whereIn('status', ['RUNNING', 'PAUSED'])
            ->with('universeTournament')
            ->orderByDesc('started_at')
            ->limit(4)
            ->get();

        /*
         * Que toca jugar en la temporada actual segun la recurrencia
         * configurada, descontando lo que ya se creo.
         */
        $alreadyPlayed =
            $activeSeason
            ? $universe->tournamentInstances()
                ->where('universe_season_id', $activeSeason->id)
                ->pluck('universe_tournament_id')
                ->all()
            : [];

        $upcoming =
            $activeSeason
            ? $universe->universeTournaments()
                ->where('status', 'ACTIVE')
                ->get()
                ->filter(
                    fn($tournament) =>
                    $tournament->occursInSeason($activeSeason->number)
                    && ! in_array($tournament->id, $alreadyPlayed, true)
                )
                ->values()
            : collect();

        return view(
            'universes.show',
            compact(
                'universe',
                'statistics',
                'activeSeason',
                'ranking',
                'recentChampions',
                'activity',
                'liveCompetitions',
                'upcoming'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */


    public function edit(
        Universe $universe
    ): View {

        $this->authorize(
            'update',
            $universe
        );

        return view(
            'universes.edit',
            compact(
                'universe'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */


    public function update(
        UpdateUniverseRequest $request,
        Universe $universe
    ): RedirectResponse {

        $this->service
            ->update(

                $universe,

                $request->validated(),

                $request->file(
                    'image'
                )
            );

        return redirect()
            ->route(
                'tournaments.universes.show',
                $universe
            )
            ->with(
                'success',
                'Universo actualizado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Archive
    |--------------------------------------------------------------------------
    */


    public function archive(
        Universe $universe
    ): RedirectResponse {

        $this->authorize(
            'update',
            $universe
        );

        $this->service
            ->archive(
                $universe
            );

        return redirect()
            ->route(
                'tournaments.universes.show',
                $universe
            )
            ->with(
                'success',
                'Universo archivado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */


    public function destroy(
        Universe $universe
    ): RedirectResponse {

        $this->authorize(
            'delete',
            $universe
        );

        $this->service
            ->delete(
                $universe
            );

        return redirect()
            ->route(
                'tournaments.universes.index'
            )
            ->with(
                'success',
                'Universo eliminado correctamente.'
            );
    }
}
