<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Models\TournamentInstance;
use App\Models\TournamentInstanceParticipant;
use App\Models\Universe;
use App\Models\UniverseActivity;
use App\Models\UniverseEntity;
use App\Services\Universes\UniverseRankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| UniverseDashboardController
|--------------------------------------------------------------------------
|
| El centro de mando de TODOS los mundos.
|
| No es la estanteria -esa es «Mis universos», y sirve para encontrar y
| comparar-. Esta pantalla contesta otra pregunta: que esta pasando ahora
| mismo en todos tus mundos a la vez, y donde hace falta que entres.
|
| Todo lo que sale aqui es transversal: una competicion bloqueada en el
| universo 7 y otra sin premios en el 6 salen en la misma lista, cada una
| con su mundo al lado, porque lo que se quiere saber es «donde tengo algo
| parado», no «que le pasa a este universo concreto».
|
| Los criterios de atencion son los mismos que usa el Resumen de cada
| universo: si la estanteria dijese una cosa y el mundo otra, uno de los dos
| estaria mintiendo.
|
| Ver docs/md/73-Universos-Centro.md
|
*/

class UniverseDashboardController extends Controller
{
    public function __construct(
        private readonly UniverseRankingService $ranking
    ) {}


    public function __invoke(
        Request $request
    ): View {

        $this->authorize('viewAny', Universe::class);

        $user = $request->user();

        $mundos =
            Universe::query()
            ->ownedBy($user)
            ->withCount([
                'entities',
                'seasons',
                'universeTournaments',
                'trophies',
                'tournamentInstances',

                'tournamentInstances as vivas_count' => fn($q) =>
                $q->whereIn('status', ['RUNNING', 'PAUSED']),
            ])
            ->withMax('activities', 'occurred_at')
            ->get()
            ->keyBy('id');

        $ids = $mundos->keys();


        /*
        |--------------------------------------------------------------------------
        | Las cifras de todo
        |--------------------------------------------------------------------------
        */

        $instancias =
            TournamentInstance::query()
            ->whereIn('universe_id', $ids)
            ->with(['universeTournament', 'season'])
            ->get();

        $statistics = [

            'universos' => $mundos->count(),

            'activos' => $mundos->where('status', 'ACTIVE')->count(),

            'habitantes' => (int) $mundos->sum('entities_count'),

            'torneos' => (int) $mundos->sum('universe_tournaments_count'),

            'competiciones' => $instancias->count(),

            'en_juego' => $instancias->whereIn('status', ['RUNNING', 'PAUSED'])->count(),

            'terminadas' => $instancias->where('status', 'COMPLETED')->count(),

            'temporadas' => (int) $mundos->sum('seasons_count'),
        ];

        $statistics['partidas'] =
            (int) TournamentInstanceParticipant::query()
            ->whereIn('tournament_instance_id', $instancias->pluck('id'))
            ->sum('matches');


        /*
        |--------------------------------------------------------------------------
        | Lo que espera por ti, en todos tus mundos
        |--------------------------------------------------------------------------
        |
        | Mismos criterios que el Resumen de cada universo, pero juntos y con el
        | mundo al lado. Cada punto lleva a donde se resuelve.
        */

        $atencion = collect();

        foreach ($mundos as $mundo) {

            $suyas = $instancias->where('universe_id', $mundo->id);

            $paradas = $suyas->whereIn('runtime_status', ['BLOCKED', 'AWAITING_DECISION']);

            if ($paradas->isNotEmpty()) {

                $atencion->push([
                    'mundo' => $mundo,
                    'tono' => '#fb7185',
                    'urgente' => true,
                    'cuantos' => $paradas->count(),
                    'titulo' => $paradas->count() === 1
                        ? 'Una competición está parada esperándote'
                        : $paradas->count() . ' competiciones están paradas esperándote',
                    'texto' => 'El recorrido no puede seguir sin una decisión tuya.',
                    'url' => route('universes.competitions.index', $mundo),
                ]);
            }

            $listas = $suyas->where('status', 'DRAFT');

            if ($listas->isNotEmpty()) {

                $atencion->push([
                    'mundo' => $mundo,
                    'tono' => '#60a5fa',
                    'urgente' => false,
                    'cuantos' => $listas->count(),
                    'titulo' => $listas->count() === 1
                        ? 'Una competición está lista y sin empezar'
                        : $listas->count() . ' competiciones están listas y sin empezar',
                    'texto' => 'Tienen sus participantes puestos; solo falta darles al play.',
                    'url' => route('universes.competitions.index', $mundo) . '?status=DRAFT',
                ]);
            }

            /*
             * Terminadas sin repartir premios, y SOLO si hay premios que dar:
             * avisar de trofeos que nadie configuró manda a una pantalla donde
             * no hay nada que hacer.
             */
            $sinPremios =
                $suyas
                ->where('status', 'COMPLETED')
                ->whereNull('rewards_processed_at')
                ->filter(
                    fn($i) => $i->universeTournament
                        && ($i->universeTournament->rewards()->exists() || $i->rewards()->exists())
                );

            if ($sinPremios->isNotEmpty()) {

                $atencion->push([
                    'mundo' => $mundo,
                    'tono' => '#fbbf24',
                    'urgente' => false,
                    'cuantos' => $sinPremios->count(),
                    'titulo' => $sinPremios->count() === 1
                        ? 'Una competición terminó sin repartir sus premios'
                        : $sinPremios->count() . ' competiciones terminaron sin repartir sus premios',
                    'texto' => 'Hay campeón, pero sus trofeos y bonus no se han concedido.',
                    'url' => route('universes.show', $mundo),
                ]);
            }

            if ($mundo->seasons_count === 0 && $mundo->entities_count > 0) {

                $atencion->push([
                    'mundo' => $mundo,
                    'tono' => '#a78bfa',
                    'urgente' => false,
                    'cuantos' => 0,
                    'titulo' => 'Este mundo no tiene tiempo todavía',
                    'texto' => 'Sin temporadas, los torneos que se repiten no saben cuándo les toca.',
                    'url' => route('universes.seasons.index', $mundo),
                ]);
            }

            if ($mundo->entities_count === 0) {

                $atencion->push([
                    'mundo' => $mundo,
                    'tono' => '#94a3b8',
                    'urgente' => false,
                    'cuantos' => 0,
                    'titulo' => 'Este mundo está vacío',
                    'texto' => 'Sin habitantes no puede jugarse nada en él.',
                    'url' => route('universes.entities.create', $mundo),
                ]);
            }
        }

        $atencion = $atencion->sortByDesc('urgente')->values();


        /*
        |--------------------------------------------------------------------------
        | Lo que se juega ahora mismo, venga del mundo que venga
        |--------------------------------------------------------------------------
        */

        $enJuego =
            $instancias
            ->whereIn('status', ['RUNNING', 'PAUSED'])
            ->sortByDesc('started_at')
            ->take(8)
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Sigue donde lo dejaste
        |--------------------------------------------------------------------------
        */

        $recientes =
            $mundos
            ->sortByDesc(
                fn($m) => $m->activities_max_occurred_at ?? $m->created_at
            )
            ->take(4)
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Quien manda en cada mundo
        |--------------------------------------------------------------------------
        |
        | Lo que hace interesante juntarlos: la clasificacion es de cada
        | universo, asi que el numero uno de uno puede ser el dieciocho de otro.
        | Hasta ahora eso no se veia en ninguna parte, porque nadie ponia dos
        | mundos al lado.
        */

        $mandan = collect();

        foreach ($mundos->sortByDesc('tournament_instances_count')->take(6) as $mundo) {

            if ($mundo->tournament_instances_count === 0) {
                continue;
            }

            $suRanking = $this->ranking->ranking($mundo);

            $primero = $suRanking->first();

            if (! $primero?->entity) {
                continue;
            }

            $mandan->push([
                'mundo' => $mundo,
                'quien' => $primero->entity,
                'puntos' => $primero->points,
                'titulos' => $primero->titles,
                'de_cuantos' => $suRanking->count(),
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Los ultimos en ganar, de todos los mundos
        |--------------------------------------------------------------------------
        */

        $campeones =
            TournamentInstanceParticipant::query()
            ->join(
                'tournament_instances',
                'tournament_instances.id',
                '=',
                'tournament_instance_participants.tournament_instance_id'
            )
            ->whereIn('tournament_instances.universe_id', $ids)
            ->where('tournament_instance_participants.outcome', 'CHAMPION')
            ->orderByDesc('tournament_instances.completed_at')
            ->orderByDesc('tournament_instances.id')
            ->limit(60)
            ->with(['universeEntity', 'tournamentInstance.season'])
            ->select([
                'tournament_instance_participants.*',
                'tournament_instances.universe_id as del_mundo',
            ])
            ->get()
            ->groupBy('tournament_instance_id')
            ->take(5);


        /*
        |--------------------------------------------------------------------------
        | Que ha pasado, en todos los mundos
        |--------------------------------------------------------------------------
        */

        $actividad =
            UniverseActivity::query()
            ->whereIn('universe_id', $ids)
            ->with('universeEntity')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(14)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | El pulso de los ultimos doce meses
        |--------------------------------------------------------------------------
        |
        | Cuantas competiciones se jugaron cada mes, sumando todos los mundos. Es
        | lo unico que dice si esto se usa o se uso.
        */

        $desde = Carbon::now()->startOfMonth()->subMonths(11);

        $porMes = [];

        for ($i = 0; $i < 12; $i++) {

            $mes = (clone $desde)->addMonths($i);

            $porMes[$mes->format('Y-m')] = [
                'clave' => $mes->format('Y-m'),
                'mes' => $mes->month,
                'anio' => $mes->year,
                'cuantas' => 0,
            ];
        }

        foreach ($instancias as $instancia) {

            $cuando = $instancia->started_at ?? $instancia->created_at;

            if (! $cuando) {
                continue;
            }

            $clave = Carbon::parse($cuando)->format('Y-m');

            if (isset($porMes[$clave])) {
                $porMes[$clave]['cuantas']++;
            }
        }

        $pulso = collect(array_values($porMes));


        /*
        |--------------------------------------------------------------------------
        | Caras
        |--------------------------------------------------------------------------
        */

        $mosaico =
            UniverseEntity::query()
            ->whereIn('universe_id', $ids)
            ->whereNotNull('image')
            ->inRandomOrder()
            ->limit(24)
            ->get();

        return view(
            'universes.dashboard',
            compact(
                'mundos',
                'statistics',
                'atencion',
                'enJuego',
                'recientes',
                'mandan',
                'campeones',
                'actividad',
                'pulso',
                'mosaico'
            )
        );
    }
}
