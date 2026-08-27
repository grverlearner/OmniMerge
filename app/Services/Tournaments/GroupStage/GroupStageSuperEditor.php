<?php

namespace App\Services\Tournaments\GroupStage;

use App\Models\PhaseExit;
use App\Models\PhaseGroupStageGroup;
use App\Models\PhaseInputGate;
use App\Models\PhaseTemplate;
use App\Models\User;
use App\Services\Tournaments\PhaseEditor\PhaseSuperEditorContract;
use App\Services\Tournaments\PhaseEditor\SupportsEditableGroups;
use App\Services\Tournaments\PhaseExitService;
use App\Services\Tournaments\Preview\PreviewCastService;
use App\Services\Tournaments\RoundRobin\RoundRobinScheduleCalculator;
use Illuminate\Validation\Rule;

/*
|--------------------------------------------------------------------------
| GroupStageSuperEditor
|--------------------------------------------------------------------------
|
| Todo lo que la Super Edicion necesita saber de una fase de grupos.
|
| Una fase de grupos es una liga pequena repetida N veces y jugada en
| paralelo, asi que aqui no se ha escrito ni un algoritmo nuevo: el reparto
| en grupos sale de GroupStageAllocator y el calendario de cada grupo del
| MISMO RoundRobinScheduleCalculator que usa la Super Edicion de liga.
|
| La costura vuelve a ser la semilla. El repartidor decide que SEMILLA cae
| en que grupo, y el calculador empareja semillas dentro de cada grupo. La
| unica capa que anade este editor es la de siempre: quien ocupa cada
| semilla, y como se ve.
|
| Dos avisos sobre lo que NO se configura aqui, iguales que en liga:
|
|   - Formato de batalla. internal_best_of, internal_series_format e
|     internal_fixed_games siguen en la tabla y el motor los sigue leyendo,
|     pero decidir como se pelea un enfrentamiento es del torneo real.
|
|   - Criterios de desempate. La cadena es fija y termina en la semilla,
|     que es unica, asi que un grupo no puede acabar en un empate sin
|     resolver.
|
*/
class GroupStageSuperEditor implements PhaseSuperEditorContract, SupportsEditableGroups
{
    /*
     * Un color por grupo.
     *
     * Los tonos van elegidos por SEPARACION, no por gusto: azul, ambar,
     * verde y magenta estan a mas de 100 grados unos de otros en el circulo
     * cromatico, que es lo que hace que cuatro grupos se distingan de un
     * vistazo. Los cuatro siguientes rellenan los huecos.
     *
     * El color no decora: es lo que permite seguir a un participante desde
     * la fila de entrantes, a su grupo, a su jornada y a su salida, sin leer
     * un solo numero.
     */
    private const PALETTE = [
        ['key' => 'sky', 'dot' => 'bg-sky-400', 'ring' => 'ring-sky-400/70', 'text' => 'text-sky-300', 'soft' => 'bg-sky-500/10', 'border' => 'border-sky-400/40', 'solid' => 'bg-sky-500'],
        ['key' => 'amber', 'dot' => 'bg-amber-400', 'ring' => 'ring-amber-400/70', 'text' => 'text-amber-300', 'soft' => 'bg-amber-500/10', 'border' => 'border-amber-400/40', 'solid' => 'bg-amber-500'],
        ['key' => 'emerald', 'dot' => 'bg-emerald-400', 'ring' => 'ring-emerald-400/70', 'text' => 'text-emerald-300', 'soft' => 'bg-emerald-500/10', 'border' => 'border-emerald-400/40', 'solid' => 'bg-emerald-500'],
        ['key' => 'fuchsia', 'dot' => 'bg-fuchsia-400', 'ring' => 'ring-fuchsia-400/70', 'text' => 'text-fuchsia-300', 'soft' => 'bg-fuchsia-500/10', 'border' => 'border-fuchsia-400/40', 'solid' => 'bg-fuchsia-500'],
        ['key' => 'rose', 'dot' => 'bg-rose-400', 'ring' => 'ring-rose-400/70', 'text' => 'text-rose-300', 'soft' => 'bg-rose-500/10', 'border' => 'border-rose-400/40', 'solid' => 'bg-rose-500'],
        ['key' => 'lime', 'dot' => 'bg-lime-400', 'ring' => 'ring-lime-400/70', 'text' => 'text-lime-300', 'soft' => 'bg-lime-500/10', 'border' => 'border-lime-400/40', 'solid' => 'bg-lime-500'],
        ['key' => 'blue', 'dot' => 'bg-blue-400', 'ring' => 'ring-blue-400/70', 'text' => 'text-blue-300', 'soft' => 'bg-blue-500/10', 'border' => 'border-blue-400/40', 'solid' => 'bg-blue-500'],
        ['key' => 'purple', 'dot' => 'bg-purple-400', 'ring' => 'ring-purple-400/70', 'text' => 'text-purple-300', 'soft' => 'bg-purple-500/10', 'border' => 'border-purple-400/40', 'solid' => 'bg-purple-500'],
    ];

    /*
     * Paleta propia para las salidas, separada en DOS ejes.
     *
     * En TONO: violeta, teal, naranja y rosa caen en los huecos que dejan
     * los colores de grupo, asi que ninguna salida se confunde con un grupo
     * -antes la salida #1 recibia el mismo magenta que el Grupo D-.
     *
     * Dos fondos, no uno: `wash` casi invisible mientras no se ha jugado
     * nada -lo que se ve entonces es una prevision, no un resultado- y
     * `soft` en cuanto hay marcadores. Asi simular se nota.
     *
     * En INTENSIDAD: las salidas van un escalon mas claras (-300 frente a
     * -400). Aunque dos tonos queden cerca, el brillo dice a que familia
     * pertenece cada marca. Con un solo eje no llegan los colores: hay ocho
     * grupos y seis salidas, y el circulo cromatico no da para catorce tonos
     * que de verdad se distingan.
     */
    private const EXIT_PALETTE = [
        ['key' => 'violet', 'dot' => 'bg-violet-300', 'ring' => 'ring-violet-300/70', 'text' => 'text-violet-200', 'soft' => 'bg-violet-400/10', 'wash' => 'bg-violet-400/5', 'border' => 'border-violet-300/50', 'solid' => 'bg-violet-400'],
        ['key' => 'teal', 'dot' => 'bg-teal-300', 'ring' => 'ring-teal-300/70', 'text' => 'text-teal-200', 'soft' => 'bg-teal-400/10', 'wash' => 'bg-teal-400/5', 'border' => 'border-teal-300/50', 'solid' => 'bg-teal-400'],
        ['key' => 'orange', 'dot' => 'bg-orange-300', 'ring' => 'ring-orange-300/70', 'text' => 'text-orange-200', 'soft' => 'bg-orange-400/10', 'wash' => 'bg-orange-400/5', 'border' => 'border-orange-300/50', 'solid' => 'bg-orange-400'],
        ['key' => 'pink', 'dot' => 'bg-pink-300', 'ring' => 'ring-pink-300/70', 'text' => 'text-pink-200', 'soft' => 'bg-pink-400/10', 'wash' => 'bg-pink-400/5', 'border' => 'border-pink-300/50', 'solid' => 'bg-pink-400'],
        ['key' => 'indigo', 'dot' => 'bg-indigo-300', 'ring' => 'ring-indigo-300/70', 'text' => 'text-indigo-200', 'soft' => 'bg-indigo-400/10', 'wash' => 'bg-indigo-400/5', 'border' => 'border-indigo-300/50', 'solid' => 'bg-indigo-400'],
        ['key' => 'slate', 'dot' => 'bg-slate-300', 'ring' => 'ring-slate-300/70', 'text' => 'text-slate-200', 'soft' => 'bg-slate-400/10', 'wash' => 'bg-slate-400/5', 'border' => 'border-slate-300/50', 'solid' => 'bg-slate-400'],
    ];

