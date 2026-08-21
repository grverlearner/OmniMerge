<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Universes\StoreUniverseRequest;
use App\Http\Requests\Universes\UpdateUniverseRequest;
use App\Models\Universe;
use App\Services\Universes\UniverseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;


class UniverseController extends Controller
{
    public function __construct(
        private readonly
        UniverseService $service
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
                'competitors',
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
         * Resumen del Universo: cifras de sus tres contenidos
         * y los últimos elementos de cada uno.
         */
        $statistics = [

            'competitors' =>
            $universe
                ->competitors()
                ->count(),

            'active_competitors' =>
            $universe
                ->competitors()
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'seasons' =>
            $universe
                ->seasons()
                ->count(),

            'tournaments' =>
            $universe
                ->universeTournaments()
                ->count(),
        ];

        $activeSeason =
            $universe->activeSeason();

        $recentCompetitors =
            $universe
            ->competitors()
            ->with('entity')
            ->latest()
            ->limit(6)
            ->get();

        $recentTournaments =
            $universe
            ->universeTournaments()
            ->with('tournamentTemplate')
            ->latest()
            ->limit(4)
            ->get();

        return view(
            'universes.show',
            compact(
                'universe',
                'statistics',
                'activeSeason',
                'recentCompetitors',
                'recentTournaments'
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
