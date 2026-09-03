<?php

namespace App\Http\Requests\Universes\Concerns;

use App\Models\UniverseTournament;
use Illuminate\Validation\Rule;

/*
|--------------------------------------------------------------------------
| ValidatesCompetitionConfiguration
|--------------------------------------------------------------------------
|
| Las reglas que comparten crear y editar una edicion.
|
| Estan en un solo sitio porque las dos pantallas envian exactamente el
| mismo formulario: si las reglas viviesen por duplicado, un dia editar
| aceptaria algo que crear rechaza y nadie sabria cual de las dos tiene
| razon.
|
| Lo que aqui NO se comprueba: que el torneo permita lo que se pide. Eso
| depende del torneo concreto y lo resuelve el servicio, que es quien lo
| tiene delante.
|
*/
trait ValidatesCompetitionConfiguration
{
    protected function configurationRules(int $universeId): array
    {
        return [

            'description' => ['nullable', 'string', 'max:2000'],

            'image' => ['nullable', 'image', 'max:4096'],

            /*
             * El juego de esta edicion. Que pueda elegirlo o no lo decide
             * el torneo; aqui solo se comprueba que exista.
             */
            'game_key' => ['nullable', 'string', 'max:60'],

            /*
             * Donde se decide cada cosa dentro de esta edicion. PHASE solo
             * se acepta si el torneo lo permitio, y eso lo baja el servicio
             * a COMPETITION en silencio en vez de rechazar el envio: es un
             * permiso que se pudo cerrar despues de abrir la pantalla.
             */
            'game_scope' => ['nullable', Rule::in(['COMPETITION', 'PHASE'])],
            'battle_scope' => ['nullable', Rule::in(['COMPETITION', 'PHASE'])],

            'battle_participants' => ['nullable', 'integer', 'min:2', 'max:64'],

            'decision_mode' => [
                'nullable',
                Rule::in(['SERIES_THEN_POINTS', 'POINTS_ONLY']),
            ],

            'allow_draws' => ['nullable', 'boolean'],


            /*
            |------------------------------------------------------------------
            | La excepcion de cada fase
            |------------------------------------------------------------------
            |
            | phases[nodeId][campo]. Todo nullable: un nulo significa "lo
            | que diga la competicion", que es el caso normal.
            |
            */

            'phases' => ['nullable', 'array'],
            'phases.*' => ['array'],
            'phases.*.game_key' => ['nullable', 'string', 'max:60'],

            'phases.*.series_format' => [
                'nullable',
                Rule::in(['BEST_OF', 'FIXED_GAMES']),
            ],

            'phases.*.best_of' => ['nullable', 'integer', 'min:1', 'max:15'],
            'phases.*.fixed_games' => ['nullable', 'integer', 'min:1', 'max:15'],
            'phases.*.battle_participants' => ['nullable', 'integer', 'min:2', 'max:64'],

            'phases.*.decision_mode' => [
                'nullable',
                Rule::in(['SERIES_THEN_POINTS', 'POINTS_ONLY']),
            ],

            'phases.*.allow_draws' => ['nullable', 'boolean'],

            /*
             * Solo lo usa una fase de grupos: como se construye su lista
             * unica. Nulo = lo que diga la plantilla.
             */
            'phases.*.overall_ranking_mode' => [
                'nullable',
                Rule::in(array_keys(
                    \App\Services\Tournaments\GroupStage\GroupStageOverallRanking::MODES
                )),
            ],


            /*
            |------------------------------------------------------------------
            | Premios propios de esta edicion
            |------------------------------------------------------------------
            */

            'rewards' => ['nullable', 'array', 'max:60'],
            'rewards.*' => ['array'],

            /* En que fase se gana. Vacio = al terminar la competicion */
            'rewards.*.node_id' => ['nullable', 'integer'],

            'rewards.*.trigger' => [
                'nullable',
                Rule::in([
                    'POSITION',
                    'PARTICIPATION',
                    'UNBEATEN',
                    'WIN_COUNT',
                    'ENCOUNTER_WIN_COUNT',
                ]),
            ],

            'rewards.*.threshold' => ['nullable', 'integer', 'min:1', 'max:999'],
            'rewards.*.stat_key' => ['nullable', 'string', 'max:60'],

            'rewards.*.operation' => [
                'nullable',
                Rule::in(['ADD', 'SUBTRACT', 'MULTIPLY', 'SET']),
            ],

            'rewards.*.amount' => ['nullable', 'numeric', 'between:-9999,9999'],
            'rewards.*.label' => ['nullable', 'string', 'max:150'],
            'rewards.*.carry_forward' => ['nullable', 'boolean'],

            'rewards.*.universe_trophy_id' => [
                'nullable',
                'integer',
                Rule::exists('universe_trophies', 'id')
                    ->where('universe_id', $universeId),
            ],


            /*
            |------------------------------------------------------------------
            | Por que puerta entra cada uno
            |------------------------------------------------------------------
            |
            | start_rules[i] = { start_id, mode, rules[] }. Es la forma
            | escrita de las asignaciones: se guarda para que la edicion
            | siguiente pueda copiarse sin volver a marcar a nadie.
            |
            */

            'start_rules' => ['nullable', 'array', 'max:40'],
            'start_rules.*.start_id' => ['required_with:start_rules', 'integer'],
            'start_rules.*.mode' => ['nullable', Rule::in(['ALL', 'ANY', 'NONE', 'ONE'])],
            'start_rules.*.rules' => ['nullable', 'array', 'max:20'],
            'start_rules.*.rules.*.attribute' => ['nullable', 'string', 'max:120'],
            'start_rules.*.rules.*.values' => ['nullable', 'array', 'max:60'],
            'start_rules.*.rules.*.values.*' => ['nullable', 'string', 'max:120'],

            /* Una puerta usa el mismo lenguaje que un torneo: grupos y mano */
            'start_rules.*.groups' => ['nullable', 'array', 'max:10'],
            'start_rules.*.groups.*.mode' => ['nullable', Rule::in(['ALL', 'ANY', 'NONE', 'ONE'])],
            'start_rules.*.groups.*.rules' => ['nullable', 'array', 'max:20'],
            'start_rules.*.groups.*.rules.*.attribute' => ['nullable', 'string', 'max:120'],
            'start_rules.*.groups.*.rules.*.values' => ['nullable', 'array', 'max:60'],
            'start_rules.*.groups.*.rules.*.values.*' => ['nullable', 'string', 'max:120'],

            'start_rules.*.include' => ['nullable', 'array', 'max:500'],
            'start_rules.*.include.*' => ['integer'],
            'start_rules.*.exclude' => ['nullable', 'array', 'max:500'],
            'start_rules.*.exclude.*' => ['integer'],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Lo que se guarda de los premios
    |--------------------------------------------------------------------------
    |
    | Una fila que no da NADA -ni trofeo ni estadistica- se descarta sola:
    | es una fila que se abrio y no se llego a rellenar, no un premio que
    | no otorga nada.
    |
    | Devolver null significa "el formulario no hablo de premios", que no
    | es lo mismo que "no hay premios": esto ultimo se dice con [].
    |
    */
    public function rewardsPayload(): ?array
    {
        if (! $this->has('rewards')) {
            return null;
        }

        return collect((array) $this->input('rewards', []))
            ->map(function ($row) {

                if (! is_array($row)) {
                    return null;
                }

                $trophy = $row['universe_trophy_id'] ?? null;
                $stat = trim((string) ($row['stat_key'] ?? ''));

                if (! $trophy && $stat === '') {
                    return null;
                }

                $node = (int) ($row['node_id'] ?? 0);

                return [
                    'node_id' => $node > 0 ? $node : null,
                    'trigger' => $row['trigger'] ?? 'POSITION',
                    'threshold' => ($row['threshold'] ?? null) !== null && $row['threshold'] !== ''
                        ? (int) $row['threshold']
                        : null,
                    'stat_key' => $stat !== '' ? $stat : null,
                    'operation' => $row['operation'] ?? 'ADD',
                    'amount' => (float) ($row['amount'] ?? 0),
                    'universe_trophy_id' => $trophy ? (int) $trophy : null,
                    'label' => trim((string) ($row['label'] ?? '')) ?: null,
                    'carry_forward' => (bool) ($row['carry_forward'] ?? true),
                    'is_active' => true,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /*
     * La excepcion de cada fase, limpia.
     *
     * Las cadenas vacias se convierten en nulos: un desplegable devuelto a
     * "lo que diga la competicion" manda "" y guardarlo tal cual dejaria
     * una excepcion invisible que ya no se puede quitar.
     *
     * @return array<int,array<string,mixed>>
     */
    public function phasesPayload(): ?array
    {
        if (! $this->has('phases')) {
            return null;
        }

        $clean = [];

        foreach ((array) $this->input('phases', []) as $nodeId => $row) {

            if (! is_array($row) || (int) $nodeId <= 0) {
                continue;
            }

            $vacio = fn ($v) => $v === null || $v === '' || $v === [];

            $clean[(int) $nodeId] = [
                'game_key' => $vacio($row['game_key'] ?? null)
                    ? null
                    : strtoupper((string) $row['game_key']),

                'series_format' => $vacio($row['series_format'] ?? null)
                    ? null
                    : (string) $row['series_format'],

                'best_of' => $vacio($row['best_of'] ?? null)
                    ? null
                    : max(1, (int) $row['best_of']),

                'fixed_games' => $vacio($row['fixed_games'] ?? null)
                    ? null
                    : max(1, (int) $row['fixed_games']),

                'battle_participants' => $vacio($row['battle_participants'] ?? null)
                    ? null
                    : max(2, (int) $row['battle_participants']),

                'decision_mode' => $vacio($row['decision_mode'] ?? null)
                    ? null
                    : (string) $row['decision_mode'],

                /*
                 * Tres estados y no dos: si, no, y "lo que diga la
                 * competicion". Un checkbox no sabe decir el tercero, asi
                 * que la pantalla manda un select con "" para heredar.
                 */
                'allow_draws' => $vacio($row['allow_draws'] ?? null)
                    ? null
                    : (bool) $row['allow_draws'],
            ];
        }

        return $clean;
    }

    /*
     * Por que puerta entra cada uno, limpio.
     */
    public function startRulesPayload(): ?array
    {
        if (! $this->has('start_rules')) {
            return null;
        }

        return collect((array) $this->input('start_rules', []))
            ->map(function ($row) {

                if (! is_array($row) || (int) ($row['start_id'] ?? 0) <= 0) {
                    return null;
                }

                $limpiar = fn ($reglas) => collect($reglas ?? [])
                    ->map(fn ($r) => is_array($r) ? [
                        'attribute' => (string) ($r['attribute'] ?? ''),
                        'values' => array_values(array_filter(
                            (array) ($r['values'] ?? []),
                            fn ($v) => trim((string) $v) !== ''
                        )),
                    ] : null)
                    ->filter(fn ($r) => $r && $r['attribute'] !== '')
                    ->values()
                    ->all();

                $ids = fn ($lista) => collect((array) $lista)
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'start_id' => (int) $row['start_id'],

                    'mode' => in_array($row['mode'] ?? 'ALL', ['ALL', 'ANY', 'NONE', 'ONE'], true)
                        ? $row['mode']
                        : 'ALL',

                    'rules' => $limpiar($row['rules'] ?? []),

                    'groups' => collect($row['groups'] ?? [])
                        ->map(fn ($g) => is_array($g) ? [
                            'mode' => in_array($g['mode'] ?? 'ALL', ['ALL', 'ANY', 'NONE', 'ONE'], true)
                                ? $g['mode']
                                : 'ALL',
                            'rules' => $limpiar($g['rules'] ?? []),
                        ] : null)
                        ->filter(fn ($g) => $g && $g['rules'] !== [])
                        ->values()
                        ->all(),

                    'include' => $ids($row['include'] ?? []),
                    'exclude' => $ids($row['exclude'] ?? []),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Asignaciones limpias
    |--------------------------------------------------------------------------
    |
    | Descarta entradas vacias y competidores repetidos: un mismo competidor
    | no puede entrar dos veces a la misma competicion.
    |
    | Vive aqui y no en cada request porque alta y edicion reparten
    | exactamente igual, y dos limpiezas distintas de lo mismo acaban
    | aceptando cosas distintas.
    |
    */
    public function assignments(): array
    {
        $clean = [];

        $seen = [];

        foreach ((array) $this->input('assignments', []) as $startId => $ids) {

            $limpios = [];

            foreach ((array) $ids as $id) {

                $id = (int) $id;

                if ($id <= 0 || isset($seen[$id])) {
                    continue;
                }

                $seen[$id] = true;

                $limpios[] = $id;
            }

            if ($limpios === []) {
                continue;
            }

            $clean[(int) $startId] = $limpios;
        }

        return $clean;
    }

    /*
     * Las reglas de validacion del reparto. Iguales en alta y edicion.
     */
    protected function assignmentRules(int $universeId): array
    {
        return [
            'assignments' => ['nullable', 'array'],
            'assignments.*' => ['array'],
            'assignments.*.*' => [
                'integer',
                Rule::exists('universe_entities', 'id')
                    ->where('universe_id', $universeId),
            ],
        ];
    }

    /*
     * Lo que el torneo permite, aplicado.
     *
     * Una pantalla abierta hace media hora pudo enviarse despues de que el
     * torneo cerrase la puerta. Bajarlo en silencio y no rechazar el envio
     * es lo correcto: el usuario no hizo nada malo, y perder el formulario
     * entero por un permiso que cambio detras seria peor que guardar la
     * edicion con la configuracion que si esta permitida.
     */
    protected function clampToTournament(array $data, ?UniverseTournament $tournament): array
    {
        if (! $tournament) {
            return $data;
        }

        if (! $tournament->allow_phase_game) {
            $data['game_scope'] = 'COMPETITION';
        }

        if (! $tournament->allow_phase_battle) {
            $data['battle_scope'] = 'COMPETITION';
        }

        /*
         * Torneo de juego unico: su juego es su juego. Una edicion que lo
         * cambiase ya no seria la misma competicion.
         */
        if (($tournament->game_mode ?: 'SINGLE') !== 'VARIED' && $tournament->game_key) {
            $data['game_key'] = $tournament->game_key;
        }

        return $data;
    }
}