    private function exitColor(int $sequence): array
    {
        return self::EXIT_PALETTE[
            ($sequence - 1 + count(self::EXIT_PALETTE)) % count(self::EXIT_PALETTE)
        ];
    }

    /* Cuantas jornadas se dibujan por grupo */
    private const ROUND_LIMIT = 60;

    public function __construct(
        private readonly GroupStageSettingsService $settingsService,
        private readonly GroupStageAllocator $allocator,
        private readonly GroupStageDefinitionService $definitions,
        private readonly GroupStageExitForecastService $exitForecast,
        private readonly GroupStageGroupService $groupService,
        private readonly GroupStageGateService $gateService,
        private readonly RoundRobinScheduleCalculator $calculator,
        private readonly PhaseExitService $exitService,
        private readonly PreviewCastService $cast
    ) {}

    public function phaseType(): string
    {
        return 'GROUP_STAGE';
    }

    public function configView(): string
    {
        return 'tournaments.phase-templates.super.group-stage.config';
    }

    public function stageView(): string
    {
        return 'tournaments.phase-templates.super.group-stage.stage';
    }

    public function gatesView(): string
    {
        return 'tournaments.phase-templates.super.group-stage.gates';
    }

    public function scheduleView(): string
    {
        return 'tournaments.phase-templates.super.group-stage.schedule';
    }

    public function saveFieldsView(): string
    {
        return 'tournaments.phase-templates.super.group-stage.save-fields';
    }

    public function previewOverrideKeys(): array
    {
        return [
            'participants' => 'int',
            'round_limit' => 'int',

            'group_count_mode' => 'string',
            'group_count' => 'int',
            'target_group_size' => 'int',
            'min_group_size' => 'int',
            'max_group_size' => 'int',

            'remainder_policy' => 'string',
            'distribution_mode' => 'string',

            'internal_cycles' => 'int',
        ];
    }

