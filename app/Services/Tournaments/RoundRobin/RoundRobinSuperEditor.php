<?php

namespace App\Services\Tournaments\RoundRobin;

use App\Models\PhaseTemplate;
use App\Models\User;
use App\Services\Tournaments\PhaseEditor\PhaseSuperEditorContract;
use App\Models\PhaseExit;
use App\Models\PhaseInputGate;
use App\Services\Tournaments\PhaseExitService;
use App\Services\Tournaments\Preview\PreviewCastService;
use Illuminate\Validation\Rule;

/*
|--------------------------------------------------------------------------
| RoundRobinSuperEditor
|--------------------------------------------------------------------------
|
| Todo lo que la Super Edicion necesita saber de una fase Round Robin.
|
| No calcula ni un emparejamiento: el calendario sale entero de
| RoundRobinScheduleCalculator, que es el mismo que usan la vista previa
| clasica, el simulador y Group Stage cuando mete un round robin dentro de
| cada grupo. Aqui solo se junta lo que ya existe y se le pone encima la
| capa que faltaba: quien ocupa cada semilla.
|
| Esa es la costura que hace barato todo lo demas. El calculador empareja
| SEMILLAS —1 contra 8, 2 contra 7—, nunca participantes. Asi que cambiar
| el orden de entrada, barajar o reordenar a mano no recalcula nada: es una
| permutacion del mapa semilla -> participante. El calendario no se entera.
|
| Lo que NO se configura aqui, a proposito:
|
|   - Formato de batalla (best of, series, juegos fijos). Las columnas
|     siguen existiendo y el motor las sigue leyendo, pero decidir como se
|     pelea un enfrentamiento es del torneo real, no de la fase.
|
|   - Criterios de desempate. La cadena es fija —puntos, diferencia,
|     anotadas, victorias, enfrentamiento directo— y el motor ya cierra
|     siempre con la semilla, asi que una fase Round Robin no puede acabar
|     en empate irresoluble.
|
*/
class RoundRobinSuperEditor implements PhaseSuperEditorContract
{
    /*
     * Paleta de las puertas.
     *
     * El color no es decoracion: es como se reconoce la misma puerta en el
     * panel derecho, en el borde del participante y en la linea que los une.
     * Va indexada por numero de secuencia para que una puerta conserve su
     * color aunque se cree o se borre otra.
     */
    private const PALETTE = [
        ['key' => 'sky',     'dot' => 'bg-sky-400',     'ring' => 'ring-sky-400/70',     'text' => 'text-sky-300',     'soft' => 'bg-sky-500/10',     'border' => 'border-sky-400/40'],
        ['key' => 'emerald', 'dot' => 'bg-emerald-400', 'ring' => 'ring-emerald-400/70', 'text' => 'text-emerald-300', 'soft' => 'bg-emerald-500/10', 'border' => 'border-emerald-400/40'],
        ['key' => 'amber',   'dot' => 'bg-amber-400',   'ring' => 'ring-amber-400/70',   'text' => 'text-amber-300',   'soft' => 'bg-amber-500/10',   'border' => 'border-amber-400/40'],
        ['key' => 'fuchsia', 'dot' => 'bg-fuchsia-400', 'ring' => 'ring-fuchsia-400/70', 'text' => 'text-fuchsia-300', 'soft' => 'bg-fuchsia-500/10', 'border' => 'border-fuchsia-400/40'],
        ['key' => 'rose',    'dot' => 'bg-rose-400',    'ring' => 'ring-rose-400/70',    'text' => 'text-rose-300',    'soft' => 'bg-rose-500/10',    'border' => 'border-rose-400/40'],
        ['key' => 'violet',  'dot' => 'bg-violet-400',  'ring' => 'ring-violet-400/70',  'text' => 'text-violet-300',  'soft' => 'bg-violet-500/10',  'border' => 'border-violet-400/40'],
        ['key' => 'lime',    'dot' => 'bg-lime-400',    'ring' => 'ring-lime-400/70',    'text' => 'text-lime-300',    'soft' => 'bg-lime-500/10',    'border' => 'border-lime-400/40'],
        ['key' => 'cyan',    'dot' => 'bg-cyan-400',    'ring' => 'ring-cyan-400/70',    'text' => 'text-cyan-300',    'soft' => 'bg-cyan-500/10',    'border' => 'border-cyan-400/40'],
    ];

