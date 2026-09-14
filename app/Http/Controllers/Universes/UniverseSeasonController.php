<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Universes\StoreUniverseSeasonRequest;
use App\Http\Requests\Universes\UpdateUniverseSeasonRequest;
use App\Models\Universe;
use App\Models\UniverseSeason;
use App\Services\Universes\UniverseSeasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        Request $request,
        Universe $universe
    ): View {

        $this->authorize(
            'view',
            $universe
        );

        $search = trim((string) $request->input('search'));

        $status = (string) $request->input('status');

        $played = (string) $request->input('played');

        $sort = (string) $request->input('sort', 'number_desc');


        $seasons =
            $universe
            ->seasons()
            ->withCount([
                'competitions',
                'competitions as completadas_count' =>
                fn($query) => $query->where('status', 'COMPLETED'),
            ])
            ->when(
                $search,
                fn($q) => $q->where(
                    fn($s) => $s
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                )
            )
            ->when(
                $status,
                fn($q) => $q->where('status', $status)
            )
            ->when(
                $played === 'yes',
                fn($q) => $q->has('competitions')
            )
            ->when(
                $played === 'no',
                fn($q) => $q->doesntHave('competitions')
            );

        match ($sort) {
            'number_asc' => $seasons->orderBy('number'),
            'competitions' => $seasons
                ->orderByDesc('competitions_count')
                ->orderByDesc('number'),
            default => $seasons->orderByDesc('number'),
        };

        $seasons = $seasons->paginate(24)->withQueryString();


        $statistics = [

            'total' =>
            $universe->seasons()->count(),

            'planned' =>
            $universe->seasons()->where('status', 'PLANNED')->count(),

            'completed' =>
            $universe->seasons()->where('status', 'COMPLETED')->count(),

            'competiciones' =>
            $universe->tournamentInstances()->count(),

            'sin_jugar' =>
            $universe->seasons()->doesntHave('competitions')->count(),
        ];

        $activeSeason =
            $universe->activeSeason();


        /*
        |--------------------------------------------------------------------------
        | El calendario de recurrencia
        |--------------------------------------------------------------------------
        |
        | Al configurar un torneo se dice cada cuanto toca —cada temporada, cada
        | N, una sola vez—, y eso no se veia en ninguna parte. `occursInSeason`
        | ya sabia contestarlo; aqui se le pregunta por cada temporada.
        |
        | Se proyectan ademas cuatro temporadas mas alla de la ultima creada:
        | saber que tocaria en la 11 es lo que dice si merece la pena crearla.
        |
        */

        $torneos =
            $universe
            ->universeTournaments()
            ->where('status', '!=', 'ARCHIVED')
            ->get();

        $numeros =
            $universe->seasons()->pluck('number')->sort()->values();

        $ultimo = (int) ($numeros->last() ?? 0);

        $proyectadas =
            collect(range($ultimo + 1, $ultimo + 4));

        $calendario =
            $numeros
            ->map(fn($n) => ['numero' => (int) $n, 'existe' => true])
            ->concat(
                $proyectadas->map(fn($n) => ['numero' => (int) $n, 'existe' => false])
            )
            ->map(
                function (array $fila) use ($torneos) {

                    $fila['torneos'] =
                        $torneos->filter(
                            fn($torneo) => $torneo->occursInSeason($fila['numero'])
                        )->values();

                    return $fila;
                }
            )
            ->values();

        /* Los que nunca se anuncian: se lanzan a mano cuando uno quiere. */
        $manuales =
            $torneos->where('recurrence_mode', 'MANUAL')->values();


        return view(
            'universes.seasons.index',
            compact(
                'universe',
                'seasons',
                'statistics',
                'activeSeason',
                'calendario',
                'manuales',
                'torneos',
                'search',
                'status',
                'played',
                'sort'
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
            [
                'universe' => $universe,
                'nextNumber' => $nextNumber,
                'activeSeason' => $activeSeason,

                /* Para avisar de a quien va a relevar */
                'otraActiva' => $activeSeason,
            ]
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
    | Crear varias de golpe
    |--------------------------------------------------------------------------
    |
    | Un mundo con historia necesita diez temporadas, no una.
    |
    */

    public function storeMany(
        Request $request,
        Universe $universe
    ): RedirectResponse {

        $this->authorize('update', $universe);

        $data = $request->validate(
            [
                'count' => ['required', 'integer', 'min:1', 'max:50'],

                'name_pattern' => ['required', 'string', 'max:150'],

                'starts_at' => ['nullable', 'date'],

                'duration' => ['nullable', 'integer', 'min:0', 'max:120'],

                'duration_unit' => ['required', 'in:days,weeks,months,years'],

                'first_status' => ['required', 'in:PLANNED,ACTIVE'],

                'description' => ['nullable', 'string', 'max:5000'],
            ],
            [
                'count.required' => 'Falta decir cuántas temporadas crear.',
                'count.max' => 'De golpe, como mucho cincuenta.',
                'name_pattern.required' => 'Hace falta un patrón para los nombres.',
                'duration_unit.in' => 'Esa unidad de tiempo no existe.',
            ]
        );

        $creadas = $this->service
            ->createMany($universe, $data);

        return redirect()
            ->route('universes.seasons.index', $universe)
            ->with(
                'success',
                $creadas->count() . ' temporadas creadas, de la '
                    . $creadas->first()->number . ' a la '
                    . $creadas->last()->number . '.'
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

        $otraActiva = $universe->activeSeason();

        /* A si misma no se releva */
        if ($otraActiva && $otraActiva->is($season)) {
            $otraActiva = null;
        }

        return view(
            'universes.seasons.edit',
            compact(
                'universe',
                'season',
                'otraActiva'
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