    public function clientEngine(): string
    {
        return 'groupStage';
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

        $settings = $this->settingsService->ensure($phaseTemplate);

        /*
         * Con grupos personalizados la cantidad de participantes NO es un
         * numero aparte: es la suma de los cupos.
         *
         * Tratarla como dos cosas independientes obligaba a que cuadraran, y
         * cambiar el cupo de un solo grupo dejaba la fase invalida -"suman
         * 15, pero el preview usa 16"- hasta retocar otro. No habia forma de
         * editar un grupo sin pasar por un estado roto.
         */
        $contract = $this->contract(
            $phaseTemplate,
            $overrides['participants'] ?? null,
            $this->customCapacity($phaseTemplate, $overrides),
            $this->rememberedParticipants($settings)
        );

        /*
         * Lo que se esta tocando en pantalla manda sobre lo guardado: si no,
         * mover un control devolveria la configuracion anterior y la
         * pantalla parpadearia hacia atras.
         */
        $preview = clone $settings;

        foreach ([
            'group_count_mode', 'group_count', 'target_group_size',
            'min_group_size', 'max_group_size',
            'remainder_policy', 'distribution_mode', 'internal_cycles',
        ] as $field) {
            if (isset($overrides[$field]) && $overrides[$field] !== null) {
                $preview->{$field} = $overrides[$field];
            }
        }

        $definitions = $phaseTemplate->groupStageGroups()->get();

        $allocation = $this->allocator->allocate(
            $phaseTemplate,
            $preview,
            $definitions,
            $contract['resolved']
        );

        $groups = $this->groups(
            $allocation,
            $preview,
            $definitions
        );

        return [
            'phase' => $this->phaseSummary($phaseTemplate),

            'contract' => $contract,

            'settings' => [
                'group_count_mode' => $preview->group_count_mode,
                'group_count' => (int) $preview->group_count,
                'target_group_size' => (int) $preview->target_group_size,

                'min_group_size' => (int) $preview->min_group_size,
                'max_group_size' => (int) $preview->max_group_size,

                'remainder_policy' => $preview->remainder_policy,
                'distribution_mode' => $preview->distribution_mode,

                'internal_cycles' => (int) $preview->internal_cycles,
                'internal_allow_draws' => (bool) $preview->internal_allow_draws,

                'win_points' => (float) $preview->internal_win_points,
                'draw_points' => (float) $preview->internal_draw_points,
                'loss_points' => (float) $preview->internal_loss_points,

                'cross_group_normalization' => $preview->cross_group_normalization,

                'round_limit' => $this->roundLimit($settings, $overrides, $groups),
            ],

            'structure' => $this->structureSummary($allocation, $groups),

            'groups' => $groups,

            'cast' => $this->buildCast($user, $contract['resolved']),

            'gates' => $this->gates($phaseTemplate, $groups),

            /*
             * El reparto que se propone si se pasa a grupos personalizados,
             * ya contando lo que prometen las puertas.
             */
            'suggested_sizes' => $this->suggestedSizes(
                $phaseTemplate,
                $contract['resolved']
            ),

            'exits' => $this->exits($phaseTemplate, $contract['resolved']),

            'rules' => $this->rules($phaseTemplate, $contract['resolved']),

            'catalog' => [
                'group_count_modes' => $this->definitions->groupCountModes(),
                'remainder_policies' => $this->definitions->remainderPolicies(),
                'distribution_modes' => $this->definitions->distributionModes(),

                'rule_types' => $this->definitions->ruleTypes(),

                'standings_columns' => $this->standingsColumns(),
                'tiebreak_chain' => $this->tiebreakChain(),
                'ranking_keys' => $this->rankingKeys(),
            ],

            'diagnostics' => $this->diagnostics(
                $phaseTemplate,
                $allocation,
                $groups,
                $preview,
                $definitions
            ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Grupos
    |--------------------------------------------------------------------------
    |
    | Cada grupo se lleva su composicion (que semillas caen dentro) y su
    | propio calendario. El calendario es un round robin de tamano del
    | grupo: se pide al calculador de liga con semillas LOCALES y se
    | traducen a las globales, que son las que conoce el resto de la
    | pantalla.
    |
    */

    private function groups(
        array $allocation,
        $settings,
        $definitions
    ): array {

        if (! ($allocation['valid'] ?? false)) {
            return [];
        }

        $groups = [];

        foreach ($allocation['groups'] as $index => $group) {

            $seeds = array_values(array_filter(
                array_map(
                    fn(array $member) => $member['seed'] ?? null,
                    $group['members'] ?? []
                ),
                fn($seed) => $seed !== null
            ));

            /*
             * Un grupo puede llevar sus propias vueltas. Vive en `settings`
             * del grupo porque solo tiene sentido cuando la construccion es
             * personalizada, y darle una columna la dejaria nula en todos
             * los demas casos.
             */
            $definition = $definitions->firstWhere('id', $group['definition_id'] ?? null);

            $cycles = (int) (
                $definition?->settings['cycles']
                ?? $settings->internal_cycles
                ?? 1
            );

            $schedule = $this->calculator->calculateStructure(
                $group['size'],
                max(1, $cycles),
                1,
                (bool) $settings->internal_allow_draws,
                self::ROUND_LIMIT
            );

            $groups[] = [
                'index' => $group['index'],
                'definition_id' => $group['definition_id'],
                'code' => $group['code'],
                'name' => $group['name'],
                'size' => $group['size'],

                'color' => $this->color($group['index']),

                /* Semillas globales, en el orden en que quedaron dentro */
                'seeds' => $seeds,

                'cycles' => max(1, $cycles),
                'has_custom_cycles' => isset($definition?->settings['cycles']),

                'total_rounds' => $schedule['total_rounds'] ?? 0,
                'total_series' => $schedule['total_series'] ?? 0,

                'rounds' => $this->localiseRounds(
                    $schedule['rounds'] ?? [],
                    $seeds
                ),
            ];
        }

        return $groups;
    }

    /*
     * Traduce las semillas locales de un grupo a las globales.
     *
     * El calculador no sabe que esta dentro de un grupo: empareja 1 contra
     * 4 y 2 contra 3. Aqui el 1 es "el primero de este grupo", que en la
     * fase entera puede ser la semilla 7.
     */
    private function localiseRounds(array $rounds, array $seeds): array
    {
        $map = fn(?int $local) => $local === null
            ? null
            : ($seeds[$local - 1] ?? null);

        return array_values(array_map(
            fn(array $round) => [
                'number' => $round['number'],
                'label' => $round['label'] ?? ('Jornada ' . $round['number']),
                'cycle' => $round['cycle'] ?? 1,

                'rest_seed' => $map($round['rest_seed'] ?? null),

                'pairings' => array_values(array_map(
                    fn(array $pair) => [
                        'seed_a' => $map($pair['seed_a'] ?? null),
                        'seed_b' => $map($pair['seed_b'] ?? null),
                    ],
                    $round['pairings'] ?? []
                )),
            ],
            $rounds
        ));
    }


    /*
    |--------------------------------------------------------------------------
    | Puertas
    |--------------------------------------------------------------------------
    |
    | En una fase de grupos una puerta de entrada no reparte puestos de una
    | parrilla: reparte GRUPOS. Dice que tramo de los que llegan va a que
    | grupo, y por eso se lee junto a la fila de entrantes.
    |
    */

    private function gates(
        PhaseTemplate $phaseTemplate,
        array $groups
    ): array {

        $byCode = collect($groups)->keyBy('code');

        return $phaseTemplate
            ->inputGates()
            ->get()
            ->values()
            ->map(function ($gate, int $index) use ($byCode) {

                $targetCode = $gate->settings['target_group_code'] ?? null;

                $target = $targetCode ? $byCode->get($targetCode) : null;

                $range = $gate->settings['entry_range'] ?? null;

                $from = isset($range['from']) ? (int) $range['from'] : null;
                $to = isset($range['to']) ? (int) $range['to'] : null;

                return [
                    'id' => $gate->id,
                    'code' => $gate->code,
                    'number' => $gate->sequence_number,
                    'name' => $gate->name,

                    /*
                     * La puerta toma prestado el color de su grupo destino.
                     * Asi el tramo de entrantes que manda a Grupo B se ve
                     * del mismo color que el Grupo B, sin leer nada.
                     */
                    'color' => $target
                        ? $target['color']
                        : $this->color($gate->sequence_number ?? ($index + 1)),

                    'target_group_code' => $targetCode,
                    'target_group_name' => $target['name'] ?? null,

                    'entry_from' => $from,
                    'entry_to' => $to,

                    'range_label' => $from && $to
                        ? 'entrantes ' . $from . '–' . $to
                        : ($from ? 'desde el ' . $from : 'todos los que lleguen'),

                    'is_required' => (bool) $gate->is_required,
                    'status' => $gate->status,
                ];
            })
            ->all();
    }

    /*
     * Las salidas, con los criterios que las cruzan.
     *
     * Aqui no se inventa nada: quien decide cuanta gente sale por cada
     * puerta son las reglas de clasificacion, que ya existen y ya las
     * ejecuta el motor. La puerta solo pone el nombre y el destino.
     */
    /*
     * Cuanta gente saca cada puerta.
     *
     * Se pronostica con los participantes que se estan MIRANDO, no con el
     * minimo del contrato: con 16 en pantalla y un minimo de 8, el contador
     * hablaba de un reparto distinto al dibujado, y si ese minimo ni
     * siquiera repartia en grupos validos se quedaba mudo del todo.
     */
    private function exits(
        PhaseTemplate $phaseTemplate,
        int $participants
    ): array {

        $forecast = $this->exitForecast->forecast($phaseTemplate, $participants) ?? [];

        return $phaseTemplate
            ->exits()
            ->get()
            ->values()
            ->map(fn($exit, int $index) => [
                'id' => $exit->id,
                'code' => $exit->code,
                'number' => $exit->sequence_number,
                'name' => $exit->name,

                'color' => $this->exitColor(
                    $exit->sequence_number ?? ($index + 1)
                ),

                'selector_type' => $exit->selector_type,
                'summary' => $exit->selection_summary,

                'emits' => $forecast['by_exit'][$exit->id] ?? null,

                'priority' => $exit->priority,
                'status' => $exit->status,
            ])
            ->all();
    }

    private function rules(
        PhaseTemplate $phaseTemplate,
        int $participants
    ): array {

        $forecast = $this->exitForecast->forecast($phaseTemplate, $participants) ?? [];

        return $phaseTemplate
            ->groupStageAdvancementRules()
            ->with(['phaseExit', 'group'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($rule) => [
                'id' => $rule->id,
                'exit_id' => $rule->phase_exit_id,

                'rule_type' => $rule->rule_type,
                'summary' => $rule->rule_summary,

                'take' => $rule->take,
                'position_from' => $rule->position_from,
                'position_to' => $rule->position_to,

                'group_id' => $rule->phase_group_stage_group_id,
                'group_name' => $rule->group?->name,

                'emits' => $forecast['by_rule'][$rule->id] ?? null,

                'status' => $rule->status,
            ])
            ->all();
    }


    /*
    |--------------------------------------------------------------------------
    | Contrato, reparto y catálogos
    |--------------------------------------------------------------------------
    */

    /*
     * Con cuantos abre el editor.
     *
     * NO es lo mismo que `exact_participants`. Esa columna es el CONTRATO
     * -"esta fase admite exactamente N"- y cambiarla porque alguien
     * previsualizo con doce seria convertir una fase flexible en una rigida
     * a sus espaldas.
     *
     * Pero tampoco vale olvidarlo: escribir un numero, guardar, y ver que
     * vuelve al de por defecto parece que el guardado no funciona. Asi que
     * el numero se recuerda como preferencia de trabajo, en `settings`, y
     * solo decide con que abre la pantalla. El contrato sigue detras de su
     * casilla.
     */
    private function rememberedParticipants($settings): ?int
    {
        $stored = $settings->settings['preview_participants'] ?? null;

        return is_numeric($stored) && (int) $stored >= 2
            ? (int) $stored
            : null;
    }

    private function contract(
        PhaseTemplate $phaseTemplate,
        ?int $requested,
        ?int $derived = null,
        ?int $remembered = null
    ): array {

        $min = (int) ($phaseTemplate->min_participants ?? 4);
        $max = $phaseTemplate->max_participants !== null ? (int) $phaseTemplate->max_participants : null;
        $exact = $phaseTemplate->exact_participants !== null ? (int) $phaseTemplate->exact_participants : null;
        $multiple = $phaseTemplate->participant_multiple !== null ? (int) $phaseTemplate->participant_multiple : null;

        $default = $exact ?? $remembered ?? max($min, 16);

        $resolved = $derived
            ?? ($requested !== null && $requested > 0 ? $requested : $default);

        return [
            'min' => $min,
            'max' => $max,
            'exact' => $exact,
            'multiple' => $multiple,
            'mode' => $phaseTemplate->participant_mode,
            'default' => $default,
            'requested' => $requested,
            'resolved' => max(2, min(256, $resolved)),
            'is_pinned' => $exact !== null,

            /* Cuando la manda la suma de los cupos, no se edita a mano */
            'is_derived' => $derived !== null,
        ];
    }

    /*
     * La suma de los cupos, cuando la construccion es personalizada.
     *
     * null cuando no aplica: otro modo, o grupos todavia sin cupo -ahi la
     * cantidad sigue siendo la del contrato para poder proponer un reparto-.
     */
    private function customCapacity(
        PhaseTemplate $phaseTemplate,
        array $overrides
    ): ?int {

        $mode = $overrides['group_count_mode']
            ?? $this->settingsService->ensure($phaseTemplate)->group_count_mode;

        if ($mode !== 'CUSTOM_GROUPS') {
            return null;
        }

        $total = (int) $phaseTemplate
            ->groupStageGroups()
            ->where('is_active', true)
            ->sum('capacity');

        return $total > 0 ? $total : null;
    }

    /*
     * El reparto que se propone al pasar a grupos personalizados.
     *
     * No es un reparto parejo a secas: las puertas de entrada ya dicen que
     * tramo de los que llegan va a que grupo, asi que un grupo que tiene
     * puertas apuntandole necesita al menos tantos sitios como gente le
     * mandan. Proponer menos seria proponer algo que no cabe.
     *
     * Lo que ninguna puerta reclama se reparte a partes iguales.
     *
     * @return array<int,int>
     */
    private function suggestedSizes(
        PhaseTemplate $phaseTemplate,
        int $participants
    ): array {

        $groups = $phaseTemplate
            ->groupStageGroups()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('sequence_number')
            ->get()
            ->values();

        if ($groups->isEmpty()) {
            return [];
        }

        /* Lo que cada grupo tiene ya prometido por sus puertas */
        $claimed = array_fill(0, $groups->count(), 0);

        $byCode = [];

        foreach ($groups as $index => $group) {
            $byCode[$group->code] = $index;
        }

        foreach (
            $phaseTemplate->inputGates()->where('status', 'ACTIVE')->get()
            as $gate
        ) {
            $code = $gate->settings['target_group_code'] ?? null;
            $range = $gate->settings['entry_range'] ?? null;

            if ($code === null || ! isset($byCode[$code]) || ! is_array($range)) {
                continue;
            }

            $from = (int) ($range['from'] ?? 0);
            $to = (int) ($range['to'] ?? $from);

            if ($from < 1 || $to < $from) {
                continue;
            }

            $claimed[$byCode[$code]] += min($to, $participants) - $from + 1;
        }

        $promised = array_sum($claimed);

        /*
         * Si las puertas ya prometen mas gente de la que entra, el reparto
         * propuesto es el que piden: el diagnostico dira que no cabe, pero
         * proponer otra cosa escondería el choque.
         */
        if ($promised >= $participants) {
            return array_map(fn(int $n) => max(1, $n), $claimed);
        }

        $free = $participants - $promised;

        $count = $groups->count();

        $base = intdiv($free, $count);
        $extra = $free % $count;

        $sizes = [];

        foreach ($claimed as $index => $reserved) {
            $sizes[] = max(1, $reserved + $base + ($index < $extra ? 1 : 0));
        }

        return $sizes;
    }

    private function buildCast(?User $user, int $count): array
    {
        return $this->cast
            ->borrow($user, $count)
            ->values()
            ->map(fn(array $member, int $index) => [
                'index' => $index,
                'name' => $member['name'],
                'short' => $this->shortName($member['name']),
                'image_url' => $member['image_url'] ?? null,
                'is_borrowed' => (bool) ($member['is_borrowed'] ?? false),
            ])
            ->all();
    }

    private function shortName(string $name): string
    {
        $name = trim($name);

        if (mb_strlen($name) <= 12) {
            return $name;
        }

        $first = mb_substr($name, 0, mb_strpos($name . ' ', ' '));

        return mb_strlen($first) >= 3 && mb_strlen($first) <= 12
            ? $first
            : mb_substr($name, 0, 11) . '…';
    }

    private function color(int $sequence): array
    {
        return self::PALETTE[
            ($sequence - 1 + count(self::PALETTE)) % count(self::PALETTE)
        ];
    }

    private function phaseSummary(PhaseTemplate $phaseTemplate): array
    {
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
     * Las columnas de una tabla de grupo. Muy pocas a proposito: hay que
     * poder poner cuatro grupos en pantalla a la vez, y una tabla con once
     * columnas por grupo no cabe ni se lee.
     */
    private function standingsColumns(): array
    {
        return [
            'PLAYED' => 'PJ',
            'WINS' => 'PG',
            'DRAWS' => 'PE',
            'LOSSES' => 'PP',
            'SCORE_DIFFERENCE' => 'DIF',
            'POINTS' => 'PTS',
        ];
    }

    /*
     * La cadena de desempate, en su orden y con nombres legibles.
     *
     * defaultCrossGroupCriteria() devuelve filas
     * ['criterion' => ..., 'normalization' => ...], no cadenas sueltas:
     * cada criterio lleva ademas como se normaliza entre grupos de tamano
     * distinto. Aqui solo interesa el nombre.
     */
    private function tiebreakChain(): array
    {
        $criteria = $this->definitions->crossGroupCriteria();

        $chain = [];

        foreach ($this->defaultCriteriaKeys() as $key) {
            $chain[] = $criteria[$key]['label'] ?? $key;
        }

        return array_values(array_unique($chain));
    }

    /**
     * @return array<int,string>
     */
    private function defaultCriteriaKeys(): array
    {
        return array_values(array_filter(array_map(
            fn($row) => is_array($row) ? ($row['criterion'] ?? null) : $row,
            $this->definitions->defaultCrossGroupCriteria()
        )));
    }

    private function rankingKeys(): array
    {
        $direction = [
            'POINTS' => 'DESC',
            'SCORE_DIFFERENCE' => 'DESC',
            'SCORE_FOR' => 'DESC',
            'WINS' => 'DESC',
            'SEED' => 'ASC',
        ];

        $keys = [];

        foreach (['POINTS', ...$this->defaultCriteriaKeys(), 'SEED'] as $key) {

            if (! isset($direction[$key])) {
                continue;
            }

            if (in_array($key, array_column($keys, 'key'), true)) {
                continue;
            }

            $keys[] = ['key' => $key, 'direction' => $direction[$key]];
        }

        return $keys;
    }

    private function structureSummary(array $allocation, array $groups): array
    {
        if (! ($allocation['valid'] ?? false)) {
            return ['valid' => false, 'participants' => $allocation['participants'] ?? 0];
        }

        $sizes = array_column($groups, 'size');

        return [
            'valid' => true,

            'participants' => $allocation['participants'],
            'groups_count' => count($groups),

            'min_size' => $sizes ? min($sizes) : 0,
            'max_size' => $sizes ? max($sizes) : 0,
            'uneven' => $sizes && min($sizes) !== max($sizes),

            'total_series' => array_sum(array_column($groups, 'total_series')),
            'max_rounds' => $groups ? max(array_column($groups, 'total_rounds')) : 0,
        ];
    }

    private function roundLimit($settings, array $overrides, array $groups): ?int
    {
        $total = $groups ? max(array_column($groups, 'total_rounds')) : null;

        if ($total === null) {
            return null;
        }

        $value = $overrides['round_limit'] ?? ($settings->settings['round_limit'] ?? null);

        if ($value === null || (int) $value < 1) {
            return $total;
        }

        return min((int) $value, $total);
    }

    private function diagnostics(
        PhaseTemplate $phaseTemplate,
        array $allocation,
        array $groups,
        $preview,
        $definitions
    ): array {

        $errors = $allocation['errors'] ?? [];
        $warnings = [];

        if ($allocation['valid'] ?? false) {

            $sizes = array_column($groups, 'size');

            if ($sizes && min($sizes) !== max($sizes)) {
                $warnings[] =
                    'Los grupos no tienen el mismo tamaño ('
                    . min($sizes) . '–' . max($sizes)
                    . '): quien juegue en el más pequeño disputa menos partidos.';
            }

            foreach ($groups as $group) {
                if ($group['size'] % 2 !== 0) {
                    $warnings[] =
                        $group['name']
                        . ' tiene '
                        . $group['size']
                        . ' participantes (impar): alguien descansa cada jornada.';

                    break;
                }
            }
        }

        /*
         * Con grupos personalizados cada grupo necesita su cupo, y los
         * grupos creados en modo automatico no lo tienen: se creaban sin el
         * porque el reparto lo calculaba la fase.
         *
         * El repartidor devuelve un error generico; aqui se dice CUALES
         * faltan, que es lo unico accionable.
         */
        if ($preview->group_count_mode === 'CUSTOM_GROUPS') {

            $sinCupo = $definitions
                ->where('is_active', true)
                ->filter(fn($group) => ! $group->capacity || $group->capacity < 1)
                ->pluck('name');

            if ($definitions->where('is_active', true)->isEmpty()) {
                $errors[] =
                    'Con grupos personalizados hay que crear los grupos a mano. '
                    . 'Usa «+ Grupo» en el panel derecho.';
            } elseif ($sinCupo->isNotEmpty()) {
                $errors[] =
                    'Sin cupo: '
                    . $sinCupo->implode(', ')
                    . '. Ponle a cada uno cuánta gente admite, o adopta el reparto anterior.';
            }
        }

        if ($phaseTemplate->exits()->where('status', 'ACTIVE')->count() === 0) {
            $warnings[] = 'Sin puertas de salida nadie avanza a la siguiente fase.';
        } elseif ($phaseTemplate->groupStageAdvancementRules()->where('status', 'ACTIVE')->count() === 0) {
            $warnings[] = 'Hay salidas pero ningún criterio las cruza: no avanzaría nadie.';
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
    */

    public function persistenceRules(): array
    {
        return [
            'group_count_mode' => ['required', Rule::in(['FIXED_GROUP_COUNT', 'TARGET_GROUP_SIZE', 'CUSTOM_GROUPS'])],
            'group_count' => ['nullable', 'integer', 'min:2', 'max:64'],
            'target_group_size' => ['nullable', 'integer', 'min:2', 'max:64'],

            'min_group_size' => ['required', 'integer', 'min:2', 'max:64'],
            'max_group_size' => ['required', 'integer', 'min:2', 'max:64', 'gte:min_group_size'],

            'remainder_policy' => ['required', Rule::in(['BALANCED', 'FIRST_GROUPS', 'LAST_GROUPS', 'MANUAL'])],
            'distribution_mode' => ['required', Rule::in(['INPUT_ORDER', 'RANDOM', 'SNAKE_SEEDED', 'POT_DRAW', 'MANUAL'])],

            'internal_cycles' => ['required', 'integer', 'min:1', 'max:10'],
            'internal_allow_draws' => ['boolean'],

            'win_points' => ['required', 'numeric', 'between:-9999.99,9999.99'],
            'draw_points' => ['required', 'numeric', 'between:-9999.99,9999.99'],
            'loss_points' => ['required', 'numeric', 'between:-9999.99,9999.99'],

            'round_limit' => ['nullable', 'integer', 'min:1', 'max:5000'],

            'pin_participants' => ['boolean'],
            'participants' => ['nullable', 'integer', 'min:2', 'max:512'],
        ];
    }

    public function persist(
        PhaseTemplate $phaseTemplate,
        array $data
    ): void {

        $settings = $this->settingsService->ensure($phaseTemplate);

        $stored = $settings->settings ?? [];

        /* Con cuantos abrir la proxima vez. No es el contrato: ver abajo. */
        if (! empty($data['participants'])) {
            $stored['preview_participants'] = (int) $data['participants'];
        }

        if (! empty($data['round_limit'])) {
            $stored['round_limit'] = (int) $data['round_limit'];
        } else {
            unset($stored['round_limit']);
        }

        $settings->fill([
            'group_count_mode' => $data['group_count_mode'],
            'group_count' => $data['group_count'] ?: null,
            'target_group_size' => $data['target_group_size'] ?: null,

            'min_group_size' => (int) $data['min_group_size'],
            'max_group_size' => (int) $data['max_group_size'],

            'remainder_policy' => $data['remainder_policy'],
            'distribution_mode' => $data['distribution_mode'],

            'internal_cycles' => (int) $data['internal_cycles'],
            'internal_allow_draws' => (bool) ($data['internal_allow_draws'] ?? false),

            'internal_win_points' => (float) $data['win_points'],
            'internal_draw_points' => (float) $data['draw_points'],
            'internal_loss_points' => (float) $data['loss_points'],

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
    |--------------------------------------------------------------------------
    | Puertas de entrada
    |--------------------------------------------------------------------------
    */

    public function gateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'target_group_code' => ['nullable', 'string', 'max:40'],

            'entry_from' => ['nullable', 'integer', 'min:1', 'max:512'],
            'entry_to' => ['nullable', 'integer', 'min:1', 'max:512', 'gte:entry_from'],

            'is_required' => ['boolean'],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    public function persistGate(
        PhaseTemplate $phaseTemplate,
        mixed $gate,
        array $data
    ): void {

        $payload = [
            'name' => $data['name'],
            'input_type' => 'POOL',
            'merge_policy' => 'APPEND',
            'distribution_mode' => 'INPUT_ORDER',
            'empty_behavior' => 'ALLOW_EMPTY',
            'capacity_mode' => 'RANGE',
            'is_required' => (bool) ($data['is_required'] ?? false),
            'status' => $data['status'] ?? 'ACTIVE',
            'target_group_code' => $data['target_group_code'] ?: null,
        ];

        if ($gate instanceof PhaseInputGate) {
            $this->gateService->update($gate, $payload);
            $saved = $gate->fresh();
        } else {
            $saved = $this->gateService->create($phaseTemplate, $payload);
        }

        /*
         * El tramo de entrantes y el grupo destino viven en `settings`.
         *
         * Se escriben aqui los dos y no se delega en el servicio: su
         * `create()` no mira target_group_code -solo lo hace `update()`-,
         * asi que una puerta recien creada perdia su grupo hasta que
         * alguien la editaba. Escribiendolos juntos, los dos caminos hacen
         * lo mismo.
         */
        $settings = $saved->settings ?? [];

        if (! empty($data['target_group_code'])) {
            $settings['target_group_code'] = $data['target_group_code'];
        } else {
            unset($settings['target_group_code']);
        }

        if (! empty($data['entry_from'])) {
            $settings['entry_range'] = [
                'from' => (int) $data['entry_from'],
                'to' => (int) ($data['entry_to'] ?: $data['entry_from']),
            ];
        } else {
            unset($settings['entry_range']);
        }

        $saved->forceFill(['settings' => $settings ?: null])->save();
    }

    public function deleteGate(PhaseTemplate $phaseTemplate, mixed $gate): void
    {
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
    | La puerta se crea con su criterio, igual que en la pantalla de
    | entradas y salidas: una puerta sin criterio no la cruza nadie y un
    | criterio sin puerta no lleva a ningun sitio.
    |
    | El selector se fija en ENGINE_RULES a proposito. En una fase de grupos
    | el motor entrega la lista que producen los criterios y el selector
    | propio de la puerta no lo aplica nadie; guardar ademas un numero seria
    | tener dos verdades sobre lo mismo, que es lo que dejo un torneo
    | bloqueado con la fase entera jugada.
    |
    */

    public function exitRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],

            'rule_type' => ['required', Rule::in(array_keys($this->definitions->ruleTypes()))],

            'take' => ['nullable', 'integer', 'min:1', 'max:512'],
            'position_from' => ['nullable', 'integer', 'min:1', 'max:512'],
            'position_to' => ['nullable', 'integer', 'min:1', 'max:512', 'gte:position_from'],

            'phase_group_stage_group_id' => ['nullable', 'integer', 'exists:phase_group_stage_groups,id'],

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
            'selector_type' => 'ENGINE_RULES',
            'exit_timing' => 'PHASE_END',
            'priority' => (int) ($data['priority'] ?? 10),
            'status' => $data['status'] ?? 'ACTIVE',
        ];

        if ($exit instanceof PhaseExit) {
            $this->exitService->update($exit, $payload);

            return;
        }

        $saved = $this->exitService->create($phaseTemplate, $payload);

        app(GroupStageAdvancementRuleService::class)->create($phaseTemplate, [
            'phase_exit_id' => $saved->id,
            'rule_type' => $data['rule_type'],
            'take' => $data['take'] ?? null,
            'position_from' => $data['position_from'] ?? null,
            'position_to' => $data['position_to'] ?? null,
            'phase_group_stage_group_id' => $data['phase_group_stage_group_id'] ?? null,
            'status' => $data['status'] ?? 'ACTIVE',
        ]);
    }

    public function deleteExit(PhaseTemplate $phaseTemplate, mixed $exit): void
    {
        abort_unless(
            $exit instanceof PhaseExit
                && (int) $exit->phase_template_id === (int) $phaseTemplate->id,
            404
        );

        /*
         * Los criterios se van con su puerta: la tabla guarda el id sin
         * llave foranea, asi que dejarlos atras no da error, deja reglas
         * apuntando a una puerta que ya no existe.
         */
        $phaseTemplate
            ->groupStageAdvancementRules()
            ->where('phase_exit_id', $exit->id)
            ->delete();

        $this->exitService->delete($exit);
    }


    /*
    |--------------------------------------------------------------------------
    | Grupos personalizados
    |--------------------------------------------------------------------------
    |
    | Solo tienen sentido con construccion CUSTOM_GROUPS. Se editan en el
    | panel derecho, junto a las puertas, porque decidir cuantos grupos hay
    | y a que grupo manda cada puerta es la misma conversacion.
    |
    */

    public function groupRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'capacity' => ['nullable', 'integer', 'min:2', 'max:64'],
            'cycles' => ['nullable', 'integer', 'min:1', 'max:10'],
            'is_active' => ['boolean'],
        ];
    }

    public function persistGroup(
        PhaseTemplate $phaseTemplate,
        mixed $group,
        array $data
    ): void {

        $payload = [
            'name' => $data['name'],
            'capacity' => $data['capacity'] ?: null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];

        if ($group instanceof PhaseGroupStageGroup) {
            $this->groupService->update($group, $payload);
            $saved = $group->fresh();
        } else {
            /*
             * Crear un grupo a mano ES elegir el modo personalizado.
             *
             * El servicio exige que el modo GUARDADO sea CUSTOM_GROUPS, y el
             * editor deja elegirlo sin guardar todavia: crear el primer grupo
             * fallaba con "solo puedes agregar grupos manualmente cuando el
             * modo es Grupos personalizados" y la pantalla volvia a cantidad
             * fija. Un grupo con nombre y cupo propios no significa nada en
             * ningun otro modo, asi que se fija aqui en vez de rechazar.
             */
            $this->useCustomGroups($phaseTemplate);

            $saved = $this->groupService->create($phaseTemplate, $payload);
        }

        $settings = $saved->settings ?? [];

        if (! empty($data['cycles'])) {
            $settings['cycles'] = (int) $data['cycles'];
        } else {
            unset($settings['cycles']);
        }

        $saved->forceFill(['settings' => $settings ?: null])->save();
    }

    /*
     * Convertir el reparto automatico en grupos personalizados.
     *
     * Al pasar a "grupos personalizados" la estructura desaparecia: los
     * grupos venian del modo automatico y no tenian cupo, que es justo lo
     * que el modo personalizado exige. Quedaba una pantalla en blanco sin
     * mas pista que un error generico.
     *
     * Esto coge el reparto que se estaba viendo y lo escribe como cupos, que
     * es lo que casi siempre se quiere: partir de lo que ya habia y retocar
     * un grupo, no empezar de cero.
     *
     * @param  array<int,int>  $sizes  el reparto que habia en pantalla
     */
    public function adoptGroupSizes(
        PhaseTemplate $phaseTemplate,
        array $sizes
    ): void {

        $sizes = array_values(array_filter(
            array_map('intval', $sizes),
            fn(int $size) => $size > 0
        ));

        if ($sizes === []) {
            return;
        }

        /* Adoptar un reparto como cupos propios es elegir el modo */
        $this->useCustomGroups($phaseTemplate);

        $existing = $phaseTemplate
            ->groupStageGroups()
            ->orderBy('sort_order')
            ->orderBy('sequence_number')
            ->get()
            ->values();

        foreach ($sizes as $index => $size) {

            $group = $existing->get($index);

            if ($group) {
                $group->forceFill([
                    'capacity' => $size,
                    'is_active' => true,
                ])->save();

                continue;
            }

            /*
             * Faltan grupos: se crean con el nombre que les tocaria, para
             * que el reparto adoptado quepa entero.
             */
            $this->groupService->create($phaseTemplate, [
                'name' => 'Grupo ' . $this->alphabetic($index + 1),
                'capacity' => $size,
                'is_active' => true,
            ]);
        }

        /*
         * Los que sobran se desactivan en vez de borrarse: pueden tener
         * criterios de salida apuntandoles, y borrarlos en silencio los
         * dejaria huerfanos.
         */
        $existing->slice(count($sizes))->each(
            fn($group) => $group->forceFill(['is_active' => false])->save()
        );
    }

    private function useCustomGroups(PhaseTemplate $phaseTemplate): void
    {
        $settings = $this->settingsService->ensure($phaseTemplate);

        if ($settings->group_count_mode === 'CUSTOM_GROUPS') {
            return;
        }

        $settings->forceFill([
            'group_count_mode' => 'CUSTOM_GROUPS',
        ])->save();
    }

    private function alphabetic(int $position): string
    {
        $label = '';

        while ($position > 0) {
            $position--;
            $label = chr(65 + ($position % 26)) . $label;
            $position = intdiv($position, 26);
        }

        return $label;
    }

    public function deleteGroup(PhaseTemplate $phaseTemplate, mixed $group): void
    {
        abort_unless(
            $group instanceof PhaseGroupStageGroup
                && (int) $group->phase_template_id === (int) $phaseTemplate->id,
            404
        );

        $this->groupService->delete($phaseTemplate, $group);
    }

    /*
     * Etiqueta de catalogo, o la clave cruda si el catalogo no la conoce.
     *
     * Devolver la clave en vez de una cadena vacia es deliberado: un ajuste
     * viejo que ya no esta en el catalogo se ve como "POTS" y se puede
     * arreglar, mientras que un hueco en blanco solo desconcierta.
     */
    private function labelOf(array $catalog, ?string $key, string $fallback = '—'): string
    {
        if ($key === null || $key === '') {
            return $fallback;
        }

        return $catalog[$key]['label'] ?? $key;
    }

    private function hintOf(array $catalog, ?string $key): ?string
    {
        return $key === null ? null : ($catalog[$key]['hint'] ?? null);
    }

    private function plural(int $n, string $singular, string $plural): string
    {
        return $n . ' ' . ($n === 1 ? $singular : $plural);
    }

    /*
     * Una fase de grupos se reconoce por sus cajas: un grupo, una caja.
     */
    public function outline(PhaseTemplate $phaseTemplate): array
    {
        $settings = $this->settingsService->ensure($phaseTemplate);

        $contract = $this->contract(
            $phaseTemplate,
            null,
            null,
            $this->rememberedParticipants($settings)
        );

        $players = $contract['resolved'];

        /*
         * Cuantos grupos, sin repartir a nadie. Con cantidad fija lo dice el
         * ajuste; con tamano objetivo se divide y se redondea hacia arriba,
         * que es lo que hace el repartidor de verdad.
         */
        $groups = match ($settings->group_count_mode) {
            'FIXED_COUNT' => max(1, (int) $settings->group_count),
            'TARGET_SIZE' => max(1, (int) ceil($players / max(1, (int) $settings->target_group_size))),
            default => max(1, (int) ($settings->group_count ?: 1)),
        };

        $size = (int) ceil($players / $groups);

        return [
            'kind' => 'GROUPS',
            'label' => $groups . ' grupos de ' . $size,
            'columns' => array_fill(0, min($groups, 12), $size),
            'slots' => $players,
        ];
    }

    /*
     * La fase de grupos, contada. Ver PhaseSuperEditorContract::summary().
     */
    public function summary(PhaseTemplate $phaseTemplate, array $payload): array
    {
        $s = $payload['settings'];
        $catalog = $payload['catalog'];
        $structure = $payload['structure'];

        $mode = $s['group_count_mode'];
        $cycles = (int) $s['internal_cycles'];
        $limit = $s['round_limit'] ?? null;
        $rounds = (int) ($structure['max_rounds'] ?? 0);

        $grupos = [
            [
                'label' => 'Se deciden',
                'value' => $this->labelOf($catalog['group_count_modes'], $mode),
                'hint' => $this->hintOf($catalog['group_count_modes'], $mode),
            ],
            [
                'label' => 'Cuántos',
                'value' => $this->plural((int) ($structure['groups_count'] ?? 0), 'grupo', 'grupos'),
            ],
            [
                'label' => 'Tamaño',
                'value' => ($structure['min_size'] ?? null) === ($structure['max_size'] ?? null)
                    ? $this->plural((int) ($structure['min_size'] ?? 0), 'competidor', 'competidores')
                    : ($structure['min_size'] ?? '?') . '–' . ($structure['max_size'] ?? '?') . ' competidores',
                'hint' => ($structure['min_size'] ?? null) === ($structure['max_size'] ?? null)
                    ? 'Todos los grupos son iguales.'
                    : 'Los grupos no son iguales: la clasificación entre ellos se normaliza.',
            ],
        ];

        $reparto = [
            [
                'label' => 'Cómo se reparten',
                'value' => $this->labelOf($catalog['distribution_modes'], $s['distribution_mode']),
                'hint' => $this->hintOf($catalog['distribution_modes'], $s['distribution_mode']),
            ],
            [
                'label' => 'Los que sobran',
                'value' => $this->labelOf($catalog['remainder_policies'], $s['remainder_policy']),
                'hint' => $this->hintOf($catalog['remainder_policies'], $s['remainder_policy']),
            ],
        ];

        return [

            'figures' => [
                [
                    'label' => 'Compiten',
                    'value' => (string) ($structure['participants'] ?? 0),
                ],
                [
                    'label' => 'Grupos',
                    'value' => (string) ($structure['groups_count'] ?? 0),
                    'accent' => 'text-indigo-300',
                ],
                [
                    'label' => 'Jornadas',
                    'value' => (string) ($limit ?? $rounds),
                    'accent' => 'text-cyan-300',
                ],
                [
                    'label' => 'Duelos',
                    'value' => (string) ($structure['total_series'] ?? 0),
                    'accent' => 'text-amber-300',
                ],
            ],

            'groups' => [
            [
                'title' => 'Los grupos',
                'icon' => '▦',
                'accent' => 'indigo',
                'rows' => $grupos,
            ],
            [
                'title' => 'Cómo se juega dentro',
                'icon' => '↻',
                'accent' => 'cyan',
                'rows' => [
                    [
                        'label' => 'Vueltas',
                        'value' => match ($cycles) {
                            1 => 'Una vuelta',
                            2 => 'Ida y vuelta',
                            default => $cycles . ' vueltas',
                        },
                        'hint' => 'Dentro de cada grupo, por separado.',
                    ],
                    [
                        'label' => 'Jornadas',
                        'value' => $limit !== null && $limit < $rounds
                            ? $limit . ' de ' . $rounds
                            : $this->plural($rounds, 'jornada', 'jornadas'),
                        'hint' => $limit !== null && $limit < $rounds
                            ? 'Los grupos se cortan antes de terminar.'
                            : 'Todos los grupos se juegan enteros.',
                    ],
                    [
                        'label' => 'Enfrentamientos',
                        'value' => $this->plural((int) ($structure['total_series'] ?? 0), 'duelo', 'duelos'),
                        'hint' => 'Sumando todos los grupos.',
                    ],
                    [
                        'label' => 'Empates',
                        'value' => $s['internal_allow_draws'] ? 'Se permiten' : 'No hay',
                    ],
                ],
            ],
            [
                'title' => 'Puntos',
                'icon' => '⊕',
                'accent' => 'emerald',
                'rows' => [
                    ['label' => 'Victoria', 'value' => $this->points($s['win_points'])],
                    ['label' => 'Empate', 'value' => $s['internal_allow_draws'] ? $this->points($s['draw_points']) : '—'],
                    ['label' => 'Derrota', 'value' => $this->points($s['loss_points'])],
                ],
            ],
            [
                'title' => 'Reparto',
                'icon' => '⇥',
                'accent' => 'amber',
                'rows' => $reparto,
            ],
            [
                'title' => 'Cómo se desempata',
                'icon' => '⚖',
                'accent' => 'violet',
                'rows' => array_values(array_map(
                    fn (string $label, int $i) => [
                        'label' => ($i + 1) . 'º',
                        'value' => $label,
                    ],
                    $catalog['tiebreak_chain'],
                    array_keys($catalog['tiebreak_chain'])
                )),
            ],
            ],
        ];
    }

    private function points(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',')
            . ' pt' . (abs($value) === 1.0 ? '' : 's');
    }

}
