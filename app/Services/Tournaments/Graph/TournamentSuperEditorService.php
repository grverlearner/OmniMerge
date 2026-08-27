<?php

namespace App\Services\Tournaments\Graph;

use App\Models\TournamentTemplate;
use App\Models\User;
use App\Services\Tournaments\Graph\Flow\TournamentGraphFlowAnalysisService;
use App\Services\Tournaments\Graph\Flow\TournamentGraphFlowValidationService;
use App\Services\Tournaments\Graph\Flow\TournamentGraphPayloadService;
use App\Services\Tournaments\PhaseEditor\PhaseSuperEditorRegistry;
use App\Services\Tournaments\Preview\PreviewCastService;

/*
|--------------------------------------------------------------------------
| TournamentSuperEditorService
|--------------------------------------------------------------------------
|
| Todo lo que la Super Edicion de un TORNEO necesita saber.
|
| Un torneo no es una fase: es el grafo que las une. Inicios por donde entra
| la gente, nodos que son fases, conexiones que llevan a los que salen de una
| a la entrada de otra, y terminales donde acaba el recorrido.
|
| Eso YA existe entero -TournamentGraphPayloadService lo arma con sus nodos,
| sus niveles, sus bifurcaciones, su validacion y hasta la URL de cada CRUD-
| asi que aqui no se ha escrito ni una regla de grafo nueva. Este servicio
| solo anade las cuatro cosas que el constructor de grafos no necesitaba y
| una pantalla de presentacion si:
|
|   map         el grafo repartido en columnas, de izquierda a derecha
|   links       las conexiones normalizadas a "de esta llave a esta otra"
|   neighbours  quien esta antes y quien despues de cada pieza
|   outlines    la forma de cada fase, para reconocerla dentro del mapa
|
| La costura con las fases es `outline()`: un dibujo en numeros que cuesta
| una consulta, en vez de montar el payload completo de cada fase -con su
| reparto prestado y sus emparejamientos- solo para ensenar que forma tiene.
|
*/
class TournamentSuperEditorService
{
    /*
     * Un color por nivel del torneo, para que se vea de un vistazo por
     * donde va el recorrido. Literales: Tailwind lee el codigo fuente.
     */
    private const LEVEL_PALETTE = [
        ['key' => 'sky',     'dot' => 'bg-sky-400',     'text' => 'text-sky-300',     'soft' => 'bg-sky-500/10',     'wash' => 'bg-sky-500/5',     'border' => 'border-sky-400/40',     'ring' => 'ring-sky-400/60',     'solid' => 'bg-sky-500',     'stroke' => 'stroke-sky-400'],
        ['key' => 'violet',  'dot' => 'bg-violet-400',  'text' => 'text-violet-300',  'soft' => 'bg-violet-500/10',  'wash' => 'bg-violet-500/5',  'border' => 'border-violet-400/40',  'ring' => 'ring-violet-400/60',  'solid' => 'bg-violet-500',  'stroke' => 'stroke-violet-400'],
        ['key' => 'amber',   'dot' => 'bg-amber-400',   'text' => 'text-amber-300',   'soft' => 'bg-amber-500/10',   'wash' => 'bg-amber-500/5',   'border' => 'border-amber-400/40',   'ring' => 'ring-amber-400/60',   'solid' => 'bg-amber-500',   'stroke' => 'stroke-amber-400'],
        ['key' => 'emerald', 'dot' => 'bg-emerald-400', 'text' => 'text-emerald-300', 'soft' => 'bg-emerald-500/10', 'wash' => 'bg-emerald-500/5', 'border' => 'border-emerald-400/40', 'ring' => 'ring-emerald-400/60', 'solid' => 'bg-emerald-500', 'stroke' => 'stroke-emerald-400'],
        ['key' => 'pink',    'dot' => 'bg-pink-400',    'text' => 'text-pink-300',    'soft' => 'bg-pink-500/10',    'wash' => 'bg-pink-500/5',    'border' => 'border-pink-400/40',    'ring' => 'ring-pink-400/60',    'solid' => 'bg-pink-500',    'stroke' => 'stroke-pink-400'],
        ['key' => 'cyan',    'dot' => 'bg-cyan-400',    'text' => 'text-cyan-300',    'soft' => 'bg-cyan-500/10',    'wash' => 'bg-cyan-500/5',    'border' => 'border-cyan-400/40',    'ring' => 'ring-cyan-400/60',    'solid' => 'bg-cyan-500',    'stroke' => 'stroke-cyan-400'],
    ];

