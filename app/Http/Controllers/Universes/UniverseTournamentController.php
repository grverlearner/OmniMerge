<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Universes\StoreUniverseTournamentRequest;
use App\Http\Requests\Universes\UpdateUniverseTournamentRequest;
use App\Models\TournamentTemplate;
use App\Models\Universe;
use App\Models\UniverseTournament;
use App\Services\Universes\UniverseTournamentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UniverseTournamentController extends Controller
{
    public function __construct(
        private readonly
        UniverseTournamentService $service
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

        $universeTournaments =
            $universe
            ->universeTournaments()
            ->with([
                'tournamentTemplate',
            ])
            ->latest()
            ->paginate(20);

        $statistics = [

            'total' =>
            $universe
                ->universeTournaments()
                ->count(),

            'active' =>
            $universe
                ->universeTournaments()
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'draft' =>
            $universe
                ->universeTournaments()
                ->where(
                    'status',
                    'DRAFT'
                )
                ->count(),
        ];

        return view(
            'universes.tournaments.index',
            compact(
                'universe',
                'universeTournaments',
                'statistics'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    |
    | El puente entre configuración y ejecución: desde aquí se lanza una
    | competición real y se ven las que ya se jugaron.
    |
    */

    public function show(
        Universe $universe,
        UniverseTournament $universeTournament
    ): View {

        $this->authorize(
            'view',
            $universe
        );

        $universeTournament->load(
            'tournamentTemplate'
        );

        $competitions =
            $universeTournament
            ->instances()
            ->with('season')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view(
            'universes.tournaments.show',
            compact(
                'universe',
                'universeTournament',
                'competitions'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    |
    | Se adopta una plantilla existente de la Biblioteca de Torneos.
    | La plantilla no se copia ni se modifica.
    |
    */

    public function create(
        Request $request,
        Universe $universe
    ): View {

        $this->authorize(
            'update',
            $universe
        );

        $templates =
            TournamentTemplate::query()
            ->ownedBy(
                $request->user()
            )
            ->withCount('graphNodes')
            ->orderBy('name')
            ->get();

        return view(
            'universes.tournaments.create',
            compact(
                'universe',
                'templates'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreUniverseTournamentRequest $request,
        Universe $universe
    ): RedirectResponse {

        $this->service
            ->create(
                $universe,

                $request->validated()
            );

        return redirect()
            ->route(
                'universes.tournaments.index',
                $universe
            )
            ->with(
                'success',
                'Torneo añadido al Universo.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        Universe $universe,
        UniverseTournament $universeTournament
    ): View {

        $this->authorize(
            'update',
            $universe
        );

        $universeTournament->load(
            'tournamentTemplate'
        );

        return view(
            'universes.tournaments.edit',
            compact(
                'universe',
                'universeTournament'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateUniverseTournamentRequest $request,
        Universe $universe,
        UniverseTournament $universeTournament
    ): RedirectResponse {

        $this->service
            ->update(
                $universeTournament,

                $request->validated()
            );

        return redirect()
            ->route(
                'universes.tournaments.index',
                $universe
            )
            ->with(
                'success',
                'Torneo actualizado correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Universe $universe,
        UniverseTournament $universeTournament
    ): RedirectResponse {

        $this->authorize(
            'update',
            $universe
        );

        $this->service
            ->delete($universeTournament);

        return redirect()
            ->route(
                'universes.tournaments.index',
                $universe
            )
            ->with(
                'success',
                'Torneo quitado del Universo. La plantilla sigue en tu Biblioteca de Torneos.'
            );
    }
}
