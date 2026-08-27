<?php

namespace App\Services\Tournaments\SingleElimination;

use App\Models\PhaseExit;
use App\Models\PhaseInputGate;
use App\Models\PhaseTemplate;
use App\Models\User;
use App\Services\Tournaments\PhaseEditor\PhaseSuperEditorContract;
use App\Services\Tournaments\PhaseExitService;
use App\Services\Tournaments\Preview\PreviewCastService;
use App\Services\Tournaments\RoundRobin\RoundRobinSeedRuleResolver;
use Illuminate\Validation\Rule;

/*
|--------------------------------------------------------------------------
| SingleEliminationSuperEditor
|--------------------------------------------------------------------------
|
| Todo lo que la Super Edicion necesita saber de una eliminacion directa.
|
| El cuadro sale de SingleEliminationBracketPlanner y las cuentas
| -cuantas rondas, cuantos descansos, si la configuracion vale- del
| calculador y el validador de siempre. Aqui no se ha escrito ni una regla
| de torneo nueva.
|
| La costura es la misma que en los otros dos motores: el planificador
| empareja PUESTOS del cuadro -el 1 contra el 8-, nunca personas, asi que
| cambiar el orden de entrada, barajar o sembrar por ranking no recalcula el
| arbol: solo cambia quien ocupa cada puesto.
|
| Lo que NO se configura aqui, igual que en los demas:
|
|   - Formato de batalla. series_format, default_best_of y fixed_games
|     siguen en la tabla y el motor los lee, pero como se pelea un
|     enfrentamiento es del torneo real.
|
|   - Enfrentamientos de tres o mas. El modelo los admite
|     (encounter_profile = MULTI_COMPETITOR) pero esta version dibuja solo
|     duelos 1 contra 1.
|
*/
class SingleEliminationSuperEditor implements PhaseSuperEditorContract
{
    /* Las rondas, para que cada columna del arbol se distinga */
    private const ROUND_PALETTE = [
        ['key' => 'sky',     'dot' => 'bg-sky-400',     'ring' => 'ring-sky-400/70',     'text' => 'text-sky-300',     'soft' => 'bg-sky-500/10',     'border' => 'border-sky-400/40',     'solid' => 'bg-sky-500'],
        ['key' => 'emerald', 'dot' => 'bg-emerald-400', 'ring' => 'ring-emerald-400/70', 'text' => 'text-emerald-300', 'soft' => 'bg-emerald-500/10', 'border' => 'border-emerald-400/40', 'solid' => 'bg-emerald-500'],
        ['key' => 'amber',   'dot' => 'bg-amber-400',   'ring' => 'ring-amber-400/70',   'text' => 'text-amber-300',   'soft' => 'bg-amber-500/10',   'border' => 'border-amber-400/40',   'solid' => 'bg-amber-500'],
        ['key' => 'fuchsia', 'dot' => 'bg-fuchsia-400', 'ring' => 'ring-fuchsia-400/70', 'text' => 'text-fuchsia-300', 'soft' => 'bg-fuchsia-500/10', 'border' => 'border-fuchsia-400/40', 'solid' => 'bg-fuchsia-500'],
        ['key' => 'lime',    'dot' => 'bg-lime-400',    'ring' => 'ring-lime-400/70',    'text' => 'text-lime-300',    'soft' => 'bg-lime-500/10',    'border' => 'border-lime-400/40',    'solid' => 'bg-lime-500'],
        ['key' => 'cyan',    'dot' => 'bg-cyan-400',    'ring' => 'ring-cyan-400/70',    'text' => 'text-cyan-300',    'soft' => 'bg-cyan-500/10',    'border' => 'border-cyan-400/40',    'solid' => 'bg-cyan-500'],
    ];

    /* Las salidas, con paleta propia para no confundirse con las rondas */
    private const EXIT_PALETTE = [
        ['key' => 'violet', 'dot' => 'bg-violet-400', 'ring' => 'ring-violet-400/70', 'text' => 'text-violet-300', 'soft' => 'bg-violet-400/10', 'wash' => 'bg-violet-400/5', 'border' => 'border-violet-400/40', 'solid' => 'bg-violet-500'],
        ['key' => 'teal',   'dot' => 'bg-teal-400',   'ring' => 'ring-teal-400/70',   'text' => 'text-teal-300',   'soft' => 'bg-teal-400/10',   'wash' => 'bg-teal-400/5',   'border' => 'border-teal-400/40',   'solid' => 'bg-teal-500'],
        ['key' => 'orange', 'dot' => 'bg-orange-400', 'ring' => 'ring-orange-400/70', 'text' => 'text-orange-300', 'soft' => 'bg-orange-400/10', 'wash' => 'bg-orange-400/5', 'border' => 'border-orange-400/40', 'solid' => 'bg-orange-500'],
        ['key' => 'indigo', 'dot' => 'bg-indigo-400', 'ring' => 'ring-indigo-400/70', 'text' => 'text-indigo-300', 'soft' => 'bg-indigo-400/10', 'wash' => 'bg-indigo-400/5', 'border' => 'border-indigo-400/40', 'solid' => 'bg-indigo-500'],
        ['key' => 'pink',   'dot' => 'bg-pink-400',   'ring' => 'ring-pink-400/70',   'text' => 'text-pink-300',   'soft' => 'bg-pink-400/10',   'wash' => 'bg-pink-400/5',   'border' => 'border-pink-400/40',   'solid' => 'bg-pink-500'],
        ['key' => 'slate',  'dot' => 'bg-slate-400',  'ring' => 'ring-slate-400/70',  'text' => 'text-slate-300',  'soft' => 'bg-slate-400/10',  'wash' => 'bg-slate-400/5',  'border' => 'border-slate-400/40',  'solid' => 'bg-slate-500'],
    ];

