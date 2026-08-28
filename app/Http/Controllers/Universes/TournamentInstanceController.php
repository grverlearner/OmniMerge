<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Universes\StoreTournamentInstanceRequest;
use App\Http\Requests\Universes\TournamentInstanceActionRequest;
use App\Models\TournamentInstance;
use App\Models\Universe;
use App\Models\UniverseEntity;
use App\Models\UniverseTournament;
use App\Services\Tournaments\CompetitionLab\CompetitionLabService;
use App\Services\Tournaments\History\TournamentHistoryService;
use App\Services\Tournaments\Runtime\TournamentInstanceRuntimeService;
use App\Services\Tournaments\Runtime\TournamentInstanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        CompetitionLabService $engine,

        private readonly
        TournamentHistoryService $history
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

        $universeEntities =
            UniverseEntity::query()
            ->where(
                'universe_id',
                $universe->id
            )
            ->where(
                'status',
                'ACTIVE'
            )
            /*
             * Sin eager loading de Biblioteca: la entidad del Universo
             * lleva su propia copia de nombre, imagen, tipo y atributos.
             */
            ->get()
            ->sortBy(
                fn($universeEntity) =>
                mb_strtolower(
                    $universeEntity->display_label
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

        /*
         * Las formas con las que se puede jugar esta edicion.
         *
         * La del torneo primero, porque es lo habitual; despues las demas
         * plantillas activas del mismo dueno. Es lo que permite que la
         * cuarta temporada tenga una fase previa que la primera no tenia.
         */
        $availableTemplates = $this->availableTemplates($universeTournament);

        return view(
            'universes.competitions.create',
            compact(
                'universe',
                'universeTournament',
                'template',
                'starts',
                'universeEntities',
                'seasons',
                'activeSeason',
                'graphErrors',
                'availableTemplates'
            )
        );
    }

    /*
     * Las plantillas entre las que puede elegir una competicion.
     *
     * @return array<int,array{id:int,name:string,phases:int,is_default:bool}>
     */
    private function availableTemplates(UniverseTournament $universeTournament): array
    {
        $default = $universeTournament->tournamentTemplate;

        if (! $default) {
            return [];
        }

        return \App\Models\TournamentTemplate::query()
            ->where('user_id', $default->user_id)
            ->where('status', 'ACTIVE')
            ->withCount('graphNodes')
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$default->id])
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'phases' => $t->graph_nodes_count,
                'is_default' => (int) $t->id === (int) $default->id,
            ])
            ->all();
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

        /*
         * La Entidad se carga solo para la imagen y para poder enlazar
         * a su ficha: el nombre, la versión y los atributos que se
         * muestran vienen congelados de la propia fila.
         */
        $participants =
            $competition
            ->participants()
            ->with('universeEntity')
            ->get();

        $events =
            $competition
            ->events()
            ->orderByDesc('sequence')
            ->limit(40)
            ->get();

        /*
         * Historial (Fase 8). Se calcula siempre: una competición en
         * curso también tiene historia parcial que merece verse.
         */
        $history =
            $this->history
            ->summary($competition);

        $phaseBlocks =
            $this->history
            ->phases($competition);

        $finalStandings =
            $this->history
            ->standings($competition);

        return view(
            'universes.competitions.show',
            compact(
                'universe',
                'competition',
                'payload',
                'participants',
                'events',
                'history',
                'phaseBlocks',
                'finalStandings'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Jugar: la experiencia de ejecución (Fase 13)
    |--------------------------------------------------------------------------
    |
    | Misma fuente de verdad que show(), otra puesta en escena. Aquí no se
    | calcula nada nuevo: el Runtime ya sabe quién compite, el historial ya
    | sabe cómo va cada fase, y el Game Engine ya registró cada número.
    |
    | Una competición terminada abre la misma pantalla en solo lectura.
    |
    */

    public function play(
        Universe $universe,
        TournamentInstance $competition
    ): View {

        $this->authorize('view', $competition);

        $competition->load([
            'universeTournament',
            'season',
        ]);

        $payload =
            $this->runtime->payload($competition);

        $participants =
            $competition->participants()
            ->with('universeEntity.gameStats')
            ->orderBy('seed')
            ->get();

        /*
         * Las fases se leen del historial, que desde la Fase 8 funciona
         * igual con una competición en curso: no hay lógica paralela.
         */
        $phaseBlocks =
            $this->history->phases($competition);

        $standings =
            $this->history->standings($competition);

        $definition =
            app(\App\Services\Games\GameRegistry::class)
            ->definition($competition->game_key);

        /* Posición de cada competidor en la clasificación del Universo */
        $ranking =
            app(\App\Services\Universes\UniverseRankingService::class)
            ->ranking($universe)
            ->keyBy('universe_entity_id');

        /*
         * Indice ligero de batallas.
         *
         * Aqui se construia el detalle COMPLETO de las 238 batallas
         * -trofeos, atributos con icono, historial de duelos, valores de
         * cada juego- para que la interfaz pudiera abrir cualquiera sin
         * ir al servidor. Costaba ~5 s, ~1.900 consultas y 4 MB de HTML,
         * y 237 de esas batallas no se abren nunca.
         *
         * Ahora viaja solo lo justo para PINTAR la cabecera al pulsar; el
         * detalle se pide a universes.competitions.battles.show. El coste
         * pasa de "todas las batallas siempre" a "la que estas mirando".
         */
        $battles =
            $competition->matches()
            ->get([
                'runtime_match_id',
                'label',
                'round_number',
                'group_label',
                'status',
            ])
            ->mapWithKeys(
                fn($match) => [

                    $match->runtime_match_id => [
                        'label' => $match->label,
                        'round' => $match->round_number,
                        'group' => $match->group_label,
                        'status' => $match->status,
                    ],
                ]
            );

        /*
         * Hasta donde el torneo AFIRMA una posicion.
         *
         * El motor distingue entre RANKED —una posicion que se disputo— y
         * TIED_BAND —un rango sin desempatar, tipico del 3.o y 4.o cuando
         * no hubo partido por el tercer puesto—. Sin esta distincion, el
         * podio inventaba un bronce que nadie gano.
         */
        $bands = [];

        foreach (($payload['state']['nodes'] ?? []) as $node) {

            foreach (($node['runtime']['standings'] ?? []) as $row) {

                $key = (string) ($row['participant_id'] ?? '');

                if ($key === '') {
                    continue;
                }

                $bands[$key] = [
                    'from' => (int) ($row['position_from'] ?? $row['position'] ?? 0),
                    'to' => (int) ($row['position_to'] ?? $row['position'] ?? 0),
                    'definitive' => ($row['placement_status'] ?? null) === 'RANKED',
                ];
            }
        }

        /*
         * El recorrido del campeon: cada batalla que tuvo que ganar,
         * con su fase y su rival. Se lee de lo ya proyectado.
         */
        $championKey =
            $standings->firstWhere('outcome', 'CHAMPION')?->runtime_key;

        $championRoute = collect();

        if ($championKey) {

            $phaseNames =
                $phaseBlocks->mapWithKeys(
                    fn($block) => [
                        $block['phase']->node_id => $block['phase']->node_name,
                    ]
                );

            $championRoute =
                $competition->matches()
                ->where('status', 'COMPLETED')
                ->where(
                    fn($query) => $query
                        ->where('participant_a_key', $championKey)
                        ->orWhere('participant_b_key', $championKey)
                )
                ->with(['participantAEntity', 'participantBEntity'])
                ->orderBy('round_number')
                ->orderBy('id')
                ->get()
                ->map(
                    function ($match) use ($championKey, $phaseNames) {

                        $isA = $match->participant_a_key === $championKey;

                        return [
                            'match' => $match,
                            'phase' => $phaseNames->get($match->node_id) ?? $match->node_id,
                            'round' => $match->round_number,
                            'group' => $match->group_label,
                            'rival_name' => $isA
                                ? $match->participant_b_name
                                : $match->participant_a_name,
                            'rival_entity' => $isA
                                ? $match->participantBEntity
                                : $match->participantAEntity,
                            'score' => $match->series
                                ? ($isA ? $match->series_score : array_reverse($match->series_score))
                                : null,
                            'won' => $match->winner_key === $championKey,
                        ];
                    }
                );
        }

        /*
         * Puntos reales por fase (Fase 13).
         *
         * La clasificacion del motor solo sabe de victorias y derrotas.
         * Esto anade lo que cada uno hizo y encajo de verdad, cuando el
         * juego declara que sus tiradas cuentan como puntos.
         */
        $points = app(\App\Services\Tournaments\Runtime\PhasePointsService::class);

        $tracksPoints = $points->tracksPoints($competition->game_key);

        $pointsLabel = $points->label($competition->game_key);

        $pointsByPhase = $tracksPoints
            ? $phaseBlocks->mapWithKeys(
                fn($block) => [
                    (string) $block['phase']->node_id =>
                    $points->forPhase($competition, (string) $block['phase']->node_id),
                ]
            )
            : collect();

        /*
         * Donde esta el corte de clasificacion de cada fase (Fase 13).
         * Mientras la fase se juega nadie es ADVANCED todavia, asi que sin
         * esto no se distingue quien esta pasando de quien esta quedando
         * fuera.
         */
        $qualification =
            app(\App\Services\Tournaments\Runtime\PhaseQualificationService::class)
            ->forInstance($competition);

        /*
         * Qué se ha llevado cada competidor (Fase 12).
         *
         * Los premios existían pero eran invisibles: un bonus solo se
         * notaba como un número raro dentro de una batalla. Aquí se ven,
         * y sobre todo se distingue lo que dura de lo que no.
         */
        $awards =
            app(\App\Services\Rewards\CompetitionAwardsService::class)
            ->forInstance($competition);

        $readonly =
            $competition->isClosed()
            || $competition->status === 'PAUSED';

        return view(
            'universes.competitions.play',
            compact(
                'universe',
                'competition',
                'payload',
                'participants',
                'phaseBlocks',
                'standings',
                'definition',
                'ranking',
                'battles',
                'bands',
                'championRoute',
                'tracksPoints',
                'pointsLabel',
                'pointsByPhase',
                'qualification',
                'awards',
                'readonly'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Ajustar a mano la stat de un competidor
    |--------------------------------------------------------------------------
    |
    | Salta por encima de todo lo que normalmente explica un cambio: el
    | torneo, la recompensa, la regla que lo justificaba. Por eso exige una
    | confirmación explícita y se registra como MANUAL, para que dentro de
    | seis meses se distinga de lo que alguien ganó jugando.
    |
    | Escribe donde escribe todo lo demás —universe_stat_changes y la stat
    | del competidor— porque un ajuste manual que no dejara rastro sería
    | precisamente lo que no se quiere.
    |
    */

    public function adjust(
        Request $request,
        Universe $universe,
        TournamentInstance $competition
    ): RedirectResponse {

        $this->authorize('update', $universe);

        abort_unless(
            $competition->universe_id === $universe->id,
            404
        );

        $data = $request->validate([

            'universe_entity_id' => [
                'required',
                Rule::exists('universe_entities', 'id')
                    ->where('universe_id', $universe->id),
            ],

            'stat_key' => ['required', 'string', 'max:60'],

            'operation' => [
                'required',
                Rule::in(array_keys(\App\Models\UniverseTournamentReward::OPERATIONS)),
            ],

            'amount' => ['required', 'numeric', 'between:-9999,9999'],

            'reason' => ['nullable', 'string', 'max:150'],

            'confirm' => ['accepted'],
        ]);

        $change =
            app(\App\Services\Rewards\ManualStatAdjuster::class)
            ->apply(
                $competition,
                (int) $data['universe_entity_id'],
                $data['stat_key'],
                $data['operation'],
                (float) $data['amount'],
                $data['reason'] ?? null
            );

        if ($change === null) {

            return back()->withErrors([
                'adjust' => 'Ese competidor no tiene esa estadística en el juego de la competición.',
            ]);
        }

        return back()->with(
            'success',
            'Ajuste aplicado: ' . $change['stat_key'] . ' pasa de '
                . $change['before'] . ' a ' . $change['after'] . '.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Acción del motor
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | El detalle de UNA batalla
    |--------------------------------------------------------------------------
    |
    | Antes esto viajaba dentro de la pagina, multiplicado por todas las
    | batallas de la competicion. Es material caro -trofeos, atributos con
    | su icono de catalogo, historial de duelos, los valores de cada juego-
    | y solo se mira de una cada vez, asi que se sirve cuando se pide.
    |
    */

    public function battle(
        Universe $universe,
        TournamentInstance $competition,
        string $battle
    ): JsonResponse {

        $this->authorize('view', $competition);

        $match =
            $competition->matches()
            ->where('runtime_match_id', $battle)
            ->with([
                'participantAEntity',
                'participantBEntity',
            ])
            ->firstOrFail();

        return response()->json(
            $this->battlePayload(
                $competition,
                $match,
                $this->optionThumbs($universe)
            )
        );
    }

    /**
     * Miniaturas del catalogo.
     *
     * El snapshot congelado guarda los valores de atributo como texto
     * ("boruto", "hoja") porque eso es lo que importa para competir. Para
     * PINTARLOS se recupera el icono de la opcion del catalogo del
     * usuario, que es material de presentacion, no competitivo.
     *
     * Si una opcion se renombro o se borro, el atributo se muestra como
     * texto. Nunca se inventa una imagen.
     */
    private function optionThumbs(
        Universe $universe
    ): \Illuminate\Support\Collection {

        return \App\Models\AttributeOption::query()
            ->whereHas(
                'attribute',
                fn($query) => $query->where('user_id', $universe->user_id)
            )
            ->get()
            ->mapWithKeys(
                fn($option) => [
                    mb_strtolower(trim($option->name)) => [
                        'image' => $option->image_url,
                        'icon' => $option->icon,
                        'color' => $option->color,
                    ],
                ]
            );
    }

    /**
     * Todo lo que la ventana de batalla necesita pintar.
     */
    private function battlePayload(
        TournamentInstance $competition,
        \App\Models\TournamentInstanceMatch $match,
        \Illuminate\Support\Collection $optionThumbs
    ): array {

        $battleView =
            app(\App\Services\Tournaments\Runtime\BattleViewService::class);

        $data = $battleView->battle($competition, $match);

        return [

            'label' => $match->label,
            'round' => $match->round_number,
            'group' => $match->group_label,
            'status' => $match->status,

            'series' => $data['series'],

            'participants' =>
            $data['participants']
                ->map(
                    fn(array $participant) => [
                        'key' => $participant['key'],
                        'name' => $participant['name'],
                        'image' => $participant['image_url'],
                        'is_winner' => $participant['is_winner'],
                        'stats' => $participant['stats'],
                        'trophies' => $participant['trophies'],

                        /*
                         * Atributos congelados + su icono
                         * de catalogo cuando lo hay.
                         */
                        'attributes' =>
                        collect(
                            $participant['participant']?->attribute_snapshot ?? []
                        )
                            ->map(
                                function (array $attribute) use ($optionThumbs) {

                                    $key = mb_strtolower(
                                        trim((string) ($attribute['display'] ?? ''))
                                    );

                                    $thumb = $optionThumbs->get($key);

                                    return [
                                        'name' => $attribute['name'] ?? '',
                                        'display' => $attribute['display'] ?? '',
                                        'numeric' => $attribute['numeric'] ?? null,
                                        'image' => $thumb['image'] ?? null,
                                        'icon' => $thumb['icon'] ?? null,
                                    ];
                                }
                            )
                            ->values(),
                    ]
                )
                ->values(),

            'encounters' =>
            $data['encounters']
                ->map(
                    fn(array $encounter) => [
                        'number' => $encounter['number'],
                        'values' => $encounter['values'],
                        'summary' => $encounter['summary'],
                        'is_draw' => $encounter['is_draw'],
                        'is_tiebreak' => $encounter['is_tiebreak'] ?? false,
                    ]
                )
                ->values(),

            'head_to_head' =>
            $data['head_to_head']
                ? [
                    'total' => $data['head_to_head']['total'],
                    'left_wins' => $data['head_to_head']['left_wins'],
                    'right_wins' => $data['head_to_head']['right_wins'],

                    /*
                     * Los ultimos duelos, no solo el
                     * agregado: "gano Naruto, gano Sasuke,
                     * gano Naruto" cuenta una historia que
                     * un 2-1 no cuenta.
                     */
                    'recent' =>
                    collect($data['head_to_head']['matches'] ?? [])
                        ->take(5)
                        ->map(
                            fn($previous) => [
                                'competition' =>
                                $previous->tournamentInstance?->name,

                                'winner' =>
                                $previous->winner_key === $previous->participant_a_key
                                    ? $previous->participant_a_name
                                    : ($previous->winner_key === $previous->participant_b_key
                                        ? $previous->participant_b_name
                                        : null),

                                'score' =>
                                $previous->series
                                    ? implode('–', $previous->series_score)
                                    : null,

                                'is_draw' => (bool) $previous->is_draw,
                            ]
                        )
                        ->values(),
                ]
                : null,

            'is_playable' => $data['is_playable'],
        ];
    }

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