    /*
     * Tope de jornadas que se dibujan.
     *
     * La vista previa clasica cortaba en 20 y avisaba de que habia mas; aqui
     * la finalidad es revisar la programacion entera, asi que se sube mucho.
     * El tope sigue existiendo porque 512 participantes a doble vuelta son
     * mas de 260.000 enfrentamientos y ningun navegador dibuja eso.
     */
    private const ROUND_LIMIT = 200;

    public function __construct(
        private readonly RoundRobinSettingsService $settingsService,
        private readonly RoundRobinScheduleCalculator $calculator,
        private readonly RoundRobinRankingDefinitionService $definitions,
        private readonly PreviewCastService $cast,
        private readonly RoundRobinSeedRuleResolver $seedRules,
        private readonly RoundRobinGateService $gateService,
        private readonly PhaseExitService $exitService
    ) {}

    public function phaseType(): string
    {
        return 'ROUND_ROBIN';
    }

    public function configView(): string
    {
        return 'tournaments.phase-templates.super.round-robin.config';
    }

    public function stageView(): string
    {
        return 'tournaments.phase-templates.super.round-robin.stage';
    }


    /*
    |--------------------------------------------------------------------------
    | Payload
    |--------------------------------------------------------------------------
    */

    public function payload(
        PhaseTemplate $phaseTemplate,
        ?User $user,
        array $overrides = []
    ): array {

        $settings =
            $this->settingsService->ensure(
                $phaseTemplate
            );

        $contract =
            $this->contract(
                $phaseTemplate,
                $overrides['participants'] ?? null
            );

        /*
         * Las vueltas pueden venir del control del panel sin haberse
         * guardado todavia: el preview tiene que responder a lo que se esta
         * tocando, no a lo ultimo persistido.
         */
        $cycles =
            max(
                1,
                min(
                    10,
                    (int) ($overrides['cycles'] ?? $settings->cycles)
                )
            );

        $preview =
            clone $settings;

        $preview->cycles =
            $cycles;

        $structure =
            $this->calculator->calculate(
                $phaseTemplate,
                $preview,
                $contract['resolved'],
                self::ROUND_LIMIT
            );

        return [
            'phase' =>
            $this->phaseSummary($phaseTemplate),

            'contract' =>
            $contract,

            'settings' => [
                'cycles' =>
                $cycles,

                'initial_order_mode' =>
                $settings->initial_order_mode,

                'ranking_source' =>
                $this->rankingSource($settings),

                'allow_draws' =>
                (bool) $settings->allow_draws,

                'win_points' =>
                (float) $settings->win_points,

                'draw_points' =>
                (float) $settings->draw_points,

                'loss_points' =>
                (float) $settings->loss_points,

                /*
                 * Hasta que jornada se juega. Por defecto la liga entera;
                 * recortarla es lo que da sentido a sembrar por puerta,
                 * porque con el calendario completo todo el mundo se
                 * enfrenta a todo el mundo y el puesto inicial solo cambia
                 * el ORDEN de los partidos, no quien juega contra quien.
                 */
                'round_limit' =>
                $this->roundLimit(
                    $settings,
                    $overrides,
                    $structure
                ),
            ],

            'structure' =>
            $this->structureSummary($structure),

            'rounds' =>
            $structure['rounds'] ?? [],

            'cast' =>
            $this->buildCast(
                $user,
                $contract['resolved']
            ),

            'gates' =>
            $this->gates($phaseTemplate, $contract['resolved']),

            'seed_map' =>
            $this->seedRules->resolve(
                $contract['resolved'],
                $phaseTemplate->inputGates()->get()
            ),

            'exits' =>
            $this->exits($phaseTemplate),

            'catalog' => [
                'order_modes' =>
                $this->orderModes(),

                'ranking_sources' =>
                $this->rankingSources(),

                'standings_columns' =>
                $this->standingsColumns(),

                'tiebreak_chain' =>
                $this->tiebreakChain(),

                /*
                 * Los criterios de ordenacion, en su orden y en clave, para
                 * que la tabla del editor ordene EXACTAMENTE igual que el
                 * motor. Si el navegador llevara su propia lista, las dos
                 * se separarian a la primera correccion.
                 */
                'ranking_keys' =>
                $this->rankingKeys(),

                'seed_rule_types' =>
                \App\Services\Tournaments\RoundRobin\RoundRobinSeedRuleResolver::TYPES,

                'exit_selectors' =>
                $this->exitSelectors(),
            ],

            'diagnostics' =>
            $this->diagnostics(
                $phaseTemplate,
                $contract,
                $structure
            ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Identidad de la fase
    |--------------------------------------------------------------------------
    */

    private function phaseSummary(
        PhaseTemplate $phaseTemplate
    ): array {

        return [
            'id' => $phaseTemplate->id,
            'code' => $phaseTemplate->code,

            'name' => $phaseTemplate->name,
            'description' => $phaseTemplate->description,

            'image_url' => $phaseTemplate->image_url,

            'type' => $phaseTemplate->phase_type,
            'type_label' => $phaseTemplate->type_label,

            'status' => $phaseTemplate->status,
            'status_label' => $phaseTemplate->status_label,

            'visibility' => $phaseTemplate->visibility,

            'contract_label' => $phaseTemplate->participant_contract_label,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Contrato de participantes
    |--------------------------------------------------------------------------
    |
    | Cuanta gente se dibuja. Es un numero de PREVISUALIZACION: mover el
    | control no estrecha el contrato de la fase, que es una pieza de
    | biblioteca reutilizable. Solo se guarda si se pide explicitamente.
    |
    */

    private function contract(
        PhaseTemplate $phaseTemplate,
        ?int $requested
    ): array {

        $min =
            (int) ($phaseTemplate->min_participants ?? 2);

        $max =
            $phaseTemplate->max_participants !== null
            ? (int) $phaseTemplate->max_participants
            : null;

        $exact =
            $phaseTemplate->exact_participants !== null
            ? (int) $phaseTemplate->exact_participants
            : null;

        $multiple =
            $phaseTemplate->participant_multiple !== null
            ? (int) $phaseTemplate->participant_multiple
            : null;

        $default =
            $exact
            ?? max($min, 8);

        $resolved =
            $requested !== null && $requested > 0
            ? $requested
            : $default;

        /*
         * Se acota a lo que el navegador puede dibujar sin morir, no a lo
         * que la fase admite: si el numero pedido viola el contrato, el
         * diagnostico lo dira con su nombre en vez de corregirlo en
         * silencio.
         */
        $resolved =
            max(2, min(256, $resolved));

        return [
            'min' => $min,
            'max' => $max,
            'exact' => $exact,
            'multiple' => $multiple,

            'mode' => $phaseTemplate->participant_mode,

            'default' => $default,
            'requested' => $requested,
            'resolved' => $resolved,

            'is_pinned' => $exact !== null,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Reparto prestado
    |--------------------------------------------------------------------------
    |
    | Caras de verdad, tomadas de los universos y la biblioteca del usuario,
    | solo para poder mirar el editor y entenderlo. No se guarda ninguna, no
    | se inscribe a nadie y no tienen nada que ver con los participantes de
    | un torneo real.
    |
    */

    private function buildCast(
        ?User $user,
        int $count
    ): array {

        return $this->cast
            ->borrow($user, $count)
            ->values()
            ->map(
                fn(array $member, int $index) => [
                    'index' => $index,

                    'name' => $member['name'],

                    'short' =>
                    $this->shortName($member['name']),

                    'image_url' => $member['image_url'] ?? null,

                    'is_borrowed' => (bool) ($member['is_borrowed'] ?? false),
                ]
            )
            ->all();
    }

    private function shortName(string $name): string
    {
        $name = trim($name);

        if (mb_strlen($name) <= 14) {
            return $name;
        }

        /*
         * Se prefiere cortar por palabras: "Konohamaru Sarutobi" se lee
         * mucho mejor como "Konohamaru" que como "Konohamaru Sar…".
         */
        $first = mb_substr(
            $name,
            0,
            mb_strpos($name . ' ', ' ')
        );

        return mb_strlen($first) >= 4 && mb_strlen($first) <= 14
            ? $first
            : mb_substr($name, 0, 13) . '…';
    }


    /*
    |--------------------------------------------------------------------------
    | Puertas
    |--------------------------------------------------------------------------
    */

    private function gates(
        PhaseTemplate $phaseTemplate,
        int $participants
    ): array {

        $gates = $phaseTemplate
            ->inputGates()
            ->get()
            ->values();

        $resolution = $this->seedRules->resolve($participants, $gates);

        return $gates
            ->map(
                function ($gate, int $index) use ($resolution, $participants) {

                    $rule = $this->seedRules->ruleOf($gate);

                    $seeds = $resolution['assignments'][$index] ?? [];

                    return [
                        'id' => $gate->id,
                        'code' => $gate->code,
                        'number' => $gate->sequence_number,
                        'name' => $gate->name,
                        'description' => $gate->description,

                        'color' =>
                        $this->color($gate->sequence_number ?? ($index + 1)),

                        /*
                         * Los puestos de la parrilla que reclama. Es lo que
                         * de verdad decide una puerta en una liga: todos se
                         * enfrentan a todos, asi que lo unico que queda por
                         * decidir es por donde entra cada uno.
                         */
                        'seed_type' => $rule['type'],
                        'seed_count' => $rule['count'],
                        'seed_from' => $rule['from'],
                        'seed_to' => $rule['to'],

                        'seeds' => $seeds,

                        'rule_label' =>
                        $this->seedRules->summarize($rule, $participants),

                        'capacity_label' =>
                        $this->capacityLabel(
                            $gate->min_participants,
                            $gate->max_participants,
                            $gate->exact_participants
                        ),

                        'is_required' => (bool) $gate->is_required,
                        'priority' => $gate->priority,
                        'status' => $gate->status,
                    ];
                }
            )
            ->all();
    }

    private function exits(
        PhaseTemplate $phaseTemplate
    ): array {

        return $phaseTemplate
            ->exits()
            ->get()
            ->values()
            ->map(
                fn($exit, int $index) => [
                    'id' => $exit->id,
                    'code' => $exit->code,
                    'number' => $exit->sequence_number,
                    'name' => $exit->name,

                    'color' =>
                    $this->color(
                        ($exit->sequence_number ?? ($index + 1)) + 3
                    ),

                    'selector_type' => $exit->selector_type,
                    'selector_from' => $exit->selector_from,
                    'selector_to' => $exit->selector_to,

                    'summary' => $exit->selection_summary,

                    /*
                     * Que puestos se lleva, para poder pintarlos sobre la
                     * tabla. null = no depende de la posicion final.
                     */
                    'positions' =>
                    $this->exitPositions($exit),

                    'priority' => $exit->priority,
                    'status' => $exit->status,
                ]
            )
            ->all();
    }

    /*
     * Los puestos que reclama una salida, resueltos contra la cantidad de
     * participantes del preview. Es lo que permite que el panel derecho y la
     * tabla central pinten el mismo color en la misma fila.
     */
    private function exitPositions($exit): ?array
    {
        $from = (int) $exit->selector_from;
        $to = (int) $exit->selector_to;

        return match ($exit->selector_type) {
            'TOP_N' => $from > 0 ? ['from' => 1, 'to' => $from, 'anchor' => 'TOP'] : null,
            'BOTTOM_N' => $from > 0 ? ['from' => 1, 'to' => $from, 'anchor' => 'BOTTOM'] : null,
            'WINNER' => ['from' => 1, 'to' => 1, 'anchor' => 'TOP'],
            'RUNNER_UP' => ['from' => 2, 'to' => 2, 'anchor' => 'TOP'],
            'RANK_POSITION', 'POSITION' => $from > 0 ? ['from' => $from, 'to' => $from, 'anchor' => 'TOP'] : null,
            'RANK_RANGE' => $from > 0 && $to >= $from ? ['from' => $from, 'to' => $to, 'anchor' => 'TOP'] : null,
            default => null,
        };
    }

    private function capacityLabel(
        ?int $min,
        ?int $max,
        ?int $exact
    ): string {

        if ($exact !== null) {
            return $exact . ' exactos';
        }

        if ($min !== null && $max !== null) {
            return $min . '–' . $max;
        }

        if ($min !== null) {
            return $min . ' o más';
        }

        if ($max !== null) {
            return 'hasta ' . $max;
        }

        return 'sin límite';
    }

    private function color(int $sequence): array
    {
        return self::PALETTE[
            ($sequence - 1 + count(self::PALETTE)) % count(self::PALETTE)
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Catálogos
    |--------------------------------------------------------------------------
    */

    private function orderModes(): array
    {
        return [
            'INPUT_ORDER' => [
                'label' => 'Orden de entrada',
                'hint' => 'Compiten en el orden en que llegan a la fase.',
                'live' => true,
            ],

            'RANKING' => [
                'label' => 'Ranking',
                'hint' => 'Se siembra por clasificación. La fuente se elige abajo.',
                'live' => false,
            ],

            'RANDOM' => [
                'label' => 'Aleatorio',
                'hint' => 'El sorteo lo hace el motor al arrancar la fase.',
                'live' => false,
            ],

            'MANUAL' => [
                'label' => 'Manual',
                'hint' => 'La fase se detiene y te pide colocarlos tú.',
                'live' => false,
            ],

            'BY_GATE' => [
                'label' => 'Por puerta',
                'hint' => 'El orden lo dictan las puertas de entrada y su capacidad.',
                'live' => true,
            ],
        ];
    }

    /*
     * Las dos fuentes de ranking.
     *
     * Ninguna se puede resolver aqui: una PhaseTemplate es una pieza de
     * biblioteca, no pertenece a ningun universo ni a ningun torneo, asi que
     * mientras la editas no hay clasificacion que leer. Lo que la fase
     * guarda es CUAL usar cuando corra el torneo de verdad; lo que el editor
     * ensena es una demostracion con las caras prestadas.
     */
    private function rankingSources(): array
    {
        return [
            'TOURNAMENT' => [
                'label' => 'Ranking del torneo',
                'hint' => 'La clasificación acumulada dentro del torneo que use esta fase.',
            ],

            'UNIVERSAL' => [
                'label' => 'Ranking universal',
                'hint' => 'La clasificación global del universo, por encima de un solo torneo.',
            ],
        ];
    }

    private function rankingSource($settings): string
    {
        $stored = $settings->settings['ranking_source'] ?? null;

        return in_array($stored, ['TOURNAMENT', 'UNIVERSAL'], true)
            ? $stored
            : 'TOURNAMENT';
    }

    /*
     * Las columnas de la tabla, con las que ya usa el sistema. Se recortan a
     * lo que una liga necesita mirar de un vistazo: los juegos ganados y
     * perdidos dentro de una serie son cosa del torneo real.
     */
    private function standingsColumns(): array
    {
        $all = $this->definitions->standingsColumns();

        $keep = ['PLAYED', 'WINS', 'DRAWS', 'LOSSES', 'SCORE_FOR', 'SCORE_AGAINST', 'SCORE_DIFFERENCE', 'POINTS'];

        $columns = [];

        foreach ($keep as $key) {
            if (isset($all[$key])) {
                $columns[$key] = $all[$key];
            }
        }

        return $columns;
    }

    /*
     * La cadena de desempate, fija y en orden. Se ensena para que se sepa
     * como se ordena, no para tocarla.
     */
    private function tiebreakChain(): array
    {
        $criteria = $this->definitions->criteria();

        $chain = [
            $this->definitions->primaryCriterion()['label'],
        ];

        foreach ($this->definitions->defaultCriteria() as $key) {
            $chain[] = $criteria[$key]['label'] ?? $key;
        }

        $chain[] = $criteria['SEED']['label'] ?? 'Orden de entrada';

        return $chain;
    }


    /*
     * El orden de ordenacion, en clave y con su direccion.
     *
     * POINTS manda siempre; detras va la cadena fija de desempate y, al
     * final, la semilla, que es unica y por eso cierra cualquier empate.
     */
    private function rankingKeys(): array
    {
        $direction = [
            'POINTS' => 'DESC',
            'SCORE_DIFFERENCE' => 'DESC',
            'SCORE_FOR' => 'DESC',
            'WINS' => 'DESC',
            'FEWEST_LOSSES' => 'ASC',
            'GAME_DIFFERENCE' => 'DESC',
            'GAME_WINS' => 'DESC',
            'SEED' => 'ASC',
        ];

        $keys = [];

        foreach (['POINTS', ...$this->definitions->defaultCriteria(), 'SEED'] as $key) {

            /*
             * El enfrentamiento directo no se puede resolver con una
             * columna de la tabla: necesita mirar el partido concreto. La
             * tabla del editor lo salta y sigue con el criterio siguiente,
             * que es lo mismo que hace el motor cuando no hay resultado.
             */
            if ($key === 'HEAD_TO_HEAD') {
                continue;
            }

            $keys[] = [
                'key' => $key,
                'direction' => $direction[$key] ?? 'DESC',
            ];
        }

        return $keys;
    }

    /*
     * Los selectores que tienen sentido al terminar una liga. No se ofrecen
     * los de eliminacion inmediata: aqui nadie queda eliminado a mitad de
     * fase, todos juegan sus jornadas.
     */
    private function exitSelectors(): array
    {
        return [
            'TOP_N' => [
                'label' => 'Los primeros N',
                'hint' => 'Los N mejores de la clasificación final.',
                'needs' => ['from'],
            ],

            'BOTTOM_N' => [
                'label' => 'Los últimos N',
                'hint' => 'Los N peores de la clasificación final.',
                'needs' => ['from'],
            ],

            'RANK_POSITION' => [
                'label' => 'Un puesto concreto',
                'hint' => 'Solo quien acabe exactamente en ese puesto.',
                'needs' => ['from'],
            ],

            'RANK_RANGE' => [
                'label' => 'Un tramo de puestos',
                'hint' => 'Del puesto X al Y, ambos incluidos.',
                'needs' => ['from', 'to'],
            ],

            'REMAINING' => [
                'label' => 'Los que sobren',
                'hint' => 'Quien no haya salido ya por otra puerta.',
                'needs' => [],
            ],

            'ALL' => [
                'label' => 'Todos',
                'hint' => 'Toda la fase sale por aquí.',
                'needs' => [],
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Estructura y diagnóstico
    |--------------------------------------------------------------------------
    */

    private function structureSummary(array $structure): array
    {
        if (! ($structure['valid'] ?? false)) {
            return [
                'valid' => false,
                'participants' => $structure['participants'] ?? 0,
            ];
        }

        return [
            'valid' => true,

            'participants' => $structure['participants'],
            'cycles' => $structure['cycles'],
            'is_odd' => $structure['is_odd'],

            'rounds_per_cycle' => $structure['rounds_per_cycle'],
            'total_rounds' => $structure['total_rounds'],

            'series_per_cycle' => $structure['series_per_cycle'],
            'total_series' => $structure['total_series'],
            'series_per_round' => $structure['series_per_round'],

            'rests_per_round' => $structure['rests_per_round'],
            'total_rest_assignments' => $structure['total_rest_assignments'],

            'preview_rounds_count' => $structure['preview_rounds_count'],
            'has_more_rounds' => $structure['has_more_rounds'],
        ];
    }

    /*
     * Que esta bien, que avisa y que impide generar la fase.
     *
     * El objetivo es que no haya que guardar para enterarse: los errores del
     * validador de siempre se muestran aqui, con los avisos que solo tienen
     * sentido mirando la pantalla entera.
     */
    private function diagnostics(
        PhaseTemplate $phaseTemplate,
        array $contract,
        array $structure
    ): array {

        $errors = $structure['errors'] ?? [];
        $warnings = [];

        if (($structure['valid'] ?? false)) {

            if ($structure['is_odd']) {
                $warnings[] =
                    'Con '
                    . $structure['participants']
                    . ' participantes (impar) alguien descansa en cada jornada: '
                    . $structure['total_rest_assignments']
                    . ' descansos en total.';
            }

            if ($structure['has_more_rounds']) {
                $warnings[] =
                    'Se dibujan '
                    . $structure['preview_rounds_count']
                    . ' de '
                    . $structure['total_rounds']
                    . ' jornadas: el resto sigue el mismo patrón.';
            }
        }

        if ($phaseTemplate->exits()->where('status', 'ACTIVE')->count() === 0) {
            $warnings[] =
                'Sin puertas de salida nadie avanza a la siguiente fase.';
        }

        /*
         * Dos puertas que reclaman el mismo puesto de la parrilla. No se
         * reparte a escondidas: gana la primera y aqui se dice cual choca,
         * porque una parrilla repartida en silencio es una que nadie pidio.
         */
        $seeds = $this->seedRules->resolve(
            $contract['resolved'],
            $phaseTemplate->inputGates()->get()
        );

        if ($seeds['conflicts'] !== []) {
            $errors[] =
                'Dos puertas de entrada reclaman el mismo puesto de la parrilla: '
                . implode(', ', $seeds['conflicts'])
                . '. Manda la primera, pero conviene arreglarlo.';
        }

        return [
            'status' => match (true) {
                $errors !== [] => 'INVALID',
                $warnings !== [] => 'WARNING',
                default => 'VALID',
            },

            'errors' => array_values($errors),
            'warnings' => $warnings,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Persistencia
    |--------------------------------------------------------------------------
    |
    | Lo que se guarda es la CONFIGURACION de la fase, nunca el preview: ni
    | las caras prestadas, ni el barajado que se acaba de ver, ni el orden
    | que se arrastro a mano. Barajar y ordenar a mano son decisiones que el
    | motor toma cuando la fase corre de verdad, con los participantes de
    | verdad; la fase solo guarda que se hara.
    |
    */

    public function persistenceRules(): array
    {
        return [
            'cycles' => ['required', 'integer', 'min:1', 'max:10'],

            'initial_order_mode' => [
                'required',
                Rule::in(['INPUT_ORDER', 'RANKING', 'RANDOM', 'MANUAL', 'BY_GATE']),
            ],

            'ranking_source' => [
                'nullable',
                Rule::in(['TOURNAMENT', 'UNIVERSAL']),
            ],

            'allow_draws' => ['boolean'],

            'win_points' => ['required', 'numeric', 'between:-9999.99,9999.99'],
            'draw_points' => ['required', 'numeric', 'between:-9999.99,9999.99'],
            'loss_points' => ['required', 'numeric', 'between:-9999.99,9999.99'],

            /*
             * Fijar la cantidad es una decision aparte y explicita: mover el
             * control de participantes solo cambia lo que se ve.
             */
            'pin_participants' => ['boolean'],
            'participants' => ['nullable', 'integer', 'min:2', 'max:512'],

            'round_limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ];
    }

    public function persist(
        PhaseTemplate $phaseTemplate,
        array $data
    ): void {

        $settings =
            $this->settingsService->ensure(
                $phaseTemplate
            );

        $stored = $settings->settings ?? [];

        $stored['ranking_source'] =
            $data['ranking_source'] ?? 'TOURNAMENT';

        /*
         * Sin recorte no se guarda nada: asi una liga que se juega entera
         * no arrastra un numero que habria que revisar cada vez que cambia
         * la cantidad de participantes.
         */
        if (! empty($data['round_limit'])) {
            $stored['round_limit'] = (int) $data['round_limit'];
        } else {
            unset($stored['round_limit']);
        }

        $settings->fill([
            'cycles' => (int) $data['cycles'],

            'initial_order_mode' => $data['initial_order_mode'],

            'allow_draws' => (bool) ($data['allow_draws'] ?? false),

            'win_points' => (float) $data['win_points'],
            'draw_points' => (float) $data['draw_points'],
            'loss_points' => (float) $data['loss_points'],

            'settings' => $stored,
        ])->save();

        if (! array_key_exists('pin_participants', $data)) {
            return;
        }

        $phaseTemplate->forceFill([
            'exact_participants' =>
            ($data['pin_participants'] ?? false) && ! empty($data['participants'])
                ? (int) $data['participants']
                : null,
        ])->save();
    }

    /*
     * Hasta que jornada se juega.
     *
     * Nunca por encima de las que existen: si el calendario tiene 7 y hay
     * guardado un 9 -porque antes habia mas participantes-, manda el 7. Un
     * numero guardado no puede inventar jornadas que no se generan.
     */
    private function roundLimit(
        $settings,
        array $overrides,
        array $structure
    ): ?int {

        $total = $structure['total_rounds'] ?? null;

        if ($total === null) {
            return null;
        }

        $stored = $settings->settings['round_limit'] ?? null;

        $value = $overrides['round_limit'] ?? $stored;

        if ($value === null || (int) $value < 1) {
            return $total;
        }

        return min((int) $value, $total);
    }


    /*
    |--------------------------------------------------------------------------
    | Puertas de entrada
    |--------------------------------------------------------------------------
    */

    public function gateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],

            'seed_type' => [
                'required',
                Rule::in(array_keys(RoundRobinSeedRuleResolver::TYPES)),
            ],

            'seed_count' => [
                Rule::requiredIf(
                    fn() => in_array(request('seed_type'), ['FIRST_N', 'LAST_N'], true)
                ),
                'nullable', 'integer', 'min:1', 'max:512',
            ],

            'seed_from' => [
                Rule::requiredIf(
                    fn() => in_array(request('seed_type'), ['RANGE', 'POSITION'], true)
                ),
                'nullable', 'integer', 'min:1', 'max:512',
            ],

            'seed_to' => [
                Rule::requiredIf(
                    fn() => request('seed_type') === 'RANGE'
                ),
                'nullable', 'integer', 'min:1', 'max:512', 'gte:seed_from',
            ],

            'is_required' => ['boolean'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:999'],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    public function persistGate(
        PhaseTemplate $phaseTemplate,
        mixed $gate,
        array $data
    ): void {

        if ($gate instanceof PhaseInputGate) {
            $this->gateService->update($gate, $data);

            return;
        }

        $this->gateService->create($phaseTemplate, $data);
    }

    public function deleteGate(
        PhaseTemplate $phaseTemplate,
        mixed $gate
    ): void {

        abort_unless(
            $gate instanceof PhaseInputGate
                && (int) $gate->phase_template_id === (int) $phaseTemplate->id,
            404
        );

        $this->gateService->delete($gate);
    }


    /*
    |--------------------------------------------------------------------------
    | Puertas de salida
    |--------------------------------------------------------------------------
    |
    | Una liga no elimina a nadie a mitad de fase: todos juegan todas sus
    | jornadas y la clasificacion no es firme hasta el final. Por eso el
    | momento de cruzar no se pregunta -siempre es al terminar- y los
    | selectores de eliminacion inmediata no se ofrecen.
    |
    */

    public function exitRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],

            'selector_type' => [
                'required',
                Rule::in(['TOP_N', 'BOTTOM_N', 'RANK_POSITION', 'RANK_RANGE', 'REMAINING', 'ALL']),
            ],

            'selector_from' => [
                Rule::requiredIf(
                    fn() => in_array(
                        request('selector_type'),
                        ['TOP_N', 'BOTTOM_N', 'RANK_POSITION', 'RANK_RANGE'],
                        true
                    )
                ),
                'nullable', 'integer', 'min:1', 'max:512',
            ],

            'selector_to' => [
                Rule::requiredIf(
                    fn() => request('selector_type') === 'RANK_RANGE'
                ),
                'nullable', 'integer', 'min:1', 'max:512', 'gte:selector_from',
            ],

            'priority' => ['nullable', 'integer', 'min:1', 'max:999'],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    public function persistExit(
        PhaseTemplate $phaseTemplate,
        mixed $exit,
        array $data
    ): void {

        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,

            'selector_type' => $data['selector_type'],
            'selector_from' => $data['selector_from'] ?? null,
            'selector_to' => $data['selector_to'] ?? null,

            'exit_timing' => 'PHASE_END',

            'priority' => (int) ($data['priority'] ?? 10),
            'status' => $data['status'] ?? 'ACTIVE',
        ];

        if ($exit instanceof PhaseExit) {
            $this->exitService->update($exit, $payload);

            return;
        }

        $this->exitService->create($phaseTemplate, $payload);
    }

    public function deleteExit(
        PhaseTemplate $phaseTemplate,
        mixed $exit
    ): void {

        abort_unless(
            $exit instanceof PhaseExit
                && (int) $exit->phase_template_id === (int) $phaseTemplate->id,
            404
        );

        $this->exitService->delete($exit);
    }
}