    /*
     * Las ramas del cuadro. Tercera paleta, y por el mismo motivo por el que
     * las salidas tienen la suya: una rama y una ronda son cosas distintas y
     * compartir color hacia creer que eran la misma familia.
     */
    private const BRANCH_PALETTE = [
        ['key' => 'rose',    'dot' => 'bg-rose-400',    'ring' => 'ring-rose-400/70',    'text' => 'text-rose-300',    'soft' => 'bg-rose-400/10',    'wash' => 'bg-rose-400/5',    'border' => 'border-rose-400/40',    'solid' => 'bg-rose-500'],
        ['key' => 'blue',    'dot' => 'bg-blue-400',    'ring' => 'ring-blue-400/70',    'text' => 'text-blue-300',    'soft' => 'bg-blue-400/10',    'wash' => 'bg-blue-400/5',    'border' => 'border-blue-400/40',    'solid' => 'bg-blue-500'],
        ['key' => 'green',   'dot' => 'bg-green-400',   'ring' => 'ring-green-400/70',   'text' => 'text-green-300',   'soft' => 'bg-green-400/10',   'wash' => 'bg-green-400/5',   'border' => 'border-green-400/40',   'solid' => 'bg-green-500'],
        ['key' => 'yellow',  'dot' => 'bg-yellow-400',  'ring' => 'ring-yellow-400/70',  'text' => 'text-yellow-300',  'soft' => 'bg-yellow-400/10',  'wash' => 'bg-yellow-400/5',  'border' => 'border-yellow-400/40',  'solid' => 'bg-yellow-500'],
        ['key' => 'purple',  'dot' => 'bg-purple-400',  'ring' => 'ring-purple-400/70',  'text' => 'text-purple-300',  'soft' => 'bg-purple-400/10',  'wash' => 'bg-purple-400/5',  'border' => 'border-purple-400/40',  'solid' => 'bg-purple-500'],
        ['key' => 'red',     'dot' => 'bg-red-400',     'ring' => 'ring-red-400/70',     'text' => 'text-red-300',     'soft' => 'bg-red-400/10',     'wash' => 'bg-red-400/5',     'border' => 'border-red-400/40',     'solid' => 'bg-red-500'],
        ['key' => 'stone',   'dot' => 'bg-stone-400',   'ring' => 'ring-stone-400/70',   'text' => 'text-stone-300',   'soft' => 'bg-stone-400/10',   'wash' => 'bg-stone-400/5',   'border' => 'border-stone-400/40',   'solid' => 'bg-stone-500'],
        ['key' => 'zinc',    'dot' => 'bg-zinc-400',    'ring' => 'ring-zinc-400/70',    'text' => 'text-zinc-300',    'soft' => 'bg-zinc-400/10',    'wash' => 'bg-zinc-400/5',    'border' => 'border-zinc-400/40',    'solid' => 'bg-zinc-500'],
    ];

    public function __construct(
        private readonly SingleEliminationSettingsService $settingsService,
        private readonly SingleEliminationBracketPlanner $planner,
        private readonly SingleEliminationValidator $validator,
        private readonly PhaseExitService $exitService,
        private readonly PreviewCastService $cast,
        private readonly RoundRobinSeedRuleResolver $seedRules
    ) {}

    public function phaseType(): string
    {
        return 'SINGLE_ELIMINATION';
    }

    public function configView(): string
    {
        return 'tournaments.phase-templates.super.single-elimination.config';
    }

    public function stageView(): string
    {
        return 'tournaments.phase-templates.super.single-elimination.stage';
    }

    public function gatesView(): string
    {
        return 'tournaments.phase-templates.super.single-elimination.gates';
    }

    public function scheduleView(): string
    {
        return 'tournaments.phase-templates.super.single-elimination.schedule';
    }

    public function saveFieldsView(): string
    {
        return 'tournaments.phase-templates.super.single-elimination.save-fields';
    }

    public function previewOverrideKeys(): array
    {
        return [
            'participants' => 'int',
            'completion_mode' => 'string',
            'target_survivors' => 'int',
            'seeding_mode' => 'string',
            'pairing_mode' => 'string',
            'bye_assignment' => 'string',

            /*
             * Los grupos activos viajan como lista separada por comas.
             * Cuando no hay ninguno viaja un guion, no una cadena vacia:
             * `filled()` descarta la cadena vacia y desactivar el ultimo
             * grupo no llegaria nunca al servidor.
             */
            'placements' => 'string',
        ];
    }

