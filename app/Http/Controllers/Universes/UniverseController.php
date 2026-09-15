<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Universes\StoreUniverseRequest;
use App\Models\Entity;
use App\Http\Requests\Universes\UpdateUniverseRequest;
use App\Models\TournamentInstance;
use App\Models\TournamentInstanceParticipant;
use App\Models\Universe;
use App\Models\UniverseEntity;
use App\Models\UniverseSeason;
use App\Models\UniverseTrophyAward;
use App\Services\Games\GameRegistry;
use App\Services\Games\UniverseGameService;
use App\Services\Universes\UniverseEntityImporter;
use App\Services\Universes\UniverseRankingService;
use App\Services\Universes\UniverseSeasonService;
use App\Services\Universes\UniverseService;
use App\Support\Universes\UniverseSettings;
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

        /*
        |--------------------------------------------------------------------------
        | Mis universos
        |--------------------------------------------------------------------------
        |
        | La estanteria de mundos. Lo que habia era una rejilla de tarjetas con
        | tres contadores; lo que faltaba es lo que hace que una estanteria
        | sirva: poder comparar lo que hay en ella y saber cual pide atencion.
        |
        | Cada universo viaja con su pulso completo -que se juega, que esta
        | atascado, cuando se toco por ultima vez- para que las cinco formas de
        | mirar salgan de la misma consulta.
        */

        $user = $request->user();

        $search = trim((string) $request->input('search'));

        $status = (string) $request->input('status', '');

        $con = (string) $request->input('con', '');

        $sort = (string) $request->input('sort', 'newest');

        $perPage = (int) $request->input('per_page', 24);

        if (! in_array($perPage, [12, 24, 48], true)) {
            $perPage = 24;
        }


        $base = fn() => Universe::query()->ownedBy($user);

        /*
         * Las cifras de arriba son del conjunto, no de lo filtrado: son el
         * indice, y un indice que cambia con el filtro deja de ser un indice.
         */
        $stats = [

            'total' => $base()->count(),

            'active' => $base()->where('status', 'ACTIVE')->count(),

            'draft' => $base()->where('status', 'DRAFT')->count(),

            'archived' => $base()->where('status', 'ARCHIVED')->count(),
        ];

        $mios = $base()->pluck('id');

        $stats['entities'] =
            UniverseEntity::query()->whereIn('universe_id', $mios)->count();

        $stats['competitions'] =
            TournamentInstance::query()->whereIn('universe_id', $mios)->count();

        $stats['running'] =
            TournamentInstance::query()
            ->whereIn('universe_id', $mios)
            ->whereIn('status', ['RUNNING', 'PAUSED'])
            ->count();

        $stats['stuck'] =
            TournamentInstance::query()
            ->whereIn('universe_id', $mios)
            ->whereIn('runtime_status', ['BLOCKED', 'AWAITING_DECISION'])
            ->count();


        $query =
            Universe::query()
            ->ownedBy($user)
            ->withCount([

                'entities',
                'seasons',
                'universeTournaments',
                'trophies',
                'activities',

                'tournamentInstances',

                'tournamentInstances as vivas_count' => fn($q) =>
                $q->whereIn('status', ['RUNNING', 'PAUSED']),

                'tournamentInstances as hechas_count' => fn($q) =>
                $q->where('status', 'COMPLETED'),

                /* Lo que frena: una edicion parada esperando una decision */
                'tournamentInstances as atascadas_count' => fn($q) =>
                $q->whereIn('runtime_status', ['BLOCKED', 'AWAITING_DECISION']),

                'tournamentInstances as listas_count' => fn($q) =>
                $q->where('status', 'DRAFT'),
            ])
            /* Cuando se movio por ultima vez: es lo que ordena «por movimiento» */
            ->withMax('activities', 'occurred_at');


        if ($search !== '') {

            $query->where(
                fn($sub) => $sub
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            );
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        /*
         * Filtros por lo que le PASA al universo, no por lo que es. Son las
         * preguntas que uno se hace de verdad al abrir esta pantalla.
         */
        match ($con) {

            'jugando' =>
            $query->whereHas(
                'tournamentInstances',
                fn($q) => $q->whereIn('status', ['RUNNING', 'PAUSED'])
            ),

            'atascados' =>
            $query->whereHas(
                'tournamentInstances',
                fn($q) => $q->whereIn('runtime_status', ['BLOCKED', 'AWAITING_DECISION'])
            ),

            'sin_jugar' =>
            $query->whereDoesntHave('tournamentInstances'),

            'vacios' =>
            $query->whereDoesntHave('entities'),

            default => null,
        };

        match ($sort) {

            'oldest' => $query->orderBy('created_at'),

            'name_asc' => $query->orderBy('name'),

            'name_desc' => $query->orderByDesc('name'),

            'entities_desc' => $query->orderByDesc('entities_count'),

            'competitions_desc' => $query->orderByDesc('tournament_instances_count'),

            'tournaments_desc' => $query->orderByDesc('universe_tournaments_count'),

            /* El ultimo que tocaste primero */
            'movement' => $query->orderByDesc('activities_max_occurred_at'),

            default => $query->orderByDesc('created_at'),
        };

        $universes =
            $query
            ->paginate($perPage)
            ->withQueryString();

        $enPantalla = collect($universes->items())->pluck('id');


        /*
        |--------------------------------------------------------------------------
        | Las caras de cada mundo
        |--------------------------------------------------------------------------
        |
        | Un universo se reconoce por su gente antes que por su nombre. Se traen
        | en UNA consulta para todos los de la pagina y se reparten despues: una
        | consulta por tarjeta convertiria la estanteria en veinte viajes.
        */

        $carasPorUniverso =
            UniverseEntity::query()
            ->whereIn('universe_id', $enPantalla)
            ->whereNotNull('image')
            ->get(['id', 'universe_id', 'name', 'display_name', 'image'])
            ->groupBy('universe_id')
            ->map(fn($grupo) => $grupo->take(12));


        /*
         * Quien gano lo ultimo en cada mundo. Un universo con campeon tiene
         * historia; uno sin campeon todavia no ha pasado nada.
         */
        $campeonPorUniverso =
            TournamentInstanceParticipant::query()
            ->join(
                'tournament_instances',
                'tournament_instances.id',
                '=',
                'tournament_instance_participants.tournament_instance_id'
            )
            ->whereIn('tournament_instances.universe_id', $enPantalla)
            ->where('tournament_instance_participants.outcome', 'CHAMPION')
            ->orderByDesc('tournament_instances.completed_at')
            ->orderByDesc('tournament_instances.id')
            ->with('universeEntity')
            ->select([
                'tournament_instance_participants.*',
                'tournament_instances.universe_id as del_universo',
                'tournament_instances.name as la_competicion',
            ])
            ->get()
            ->unique('del_universo')
            ->keyBy('del_universo');


        $temporadaPorUniverso =
            UniverseSeason::query()
            ->whereIn('universe_id', $enPantalla)
            ->where('status', 'ACTIVE')
            ->get()
            ->keyBy('universe_id');


        /*
        |--------------------------------------------------------------------------
        | El pulso: competiciones por temporada
        |--------------------------------------------------------------------------
        |
        | Para la vista que compara los mundos de lado. Dos consultas agregadas
        | para todos a la vez.
        */

        $porTemporada =
            TournamentInstance::query()
            ->whereIn('universe_id', $enPantalla)
            ->selectRaw('universe_id, universe_season_id, COUNT(*) as cuantas')
            ->groupBy('universe_id', 'universe_season_id')
            ->get()
            ->groupBy('universe_id');

        $numerosDeTemporada =
            UniverseSeason::query()
            ->whereIn('universe_id', $enPantalla)
            ->get(['id', 'universe_id', 'number', 'name'])
            ->keyBy('id');

        /*
         * Una competicion puede no pertenecer a ninguna temporada -pasa: en el
         * universo 7 hay una-. No es una temporada cero: es «fuera del
         * calendario», y se dice asi y se manda al final, porque colocarla como
         * T0 la pondria antes de la primera temporada y mentiria sobre cuando
         * ocurrio.
         */
        $pulsoPorUniverso =
            $porTemporada->map(
                fn($filas) => $filas
                    ->map(function ($fila) use ($numerosDeTemporada) {

                        $suya = $numerosDeTemporada[$fila->universe_season_id] ?? null;

                        return [
                            'numero' => $suya?->number,
                            'nombre' => $suya?->name ?? 'Fuera del calendario',
                            'cuantas' => (int) $fila->cuantas,
                        ];
                    })
                    ->sortBy(fn($fila) => $fila['numero'] ?? PHP_INT_MAX)
                    ->values()
            );


        return view(
            'universes.index',
            compact(
                'universes',
                'stats',
                'search',
                'status',
                'con',
                'sort',
                'perPage',
                'carasPorUniverso',
                'campeonPorUniverso',
                'temporadaPorUniverso',
                'pulsoPorUniverso'
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

        $user = $request->user();

        $previewCode =
            $this->service
            ->previewCode($user);

        /*
        |--------------------------------------------------------------------------
        | Lo que se puede dejar decidido antes de entrar
        |--------------------------------------------------------------------------
        |
        | Crear un universo eran cuatro campos, y despues hacian falta cuatro
        | pantallas mas para que el mundo funcionase de verdad: crear su primera
        | temporada, decidir que premia, elegir con que se juega y traerle
        | gente. Todo eso ya existia, suelto.
        |
        | Aqui se ofrece junto, y todo sigue siendo opcional: un universo se
        | puede crear con su nombre y nada mas.
        */

        $juegos =
            collect(app(GameRegistry::class)->definitions())
            ->values();

        $puntosDefecto = UniverseSettings::defaults();

        /* La Biblioteca, para traer habitantes desde el primer momento */
        $biblioteca =
            Entity::query()
            ->ownedBy($user)
            ->with('entityType')
            ->orderBy('name')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'nombre' => $e->display_label ?? $e->name,
                'img' => $e->image_url,
                'tipo' => $e->entityType?->name,
            ])
            ->values();

        $tipos =
            $biblioteca
            ->pluck('tipo')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view(
            'universes.create',
            compact(
                'previewCode',
                'juegos',
                'puntosDefecto',
                'biblioteca',
                'tipos'
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

        $datos = $request->validated();

        /*
         * Lo que no es del universo en si se aparta antes de crearlo: el
         * servicio escribe en la tabla lo que le llega, y colar aqui
         * «seasons_count» acabaria en una columna que no existe.
         */
        $extra = [];

        foreach ([
            'seasons_count', 'seasons_pattern', 'seasons_starts_at',
            'seasons_duration', 'seasons_duration_unit', 'seasons_first_status',
            'points_champion', 'points_win', 'points_draw', 'points_loss',
            'points_participation', 'game_key', 'entity_ids',
        ] as $clave) {

            $extra[$clave] = $datos[$clave] ?? null;

            unset($datos[$clave]);
        }

        $universe =
            $this->service
            ->create(
                $request->user(),
                $datos,
                $request->file('image')
            );

        /*
         * Y ahora se monta el mundo. Cada paso es opcional e independiente:
         * que falle uno no debe dejar el universo a medias, asi que se hace
         * despues de crearlo y se cuenta lo que salio.
         */
        $hecho = [];

        /* ---------- Que premia ---------- */

        $puntos = array_filter(
            [
                'points_champion' => $extra['points_champion'],
                'points_win' => $extra['points_win'],
                'points_draw' => $extra['points_draw'],
                'points_loss' => $extra['points_loss'],
                'points_participation' => $extra['points_participation'],
            ],
            fn($v) => $v !== null
        );

        if ($puntos !== []) {

            (new UniverseSettings($universe))->save($puntos);
        }

        /* ---------- Con que se juega ---------- */

        $juegoElegido = null;

        if ($extra['game_key']) {

            app(UniverseGameService::class)
                ->setDefault($universe, $extra['game_key']);

            $definicion = app(GameRegistry::class)->definition($extra['game_key']);

            if ($definicion) {
                $juegoElegido = $definicion['name'];
            }
        }

        /* ---------- El calendario ---------- */

        $cuantas = (int) ($extra['seasons_count'] ?? 0);

        if ($cuantas > 0) {

            $creadas = app(UniverseSeasonService::class)->createMany(
                $universe,
                [
                    'count' => $cuantas,
                    'name_pattern' => $extra['seasons_pattern'] ?: 'Temporada {n}',
                    'starts_at' => $extra['seasons_starts_at'],
                    'duration' => $extra['seasons_duration'],
                    'duration_unit' => $extra['seasons_duration_unit'] ?: 'months',
                    'first_status' => $extra['seasons_first_status'] ?: 'PLANNED',
                ]
            );

            $n = $creadas instanceof \Illuminate\Support\Collection
                ? $creadas->count()
                : $cuantas;

            $hecho[] = $n === 1
                ? 'su primera temporada'
                : "sus {$n} primeras temporadas";
        }

        /* ---------- Quienes lo habitan ---------- */

        $ids = array_filter((array) ($extra['entity_ids'] ?? []));

        if ($ids !== []) {

            $traidas = app(UniverseEntityImporter::class)
                ->import($universe, $ids);

            if ($traidas > 0) {
                $hecho[] = $traidas === 1
                    ? 'un competidor dentro'
                    : "{$traidas} competidores dentro";
            }
        }

        /*
         * La frase se arma en el orden en que se lee bien -«con sus tres
         * primeras temporadas y cuatro competidores dentro. Se juega a
         * Highest Number»- y no en el orden en que se ejecutaron los pasos.
         */
        if ($hecho === [] && ! $juegoElegido) {

            $mensaje = 'Universo creado. Todavía no tiene tiempo ni gente: '
                . 'el Resumen te dirá qué le falta.';

        } else {

            $lista = count($hecho) > 1
                ? implode(', ', array_slice($hecho, 0, -1)) . ' y ' . end($hecho)
                : implode('', $hecho);

            $mensaje = $lista !== ''
                ? "Universo creado con {$lista}."
                : 'Universo creado.';

            if ($juegoElegido) {
                $mensaje .= " Se juega a {$juegoElegido}.";
            }
        }

        return redirect()
            ->route('universes.show', $universe)
            ->with('success', $mensaje);
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
        |--------------------------------------------------------------------------
        | El Resumen
        |--------------------------------------------------------------------------
        |
        | La pantalla que conecta el universo entero: que se esta jugando, quien
        | manda, que toca, y -sobre todo- QUE ESPERA POR TI.
        |
        | Lo ultimo es lo que faltaba. El universo ya guardaba el estado de cada
        | competicion, si sus premios se repartieron y si una edicion se quedo
        | bloqueada esperando una decision, y nada de eso llegaba nunca a esta
        | pantalla: habia que entrar panel por panel a descubrirlo.
        */

        $instancias =
            $universe
            ->tournamentInstances()
            ->with(['universeTournament', 'season'])
            ->get();

        /*
         * reorder() y no orderBy(): la relacion seasons() ya trae un
         * orderByDesc('number') propio, asi que añadir un orderBy dejaba
         * «ORDER BY number DESC, number ASC» y la linea del tiempo salia del
         * revés, con la temporada 6 a la izquierda y la 1 a la derecha.
         */
        $temporadas =
            $universe->seasons()
            ->reorder('number')
            ->get();

        $torneos =
            $universe->universeTournaments()->get();


        /*
        |--------------------------------------------------------------------------
        | El pulso
        |--------------------------------------------------------------------------
        */

        $statistics = [

            'entities' =>
            $universe->entities()->count(),

            'active_competitors' =>
            $universe->entities()->where('status', 'ACTIVE')->count(),

            'seasons' => $temporadas->count(),

            'tournaments' => $torneos->count(),

            'competitions' => $instancias->count(),

            'competitions_running' =>
            $instancias->whereIn('status', ['RUNNING', 'PAUSED'])->count(),

            'competitions_done' =>
            $instancias->where('status', 'COMPLETED')->count(),

            'trophies' =>
            $universe->trophies()->count(),
        ];

        /*
         * Quien ha jugado de verdad. Un competidor importado y nunca alineado
         * no es lo mismo que uno que compite: la diferencia es el trabajo que
         * queda por hacer en este mundo.
         */
        $statistics['have_competed'] =
            TournamentInstanceParticipant::query()
            ->whereIn('tournament_instance_id', $instancias->pluck('id'))
            ->whereNotNull('universe_entity_id')
            ->distinct()
            ->count('universe_entity_id');

        $statistics['never_competed'] =
            max(0, $statistics['entities'] - $statistics['have_competed']);

        $statistics['trophy_awards'] =
            UniverseTrophyAward::query()
            ->whereIn('universe_trophy_id', $universe->trophies()->pluck('id'))
            ->count();


        $activeSeason =
            $universe->activeSeason();


        /*
        |--------------------------------------------------------------------------
        | Lo que espera por ti
        |--------------------------------------------------------------------------
        |
        | Cada punto lleva a donde se resuelve. Un aviso que no se puede
        | atender desde el aviso no es un aviso, es una queja.
        */

        $atencion = collect();

        $bloqueadas =
            $instancias->whereIn('runtime_status', ['BLOCKED', 'AWAITING_DECISION']);

        if ($bloqueadas->isNotEmpty()) {

            $atencion->push([
                'tono' => '#fb7185',
                'urgente' => true,
                'titulo' => $bloqueadas->count() === 1
                    ? 'Una competición está parada esperándote'
                    : $bloqueadas->count() . ' competiciones están paradas esperándote',
                'texto' => 'El recorrido no puede seguir sin una decisión tuya.',
                'accion' => 'Ir a resolverlo',
                'url' => route('universes.competitions.index', $universe),
                'cuantos' => $bloqueadas->count(),
            ]);
        }

        $preparadas =
            $instancias->where('status', 'DRAFT');

        if ($preparadas->isNotEmpty()) {

            $atencion->push([
                'tono' => '#60a5fa',
                'urgente' => false,
                'titulo' => $preparadas->count() === 1
                    ? 'Una competición está lista y sin empezar'
                    : $preparadas->count() . ' competiciones están listas y sin empezar',
                'texto' => 'Tienen sus participantes puestos; solo falta darles al play.',
                'accion' => 'Ver cuáles',
                /* Ya filtrado: llegar al panel y tener que buscarlas no es llegar */
                'url' => route('universes.competitions.index', $universe) . '?status=DRAFT',
                'cuantos' => $preparadas->count(),
            ]);
        }

        /*
         * Terminadas sin repartir premios. Esta es la que mas se echaba de
         * menos: la competicion acabo, hay un campeon, y sus trofeos y bonus
         * siguen sin concederse porque nadie volvio a entrar a darle al boton.
         */
        $sinPremios =
            $instancias
            ->where('status', 'COMPLETED')
            ->whereNull('rewards_processed_at')
            ->filter(fn($i) => $i->universeTournament !== null)
            /*
             * Y que haya premios que dar. Sin este filtro el aviso salia para
             * cualquier edicion terminada, prometiendo trofeos y bonus que
             * nadie habia configurado: pulsar el boton solo la marcaba como
             * repartida y no concedia nada. Un aviso que no lleva a ninguna
             * parte es peor que no avisar.
             */
            ->filter(
                fn($i) =>
                $i->universeTournament->rewards()->exists()
                || $i->rewards()->exists()
            )
            ->values();

        if ($sinPremios->isNotEmpty()) {

            $atencion->push([
                'tono' => '#fbbf24',
                'urgente' => false,
                'titulo' => $sinPremios->count() === 1
                    ? 'Una competición terminó sin repartir sus premios'
                    : $sinPremios->count() . ' competiciones terminaron sin repartir sus premios',
                'texto' => 'Hay campeón, pero sus trofeos y bonus no se han concedido.',
                'accion' => null,
                'url' => null,
                'cuantos' => $sinPremios->count(),
                'premios' => $sinPremios,
            ]);
        }

        if (! $activeSeason && $temporadas->isNotEmpty()) {

            $atencion->push([
                'tono' => '#a78bfa',
                'urgente' => true,
                'titulo' => 'Ninguna temporada está en marcha',
                'texto' => 'Sin temporada activa no se sabe cuándo toca cada torneo.',
                'accion' => 'Abrir temporadas',
                'url' => route('universes.seasons.index', $universe),
                'cuantos' => 0,
            ]);
        }

        if ($temporadas->isEmpty()) {

            $atencion->push([
                'tono' => '#a78bfa',
                'urgente' => true,
                'titulo' => 'Este mundo no tiene tiempo todavía',
                'texto' => 'Las temporadas son el calendario: sin ellas, los torneos no saben cuándo ocurrir.',
                'accion' => 'Crear la primera',
                'url' => route('universes.seasons.index', $universe),
                'cuantos' => 0,
            ]);
        }

        $torneosSinJugar =
            $torneos->filter(
                fn($t) => $instancias->where('universe_tournament_id', $t->id)->isEmpty()
            );

        if ($torneosSinJugar->isNotEmpty()) {

            $atencion->push([
                'tono' => '#22d3ee',
                'urgente' => false,
                'titulo' => $torneosSinJugar->count() === 1
                    ? 'Un torneo está definido y nunca se ha jugado'
                    : $torneosSinJugar->count() . ' torneos están definidos y nunca se han jugado',
                'texto' => 'Existen y tienen reglas, pero no han tenido ni una edición.',
                'accion' => 'Ver torneos',
                'url' => route('universes.tournaments.index', $universe),
                'cuantos' => $torneosSinJugar->count(),
            ]);
        }

        if ($statistics['trophies'] > 0 && $statistics['trophy_awards'] === 0) {

            $atencion->push([
                'tono' => '#fbbf24',
                'urgente' => false,
                'titulo' => 'La vitrina está hecha y vacía',
                'texto' => 'Hay trofeos creados que no se le han dado a nadie todavía.',
                'accion' => 'Abrir la vitrina',
                'url' => route('universes.trophies.index', $universe),
                'cuantos' => $statistics['trophies'],
            ]);
        }

        if ($statistics['never_competed'] > 0 && $statistics['entities'] > 0) {

            $atencion->push([
                'tono' => '#94a3b8',
                'urgente' => false,
                'titulo' => $statistics['never_competed'] === 1
                    ? 'Un competidor no ha jugado nunca'
                    : $statistics['never_competed'] . ' competidores no han jugado nunca',
                'texto' => 'Están en el mundo pero no han entrado en ninguna competición.',
                'accion' => 'Verlos en el mapa',
                'url' => route('universes.explorer', $universe) . '?criterio=COMPITE',
                'cuantos' => $statistics['never_competed'],
            ]);
        }

        $atencion = $atencion
            ->sortByDesc('urgente')
            ->values();


        /*
        |--------------------------------------------------------------------------
        | La temporada que corre
        |--------------------------------------------------------------------------
        |
        | Que le toca a esta temporada segun la recurrencia de cada torneo, que
        | se jugo ya y que falta. Es la unica forma de saber si la temporada va
        | por la mitad o esta sin empezar.
        */

        $alreadyPlayed =
            $activeSeason
            ? $instancias
                ->where('universe_season_id', $activeSeason->id)
                ->pluck('universe_tournament_id')
                ->filter()
                ->all()
            : [];

        $tocanEstaTemporada =
            $activeSeason
            ? $torneos
                ->where('status', 'ACTIVE')
                ->filter(fn($t) => $t->occursInSeason($activeSeason->number))
                ->values()
            : collect();

        $upcoming =
            $tocanEstaTemporada
            ->reject(fn($t) => in_array($t->id, $alreadyPlayed, true))
            ->values();

        $temporada = [
            'tocan' => $tocanEstaTemporada->count(),
            'jugados' => $tocanEstaTemporada->count() - $upcoming->count(),
            'faltan' => $upcoming->count(),
            'competiciones' => $activeSeason
                ? $instancias->where('universe_season_id', $activeSeason->id)->count()
                : 0,
        ];

        $temporada['avance'] =
            $temporada['tocan'] > 0
                ? (int) round($temporada['jugados'] / $temporada['tocan'] * 100)
                : 0;


        /*
        |--------------------------------------------------------------------------
        | La linea del tiempo
        |--------------------------------------------------------------------------
        |
        | Cada temporada con lo que se jugo en ella. Es el mundo entero visto de
        | lado, y sale de datos que ya existian sin ninguna consulta nueva.
        */

        $lineaDelTiempo =
            $temporadas->map(function ($t) use ($instancias, $activeSeason, $universe) {

                $suyas = $instancias->where('universe_season_id', $t->id);

                return [
                    'numero' => $t->number,
                    'nombre' => $t->name,
                    'estado' => $t->status,
                    'activa' => $activeSeason && $activeSeason->id === $t->id,
                    'competiciones' => $suyas->count(),
                    'terminadas' => $suyas->where('status', 'COMPLETED')->count(),
                    'vivas' => $suyas->whereIn('status', ['RUNNING', 'PAUSED'])->count(),
                    'url' => route('universes.seasons.show', [$universe, $t]),
                ];
            })->values();


        /*
        |--------------------------------------------------------------------------
        | Con que se juega este mundo
        |--------------------------------------------------------------------------
        */

        $definiciones =
            collect(app(GameRegistry::class)->definitions())
            ->keyBy('key');

        $juegos =
            $instancias
            ->groupBy(fn($i) => $i->game_key ?: 'SIN_JUEGO')
            ->map(fn($grupo, $clave) => [
                'clave' => $clave,
                'nombre' => $clave === 'SIN_JUEGO'
                    ? 'Sin juego'
                    : ($definiciones[$clave]['name'] ?? $clave),
                'cuantas' => $grupo->count(),
            ])
            ->sortByDesc('cuantas')
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Caras
        |--------------------------------------------------------------------------
        */

        $ranking =
            $this->ranking
            ->ranking($universe)
            ->take(5);

        /*
         * Agrupados por competicion: un torneo que se queda en fase de grupos
         * corona a todos los que clasifican, y pedir cuatro filas sueltas
         * devolvia cuatro caras de una sola edicion.
         */
        $recentChampions =
            $this->ranking
            ->recentChampions($universe, 40)
            ->groupBy('tournament_instance_id')
            ->take(4);

        $mosaico =
            $universe->entities()
            ->whereNotNull('image')
            ->inRandomOrder()
            ->limit(18)
            ->get();

        $activity =
            $universe
            ->activities()
            ->with('universeEntity')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $liveCompetitions =
            $instancias
            ->whereIn('status', ['RUNNING', 'PAUSED'])
            ->sortByDesc('started_at')
            ->take(6)
            ->values();

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
                'upcoming',
                'atencion',
                'temporada',
                'lineaDelTiempo',
                'juegos',
                'mosaico'
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

        /*
         * Todo lo que la configuracion ofrece, sacado de donde vive: las
         * opciones de UniverseSettings, los criterios con los que puede
         * abrirse el mapa y los modos de decision de una batalla.
         */
        $propios = (new \ReflectionClassConstant(UniverseExplorerController::class, 'PROPIOS'))->getValue();

        $atributos = \App\Models\UniverseEntity::query()
            ->where('universe_id', $universe->id)
            ->where('status', 'ACTIVE')
            ->pluck('attribute_snapshot')
            ->flatMap(fn ($filas) => collect((array) $filas)->pluck('name'))
            ->filter()
            ->map(fn ($nombre) => trim((string) $nombre))
            ->unique()
            ->sort()
            ->values();

        $criterios = collect($propios)->map(fn ($meta) => $meta['etiqueta'])->all()
            + $atributos->mapWithKeys(fn ($nombre) => [$nombre => $nombre])->all();

        return view('universes.edit', [
            'universe' => $universe,
            'ajustes' => $universe->ajustes()->all(),
            'porDefecto' => UniverseSettings::defaults(),
            'criteriosMapa' => $criterios,
            'decisiones' => collect(\App\Services\Tournaments\Runtime\CompetitionPhasePlan::DECISION_MODES)
                ->map(fn ($m, $clave) => is_array($m) ? ($m['label'] ?? $m[0] ?? $clave) : (string) $m)
                ->all(),
            'temporadaActiva' => $universe->activeSeason(),
            'cuentas' => [
                'entities' => $universe->entities()->count(),
                'seasons' => $universe->seasons()->count(),
                'tournaments' => $universe->universeTournaments()->count(),
                'competitions' => $universe->tournamentInstances()->count(),
            ],
        ]);
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

        /*
         * Y su configuracion. Llega entera desde la pantalla de ajustes;
         * UniverseSettings solo acepta claves conocidas y cada una en su tipo.
         */
        if (is_array($request->input('settings'))) {
            $universe->fresh()->ajustes()->save($request->input('settings'));
        }

        /*
         * Vuelve a los ajustes, a la seccion en la que estaba. Antes iba a
         * `tournaments.universes.show`, una ruta que no existe.
         */
        $seccion = preg_replace('/[^a-z-]/', '', (string) $request->input('_section'));

        return redirect()
            ->to(route('universes.edit', $universe) . ($seccion ? '#' . $seccion : ''))
            ->with(
                'success',
                'Configuración guardada. Ya se aplica en todo el universo.'
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
