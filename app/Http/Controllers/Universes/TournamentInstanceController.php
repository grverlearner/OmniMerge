<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Universes\StoreTournamentInstanceRequest;
use App\Http\Requests\Universes\TournamentInstanceActionRequest;
use App\Models\TournamentInstance;
use App\Models\Universe;
use App\Models\UniverseCompetitor;
use App\Models\UniverseTournament;
use App\Services\Tournaments\CompetitionLab\CompetitionLabService;
use App\Services\Tournaments\Runtime\TournamentInstanceRuntimeService;
use App\Services\Tournaments\Runtime\TournamentInstanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| TournamentInstanceController
|--------------------------------------------------------------------------
|
| Competiciones reales de un Universo.
|
| El usuario las llama "Competiciones" para distinguirlas sin ambigüedad
| de las plantillas (diseño) y de los torneos configurados del Universo.
|
*/

class TournamentInstanceController extends Controller
{
    public function __construct(
        private readonly
        TournamentInstanceService $service,

        private readonly
        TournamentInstanceRuntimeService $runtime,

        private readonly
        CompetitionLabService $engine
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request,
        Universe $universe
    ): View {

        $this->authorize(
            'view',
            $universe
        );

        $status =
            (string)
            $request->input(
                'status',
                ''
            );

        $base =
            TournamentInstance::query()
            ->inUniverse($universe);

        $statistics = [

            'total' => (clone $base)
                ->count(),

            'running' => (clone $base)
                ->whereIn(
                    'status',
                    [
                        'RUNNING',
                        'PAUSED',
                    ]
                )
                ->count(),

            'completed' => (clone $base)
                ->where(
                    'status',
                    'COMPLETED'
                )
                ->count(),

            'draft' => (clone $base)
                ->where(
                    'status',
                    'DRAFT'
                )
                ->count(),
        ];

        $competitions =
            TournamentInstance::query()
            ->inUniverse($universe)
            ->with([
                'universeTournament',
                'season',
            ])
            ->when(
                $status,

                fn($query) =>
                $query->where(
                    'status',
                    $status
                )
            )
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view(
            'universes.competitions.index',
            compact(
                'universe',
                'competitions',
                'statistics',
                'status'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    |
    | Aquí todavía se lee la plantilla VIVA: el snapshot se congela al
    | guardar. Por eso también se valida el grafo antes de dejar crear.
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

        $universeTournament =
            UniverseTournament::query()
            ->where(
                'universe_id',
                $universe->id
            )
            ->with(
                'tournamentTemplate'
            )
            ->findOrFail(
                (int) $request->query(
                    'universe_tournament_id'
                )
            );

        $template =
            $universeTournament
            ->tournamentTemplate;

        $graphErrors = [];

        $starts =
            collect();

        if ($template) {

            $compatibility =
                $this->engine
                ->compatibility($template);

            $graphErrors =
                $compatibility['valid']
                ? []
                : $compatibility['errors'];

            $template->loadMissing(
                'graphStarts'
            );

            $starts =
                $template
                ->graphStarts
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->values();
        }

        $competitors =
            UniverseCompetitor::query()
            ->where(
                'universe_id',
                $universe->id
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->with('entity')
            ->get()
            ->sortBy(
                fn($competitor) =>
                mb_strtolower(
                    $competitor->display_label
                )
            )
            ->values();

        $seasons =
            $universe
            ->seasons()
            ->whereIn(
                'status',
                [
                    'PLANNED',
                    'ACTIVE',
                ]
            )
            ->get();

        $activeSeason =
            $universe->activeSeason();

        return view(
            'universes.competitions.create',
            compact(
                'universe',
                'universeTournament',
                'template',
                'starts',
                'competitors',
                'seasons',
                'activeSeason',
                'graphErrors'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreTournamentInstanceRequest $request,
        Universe $universe
    ): RedirectResponse {

        $universeTournament =
            UniverseTournament::query()
            ->where(
                'universe_id',
                $universe->id
            )
            ->with(
                'tournamentTemplate'
            )
            ->findOrFail(
                $request->validated(
                    'universe_tournament_id'
                )
            );

        $instance =
            $this->service
            ->create(
                $universe,
                $universeTournament,
                $request->validated(),
                $request->assignments()
            );

        return redirect()
            ->route(
                'universes.competitions.show',
                [
                    $universe,
                    $instance,
                ]
            )
            ->with(
                'success',
                'Competición preparada. La configuración quedó congelada: '
                    . 'editar la plantilla ya no la afecta.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    |
    | El workspace. El estado viene de la base de datos, no de la sesión.
    |
    */

    public function show(
        Universe $universe,
        TournamentInstance $competition
    ): View {

        $this->authorize(
            'view',
            $competition
        );

        $competition->load([
            'universeTournament.tournamentTemplate',
            'season',
        ]);

        $payload =
            $this->runtime
            ->payload($competition);

        $participants =
            $competition
            ->participants()
            ->get();

        $events =
            $competition
            ->events()
            ->orderByDesc('sequence')
            ->limit(40)
            ->get();

        return view(
            'universes.competitions.show',
            compact(
                'universe',
                'competition',
                'payload',
                'participants',
                'events'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Acción del motor
    |--------------------------------------------------------------------------
    */

    public function action(
        TournamentInstanceActionRequest $request,
        Universe $universe,
        TournamentInstance $competition
    ): JsonResponse {

        $result =
            $this->runtime
            ->act(
                $competition,

                $request->validated('action'),

                $request->payload(),

                $request->filled('revision')
                    ? (int) $request->validated('revision')
                    : null
            );

        return response()->json(
            $result
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Ciclo de vida
    |--------------------------------------------------------------------------
    */

    public function pause(
        Universe $universe,
        TournamentInstance $competition
    ): RedirectResponse {

        $this->authorize(
            'update',
            $competition
        );

        $this->service
            ->pause($competition);

        return back()->with(
            'success',
            'Competición pausada. Puedes retomarla cuando quieras.'
        );
    }

    public function resume(
        Universe $universe,
        TournamentInstance $competition
    ): RedirectResponse {

        $this->authorize(
            'update',
            $competition
        );

        $this->service
            ->resume($competition);

        return back()->with(
            'success',
            'Competición reanudada.'
        );
    }

    public function cancel(
        Universe $universe,
        TournamentInstance $competition
    ): RedirectResponse {

        $this->authorize(
            'update',
            $competition
        );

        $this->service
            ->cancel($competition);

        return back()->with(
            'success',
            'Competición cancelada. Su historial se conserva.'
        );
    }

    public function destroy(
        Universe $universe,
        TournamentInstance $competition
    ): RedirectResponse {

        $this->authorize(
            'delete',
            $competition
        );

        $this->service
            ->delete($competition);

        return redirect()
            ->route(
                'universes.competitions.index',
                $universe
            )
            ->with(
                'success',
                'Competición eliminada.'
            );
    }
}
