<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Models\GameEncounter;
use App\Models\TournamentInstance;
use App\Models\TournamentInstanceMatch;
use App\Models\TournamentInstanceParticipant;
use App\Models\Universe;
use App\Models\UniverseActivity;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| UniverseHistoryController
|--------------------------------------------------------------------------
|
| «Que ha pasado en este mundo?»
|
| Lo que habia: una lista de competiciones jugadas. Correcto y corto: la
| historia de un universo es mas que su lista de partidas.
|
| Existia ya —y no lo miraba nadie desde aqui— un registro de actividad
| (`universe_activities`) con lo que va pasando: temporadas que empiezan,
| competiciones que arrancan y terminan, campeones coronados, entidades
| importadas. Esa es la cronica del mundo, y ahora es lo primero que se ve.
|
| Solo lectura. No toca el runtime ni el estado.
|
*/

class UniverseHistoryController extends Controller
{
    public function index(
        Request $request,
        Universe $universe
    ): View {

        $this->authorize('view', $universe);


        /*
        |--------------------------------------------------------------------------
        | Parametros
        |--------------------------------------------------------------------------
        */

        $engine = (string) $request->input('engine', '');

        $seasonId = (int) $request->input('season', 0);

        $type = (string) $request->input('type', '');

        $search = trim((string) $request->input('search'));

        $sort = (string) $request->input('sort', 'newest');

        $idsDelUniverso =
            TournamentInstance::query()
            ->inUniverse($universe)
            ->select('id');


        /*
        |--------------------------------------------------------------------------
        | La cronica
        |--------------------------------------------------------------------------
        |
        | El registro de actividad, que es lo que de verdad cuenta la historia
        | del mundo. Se agrupa por dia en la vista.
        |
        */

        $actividad =
            UniverseActivity::query()
            ->where('universe_id', $universe->id)
            ->with([
                'season',
                'universeEntity',
                'tournamentInstance.universeTournament',
            ])
            ->when(
                $type,
                fn($q) => $q->where('type', $type)
            )
            ->when(
                $seasonId,
                fn($q) => $q->where('universe_season_id', $seasonId)
            )
            ->when(
                $search,
                fn($q) => $q->where('message', 'like', "%{$search}%")
            )
            ->orderBy(
                'occurred_at',
                $sort === 'oldest' ? 'asc' : 'desc'
            )
            ->orderBy('id', $sort === 'oldest' ? 'asc' : 'desc')
            ->limit(120)
            ->get();

        /* Los tipos que existen de verdad en este mundo, para el filtro */
        $tipos =
            UniverseActivity::query()
            ->where('universe_id', $universe->id)
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | El palmares
        |--------------------------------------------------------------------------
        |
        | Lo que ya se jugo. Una competicion que nunca arranco no es historia.
        |
        */

        $query =
            TournamentInstance::query()
            ->inUniverse($universe)
            ->with([
                'universeTournament',
                'season',
            ])
            ->where('status', '!=', 'DRAFT')
            ->when(
                $engine,
                fn($builder) => $builder->whereHas(
                    'phases',
                    fn($phases) => $phases->where('phase_type', $engine)
                )
            )
            ->when(
                $seasonId,
                fn($builder) => $builder->where('universe_season_id', $seasonId)
            )
            ->when(
                $search,
                fn($builder) => $builder->where('name', 'like', "%{$search}%")
            );

        match ($sort) {
            'oldest' => $query->orderBy('started_at')->orderBy('id'),
            default => $query->orderByDesc('started_at')->orderByDesc('id'),
        };

        $competitions =
            $query
            ->paginate(24)
            ->withQueryString();


        $champions =
            TournamentInstanceParticipant::query()
            ->whereIn(
                'tournament_instance_id',
                $competitions->pluck('id')
            )
            ->where('outcome', 'CHAMPION')
            ->with('universeEntity')
            ->get()
            ->keyBy('tournament_instance_id');


        /*
        |--------------------------------------------------------------------------
        | El salon de la fama
        |--------------------------------------------------------------------------
        |
        | Quien ha ganado que, contado sobre los campeonatos de verdad. Es la
        | pregunta que un historial deberia contestar antes que ninguna otra y
        | no contestaba.
        |
        */

        $salon =
            TournamentInstanceParticipant::query()
            ->whereIn('tournament_instance_id', $idsDelUniverso)
            ->where('outcome', 'CHAMPION')
            ->with([
                'universeEntity',
                'tournamentInstance.universeTournament',
                'tournamentInstance.season',
            ])
            ->get()
            ->groupBy('universe_entity_id')
            ->map(
                fn($titulos) => [
                    'competidor' => $titulos->first()->universeEntity,
                    'nombre' => $titulos->first()->universeEntity?->display_label
                        ?? $titulos->first()->display_name
                        ?? $titulos->first()->name
                        ?? 'Sin nombre',
                    'titulos' => $titulos->values(),
                ]
            )
            ->sortByDesc(fn($fila) => $fila['titulos']->count())
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Cifras
        |--------------------------------------------------------------------------
        */

        $statistics = [

            'played' =>
            TournamentInstance::query()
                ->inUniverse($universe)
                ->where('status', '!=', 'DRAFT')
                ->count(),

            'completed' =>
            TournamentInstance::query()
                ->inUniverse($universe)
                ->where('status', 'COMPLETED')
                ->count(),

            'matches' =>
            TournamentInstanceMatch::query()
                ->whereIn('tournament_instance_id', $idsDelUniverso)
                ->where('status', 'COMPLETED')
                ->count(),

            /* Enfrentamientos resueltos por un motor de juego */
            'encuentros' =>
            GameEncounter::query()
                ->where('universe_id', $universe->id)
                ->count(),

            'campeones' =>
            $salon->count(),

            'eventos' =>
            UniverseActivity::query()
                ->where('universe_id', $universe->id)
                ->count(),
        ];


        $seasons =
            $universe
            ->seasons()
            ->orderByDesc('number')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | La historia por temporadas
        |--------------------------------------------------------------------------
        |
        | El mundo contado por tramos: que se jugo en cada uno y quien gano.
        |
        */

        $porTemporada =
            $seasons
            ->map(
                function ($temporada) use ($universe) {

                    $suyas =
                        TournamentInstance::query()
                        ->inUniverse($universe)
                        ->where('universe_season_id', $temporada->id)
                        ->where('status', '!=', 'DRAFT')
                        ->with(['universeTournament'])
                        ->orderByDesc('started_at')
                        ->get();

                    $campeonesSuyos =
                        TournamentInstanceParticipant::query()
                        ->whereIn('tournament_instance_id', $suyas->pluck('id'))
                        ->where('outcome', 'CHAMPION')
                        ->with('universeEntity')
                        ->get()
                        ->keyBy('tournament_instance_id');

                    return [
                        'temporada' => $temporada,
                        'competiciones' => $suyas,
                        'campeones' => $campeonesSuyos,
                    ];
                }
            )
            ->values();


        return view(
            'universes.history.index',
            compact(
                'universe',
                'competitions',
                'champions',
                'statistics',
                'seasons',
                'engine',
                'seasonId',
                'sort',
                'type',
                'search',
                'actividad',
                'tipos',
                'salon',
                'porTemporada'
            )
        );
    }
}