    public function clientEngine(): string
    {
        return 'singleElimination';
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

        $contract = $this->contract(
            $phaseTemplate,
            $overrides['participants'] ?? null,
            $this->rememberedParticipants($settings)
        );

        /*
         * Lo que se esta tocando manda sobre lo guardado: si no, mover un
         * control devolveria la configuracion anterior y el cuadro
         * parpadearia hacia atras.
         */
        $completion = $overrides['completion_mode'] ?? $settings->completion_mode;

        $survivors = $completion === 'SURVIVORS'
            ? max(2, (int) ($overrides['target_survivors'] ?? $settings->target_survivors ?? 2))
            : 1;

        $seeding = $overrides['seeding_mode'] ?? $settings->seeding_mode;
        $pairing = $overrides['pairing_mode'] ?? $settings->pairing_mode;
        $byes = $overrides['bye_assignment'] ?? $settings->bye_assignment;

        $placements = array_key_exists('placements', $overrides)
            ? $this->parsePlacements($overrides['placements'])
            : $this->storedPlacements($settings);

        $errors = $this->validator->validate(
            $phaseTemplate,
            $settings,
            $contract['resolved']
        );

        $plan = $errors === []
            ? $this->planner->plan(
                $contract['resolved'],
                $pairing,
                $byes,
                $survivors,
                $placements
            )
            : [
                'valid' => false,
                'bracket_size' => 0,
                'byes' => 0,
                'rounds' => [],
                'groups' => [],
                'placements' => [],
                'branches' => [],
            ];

        return [
            'phase' => $this->phaseSummary($phaseTemplate),

            'contract' => $contract,

            'settings' => [
                'completion_mode' => $completion,
                'target_survivors' => $survivors,

                'seeding_mode' => $seeding,
                'pairing_mode' => $pairing,
                'bye_assignment' => $byes,

                'placements' => $placements,

                'ranking_source' => $this->rankingSource($settings),
            ],

            'structure' => [
                'valid' => $plan['valid'],
                'participants' => $contract['resolved'],
                'bracket_size' => $plan['bracket_size'],
                'byes' => $plan['byes'],
                'round_count' => count($plan['rounds']),
                'total_matches' => $this->countMatches($plan),
                'survivors' => $survivors,
                'decided' => $this->decidedPositions($plan),
            ],

            'rounds' => $this->paint($plan['rounds']),

            /*
             * Que puestos decide el cuadro y cuales deja empatados. Es lo
             * que permite decir, antes de jugar, si una salida que pide el
             * quinto puesto tiene forma de saber quien es el quinto.
             */
            'groups' => $this->paintGroups($plan['groups']),

            'placements' => $plan['placements'],

            'branches' => $this->paintBranches($plan['branches']),

            'cast' => $this->buildCast($user, $contract['resolved']),

            'gates' => $this->gates($phaseTemplate, $contract['resolved']),

            'seed_map' => $this->seedRules->resolve(
                $contract['resolved'],
                $phaseTemplate->inputGates()->get()
            ),

            'exits' => $this->exits($phaseTemplate, $plan),

            'catalog' => [
                'completion_modes' => $this->completionModes(),
                'seeding_modes' => $this->seedingModes(),
                'pairing_modes' => $this->pairingModes(),
                'bye_assignments' => $this->byeAssignments(),
                'ranking_sources' => $this->rankingSources(),
                'exit_selectors' => $this->exitSelectors($completion, $survivors, $plan),
                'branch_options' => $this->branchOptions($plan),
                'seed_rule_types' => RoundRobinSeedRuleResolver::TYPES,
            ],

            'diagnostics' => $this->diagnostics(
                $phaseTemplate,
                $errors,
                $plan,
                $survivors
            ),
        ];
    }

    /*
     * Un color por ronda, para que las columnas del arbol se distingan de un
     * vistazo y el borde de un enfrentamiento diga a que altura esta.
     */
    private function paint(array $rounds): array
    {
        return array_values(array_map(
            fn(array $round, int $index) => [
                ...$round,
                'color' => self::ROUND_PALETTE[$index % count(self::ROUND_PALETTE)],
            ],
            $rounds,
            array_keys($rounds)
        ));
    }


    /*
     * Los grupos que SI se juegan se pintan; los que quedan empatados se
     * quedan en gris, porque un grupo sin cuadro de clasificacion no es una
     * familia del cuadro: es un empate.
     */
    private function paintGroups(array $groups): array
    {
        return array_values(array_map(
            fn (array $group, int $index) => [
                ...$group,
                'color' => self::ROUND_PALETTE[$index % count(self::ROUND_PALETTE)],
            ],
            $groups,
            array_keys($groups)
        ));
    }

    private function paintBranches(array $branches): array
    {
        return array_values(array_map(
            fn (array $branch, int $index) => [
                ...$branch,
                'color' => self::BRANCH_PALETTE[$index % count(self::BRANCH_PALETTE)],
            ],
            $branches,
            array_keys($branches)
        ));
    }

    private function countMatches(array $plan): int
    {
        $total = array_sum(array_map(
            fn (array $round) => count($round['matches']),
            $plan['rounds']
        ));

        foreach ($plan['placements'] as $placement) {
            $total += array_sum(array_map(
                fn (array $round) => count($round['matches']),
                $placement['rounds']
            ));
        }

        return $total;
    }

    /*
     * Los puestos que el cuadro sabe decidir: los de un grupo de uno -que ya
     * esta decidido- y los de un grupo con cuadro de clasificacion activo.
     *
     * @return array<int,int>
     */
    private function decidedPositions(array $plan): array
    {
        $out = [];

        foreach ($plan['groups'] as $group) {

            if (! $group['auto'] && ! ($group['enabled'] ?? false)) {
                continue;
            }

            for ($p = $group['from']; $p <= $group['to']; $p++) {
                $out[] = $p;
            }
        }

        sort($out);

        return $out;
    }

