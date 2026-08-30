<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Universes\StoreTournamentInstanceRequest;
use App\Http\Requests\Universes\UpdateTournamentInstanceRequest;
use App\Http\Requests\Universes\TournamentInstanceActionRequest;
use App\Models\TournamentInstance;
use App\Models\Universe;
use App\Models\UniverseEntity;
use App\Models\UniverseTournament;
use App\Services\Tournaments\CompetitionLab\CompetitionLabService;
use App\Services\Tournaments\History\TournamentHistoryService;
use App\Services\Tournaments\Runtime\TournamentInstanceRuntimeService;
use App\Services\Tournaments\Runtime\TournamentInstanceService;
use App\Services\Universes\CompetitionConfigurationService;
use App\Services\Universes\CompetitionDesignerContext;
use App\Services\Universes\CompetitionStartRouting;
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
        private readonly CompetitionDesignerContext $designer,
        private readonly CompetitionConfigurationService $configuration,
        private readonly CompetitionStartRouting $routing,
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
            ->with([
                'tournamentTemplate',
                'rewards.trophy',
            ])
            ->findOrFail(
                (int) $request->query(
                    'universe_tournament_id'
                )
            );

        /*
         * De que edicion se parte.
         *
         * La siguiente casi nunca se disena de cero: se copia la anterior
         * y se cambia lo que cambio -mas gente, otra fase previa, otro
         * juego-. Lo que NO cambia son las reglas del torneo, que siguen
         * siendo suyas.
         */
        $source = null;

        if ($copyId = (int) $request->query('copy')) {

            $source = TournamentInstance::query()
                ->where('universe_id', $universe->id)
                ->where('universe_tournament_id', $universeTournament->id)
                ->with(['phases', 'rewards'])
                ->find($copyId);
        }

        return view(
            'universes.competitions.create',
            [
                'universe' => $universe,
                'universeTournament' => $universeTournament,
                'competition' => null,
                'source' => $source,
                'seasons' => $this->seasons($universe),
                ...$this->designer->build(
                    $universe,
                    $universeTournament,
                    null,
                    $source
                ),
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    |
    | Retocar una edicion que todavia no empezo.
    |
    | Se puede cambiar como se pelea y que se lleva quien gane; no se puede
    | cambiar la plantilla ni quien compite, porque eso congelo el estado
    | inicial al crearla. Para eso esta copiar.
    |
    */

    public function edit(
        Universe $universe,
        TournamentInstance $competition
    ): View|RedirectResponse {

        $this->authorize('update', $universe);

        abort_unless(
            (int) $competition->universe_id === (int) $universe->id,
            404
        );

        if ($competition->status !== 'DRAFT') {

            return redirect()
                ->route('universes.competitions.show', [$universe, $competition])
                ->with(
                    'error',
                    'Esta edición ya empezó: su configuración quedó congelada. '
                        . 'Para jugar con otras reglas, crea una edición nueva copiando esta.'
                );
        }

        $universeTournament =
            $competition->universeTournament()
            ->with(['tournamentTemplate', 'rewards.trophy'])
            ->firstOrFail();

        $competition->load(['phases', 'rewards', 'trophies']);

        return view(
            'universes.competitions.edit',
            [
                'universe' => $universe,
                'universeTournament' => $universeTournament,
                'competition' => $competition,
                'source' => null,
                'seasons' => $this->seasons($universe),
                ...$this->designer->build(
                    $universe,
                    $universeTournament,
                    $competition
                ),
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateTournamentInstanceRequest $request,
        Universe $universe,
        TournamentInstance $competition
    ): RedirectResponse {

        abort_unless(
            (int) $competition->universe_id === (int) $universe->id,
            404
        );

        if ($competition->status !== 'DRAFT') {

            return back()->with(
                'error',
                'Esta edición ya empezó: su configuración quedó congelada.'
            );
        }

        $startRules = $request->startRulesPayload();

        $this->configuration->apply(
            $competition,
            $competition->universeTournament,
            $request->validated(),
            $request->phasesPayload(),
            $request->rewardsPayload(),
            $startRules,
            $request->file('image')
        );

        /*
         * Y si ademas cambio quien compite, se rehace el cuadro.
         *
         * Solo mientras no se haya jugado nada: el servicio lo comprueba y
         * se niega si toca. Va DESPUES de guardar la configuracion para que
         * el estado nuevo nazca ya con el formato de batalla recien
         * elegido.
         */
        $aviso = 'Edición actualizada.';

        if ($this->service->canReassign($competition)) {

            $assignments = $request->assignments();

            if ($assignments === [] && $startRules) {

                $assignments = array_filter(
                    $this->routing->route(
                        $universe,
                        $startRules,
                        $this->capacitiesOf($competition)
                    )['assignments'],
                    fn ($ids) => $ids !== []
                );
            }

            if ($assignments !== []) {

                $this->service->reassign($competition->fresh(), $assignments);

                $aviso = 'Edición actualizada, y su cuadro rehecho con '
                    . collect($assignments)->flatten()->count() . ' competidores.';
            }
        }

        return redirect()
            ->route('universes.competitions.show', [$universe, $competition])
            ->with('success', $aviso);
    }


    /*
    |--------------------------------------------------------------------------
    | Quien entra por cada puerta
    |--------------------------------------------------------------------------
    |
    | Devuelve, para las reglas que se estan escribiendo, a quien manda
    | cada una y quien se queda fuera. Se consulta mientras se escribe: sin
    | esto, saber si una regla trae 8 o 30 competidores obligaba a guardar
    | y volver.
    |
    */

    public function startPreview(
        Request $request,
        Universe $universe
    ) {

        $this->authorize('update', $universe);

        $capacities = collect((array) $request->input('capacities', []))
            ->mapWithKeys(fn ($v, $k) => [
                (int) $k => ($v === null || $v === '') ? null : (int) $v,
            ])
            ->all();

        $routed = $this->routing->route(
            $universe,
            (array) $request->input('start_rules', []),
            array_filter($capacities, fn ($v) => $v !== null)
        );

        return response()->json($routed);
    }


    private function seasons(Universe $universe)
    {
        return $universe
            ->seasons()
            ->whereIn('status', ['PLANNED', 'ACTIVE'])
            ->get();
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

        $startRules = $request->startRulesPayload();

        /*
         * Quien entra por cada puerta.
         *
         * Dos formas de decirlo, y las dos valen: marcarlos a mano, o
         * describirlos con una regla -"los que lleven sharingan"-. La
         * regla se resuelve AQUI, en el servidor, y no se confia en lo que
         * la pantalla calculo: una pantalla abierta hace media hora pudo
         * quedarse con competidores que ya no estan.
         */
        $assignments = $request->assignments();

        if ($assignments === [] && $startRules) {

            $assignments = $this->routing->route(
                $universe,
                $startRules,
                $this->capacities($universeTournament, $request)
            )['assignments'];

            $assignments = array_filter($assignments, fn ($ids) => $ids !== []);
        }

        if ($assignments === []) {

            return back()
                ->withInput()
                ->withErrors([
                    'assignments' =>
                    'Ningún competidor entra en la competición: márcalos a mano '
                        . 'o escribe una regla que seleccione a alguien.',
                ]);
        }

        $instance =
            $this->service
            ->create(
                $universe,
                $universeTournament,
                $request->validated(),
                $assignments
            );

        /*
         * El resto de la configuracion, DESPUES de crear.
         *
         * No es un detalle de orden: la excepcion de una fase se guarda en
         * su fila, y esas filas no existen hasta que el proyector dibuja
         * el grafo de esta edicion.
         */
        $this->configuration->apply(
            $instance,
            $universeTournament,
            $request->validated(),
            $request->phasesPayload(),
            $request->rewardsPayload(),
            $startRules,
            $request->file('image')
        );

        if ($copied = (int) $request->input('copied_from_instance_id')) {
            $instance->update(['copied_from_instance_id' => $copied]);
        }

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
     * Cuantos caben por cada puerta de la plantilla elegida.
     *
     * Hace falta para que una regla que trae 30 no meta 30 por una puerta
     * de 8: el sobrante se aparta y se dice, en vez de reventar al crear
     * el estado inicial.
     */
    /*
     * Lo mismo, para una edicion que ya existe: su plantilla ya se congelo.
     */
    private function capacitiesOf(TournamentInstance $competition): array
    {
        $template = \App\Models\TournamentTemplate::query()
            ->with('graphStarts')
            ->find($competition->tournament_template_id);

        if (! $template) {
            return [];
        }

        return $template->graphStarts
            ->where('status', 'ACTIVE')
            ->filter(fn ($s) => $s->expected_participants)
            ->mapWithKeys(fn ($s) => [(int) $s->id => (int) $s->expected_participants])
            ->all();
    }

    private function capacities(
        UniverseTournament $universeTournament,
        StoreTournamentInstanceRequest $request
    ): array {

        $templateId = (int) ($request->validated('tournament_template_id')
            ?: $universeTournament->tournament_template_id);

        $template = \App\Models\TournamentTemplate::query()
            ->with('graphStarts')
            ->find($templateId);

        if (! $template) {
            return [];
        }

        return $template->graphStarts
            ->where('status', 'ACTIVE')
            ->filter(fn ($s) => $s->expected_participants)
            ->mapWithKeys(fn ($s) => [
                (int) $s->id => (int) $s->expected_participants,
            ])
            ->all();
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
        /*
         * La clave lleva la FASE delante.
         *
         * El identificador de un enfrentamiento es local a su motor: la
         * primera jornada de una liga es «RR-R1-M1» venga de la fase que
         * venga. Con dos ligas jugandose a la vez, este indice tenia 45
         * entradas en vez de 90 y pulsar una batalla podia abrir la de la
         * otra fase. La columna sigue guardando el nombre del motor; lo que
         * viaja a la pantalla es «94:RR-R1-M1».
         */
        $battles =
            $competition->matches()
            ->get([
                'node_id',
                'runtime_match_id',
                'label',
                'round_number',
                'group_label',
                'status',
            ])
            ->mapWithKeys(
                fn($match) => [

                    $match->node_id . ':' . $match->runtime_match_id => [
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
         * Las salidas por puesto que no se pudieron servir.
         *
         * Una salida «#3 lugar» en un cuadro puro no tiene a quien mandar:
         * varios cayeron en la misma ronda y nadie jugo para separarlos. El
         * motor lo anota en vez de elegir uno al azar, y la pantalla lo
         * dice en vez de dejar la salida muda.
         */
        $unresolvedExits = [];

        foreach (($payload['state']['nodes'] ?? []) as $node) {

            foreach (($node['runtime']['unresolved_exits'] ?? []) as $fila) {

                $unresolvedExits[] = $fila + [
                    'node_name' => $node['name'] ?? null,
                ];
            }
        }

        /*
         * Los puestos que cada fase va a disputar.
         *
         * Un cuadro no decide quien es tercero: dos pierden en semifinales y
         * ahi acaban los dos. Si la fase pide un «#3 lugar», ese puesto se
         * juega DESPUES del cuadro, en batallas que el motor crea al
         * terminarlo.
         *
         * Se anuncia desde el primer momento porque, sin decirlo, la fase
         * arranca exactamente igual que una sin puestos configurados y no hay
         * forma de distinguir «todavia no toca» de «no se aplico nada».
         *
         * Se calcula aqui y no se lee del estado guardado: el estado solo se
         * reescribe al ejecutar una accion, asi que anadir un premio al 5.o
         * puesto y recargar la pantalla no habria ensenado nada hasta jugar
         * la siguiente batalla. Lo que hay que decidir es una pregunta con
         * respuesta ahora mismo.
         */
        $placementPlan = [];

        $demandas =
            app(\App\Services\Tournaments\Runtime\PlacementDemands::class)
            ->forCompetition($competition);

        foreach ($demandas as $nodeId => $pedidos) {

            if ($pedidos === []) {
                continue;
            }

            $node = $payload['state']['nodes'][$nodeId] ?? [];

            $reparto = $node['runtime']['placement'] ?? null;

            /* Cuantas batallas de desempate quedan por jugar */
            $pendientes = 0;

            foreach (($node['runtime']['rounds'] ?? []) as $ronda) {

                if (! isset($ronda['placement'])) {
                    continue;
                }

                foreach (($ronda['matches'] ?? []) as $match) {
                    if (($match['status'] ?? null) === 'PENDING') {
                        $pendientes++;
                    }
                }
            }

            $placementPlan[(int) $nodeId] = [
                'wanted' => $pedidos,
                'total' => count($pedidos),
                'started' => $reparto !== null,
                'done' => (bool) ($reparto['done'] ?? false),
                'pending' => $pendientes,
            ];
        }

        /*
         * De dónde salió el reparto en grupos de cada fase.
         *
         * Cuando el recorrido ya lo decidió —cada puerta de entrada llena su
         * grupo— la fase no pregunta nada. Eso está bien, pero callarlo
         * dejaría al usuario sin saber por qué unos acabaron en el Grupo A y
         * otros en el B. Aquí se recupera para poder decirlo.
         */
        $groupSources = [];

        foreach (($payload['state']['nodes'] ?? []) as $nodeId => $node) {

            $fuente = $node['runtime']['group_source'] ?? null;

            if (! is_array($fuente) || $fuente === []) {
                continue;
            }

            $nombresDePuerta =
                collect($node['entry_ports'] ?? [])
                ->pluck('name')
                ->values()
                ->all();

            $groupSources[(int) $nodeId] =
                collect($fuente)
                ->map(
                    fn ($fila) => [
                        'group_name' => $fila['group_name'] ?? '',
                        'port_name' => $nombresDePuerta[$fila['port_index'] ?? -1]
                            ?? ('Entrada ' . ((int) ($fila['port_index'] ?? 0) + 1)),
                        'count' => count($fila['participant_ids'] ?? []),
                    ]
                )
                ->values()
                ->all();
        }

        /*
         * La forma real del recorrido: qué fases van a la vez y cuáles
         * esperan a cuáles. La arena la trataba como una fila, y un
         * recorrido con dos fases en paralelo no lo es.
         */
        $phaseGraph =
            app(\App\Services\Tournaments\Runtime\CompetitionPhaseGraph::class)
            ->forCompetition($competition);

        /*
         * Las decisiones que el motor está esperando.
         *
         * Una fase configurada «a mano» —grupos manuales, orden manual,
         * BYEs elegidos— no arranca sola: el motor la deja parada y pide
         * que alguien decida. Eso existía y funcionaba desde el Lab, pero
         * esta pantalla no lo enseñaba: ofrecía «Abrir la fase», y abrir
         * exige un recorrido en marcha, así que respondía «el Tournament
         * Graph Runtime no está en ejecución» —cierto y completamente
         * inútil para saber qué hacer—.
         *
         * Aquí se saca a la superficie, con las caras: repartir doce
         * competidores en cuatro grupos leyendo claves «UC-000123» no lo
         * hace nadie.
         */
        $pendingDecisions = [];

        $caras =
            $participants
            ->keyBy('runtime_key')
            ->map(
                fn($p) => [
                    'key' => $p->runtime_key,
                    'name' => $p->name ?: ($p->universeEntity?->name ?? $p->runtime_key),
                    'image_url' => $p->universeEntity?->image_url,
                    'seed' => (int) $p->seed,
                ]
            );

        foreach (($payload['state']['nodes'] ?? []) as $nodeId => $node) {

            $decision = $node['runtime']['manual_decision'] ?? null;

            if (! is_array($decision)) {
                continue;
            }

            $pendingDecisions[] = [
                'node_id' => (int) $nodeId,
                'node_name' => $node['name'] ?? 'Fase',
                'phase_type' => $node['phase_type'] ?? null,
                'decision' => $decision,

                'participants' =>
                collect($decision['eligible_participant_ids'] ?? [])
                    ->map(
                        fn($clave) => $caras[$clave] ?? [
                            'key' => $clave,
                            'name' => $clave,
                            'image_url' => null,
                            'seed' => 0,
                        ]
                    )
                    ->values()
                    ->all(),
            ];
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
                'unresolvedExits',
                'placementPlan',
                'pendingDecisions',
                'phaseGraph',
                'groupSources',
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

        /*
         * «94:RR-R1-M1» — la fase delante, porque el nombre del motor se
         * repite entre fases paralelas. Se admite tambien la forma antigua,
         * sin fase: las competiciones de una sola fase siguen funcionando
         * y los enlaces guardados no se rompen.
         */
        $consulta =
            $competition->matches()
            ->with([
                'participantAEntity',
                'participantBEntity',
            ]);

        if (str_contains($battle, ':')) {

            [$nodo, $clave] = explode(':', $battle, 2);

            $consulta
                ->where('node_id', (int) $nodo)
                ->where('runtime_match_id', $clave);
        } else {
            $consulta->where('runtime_match_id', $battle);
        }

        $match = $consulta->firstOrFail();

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