    /* Los extremos del recorrido tienen color propio: no son una fase mas */
    private const START_COLOR = ['dot' => 'bg-emerald-400', 'text' => 'text-emerald-300', 'soft' => 'bg-emerald-500/10', 'wash' => 'bg-emerald-500/5', 'border' => 'border-emerald-400/40', 'ring' => 'ring-emerald-400/60', 'solid' => 'bg-emerald-500', 'stroke' => 'stroke-emerald-400'];

    private const TERMINAL_COLOR = ['dot' => 'bg-rose-400', 'text' => 'text-rose-300', 'soft' => 'bg-rose-500/10', 'wash' => 'bg-rose-500/5', 'border' => 'border-rose-400/40', 'ring' => 'ring-rose-400/60', 'solid' => 'bg-rose-500', 'stroke' => 'stroke-rose-400'];

    public function __construct(
        private readonly TournamentGraphPayloadService $payloads,
        private readonly TournamentGraphFlowAnalysisService $flow,
        private readonly TournamentGraphFlowValidationService $flowValidation,
        private readonly TournamentGraphValidationService $validation,
        private readonly PhaseSuperEditorRegistry $phases,
        private readonly PreviewCastService $cast
    ) {}

    public function payload(TournamentTemplate $tournament, ?User $user): array
    {
        $tournament->load([
            'graphNodes.phaseTemplate.exits' => fn ($q) => $q->where('status', 'ACTIVE'),
            'graphNodes.entryPorts.incomingConnections',
            'graphStarts.outgoingConnections',
            'graphTerminals.incomingConnections',
            'graphConnections.sourceStart',
            'graphConnections.sourceNode',
            'graphConnections.sourcePhaseExit',
            'graphConnections.targetEntryPort.node',
            'graphConnections.targetTerminal',
        ]);

        $analysis = $this->flow->analyze($tournament);

        $validation = $this->merge(
            $this->validation->validate($tournament),
            $this->flowValidation->validate($tournament, $analysis)
        );

        $graph = $this->payloads->build($tournament, $analysis, $validation);

        $links = $this->links($graph['connections']);

        $graph['nodes'] = $this->enrichEntries($tournament, $graph['nodes']);

        return [
            ...$graph,

            'tournament' => $this->identity($tournament),

            'map' => $this->map($graph, $analysis),

            'links' => $links,

            'neighbours' => $this->neighbours($graph, $links),

            'outlines' => $this->outlines($tournament),

            'flow' => $this->flow($tournament, $graph, $links, $validation),

            'cast' => $this->buildCast($user, $tournament),

            'palette' => [
                'levels' => self::LEVEL_PALETTE,
                'start' => self::START_COLOR,
                'terminal' => self::TERMINAL_COLOR,
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Identidad
    |--------------------------------------------------------------------------
    */

    private function identity(TournamentTemplate $tournament): array
    {
        return [
            'id' => $tournament->id,
            'code' => $tournament->code,
            'name' => $tournament->name,
            'description' => $tournament->description,
            'image_url' => $tournament->image_url,
            'min_participants' => $tournament->min_participants,
            'max_participants' => $tournament->max_participants,
            'allow_byes' => (bool) $tournament->allow_byes,
            'status' => $tournament->status,
            'visibility' => $tournament->visibility,
        ];
    }


    /*
     * Los limites de cada puerta, tal cual estan en la base de datos.
     *
     * El payload del grafo resume el contrato de una puerta en una frase
     * -"16 exactos"- que sirve para leerlo pero no para editarlo: un
     * formulario que lo cargara de ahi no sabria si ese 16 es un minimo, un
     * maximo o un exacto, y al guardar borraria los otros dos.
     *
     * Se anaden aqui y no en el payload compartido porque solo hacen falta
     * donde se editan.
     */
    private function enrichEntries(TournamentTemplate $tournament, array $nodes): array
    {
        $limits = [];

        foreach ($tournament->graphNodes as $node) {
            foreach ($node->entryPorts as $port) {
                $limits[$port->id] = [
                    'min_participants' => $port->min_participants,
                    'max_participants' => $port->max_participants,
                    'exact_participants' => $port->exact_participants,
                ];
            }
        }

        return array_map(
            fn (array $node) => [
                ...$node,
                'entries' => array_map(
                    fn (array $entry) => [...$entry, ...($limits[$entry['id']] ?? [])],
                    $node['entries']
                ),
            ],
            $nodes
        );
    }


    /*
    |--------------------------------------------------------------------------
    | El mapa
    |--------------------------------------------------------------------------
    |
    | El recorrido, de izquierda a derecha: por donde entra la gente, las
    | fases repartidas por nivel, y donde acaba.
    |
    | Los niveles los calcula el analisis de flujo, no esta clase: una fase
    | esta en el nivel 2 porque alguien la alimenta desde el 1, y eso es una
    | propiedad del grafo.
    |
    */

    private function map(array $graph, array $analysis): array
    {
        $columns = [];

        if ($graph['starts'] !== []) {
            $columns[] = [
                'kind' => 'STARTS',
                'label' => 'Entran',
                'hint' => 'Por donde llegan los competidores',
                'color' => self::START_COLOR,
                'keys' => array_map(fn (array $s) => 'START:' . $s['id'], $graph['starts']),
            ];
        }

        foreach ($analysis['levels'] as $index => $level) {
            $columns[] = [
                'kind' => 'LEVEL',
                'level' => $level['level'],
                'label' => $level['label'],
                'hint' => count($level['node_ids']) === 1
                    ? 'Una fase'
                    : count($level['node_ids']) . ' fases en paralelo',
                'color' => self::LEVEL_PALETTE[$index % count(self::LEVEL_PALETTE)],
                'keys' => array_map(fn (int $id) => 'NODE:' . $id, $level['node_ids']),
            ];
        }

        /*
         * Las fases que no alcanza nadie no tienen nivel, y desaparecerian
         * del mapa justo cuando mas falta hace verlas.
         */
        $unreachable = $analysis['unreachable_node_ids'] ?? [];

        if ($unreachable !== []) {
            $columns[] = [
                'kind' => 'ORPHANS',
                'label' => 'Sin conectar',
                'hint' => 'No llega nadie a estas fases',
                'color' => self::LEVEL_PALETTE[count($analysis['levels']) % count(self::LEVEL_PALETTE)],
                'keys' => array_map(fn (int $id) => 'NODE:' . $id, $unreachable),
            ];
        }

        if ($graph['terminals'] !== []) {
            $columns[] = [
                'kind' => 'TERMINALS',
                'label' => 'Salen',
                'hint' => 'Donde acaba el recorrido',
                'color' => self::TERMINAL_COLOR,
                'keys' => array_map(fn (array $t) => 'TERMINAL:' . $t['id'], $graph['terminals']),
            ];
        }

        return [
            'columns' => $columns,
            'branching' => array_map(
                fn (array $n) => 'NODE:' . $n['id'],
                $analysis['branching_nodes'] ?? []
            ),
            'converging' => array_map(
                fn (array $n) => 'NODE:' . $n['id'],
                $analysis['converging_nodes'] ?? []
            ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Conexiones
    |--------------------------------------------------------------------------
    |
    | Una conexion del modelo apunta a un inicio O a un nodo, y llega a una
    | puerta de entrada O a un terminal, en cuatro columnas distintas. Para
    | dibujar el recorrido eso es incomodo: lo unico que importa es DE QUE
    | pieza sale y A QUE pieza llega.
    |
    | Aqui se aplana a dos llaves -"START:3" -> "NODE:7"- y se conserva por
    | donde pasa, que es lo que da sentido a la flecha: no es "de la fase A a
    | la fase B", es "de la salida Ganador de A a la entrada general de B".
    |
    */

    private function links(array $connections): array
    {
        return array_values(array_map(function (array $c) {

            $from = $c['source_start_id'] !== null
                ? 'START:' . $c['source_start_id']
                : 'NODE:' . $c['source_node_id'];

            $to = $c['target_terminal_id'] !== null
                ? 'TERMINAL:' . $c['target_terminal_id']
                : 'ENTRY:' . $c['target_entry_port_id'];

            return [
                'id' => $c['id'],
                'code' => $c['code'],
                'from' => $from,
                'to' => $to,
                'from_label' => $c['source_label'],
                'to_label' => $c['target_label'],
                'exit_id' => $c['source_phase_exit_id'],
                'entry_id' => $c['target_entry_port_id'],
                'terminal_id' => $c['target_terminal_id'],
                'allocation' => $c['allocation_label'],
                'allocation_mode' => $c['allocation_mode'],
                'allocation_value' => $c['allocation_value'],
                'priority' => $c['priority'],
                'status' => $c['status'],
                'update_url' => $c['update_url'],
                'delete_url' => $c['delete_url'],
            ];
        }, $connections));
    }

    /*
     * Quien esta antes y quien despues de cada pieza.
     *
     * Es lo que sostiene la vista de recorrido: se elige una fase y la
     * pantalla sabe sola que ensenar a izquierda y a derecha, incluso cuando
     * son varias por cada lado.
     *
     * Las puertas de entrada se traducen a su nodo: al recorrido le importa
     * que se viene de la fase A, no por cual de sus tres puertas.
     */
    private function neighbours(array $graph, array $links): array
    {
        $nodeOfEntry = [];

        foreach ($graph['nodes'] as $node) {
            foreach ($node['entries'] as $entry) {
                $nodeOfEntry['ENTRY:' . $entry['id']] = 'NODE:' . $node['id'];
            }
        }

        $out = [];

        $touch = function (string $key) use (&$out) {
            $out[$key] ??= ['before' => [], 'after' => []];
        };

        foreach ($graph['starts'] as $s) {
            $touch('START:' . $s['id']);
        }
        foreach ($graph['nodes'] as $n) {
            $touch('NODE:' . $n['id']);
        }
        foreach ($graph['terminals'] as $t) {
            $touch('TERMINAL:' . $t['id']);
        }

        foreach ($links as $link) {

            $from = $link['from'];
            $to = $nodeOfEntry[$link['to']] ?? $link['to'];

            $touch($from);
            $touch($to);

            if (! in_array($to, $out[$from]['after'], true)) {
                $out[$from]['after'][] = $to;
            }

            if (! in_array($from, $out[$to]['before'], true)) {
                $out[$to]['before'][] = $from;
            }
        }

        return $out;
    }


    /*
    |--------------------------------------------------------------------------
    | Cuántos entran y cuántos salen
    |--------------------------------------------------------------------------
    |
    | El pronostico de flujo ya lo calculaba el validador; lo que faltaba era
    | ensenarlo donde se trabaja.
    |
    | Sin numeros, conectar es a ciegas: se ve "Todo" en una ruta y no se sabe
    | si esa fase ya esta llena, si le faltan cuatro o si se ha pasado. Aqui
    | cada pieza dice tres cosas:
    |
    |   cabe        lo que pide su contrato -exacto, o un rango-
    |   llega       lo que de verdad le mandan las rutas conectadas
    |   faltan      la resta, que es la unica pregunta al conectar
    |
    | Las cuentas NO se rehacen: salen del mismo pronostico que produce los
    | avisos del diagnostico, asi que la pantalla y el diagnostico no pueden
    | contradecirse.
    |
    */

    private function flow(
        TournamentTemplate $tournament,
        array $graph,
        array $links,
        array $validation
    ): array {
        $f = $validation['forecasts'] ?? [];

        return [
            'starts' => $this->startFlow($graph, $links, $f),
            'entries' => $this->entryFlow($graph, $links, $f, $tournament),
            'exits' => $this->exitFlow($graph, $links, $f),
            'nodes' => $this->nodeFlow($graph, $f, $tournament),
            'terminals' => $this->terminalFlow($graph, $links, $f),
            'connections' => $f['connections'] ?? [],
        ];
    }

    /*
     * Lo que sale de una entrada del torneo frente a lo que declara tener.
     */
    private function startFlow(array $graph, array $links, array $forecasts): array
    {
        $out = [];

        foreach ($graph['starts'] as $start) {

            $key = 'START:' . $start['id'];

            $routed = $this->sumOf(
                $links,
                $forecasts,
                fn (array $l) => $l['from'] === $key
            );

            $out[$start['id']] = [
                'holds' => $start['expected_participants'],
                'routed' => $routed,
                'left' => $this->left($start['expected_participants'], $routed),
            ];
        }

        return $out;
    }

    /*
     * Lo que le llega a una puerta frente a lo que cabe en ella.
     *
     * Es el numero que hace falta para conectar: cuantos quedan por meter.
     */
    private function entryFlow(
        array $graph,
        array $links,
        array $forecasts,
        TournamentTemplate $tournament
    ): array {
        $out = [];

        $contracts = $this->phaseContracts($tournament);

        foreach ($graph['nodes'] as $node) {

            /*
             * Una puerta sin limite propio hereda el de su fase, PERO solo
             * si es la unica: con dos puertas el cupo de la fase se reparte
             * entre las dos y atribuirselo entero a cada una diria que caben
             * el doble.
             */
            $inherited = count($node['entries']) === 1
                ? ($contracts[$node['phase_template_id']] ?? null)
                : null;

            foreach ($node['entries'] as $entry) {

                $arriving = $forecasts['entries'][$entry['id']]
                    ?? $this->sumOf(
                        $links,
                        $forecasts,
                        fn (array $l) => $l['entry_id'] === $entry['id']
                    );

                /* Lo que cabe: un exacto manda sobre el maximo */
                $fits = $entry['exact_participants']
                    ?? $entry['max_participants']
                    ?? $inherited;

                $out[$entry['id']] = [
                    'fits' => $fits,
                    'min' => $entry['min_participants'],
                    'exact' => $entry['exact_participants'],
                    'arriving' => $arriving,
                    'from_phase' => $entry['exact_participants'] === null
                        && $entry['max_participants'] === null
                        && $inherited !== null,

                    'left' => $this->left($fits, $arriving),
                    'routes' => count(array_filter(
                        $links,
                        fn (array $l) => $l['entry_id'] === $entry['id']
                    )),
                ];
            }
        }

        return $out;
    }

    /*
     * Lo que produce una salida frente a lo que ya tiene encaminado.
     *
     * La clave lleva la fase ademas de la salida: dos fases hermanas que usan
     * la misma plantilla comparten los ids de sus salidas, y sin la fase las
     * cuentas de una se verian en la otra.
     */
    private function exitFlow(array $graph, array $links, array $forecasts): array
    {
        $out = [];

        foreach ($graph['nodes'] as $node) {
            foreach ($node['exits'] as $exit) {

                $produces = $forecasts['exits'][$node['id'] . ':' . $exit['id']] ?? null;

                $routed = $this->sumOf(
                    $links,
                    $forecasts,
                    fn (array $l) => $l['from'] === 'NODE:' . $node['id']
                        && $l['exit_id'] === $exit['id']
                );

                $out['NODE:' . $node['id'] . ':' . $exit['id']] = [
                    'produces' => $produces,
                    'routed' => $routed,
                    'left' => $this->left($produces['exact'] ?? null, $routed),
                ];
            }
        }

        return $out;
    }

    private function nodeFlow(
        array $graph,
        array $forecasts,
        TournamentTemplate $tournament
    ): array {
        $out = [];

        $contracts = $this->phaseContracts($tournament);

        foreach ($graph['nodes'] as $node) {

            $receives = $forecasts['nodes'][$node['id']]
                ?? ['min' => 0, 'max' => null, 'exact' => null, 'known' => false];

            $fits = $contracts[$node['phase_template_id']] ?? null;

            $out[$node['id']] = [
                'receives' => $receives,
                'fits' => $fits,
                'contract' => $node['participant_contract'],
                'left' => $this->left($fits, $receives),
            ];
        }

        return $out;
    }

    /*
     * Cuanta gente admite cada plantilla de fase, en un numero.
     *
     * El nodo del grafo trae su contrato como frase -"16 participantes
     * exactos"-, que sirve para leerlo y no para restar. Aqui se saca el
     * numero: el exacto si lo hay, y si no el maximo.
     *
     * @return array<int,int>
     */
    private function phaseContracts(TournamentTemplate $tournament): array
    {
        $out = [];

        foreach ($tournament->graphNodes as $node) {

            $phase = $node->phaseTemplate;

            if ($phase === null) {
                continue;
            }

            $fits = $phase->exact_participants ?? $phase->max_participants;

            if ($fits !== null) {
                $out[$phase->id] = (int) $fits;
            }
        }

        return $out;
    }

    private function terminalFlow(array $graph, array $links, array $forecasts): array
    {
        $out = [];

        foreach ($graph['terminals'] as $terminal) {

            $arriving = $forecasts['terminals'][$terminal['id']]
                ?? $this->sumOf(
                    $links,
                    $forecasts,
                    fn (array $l) => $l['terminal_id'] === $terminal['id']
                );

            $out[$terminal['id']] = [
                'fits' => $terminal['expected_participants'],
                'arriving' => $arriving,
                'left' => $this->left($terminal['expected_participants'], $arriving),
            ];
        }

        return $out;
    }

    /*
     * Suma el pronostico de las rutas que cumplen una condicion.
     *
     * @return array{min: int, max: ?int, exact: ?int, known: bool}
     */
    private function sumOf(array $links, array $forecasts, callable $filter): array
    {
        $min = 0;
        $max = 0;
        $allKnown = true;

        foreach (array_filter($links, $filter) as $link) {

            $f = $forecasts['connections'][$link['id']] ?? null;

            if ($f === null || ($f['max'] ?? null) === null) {
                $allKnown = false;

                $min += (int) ($f['min'] ?? 0);

                continue;
            }

            $min += (int) $f['min'];
            $max += (int) $f['max'];
        }

        return [
            'min' => $min,
            'max' => $allKnown ? $max : null,
            'exact' => $allKnown && $min === $max ? $min : null,
            'known' => $allKnown,
        ];
    }

    /*
     * Cuantos quedan por meter.
     *
     * null cuando no hay cupo contra el que restar: una puerta sin maximo
     * admite lo que le echen, y decir "faltan 0" ahi seria mentir.
     */
    private function left(?int $capacity, array $arriving): ?array
    {
        if ($capacity === null) {
            return null;
        }

        /* Sin techo en lo que llega, no se puede decir cuanto falta */
        if (! $arriving['known']) {
            return [
                'min' => null,
                'max' => max(0, $capacity - $arriving['min']),
                'exact' => null,
                'full' => false,
                'over' => false,
            ];
        }

        $min = $capacity - $arriving['max'];
        $max = $capacity - $arriving['min'];

        return [
            'min' => $min,
            'max' => $max,
            'exact' => $min === $max ? $min : null,
            'full' => $min === 0 && $max === 0,
            'over' => $max < 0,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Forma de cada fase
    |--------------------------------------------------------------------------
    */

    private function outlines(TournamentTemplate $tournament): array
    {
        $out = [];

        foreach ($tournament->graphNodes as $node) {

            $phase = $node->phaseTemplate;

            /*
             * Los motores sin Super Edicion -Swiss, League- no saben
             * dibujarse todavia. Se les deja sin esquema en vez de inventar
             * uno: una caja vacia dice la verdad, una forma prestada no.
             */
            if ($phase === null || ! $this->phases->supports($phase)) {
                continue;
            }

            $out['NODE:' . $node->id] = $this->phases->for($phase)->outline($phase);
        }

        return $out;
    }



    /*
    |--------------------------------------------------------------------------
    | Reparto prestado
    |--------------------------------------------------------------------------
    |
    | Caras de tus universos y tu biblioteca, para poder ver el recorrido con
    | gente dentro en vez de con cajas vacias. No son inscritos y no se
    | guardan: es el mismo prestamo que usan las fases.
    |
    */

    private function buildCast(?User $user, TournamentTemplate $tournament): array
    {
        $count = max(8, min(24, (int) ($tournament->max_participants ?: $tournament->min_participants ?: 16)));

        return $this->cast
            ->borrow($user, $count)
            ->values()
            ->map(fn (array $member, int $index) => [
                'index' => $index,
                'name' => $member['name'],
                'short' => $this->shortName($member['name']),
                'image_url' => $member['image_url'] ?? null,
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


    /*
    |--------------------------------------------------------------------------
    | Validación
    |--------------------------------------------------------------------------
    */

    private function merge(array $structural, array $flow): array
    {
        $unique = fn (array $a, array $b) => collect($a)
            ->merge($b)
            ->unique(fn (array $p) => $p['code'] . ':' . $p['message'])
            ->values()
            ->all();

        $errors = $unique($structural['errors'], $flow['errors']);
        $warnings = $unique($structural['warnings'], $flow['warnings']);

        return [
            'valid' => $structural['valid'] && $flow['valid'],
            'errors' => $errors,
            'warnings' => $warnings,
            'information' => $flow['information'],
            'forecasts' => $flow['forecasts'],
            'stats' => [
                ...$structural['stats'],
                'errors' => count($errors),
                'warnings' => count($warnings),
                'information' => count($flow['information']),
            ],
        ];
    }
}