    /*
     * El grupo al que pertenece un puesto, para poder decir exactamente que
     * hay que activar cuando una salida pide algo que nadie decide.
     */
    private function groupOfPosition(array $plan, int $position): ?array
    {
        foreach ($plan['groups'] as $group) {
            if ($position >= $group['from'] && $position <= $group['to']) {
                return $group;
            }
        }

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Puertas
    |--------------------------------------------------------------------------
    |
    | En un cuadro una puerta de entrada decide POR DONDE ENTRA cada uno: que
    | puestos del cuadro reclama. Y eso decide contra quien se abre y en que
    | mitad se cae, que es todo lo que hay que decidir antes de jugar.
    |
    | Reutiliza RoundRobinSeedRuleResolver tal cual. El nombre viene de donde
    | nacio, pero lo que hace -repartir posiciones de salida entre puertas- no
    | es propio de una liga: en un cuadro son puestos del arbol.
    |
    */

    private function gates(PhaseTemplate $phaseTemplate, int $participants): array
    {
        $gates = $phaseTemplate->inputGates()->get()->values();

        $resolution = $this->seedRules->resolve($participants, $gates);

        return $gates
            ->map(function ($gate, int $index) use ($resolution, $participants) {

                $rule = $this->seedRules->ruleOf($gate);

                return [
                    'id' => $gate->id,
                    'code' => $gate->code,
                    'number' => $gate->sequence_number,
                    'name' => $gate->name,

                    'color' => self::ROUND_PALETTE[
                        (($gate->sequence_number ?? ($index + 1)) - 1) % count(self::ROUND_PALETTE)
                    ],

                    'seed_type' => $rule['type'],
                    'seed_count' => $rule['count'],
                    'seed_from' => $rule['from'],
                    'seed_to' => $rule['to'],

                    'seeds' => $resolution['assignments'][$index] ?? [],

                    'rule_label' => $this->seedRules->summarize($rule, $participants),

                    'is_required' => (bool) $gate->is_required,
                    'status' => $gate->status,
                ];
            })
            ->all();
    }

    private function exits(PhaseTemplate $phaseTemplate, array $plan): array
    {
        return $phaseTemplate
            ->exits()
            ->get()
            ->values()
            ->map(fn ($exit, int $index) => [
                'id' => $exit->id,
                'code' => $exit->code,
                'number' => $exit->sequence_number,
                'name' => $exit->name,

                'color' => self::EXIT_PALETTE[
                    (($exit->sequence_number ?? ($index + 1)) - 1) % count(self::EXIT_PALETTE)
                ],

                'selector_type' => $exit->selector_type,
                'selector_from' => $exit->selector_from,
                'selector_to' => $exit->selector_to,

                'summary' => $exit->selection_summary,

                /*
                 * Que puestos FINALES se lleva. Es lo que permite pintar el
                 * cuadro: el campeon es el puesto 1, el finalista el 2, y
                 * los semifinalistas el 3 y el 4.
                 */
                'positions' => $this->exitPositions($exit),

                /* La rama del cuadro de la que recoge, si es de ese tipo */
                'branch' => $exit->selector_type === 'BRACKET_BRANCH'
                    ? (int) $exit->selector_from
                    : null,

                /*
                 * Cuantos caben. Se sabe sin jugar nada, y hace falta: un
                 * cero antes de empezar significaria "no sale nadie", que es
                 * otra cosa muy distinta de "todavia no se sabe quienes".
                 */
                'capacity' => $this->exitCapacity($exit, $plan),

                /*
                 * Si el cuadro sabe de verdad a quien se refiere. Una salida
                 * que pide el quinto puesto no se llena sola: hace falta
                 * ordenar el grupo del quinto, y hasta entonces la puerta
                 * esta pidiendo algo que nadie decide.
                 */
                ...$this->exitReadiness($exit, $plan),

                'priority' => $exit->priority,
                'status' => $exit->status,
            ])
            ->all();
    }

    private function exitCapacity($exit, array $plan): ?int
    {
        if (! $plan['valid']) {
            return null;
        }

        $survivors = count(end($plan['rounds'])['matches'] ?? []);

        return match ($exit->selector_type) {
            'WINNER', 'RUNNER_UP', 'BRACKET_BRANCH' => 1,
            'SURVIVORS' => $survivors,
            'ELIMINATED' => max(0, $plan['bracket_size'] - $plan['byes'] - $survivors),
            default => ($positions = $this->exitPositions($exit)) !== null
                ? $positions['to'] - $positions['from'] + 1
                : null,
        };
    }

    /*
     * @return array{is_ready: bool, blocked_by: ?string, blocked_hint: ?string}
     */
    private function exitReadiness($exit, array $plan): array
    {
        $ready = ['is_ready' => true, 'blocked_by' => null, 'blocked_hint' => null];

        if (! $plan['valid']) {
            return $ready;
        }

        if ($exit->selector_type === 'BRACKET_BRANCH') {

            $branch = (int) $exit->selector_from;

            return $branch >= 1 && $branch <= count($plan['branches'])
                ? $ready
                : [
                    'is_ready' => false,
                    'blocked_by' => null,
                    'blocked_hint' => match (true) {
                        count($plan['branches']) === 0 =>
                            'El cuadro no tiene ramas: solo queda uno al final.',

                        $branch < 1 =>
                            'No tiene rama elegida. Edítala y escoge una de las '
                                . count($plan['branches']) . '.',

                        default =>
                            'Apunta a la rama ' . $branch . ', y el cuadro solo tiene '
                                . count($plan['branches']) . '.',
                    },
                ];
        }

        $positions = $this->exitPositions($exit);

        if ($positions === null) {
            return $ready;
        }

        $decided = $this->decidedPositions($plan);

        for ($p = $positions['from']; $p <= $positions['to']; $p++) {

            if (in_array($p, $decided, true)) {
                continue;
            }

            $group = $this->groupOfPosition($plan, $p);

            return [
                'is_ready' => false,
                'blocked_by' => $group['key'] ?? null,
                'blocked_hint' => $group === null
                    ? 'El puesto ' . $p . ' no existe en este cuadro.'
                    : 'El puesto ' . $p . ' lo comparten ' . $group['entrants']
                        . ': activa «' . $group['label'] . '» para separarlos.',
            ];
        }

        return $ready;
    }

    private function exitPositions($exit): ?array
    {
        $from = (int) $exit->selector_from;
        $to = (int) $exit->selector_to;

        return match ($exit->selector_type) {
            'WINNER' => ['from' => 1, 'to' => 1],
            'RUNNER_UP' => ['from' => 2, 'to' => 2],
            'TOP_N' => $from > 0 ? ['from' => 1, 'to' => $from] : null,
            'RANK_POSITION', 'POSITION' => $from > 0 ? ['from' => $from, 'to' => $from] : null,
            'RANK_RANGE' => $from > 0 && $to >= $from ? ['from' => $from, 'to' => $to] : null,
            'SURVIVORS' => null,
            'BRACKET_BRANCH' => null,
            default => null,
        };
    }

    /*
     * Los grupos activos, leyendo de `settings`.
     *
     * Antes esto era un solo booleano, `third_place`, porque el partido por
     * el tercer puesto era el unico que existia. Ahora es un caso mas del
     * mecanismo general, asi que un ajuste antiguo se traduce a su grupo: el
     * que empieza en el puesto 3.
     *
     * @return array<int,string>
     */
    private function storedPlacements($settings): array
    {
        $stored = $settings->settings['placements'] ?? null;

        if (is_array($stored)) {
            return array_values(array_filter($stored, 'is_string'));
        }

        return ($settings->settings['third_place'] ?? false) ? ['P3'] : [];
    }

    /*
     * @return array<int,string>
     */
    private function parsePlacements(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter($raw, 'is_string'));
        }

        $raw = trim((string) $raw);

        /* Un guion es "ninguno": la cadena vacia no llega hasta aqui */
        if ($raw === '' || $raw === '-') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $raw)),
            fn (string $key) => preg_match('/^P\d{1,3}$/', $key) === 1
        ));
    }


    /*
    |--------------------------------------------------------------------------
    | Catálogos
    |--------------------------------------------------------------------------
    */

    private function completionModes(): array
    {
        return [
            'WINNER' => [
                'label' => 'Hasta que quede uno',
                'hint' => 'Se juega la final y sale un campeón.',
            ],

            'SURVIVORS' => [
                'label' => 'Hasta que queden N',
                'hint' => 'Se paran las últimas rondas y salen varios con vida.',
            ],
        ];
    }

    private function seedingModes(): array
    {
        return [
            'INPUT_ORDER' => [
                'label' => 'Orden de entrada',
                'hint' => 'El primero en llegar ocupa el puesto 1 del cuadro.',
            ],

            'RANDOM' => [
                'label' => 'Aleatorio',
                'hint' => 'El sorteo lo hace el motor al arrancar la fase.',
            ],

            'RANKING' => [
                'label' => 'Ranking',
                'hint' => 'Se siembra por clasificación. La fuente se elige abajo.',
            ],

            'MANUAL' => [
                'label' => 'Manual',
                'hint' => 'La fase se detiene y te pide colocarlos tú.',
            ],
        ];
    }

    private function pairingModes(): array
    {
        return [
            'STANDARD_SEEDED' => [
                'label' => 'Cuadro clásico',
                'hint' => 'El 1 contra el último, el 2 contra el penúltimo. Los dos primeros solo se cruzan en la final.',
            ],

            'SEQUENTIAL' => [
                'label' => 'Por vecinos',
                'hint' => 'El 1 contra el 2, el 3 contra el 4. Los mejores se eliminan entre sí muy pronto.',
            ],

            'RANDOM' => [
                'label' => 'Sorteo puro',
                'hint' => 'El motor cruza al azar al arrancar. Aquí se dibuja el cuadro clásico.',
            ],
        ];
    }

    private function byeAssignments(): array
    {
        return [
            'TOP_SEEDS' => ['label' => 'A los primeros', 'hint' => 'Los mejores sembrados se saltan la ronda inicial.'],
            'RANDOM' => ['label' => 'Al azar', 'hint' => 'El motor los sortea al arrancar.'],
            'MANUAL' => ['label' => 'Los pongo yo', 'hint' => 'Se deciden al arrancar la fase.'],
        ];
    }

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
     * Los selectores que tienen sentido AL FINAL DE ESTE CUADRO.
     *
     * No es la misma lista siempre, y esa es la razon de que exista este
     * metodo en vez de una constante. Cuando la fase se para antes de la
     * final no hay campeon ni finalista: ofrecerlos daba a elegir dos
     * puertas que no se iban a llenar nunca, sin decir por que.
     *
     * `needs` dice que campos pide cada uno, y el formulario ensena solo
     * esos: 'from', 'to' o 'branch'.
     */
    private function exitSelectors(string $completion, int $survivors, array $plan): array
    {
        $branches = count($plan['branches'] ?? []);

        /* Comunes: hablan de puestos, y existen se corte donde se corte */
        $common = [
            'TOP_N' => [
                'label' => 'Los N primeros',
                'hint' => 'Los N mejores puestos, en orden.',
                'needs' => ['from'],
            ],

            'RANK_POSITION' => [
                'label' => 'Un puesto concreto',
                'hint' => 'Solo quien acabe exactamente ahí. Hace falta que ese puesto esté decidido.',
                'needs' => ['from'],
            ],

            'RANK_RANGE' => [
                'label' => 'Un tramo de puestos',
                'hint' => 'Del puesto X al Y, sin importar el orden dentro del tramo.',
                'needs' => ['from', 'to'],
            ],

            'ELIMINATED' => [
                'label' => 'Los eliminados',
                'hint' => 'Todo el que se quedó por el camino.',
                'needs' => [],
            ],
        ];

        if ($completion !== 'SURVIVORS') {

            return [
                'WINNER' => [
                    'label' => 'El campeón',
                    'hint' => 'Quien gane la final.',
                    'needs' => [],
                ],

                'RUNNER_UP' => [
                    'label' => 'El finalista',
                    'hint' => 'Quien pierda la final: segundo puesto.',
                    'needs' => [],
                ],

                ...$common,
            ];
        }

        /*
         * Terminando con varios en pie, lo natural no es "el campeon" sino
         * "el que salga de esta mitad del cuadro". Por eso la rama es el
         * primer selector de la lista aqui.
         */
        return [
            'SURVIVORS' => [
                'label' => 'Todos los que sobreviven',
                'hint' => 'Los ' . $survivors . ' que siguen en pie, por la misma puerta.',
                'needs' => [],
            ],

            'BRACKET_BRANCH' => [
                'label' => 'El que sale de una rama',
                'hint' => $branches > 0
                    ? 'Una puerta por rama: ' . $branches . ' ramas, ' . $branches . ' salidas distintas.'
                    : 'Este cuadro no tiene ramas.',
                'needs' => ['branch'],
            ],

            ...$common,
        ];
    }

    /*
     * Las ramas, para el desplegable del formulario de salidas.
     */
    private function branchOptions(array $plan): array
    {
        return array_map(
            fn (array $branch) => [
                'value' => $branch['number'],
                'label' => $branch['label'],
                'hint' => 'Puestos del cuadro ' . implode(', ', $branch['seeds']),
                'color' => $branch['color'] ?? null,
            ],
            $this->paintBranches($plan['branches'] ?? [])
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Contrato, reparto y diagnóstico
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
        ?int $remembered = null
    ): array {
        $min = (int) ($phaseTemplate->min_participants ?? 2);
        $max = $phaseTemplate->max_participants !== null ? (int) $phaseTemplate->max_participants : null;
        $exact = $phaseTemplate->exact_participants !== null ? (int) $phaseTemplate->exact_participants : null;
        $multiple = $phaseTemplate->participant_multiple !== null ? (int) $phaseTemplate->participant_multiple : null;

        $default = $exact ?? $remembered ?? max($min, 8);

        $resolved = $requested !== null && $requested > 0 ? $requested : $default;

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
            'is_derived' => false,
        ];
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

    private function diagnostics(
        PhaseTemplate $phaseTemplate,
        array $errors,
        array $plan,
        int $survivors
    ): array {

        $warnings = [];

        if ($plan['valid']) {

            if ($plan['byes'] > 0) {
                $warnings[] =
                    'El cuadro es de ' . $plan['bracket_size'] . ', así que '
                    . $plan['byes'] . ($plan['byes'] === 1 ? ' pasa' : ' pasan')
                    . ' de ronda sin jugar.';
            }

            if ($survivors > 1) {
                $branches = count($plan['branches']);

                $warnings[] =
                    'La fase se para antes de la final: salen ' . $survivors
                    . ' con vida y no hay campeón'
                    . ($branches > 1
                        ? ', uno por cada una de las ' . $branches . ' ramas.'
                        : '.');
            }
        }

        if ($phaseTemplate->exits()->where('status', 'ACTIVE')->count() === 0) {
            $warnings[] = 'Sin puertas de salida nadie avanza a la siguiente fase.';
        }

        /*
         * Lo que de verdad confunde: una salida que pide un puesto que el
         * cuadro no sabe decidir. Antes solo se avisaba del tercero, que era
         * el unico caso que existia. Ahora se avisa de todos, y diciendo
         * exactamente que grupo hay que ordenar para arreglarlo.
         */
        if ($plan['valid']) {

            foreach ($phaseTemplate->exits()->where('status', 'ACTIVE')->get() as $exit) {

                $readiness = $this->exitReadiness($exit, $plan);

                if ($readiness['is_ready']) {
                    continue;
                }

                $warnings[] = 'La salida «' . $exit->name . '» no se puede resolver: '
                    . $readiness['blocked_hint'];
            }
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
            'completion_mode' => ['required', Rule::in(['WINNER', 'SURVIVORS'])],
            /*
             * Minimo UNO, no dos.
             *
             * Terminar con varios supervivientes exige dos o mas, y por eso
             * estaba en dos. Pero el formulario manda siempre este campo, y
             * en modo "hasta que quede uno" vale exactamente 1: la regla
             * rechazaba el envio entero y NADA se guardaba en ese modo, ni
             * el cuadro, ni la siembra, ni la cantidad de participantes. La
             * pantalla volvia a lo ultimo persistido y parecia que guardar
             * no hacia nada.
             *
             * Aceptar el 1 no afloja nada: `persist()` fuerza 1 cuando se
             * juega hasta el final y sube a 2 cuando no, asi que el valor
             * que acaba en la base de datos es correcto igualmente.
             */
            'target_survivors' => ['nullable', 'integer', 'min:1', 'max:256'],

            'seeding_mode' => ['required', Rule::in(['INPUT_ORDER', 'RANDOM', 'RANKING', 'MANUAL'])],
            'pairing_mode' => ['required', Rule::in(['STANDARD_SEEDED', 'SEQUENTIAL', 'RANDOM'])],
            'bye_assignment' => ['required', Rule::in(['TOP_SEEDS', 'RANDOM', 'MANUAL'])],

            'placements' => ['nullable', 'array'],
            'placements.*' => ['string', 'regex:/^P\\d{1,3}$/'],

            'ranking_source' => ['nullable', Rule::in(['TOURNAMENT', 'UNIVERSAL'])],

            'pin_participants' => ['boolean'],
            'participants' => ['nullable', 'integer', 'min:2', 'max:512'],
        ];
    }

    public function persist(PhaseTemplate $phaseTemplate, array $data): void
    {
        $settings = $this->settingsService->ensure($phaseTemplate);

        $stored = $settings->settings ?? [];

        /*
         * Los grupos ordenados y la fuente de ranking viven en `settings`,
         * sin migracion: son vocabulario de este motor y darles columna
         * propia las dejaria nulas en los otros tres.
         */
        $stored['placements'] = $this->parsePlacements($data['placements'] ?? []);
        $stored['ranking_source'] = $data['ranking_source'] ?? 'TOURNAMENT';

        /* Con cuantos abrir la proxima vez. No es el contrato: ver abajo. */
        if (! empty($data['participants'])) {
            $stored['preview_participants'] = (int) $data['participants'];
        }

        /*
         * El booleano viejo se retira al guardar. Dejarlo habria significado
         * dos fuentes para lo mismo, y la que se leyera primero ganaria.
         */
        unset($stored['third_place']);

        $settings->fill([
            'completion_mode' => $data['completion_mode'],

            'target_survivors' => $data['completion_mode'] === 'SURVIVORS'
                ? max(2, (int) ($data['target_survivors'] ?? 2))
                : 1,

            'seeding_mode' => $data['seeding_mode'],
            'pairing_mode' => $data['pairing_mode'],
            'bye_assignment' => $data['bye_assignment'],

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

            'seed_type' => ['required', Rule::in(array_keys(RoundRobinSeedRuleResolver::TYPES))],

            'seed_count' => ['nullable', 'integer', 'min:1', 'max:512'],
            'seed_from' => ['nullable', 'integer', 'min:1', 'max:512'],
            'seed_to' => ['nullable', 'integer', 'min:1', 'max:512', 'gte:seed_from'],

            'is_required' => ['boolean'],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    public function persistGate(PhaseTemplate $phaseTemplate, mixed $gate, array $data): void
    {
        $rule = ['type' => $data['seed_type']];

        foreach (['count', 'from', 'to'] as $field) {
            if (! empty($data['seed_' . $field])) {
                $rule[$field] = (int) $data['seed_' . $field];
            }
        }

        $exact = match ($rule['type']) {
            'FIRST_N', 'LAST_N' => (int) ($rule['count'] ?? 0),
            'POSITION' => 1,
            'RANGE' => max(0, (int) ($rule['to'] ?? 0) - (int) ($rule['from'] ?? 0) + 1),
            default => null,
        };

        $payload = [
            'name' => $data['name'],
            'input_type' => 'POOL',
            'merge_policy' => 'APPEND',
            'distribution_mode' => 'INPUT_ORDER',
            'empty_behavior' => 'ALLOW_EMPTY',

            'min_participants' => $exact ?: null,
            'max_participants' => $exact ?: null,
            'exact_participants' => $exact ?: null,

            'is_required' => (bool) ($data['is_required'] ?? false),
            'accepts_batch' => true,
            'accepts_multiple_connections' => true,

            'priority' => 10,
            'status' => $data['status'] ?? 'ACTIVE',

            'settings' => ['seed_rule' => $rule],
        ];

        if ($gate instanceof PhaseInputGate) {
            $gate->forceFill($payload)->save();

            return;
        }

        $sequence = ((int) $phaseTemplate->inputGates()->max('sequence_number')) + 1;

        $phaseTemplate->inputGates()->create([
            ...$payload,
            'sequence_number' => $sequence,
            'code' => PhaseInputGate::formatCode($sequence),
            'sort_order' => ((int) $phaseTemplate->inputGates()->max('sort_order')) + 10,
            'generation_source' => 'MANUAL',
        ]);
    }

    public function deleteGate(PhaseTemplate $phaseTemplate, mixed $gate): void
    {
        abort_unless(
            $gate instanceof PhaseInputGate
                && (int) $gate->phase_template_id === (int) $phaseTemplate->id,
            404
        );

        $gate->delete();
    }


    /*
    |--------------------------------------------------------------------------
    | Puertas de salida
    |--------------------------------------------------------------------------
    */

    public function exitRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],

            'selector_type' => [
                'required',
                Rule::in([
                    'WINNER', 'RUNNER_UP', 'TOP_N', 'RANK_POSITION',
                    'RANK_RANGE', 'SURVIVORS', 'ELIMINATED', 'BRACKET_BRANCH',
                ]),
            ],

            'selector_from' => ['nullable', 'integer', 'min:1', 'max:512'],
            'selector_to' => ['nullable', 'integer', 'min:1', 'max:512', 'gte:selector_from'],

            'priority' => ['nullable', 'integer', 'min:1', 'max:999'],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    public function persistExit(PhaseTemplate $phaseTemplate, mixed $exit, array $data): void
    {
        $payload = [
            'name' => $data['name'],

            'selector_type' => $data['selector_type'],
            'selector_from' => $data['selector_from'] ?? null,
            'selector_to' => $data['selector_to'] ?? null,

            /*
             * Siempre al terminar la fase.
             *
             * El modelo admite ademas salir EN CUANTO te eliminan, pero eso
             * es un comportamiento de ejecucion -el motor te expulsa a mitad
             * de cuadro- y no algo que se decida dibujando el arbol. Aqui
             * todas las salidas esperan al final, que es lo predecible y lo
             * mismo que hacen los otros dos motores.
             */
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

    public function deleteExit(PhaseTemplate $phaseTemplate, mixed $exit): void
    {
        abort_unless(
            $exit instanceof PhaseExit
                && (int) $exit->phase_template_id === (int) $phaseTemplate->id,
            404
        );

        $this->exitService->delete($exit);
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
     * Un cuadro se reconoce por su embudo: cada ronda tiene la mitad de
     * enfrentamientos que la anterior.
     */
    public function outline(PhaseTemplate $phaseTemplate): array
    {
        $settings = $this->settingsService->ensure($phaseTemplate);

        $contract = $this->contract(
            $phaseTemplate,
            null,
            $this->rememberedParticipants($settings)
        );

        $size = $this->planner->nextPowerOfTwo(max(2, $contract['resolved']));

        $survivors = $settings->completion_mode === 'SURVIVORS'
            ? max(2, (int) ($settings->target_survivors ?? 2))
            : 1;

        $columns = [];

        for ($matches = intdiv($size, 2); $matches >= 1; $matches = intdiv($matches, 2)) {

            $columns[] = $matches;

            /* Terminando con varios en pie, las ultimas rondas no se juegan */
            if ($matches <= $survivors) {
                break;
            }
        }

        return [
            'kind' => 'BRACKET',
            'label' => 'Cuadro de ' . $size,
            'columns' => $columns,
            'slots' => $size,
        ];
    }

    /*
     * El cuadro, contado. Ver PhaseSuperEditorContract::summary().
     */
    public function summary(PhaseTemplate $phaseTemplate, array $payload): array
    {
        $s = $payload['settings'];
        $catalog = $payload['catalog'];
        $structure = $payload['structure'];

        $survivors = (int) ($structure['survivors'] ?? 1);
        $branches = count($payload['branches'] ?? []);

        $final = [
            [
                'label' => 'Termina',
                'value' => $this->labelOf($catalog['completion_modes'], $s['completion_mode']),
                'hint' => $this->hintOf($catalog['completion_modes'], $s['completion_mode']),
            ],
        ];

        if ($s['completion_mode'] === 'SURVIVORS') {
            $final[] = [
                'label' => 'Sobreviven',
                'value' => $this->plural($survivors, 'competidor', 'competidores'),
                'hint' => $branches > 1
                    ? 'Uno por cada una de las ' . $branches . ' ramas del cuadro.'
                    : null,
            ];
        }

        $cuadro = [
            [
                'label' => 'Tamaño',
                'value' => ($structure['bracket_size'] ?? 0) . ' huecos',
                'hint' => ($structure['byes'] ?? 0) > 0
                    ? $structure['byes'] . ' pasan la primera ronda sin jugar.'
                    : 'Sin descansos: el número encaja justo.',
            ],
            [
                'label' => 'Cruce',
                'value' => $this->labelOf($catalog['pairing_modes'], $s['pairing_mode']),
                'hint' => $this->hintOf($catalog['pairing_modes'], $s['pairing_mode']),
            ],
        ];

        if (($structure['byes'] ?? 0) > 0) {
            $cuadro[] = [
                'label' => 'Descansos',
                'value' => $this->labelOf($catalog['bye_assignments'], $s['bye_assignment']),
                'hint' => $this->hintOf($catalog['bye_assignments'], $s['bye_assignment']),
            ];
        }

        $parrilla = [
            [
                'label' => 'Siembra',
                'value' => $this->labelOf($catalog['seeding_modes'], $s['seeding_mode']),
                'hint' => $this->hintOf($catalog['seeding_modes'], $s['seeding_mode']),
            ],
        ];

        if ($s['seeding_mode'] === 'RANKING') {
            $parrilla[] = [
                'label' => 'Ranking',
                'value' => $this->labelOf($catalog['ranking_sources'], $s['ranking_source']),
                'hint' => $this->hintOf($catalog['ranking_sources'], $s['ranking_source']),
            ];
        }

        /*
         * Los puestos: los que el cuadro decide solo, los que se ordenan
         * jugando y los que quedan empatados. Es lo que de verdad distingue
         * una eliminacion directa de otra.
         */
        $puestos = array_map(
            fn (array $group) => [
                'label' => $group['from'] === $group['to']
                    ? $group['from'] . 'º'
                    : $group['from'] . 'º–' . $group['to'] . 'º',
                'value' => match (true) {
                    (bool) $group['auto'] => $group['label'],
                    (bool) ($group['enabled'] ?? false) => 'Se ordenan jugando',
                    default => 'Empatados',
                },
                'hint' => match (true) {
                    (bool) $group['auto'] => $group['hint'],
                    (bool) ($group['enabled'] ?? false) =>
                        $group['label'] . ': ' . $this->plural($group['cost'], 'duelo extra', 'duelos extra') . '.',
                    default => $group['hint'] . ' Nada los separa.',
                },
            ],
            $payload['groups'] ?? []
        );

        return [

            'figures' => [
                [
                    'label' => 'Compiten',
                    'value' => (string) ($structure['participants'] ?? 0),
                ],
                [
                    'label' => 'Cuadro',
                    'value' => (string) ($structure['bracket_size'] ?? 0),
                    'accent' => 'text-sky-300',
                ],
                [
                    'label' => 'Rondas',
                    'value' => (string) ($structure['round_count'] ?? 0),
                    'accent' => 'text-amber-300',
                ],
                [
                    'label' => 'Duelos',
                    'value' => (string) ($structure['total_matches'] ?? 0),
                    'accent' => 'text-emerald-300',
                ],
            ],

            'groups' => [
            [
                'title' => 'Cómo termina',
                'icon' => '🏁',
                'accent' => 'amber',
                'rows' => $final,
            ],
            [
                'title' => 'El cuadro',
                'icon' => '⚔',
                'accent' => 'sky',
                'rows' => $cuadro,
            ],
            [
                'title' => 'Parrilla de salida',
                'icon' => '⇥',
                'accent' => 'emerald',
                'rows' => $parrilla,
            ],
            [
                'title' => 'Los puestos',
                'icon' => '⚖',
                'accent' => 'violet',
                'rows' => $puestos,
            ],
            ],
        ];
    }

}
