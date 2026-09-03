<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentInstance;
use App\Models\TournamentInstanceEvent;
use App\Models\TournamentInstanceMatch;
use App\Models\TournamentInstanceParticipant;
use App\Models\TournamentInstancePhase;
use App\Models\TournamentInstancePhaseParticipant;

/*
|--------------------------------------------------------------------------
| TournamentInstanceProjector
|--------------------------------------------------------------------------
|
| Vuelca el estado del motor (JSON) a las tablas consultables.
|
| El JSON es la fuente de verdad del motor; estas tablas son la vista
| legible: permiten listar encuentros, ver la clasificación o revisar el
| historial sin recorrer el estado a mano.
|
| Es IDEMPOTENTE: proyectar dos veces el mismo estado deja el mismo
| resultado. Los eventos son la excepción, y por eso se añaden solo los
| que aún no existen (el ledger nunca se reescribe).
|
| Deliberadamente NO conoce ningún motor concreto: recorre el runtime de
| cada nodo buscando encuentros, de modo que un motor futuro que respete
| la misma forma queda proyectado sin tocar esta clase.
|
*/

class TournamentInstanceProjector
{
    public function project(
        TournamentInstance $instance,
        array $state
    ): void {

        $this->projectParticipants(
            $instance,
            $state
        );

        $this->projectPhases(
            $instance,
            $state
        );

        $this->projectMatches(
            $instance,
            $state
        );

        /*
         * Historial (Fase 8). Depende de que fases y encuentros ya
         * estén proyectados, por eso va después.
         */
        $this->projectPhaseStandings(
            $instance,
            $state
        );

        $this->projectOutcomes(
            $instance,
            $state
        );

        $this->appendEvents(
            $instance,
            $state
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Participantes
    |--------------------------------------------------------------------------
    |
    | El nombre y el seed NO se actualizan: se congelaron al crear la
    | competición. Aquí solo se refresca lo que cambia al jugar.
    |
    */

    private function projectParticipants(
        TournamentInstance $instance,
        array $state
    ): void {

        /*
         * Batallas jugadas, ganadas, empatadas y perdidas, contadas desde
         * los propios encuentros del Runtime.
         *
         * No se toman de $participant['statistics'] porque ese contador
         * solo lo rellenan los motores de liga: Round Robin y Group Stage
         * llevan tabla, pero Single Elimination no, y ahi todo quedaba a
         * cero aunque se hubieran jugado siete batallas.
         *
         * Contarlo desde los encuentros funciona para los tres motores y,
         * en un torneo multifase, suma lo jugado en todas las fases.
         */
        $record =
            $this->recordFromMatches($state);

        foreach (
            ($state['participants'] ?? [])
            as
            $key => $participant
        ) {

            $statistics =
                $participant['statistics']
                ?? [];

            $counted =
                $record[(string) $key]
                ?? ['matches' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0];

            $location =
                $participant['current_location']
                ?? [];

            TournamentInstanceParticipant::query()
                ->where(
                    'tournament_instance_id',
                    $instance->id
                )
                ->where(
                    'runtime_key',
                    (string) $key
                )
                ->update([

                    'status' =>
                    $participant['status']
                        ?? 'WAITING',

                    'matches' =>
                    $counted['matches'],

                    'wins' =>
                    $counted['wins'],

                    'draws' =>
                    $counted['draws'],

                    'losses' =>
                    $counted['losses'],

                    /*
                     * Los puntos SI son del motor: cada liga tiene su
                     * propio sistema y no se pueden deducir del marcador.
                     */
                    'points' =>
                    (int) ($statistics['points'] ?? 0),

                    'final_location_type' =>
                    $location['type'] ?? null,

                    'final_location_name' =>
                    $location['name'] ?? null,

                    'updated_at' =>
                    now(),
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Fases
    |--------------------------------------------------------------------------
    */

    private function projectPhases(
        TournamentInstance $instance,
        array $state
    ): void {

        foreach (
            ($state['nodes'] ?? [])
            as
            $node
        ) {

            TournamentInstancePhase::query()
                ->updateOrCreate(
                    [
                        'tournament_instance_id' =>
                        $instance->id,

                        'node_id' =>
                        (int) ($node['id'] ?? 0),
                    ],
                    [
                        'node_code' =>
                        $node['code'] ?? null,

                        'node_name' =>
                        $node['name'] ?? 'Fase',

                        'phase_type' =>
                        $node['phase_type'] ?? null,

                        'status' =>
                        $node['status'] ?? 'LOCKED',

                        'participant_count' =>
                        count(
                            $node['participant_ids'] ?? []
                        ),
                    ]
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Encuentros
    |--------------------------------------------------------------------------
    */

    private function projectMatches(
        TournamentInstance $instance,
        array $state
    ): void {

        /*
         * Las filas ya guardadas, de una vez.
         *
         * Antes storeMatch preguntaba por la suya a la base de datos —y
         * otra vez por su completed_at—, asi que una competicion de 238
         * encuentros hacia mas de 400 consultas de LECTURA en cada accion
         * del motor, solo para volver a escribir lo mismo. Con el mapa
         * delante, la comparacion se hace en memoria y solo viaja a la
         * base de datos lo que de verdad cambio.
         */
        $existing =
            TournamentInstanceMatch::query()
            ->where(
                'tournament_instance_id',
                $instance->id
            )
            ->get()
            /*
             * Por FASE y por identificador, no solo por identificador.
             *
             * Cada motor numera sus enfrentamientos con su propia cuenta, asi
             * que dos ligas jugandose a la vez llaman «RR-R1-M1» a la primera
             * batalla de las dos. Con el mapa indexado solo por ese nombre,
             * la segunda fase encontraba la fila de la primera y la
             * SOBRESCRIBIA: 45 filas en vez de 90, y una fase entera que
             * parecia no tener enfrentamientos.
             */
            ->keyBy(
                fn ($fila) =>
                (int) $fila->node_id . '|' . $fila->runtime_match_id
            );

        $names =
            $this->participantNames(
                $state
            );

        $entities =
            $this->participantEntities(
                $state
            );

        foreach (
            ($state['nodes'] ?? [])
            as
            $node
        ) {

            $runtime =
                $node['runtime'] ?? null;

            if (! is_array($runtime)) {
                continue;
            }

            $nodeId =
                (int) ($node['id'] ?? 0);

            foreach (
                $this->collectMatches($runtime)
                as
                $match
            ) {

                $this->storeMatch(
                    $instance,
                    $nodeId,
                    $match,
                    $names,
                    $entities,

                    /*
                     * La serie NO vive dentro del encuentro: MatchSeriesRuntime
                     * la guarda aparte, indexada por id de encuentro, para
                     * sobrevivir a los motores que reconstruyen sus matches.
                     * Buscarla dentro del match dejaba la columna a null y
                     * hacia invisible todo BO3/BO5/FIXED_GAMES.
                     */
                    $runtime['series'] ?? [],

                    $existing
                );
            }
        }
    }

    /**
     * Balance de cada participante a partir de los encuentros ya
     * resueltos en el Runtime.
     *
     * Los BYE no cuentan: avanzar sin rival no es haber jugado.
     *
     * @return array<string, array{matches:int, wins:int, draws:int, losses:int}>
     */
    private function recordFromMatches(array $state): array
    {
        $record = [];

        $touch = function (string $key) use (&$record) {

            $record[$key] ??= [
                'matches' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
            ];
        };

        foreach (($state['nodes'] ?? []) as $node) {

            $runtime = $node['runtime'] ?? null;

            if (! is_array($runtime)) {
                continue;
            }

            foreach ($this->collectMatches($runtime) as $match) {

                if (($match['status'] ?? null) !== 'COMPLETED') {
                    continue;
                }

                $keyA = $match['participant_a_id'] ?? null;
                $keyB = $match['participant_b_id'] ?? null;

                /* Sin dos contendientes no hubo batalla: es un BYE */
                if (! $keyA || ! $keyB) {
                    continue;
                }

                $touch((string) $keyA);
                $touch((string) $keyB);

                $record[(string) $keyA]['matches']++;
                $record[(string) $keyB]['matches']++;

                $winner = $match['winner_id'] ?? null;

                if ($winner === null) {
                    $record[(string) $keyA]['draws']++;
                    $record[(string) $keyB]['draws']++;

                    continue;
                }

                $winner = (string) $winner;

                /*
                 * En algunos encuentros de seleccion el ganador registrado
                 * no es ninguno de los dos huecos. Sin esta comprobacion,
                 * PHP creaba una entrada a medias —solo con 'wins'— y
                 * todo lo que venia despues leia claves inexistentes.
                 */
                if (
                    $winner !== (string) $keyA
                    && $winner !== (string) $keyB
                ) {
                    continue;
                }

                $loser = $winner === (string) $keyA
                    ? (string) $keyB
                    : (string) $keyA;

                $record[$winner]['wins']++;
                $record[$loser]['losses']++;
            }
        }

        return $record;
    }

    /*
     * Recorrido recursivo: cualquier array con id + los dos huecos de
     * participante se considera un encuentro, venga del motor que venga.
     */
    private function collectMatches(
        array $runtime,
        ?int $roundNumber = null,
        ?string $roundLabel = null,
        ?string $groupLabel = null,
        bool $placement = false
    ): array {

        $found = [];

        if ($this->looksLikeMatch($runtime)) {

            $runtime['__round_number'] = $roundNumber;
            $runtime['__round_label'] = $roundLabel;
            $runtime['__group_label'] = $groupLabel;
            $runtime['__placement'] = $placement;

            return [$runtime];
        }

        foreach ($runtime as $key => $value) {

            if (! is_array($value)) {
                continue;
            }

            $childRound = $roundNumber;
            $childLabel = $roundLabel;
            $childGroup = $groupLabel;
            $childPlacement = $placement;

            /*
             * Una ronda de desempate de puestos lo dice de si misma. Se
             * recuerda para que la fila sepa que no es una ronda del cuadro.
             */
            if (isset($value['placement']) && isset($value['matches'])) {
                $childPlacement = true;
            }

            /*
             * Al atravesar una ronda se recuerda su número y etiqueta
             * para poder mostrarlos junto a cada encuentro.
             */
            if (
                isset($value['number'])
                &&
                isset($value['matches'])
            ) {
                $childRound = (int) $value['number'];
                $childLabel = $value['label'] ?? null;
            }

            /*
             * Al atravesar un grupo (Group Stage) se recuerda su nombre.
             */
            if (
                isset($value['standings'])
                &&
                isset($value['participant_ids'])
            ) {
                $childGroup =
                    $value['name']
                    ?? $value['code']
                    ?? $childGroup;
            }

            /*
             * En fase de grupos los encuentros NO cuelgan del grupo: las
             * jornadas viven en la raiz del runtime y cada una dice a que
             * grupo pertenece. Sin leer eso aqui, las 48 batallas se
             * proyectaban sin grupo y la vista, que las agrupa por
             * etiqueta, no encontraba ninguna que ensenar.
             */
            if (
                isset($value['matches'])
                &&
                (
                    isset($value['group_name'])
                    ||
                    isset($value['group_id'])
                )
            ) {
                $childGroup =
                    $value['group_name']
                    ?? $childGroup;
            }

            $found = array_merge(
                $found,
                $this->collectMatches(
                    $value,
                    $childRound,
                    $childLabel,
                    $childGroup,
                    $childPlacement
                )
            );
        }

        return $found;
    }

    /*
     * La lista completa de un enfrentamiento, en el orden en que el motor
     * los coloco -que es el orden en que se ven en el cuadro-.
     *
     * @return array<int,array<string,mixed>>|null
     */
    private function matchParticipants(
        array $match,
        array $names,
        array $entities,
        ?string $winner
    ): ?array {

        /*
         * participant_ids es la lista de verdad. Si el motor no la trae se
         * reconstruye con A y B, que es lo que hacen los duelos.
         */
        $keys = $match['participant_ids']
            ?? array_values(array_filter([
                $match['participant_a_id'] ?? null,
                $match['participant_b_id'] ?? null,
            ]));

        $keys = array_values(array_filter(
            array_map(fn ($k) => $k === null ? null : (string) $k, (array) $keys)
        ));

        if ($keys === []) {
            return null;
        }

        /* Quienes pasaron y quienes cayeron, si el encuentro ya se resolvio */
        $pasan = array_map('strval', (array) ($match['qualifier_ids'] ?? []));
        $caen = array_map('strval', (array) ($match['eliminated_ids'] ?? []));

        /*
         * El marcador solo existe por pareja -score_a y score_b-, asi que
         * con mas de dos no se reparte: se deja en null en vez de
         * atribuirle a un tercero un numero que no es suyo.
         */
        $pareja = count($keys) === 2;

        return array_values(array_map(
            function (string $key, int $i) use ($names, $entities, $winner, $pasan, $caen, $pareja, $match) {

                return [
                    'key' => $key,
                    'position' => $i + 1,
                    'name' => $names[$key] ?? null,
                    'universe_entity_id' => $entities[$key] ?? null,

                    'score' => $pareja
                        ? ($i === 0 ? ($match['score_a'] ?? null) : ($match['score_b'] ?? null))
                        : null,

                    'is_winner' => $winner !== null && $key === $winner,
                    'qualified' => in_array($key, $pasan, true),
                    'eliminated' => in_array($key, $caen, true),
                ];
            },
            $keys,
            array_keys($keys)
        ));
    }

    private function looksLikeMatch(
        array $candidate
    ): bool {

        return array_key_exists('id', $candidate)
            && is_string($candidate['id'] ?? null)
            && array_key_exists('status', $candidate)
            && (
                array_key_exists('participant_a_id', $candidate)
                ||
                array_key_exists('participant_b_id', $candidate)
            );
    }

    /**
     * Formato pactado de una batalla que aun no se ha jugado.
     *
     * No inventa resultados: games queda vacio y el marcador a cero. Solo
     * dice a que se va a jugar, que es lo que la pantalla necesita antes
     * de empezar.
     */
    private function seriesBlueprint(array $match): ?array
    {
        $format = strtoupper(
            (string) ($match['series_format'] ?? '')
        );

        if ($format === '') {
            return null;
        }

        return [
            'series_format' => $format,
            'best_of' => (int) ($match['best_of'] ?? 1),
            'fixed_games' => (int) ($match['fixed_games'] ?? 1),

            'games' => [],
            'games_played' => 0,

            'game_wins_a' => 0,
            'game_wins_b' => 0,
            'game_draws' => 0,

            'score_for_a' => 0,
            'score_for_b' => 0,

            'points_for_a' => 0.0,
            'points_for_b' => 0.0,

            'status' => 'PENDING',
            'winner_id' => null,
            'tiebreak_required' => false,
            'tiebreak_games' => 0,

            'wins_required' => $format === 'BEST_OF'
                ? intdiv(max(1, (int) ($match['best_of'] ?? 1)), 2) + 1
                : null,
        ];
    }

    private function storeMatch(
        TournamentInstance $instance,
        int $nodeId,
        array $match,
        array $names,
        array $entities = [],
        array $seriesByMatch = [],
        ?\Illuminate\Support\Collection $existing = null
    ): void {

        $key =
            (string) $match['id'];

        /*
         * La fila que ya existe, del mapa que trajo projectMatches. Sin
         * mapa se busca a la antigua: storeMatch tiene que seguir siendo
         * correcto por si alguien lo llama suelto.
         */
        $row =
            $existing?->get($nodeId . '|' . $key)
            ?? TournamentInstanceMatch::query()
            ->where('tournament_instance_id', $instance->id)
            ->where('node_id', $nodeId)
            ->where('runtime_match_id', $key)
            ->first();

        $keyA =
            $match['participant_a_id']
            ?? null;

        $keyB =
            $match['participant_b_id']
            ?? null;

        $winner =
            $match['winner_id']
            ?? null;

        $loser = null;

        if ($winner !== null) {

            $loser =
                $winner === $keyA
                ? $keyB
                : $keyA;
        }

        $scoreA =
            $match['score_a'] ?? null;

        $scoreB =
            $match['score_b'] ?? null;

        $attributes = [
            'node_id' =>
            $nodeId,

            'round_number' =>
            $match['__round_number'] ?? null,

            'label' =>
            $match['__round_label'] ?? null,

            'status' =>
            $match['status'] ?? 'PENDING',

            'participant_a_key' =>
            $keyA,

            'participant_b_key' =>
            $keyB,

            'participant_a_name' =>
            $keyA !== null
                ? ($names[$keyA] ?? null)
                : null,

            'participant_b_name' =>
            $keyB !== null
                ? ($names[$keyB] ?? null)
                : null,

            /*
             * TODOS los que juegan, no solo dos.
             *
             * Una fase puede cruzar de cuatro en cuatro, y el motor lo
             * resuelve bien: lo que se quedaba corto era esta proyeccion,
             * que solo tenia sitio para A y B. Una competicion de 16 que
             * juega de cuatro en cuatro ensenaba 8, porque de cada
             * encuentro solo llegaban los dos primeros.
             *
             * Las columnas A y B se siguen llenando: media aplicacion las
             * usa, un duelo sigue siendo el caso normal, y para un duelo
             * dicen lo mismo que esta lista.
             */
            'participants' =>
            $this->matchParticipants($match, $names, $entities, $winner),

            'score_a' =>
            is_numeric($scoreA)
                ? (int) $scoreA
                : null,

            'score_b' =>
            is_numeric($scoreB)
                ? (int) $scoreB
                : null,

            'winner_key' =>
            $winner,

            'loser_key' =>
            $loser,

            'is_draw' =>
            $winner === null
                && is_numeric($scoreA)
                && is_numeric($scoreB)
                && (int) $scoreA === (int) $scoreB,

            /*
             * La serie jugada si existe; si todavia no se ha
             * disputado, al menos su FORMATO.
             *
             * Sin esto, una batalla pendiente no sabia decir si
             * era "BO3" o "2 enfrentamientos fijos" hasta despues
             * de jugarla, que es justo cuando ya no sirve saberlo.
             */
            'series' =>
            $seriesByMatch[(string) ($match['id'] ?? '')]
                ?? $match['games']
                ?? $match['series']
                ?? $this->seriesBlueprint($match),

            /*
             * Desnormalización para el historial por Entidad y
             * el head-to-head: sin esto cada consulta necesitaría
             * un join a participantes.
             */
            'participant_a_universe_entity_id' =>
            $keyA !== null
                ? ($entities[(string) $keyA] ?? null)
                : null,

            'participant_b_universe_entity_id' =>
            $keyB !== null
                ? ($entities[(string) $keyB] ?? null)
                : null,

            'winner_universe_entity_id' =>
            $winner !== null
                ? ($entities[(string) $winner] ?? null)
                : null,

            'group_label' =>
            $match['__group_label'] ?? null,

            'is_placement' =>
            (bool) ($match['__placement'] ?? false),

            /*
             * Se sella la primera vez que el encuentro aparece
             * terminado; a partir de ahí no se toca, para que el
             * orden cronológico sea estable.
             */
            'completed_at' =>
            ($match['status'] ?? null) === 'COMPLETED'
                ? ($row?->completed_at ?? now())
                : null,
        ];

        if (! $row) {

            $row =
                TournamentInstanceMatch::query()
                ->create(
                    $attributes
                    + [
                        'tournament_instance_id' => $instance->id,
                        'runtime_match_id' => $key,
                    ]
                );

            $existing?->put($key, $row);

            return;
        }

        $row->fill($attributes);

        /*
         * MySQL reordena las claves de una columna JSON al guardarla, y
         * Eloquent decide si un campo cambio comparando los dos JSON como
         * TEXTO. Con las mismas claves en distinto orden el texto no
         * coincide, asi que la serie salia "sucia" siempre: cada accion
         * del motor reescribia las 200 filas ya jugadas para dejarlas
         * exactamente como estaban. Se compara el contenido, no el texto.
         */
        if (
            $row->isDirty('series')
            && $this->sameContent(
                $row->getOriginal('series'),
                $row->series
            )
        ) {
            $row->syncOriginalAttribute('series');
        }

        if ($row->isDirty()) {
            $row->save();
        }
    }

    /**
     * Si dos estructuras dicen lo mismo, aunque no lo digan en el mismo
     * orden. Solo se usa para DECIDIR si hace falta escribir; lo que se
     * guarda es siempre el valor tal cual lo entrego el motor.
     */
    private function sameContent(mixed $left, mixed $right): bool
    {
        $normalize = function (mixed $value) use (&$normalize): mixed {

            if (! is_array($value)) {
                return $value;
            }

            $value = array_map($normalize, $value);

            ksort($value);

            return $value;
        };

        return $normalize($left) === $normalize($right);
    }

    /*
    |--------------------------------------------------------------------------
    | Clasificación por fase (Fase 8)
    |--------------------------------------------------------------------------
    |
    | Los motores YA calculan esto: Round Robin lo deja en
    | runtime['standings'] y Group Stage en runtime['groups'][].standings.
    | Aquí solo se saca del JSON a una tabla consultable.
    |
    | Se lee de forma defensiva: un motor futuro que publique standings
    | con la misma forma queda proyectado sin tocar esta clase.
    |
    */

    private function projectPhaseStandings(
        TournamentInstance $instance,
        array $state
    ): void {

        $entities =
            $this->participantEntities(
                $state
            );

        $names =
            $this->participantNames(
                $state
            );

        $phases =
            TournamentInstancePhase::query()
            ->where(
                'tournament_instance_id',
                $instance->id
            )
            ->get()
            ->keyBy('node_id');

        /*
         * Por donde salio cada uno de cada fase. Es lo unico que responde
         * de verdad "paso o quedo fuera?".
         */
        $exitOutcomes =
            $this->exitOutcomes(
                $state
            );

        foreach (
            ($state['nodes'] ?? [])
            as
            $node
        ) {

            $phase =
                $phases->get(
                    (int) ($node['id'] ?? 0)
                );

            $runtime =
                $node['runtime'] ?? null;

            if (
                ! $phase
                ||
                ! is_array($runtime)
            ) {
                continue;
            }

            /*
             * Quién salió vivo de la fase. Los motores lo publican como
             * survivor_ids; si no está, no se afirma nada.
             */
            $survivors =
                array_map(
                    'strval',
                    $runtime['survivor_ids'] ?? []
                );

            $hasSurvivors =
                array_key_exists(
                    'survivor_ids',
                    $runtime
                );

            $routed =
                $exitOutcomes[(int) ($node['id'] ?? 0)]
                ?? [];

            foreach (
                $this->standingRows($runtime)
                as
                [$row, $groupLabel]
            ) {

                $key =
                    (string) (
                        $row['participant_id']
                        ?? ''
                    );

                if ($key === '') {
                    continue;
                }

                TournamentInstancePhaseParticipant::query()
                    ->updateOrCreate(
                        [
                            'tournament_instance_phase_id' =>
                            $phase->id,

                            'runtime_key' =>
                            $key,
                        ],
                        [
                            'tournament_instance_id' =>
                            $instance->id,

                            'universe_entity_id' =>
                            $entities[$key] ?? null,

                            'participant_name' =>
                            mb_substr(
                                (string) ($names[$key] ?? $key),
                                0,
                                150
                            ),

                            'group_label' =>
                            $groupLabel,

                            'position' =>
                            isset($row['position'])
                                ? (int) $row['position']
                                : null,

                            'matches' =>
                            (int) ($row['played'] ?? $row['matches'] ?? 0),

                            'wins' =>
                            (int) ($row['wins'] ?? 0),

                            'draws' =>
                            (int) ($row['draws'] ?? 0),

                            'losses' =>
                            (int) ($row['losses'] ?? 0),

                            'points' =>
                            (int) ($row['points'] ?? 0),

                            'score_for' =>
                            (int) ($row['score_for'] ?? 0),

                            'score_against' =>
                            (int) ($row['score_against'] ?? 0),

                            'score_difference' =>
                            (int) ($row['score_difference'] ?? 0),

                            /*
                             * Manda la puerta por la que salio. survivor_ids
                             * solo entra si la fase no repartio: en una liga
                             * significa "nadie fue eliminado jugando", que es
                             * cierto y no dice nada de quien clasifico.
                             */
                            'status' =>
                            $routed[$key]
                                ?? (
                                    ! $hasSurvivors
                                    ? 'PLAYED'
                                    : (
                                        in_array($key, $survivors, true)
                                        ? 'ADVANCED'
                                        : 'ELIMINATED'
                                    )
                                ),
                        ]
                    );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Quien paso y quien quedo fuera de cada fase
    |--------------------------------------------------------------------------
    |
    | Una fase no decide esto: lo decide la PUERTA por la que sale cada uno
    | y adonde lleva esa puerta en el grafo. Salir hacia otra fase es seguir
    | vivo; salir hacia un terminal de eliminados es quedar fuera.
    |
    | Antes se deducia de survivor_ids y ahi estaba el error: cada motor le
    | da un sentido distinto. En eliminacion directa es "quien no perdio";
    | en fase de grupos, "los seleccionados". En Round Robin es LITERALMENTE
    | TODOS, y con razon: en una liga nadie es eliminado mientras se juega,
    | todos llegan vivos al final. Eso responde a la pregunta del motor, no
    | a la del grafo, y usarlo para pintar la tabla hacia que una liga
    | terminada mostrase a los veinte como clasificados.
    |
    | Se mira en dos sitios, en este orden:
    |
    |   1. La conexion ya repartida (participant_ids). Es un hecho.
    |   2. Las outcomes del motor cruzadas con el destino de cada puerta.
    |      Sirve entre que la fase acaba y el grafo reparte.
    |
    | Y lo que ninguna puerta reclamo cae por la puerta sobrante, que es lo
    | que REMAINING significa. Con mas de una sobrante no se adivina: se
    | deja sin decidir y el proyector cae a su calculo anterior.
    |
    | @return array<int, array<string, string>>  nodo => participante => estado
    */

    private function exitOutcomes(
        array $state
    ): array {

        $terminalTypes = [];

        foreach (
            ($state['terminals'] ?? [])
            as
            $terminal
        ) {

            $terminalTypes[(int) ($terminal['id'] ?? 0)] =
                (string) ($terminal['type'] ?? '');
        }

        /*
         * Destino de cada puerta: nodo => puerta => ADVANCED | ELIMINATED.
         * Es estructural, se conoce desde antes de jugar nada.
         */
        $destinations = [];

        /* Reparto ya hecho: nodo => participante => estado */
        $outcomes = [];

        foreach (
            ($state['connections'] ?? [])
            as
            $connection
        ) {

            if (($connection['source_type'] ?? null) !== 'PHASE_EXIT') {
                continue;
            }

            $nodeId = (int) ($connection['source_node_id'] ?? 0);
            $exitId = (int) ($connection['source_phase_exit_id'] ?? 0);

            if ($nodeId === 0 || $exitId === 0) {
                continue;
            }

            /*
             * Solo un terminal de eliminados saca a alguien. Ir a otra
             * fase, o a un terminal de campeon o de clasificados, es
             * seguir dentro.
             */
            $status =
                ($connection['target_type'] ?? null) === 'TERMINAL'
                    && (
                        $terminalTypes[(int) ($connection['target_terminal_id'] ?? 0)]
                        ?? ''
                    ) === 'ELIMINATED'
                ? 'ELIMINATED'
                : 'ADVANCED';

            /*
             * Una puerta puede repartirse entre varias conexiones (los 16
             * clasificados hacia cuatro grupos). Si alguna elimina, la
             * puerta no puede darse por puerta de paso.
             */
            if (($destinations[$nodeId][$exitId] ?? null) !== 'ELIMINATED') {

                $destinations[$nodeId][$exitId] = $status;
            }

            foreach (
                ($connection['participant_ids'] ?? [])
                as
                $participantId
            ) {

                $outcomes[$nodeId][(string) $participantId] = $status;
            }
        }

        /*
         * Fases que acabaron pero que el grafo aun no ha repartido: el
         * motor ya publico por que puerta sale cada uno.
         */
        foreach (
            ($state['nodes'] ?? [])
            as
            $node
        ) {

            $nodeId = (int) ($node['id'] ?? 0);

            $runtime = $node['runtime'] ?? null;

            if (
                ! is_array($runtime)
                ||
                ! isset($destinations[$nodeId])
            ) {
                continue;
            }

            $claimedExits = [];

            foreach (
                ($runtime['outcomes'] ?? [])
                as
                $outcome
            ) {

                $exitId = (int) ($outcome['exit_id'] ?? 0);

                $claimedExits[$exitId] = true;

                $status = $destinations[$nodeId][$exitId] ?? null;

                if ($status === null) {
                    continue;
                }

                foreach (
                    ($outcome['participant_ids'] ?? [])
                    as
                    $participantId
                ) {

                    $outcomes[$nodeId][(string) $participantId] ??= $status;
                }
            }

            /*
             * La puerta sobrante. Solo se aplica si hay exactamente una:
             * con dos no hay forma de saber cual recoge a quien.
             */
            $unclaimed =
                array_keys(
                    array_diff_key(
                        $destinations[$nodeId],
                        $claimedExits
                    )
                );

            if (count($unclaimed) !== 1) {
                continue;
            }

            $status = $destinations[$nodeId][$unclaimed[0]];

            foreach (
                $this->standingRows($runtime)
                as
                [$row, $groupLabel]
            ) {

                $key =
                    (string) (
                        $row['participant_id']
                        ?? $row['id']
                        ?? ''
                    );

                if ($key === '') {
                    continue;
                }

                $outcomes[$nodeId][$key] ??= $status;
            }
        }

        return $outcomes;
    }

    /*
     * Devuelve pares [fila, etiqueta de grupo]. Cubre las dos formas que
     * publican los motores actuales.
     */
    private function standingRows(
        array $runtime
    ): array {

        $rows = [];

        foreach (
            ($runtime['groups'] ?? [])
            as
            $group
        ) {

            $label =
                $group['name']
                ?? $group['code']
                ?? null;

            foreach (
                ($group['standings'] ?? [])
                as
                $row
            ) {
                $rows[] = [$row, $label];
            }
        }

        if ($rows !== []) {
            return $rows;
        }

        foreach (
            ($runtime['standings'] ?? [])
            as
            $row
        ) {

            $rows[] = [
                $row,

                $row['group_name'] ?? null,
            ];
        }

        return $rows;
    }

    /*
    |--------------------------------------------------------------------------
    | Desenlace de cada participante (Fase 8)
    |--------------------------------------------------------------------------
    |
    | El tipo del terminal donde acabó cada uno (CHAMPION, ELIMINATED...)
    | existe en el estado pero se perdía al proyectar: solo se guardaba el
    | nombre. Sin el tipo no se puede consultar quién ganó.
    |
    */

    private function projectOutcomes(
        TournamentInstance $instance,
        array $state
    ): void {

        /*
         * Terminales por nombre, para poder traducir la ubicación final
         * de un participante a un tipo de desenlace.
         */
        $terminalTypes = [];

        foreach (
            ($state['terminals'] ?? [])
            as
            $terminal
        ) {

            $terminalTypes[(string) ($terminal['name'] ?? '')] =
                (string) ($terminal['type'] ?? '');
        }

        $roundsReached =
            $this->roundsReached(
                $instance
            );

        $champions = [];

        /*
         * Cuantos acabaron en un terminal de tipo CHAMPION.
         *
         * Normalmente uno. Pero una salida puede mandar a OCHO al mismo
         * terminal -«los clasificados van a Mejor de todos»-, y entonces
         * marcarlos a todos campeones no es cumplir la configuracion, es
         * romper el resultado: la pantalla proclama a uno al azar, los
         * premios de campeon se reparten ocho veces y el palmares registra
         * ocho titulos por un torneo.
         *
         * Un campeon es UNO. Cuando el terminal trae varios, aqui no se
         * decide cual: se les deja sin desenlace firme y lo decide quien
         * sabe ordenarlos -TournamentPlacementResolver-, que compara con los
         * criterios de la fase y ademas escribe el puesto de cada uno.
         */
        $enTerminalDeCampeon = 0;

        foreach (($state['participants'] ?? []) as $participant) {

            $donde = $participant['current_location'] ?? [];

            if (
                ($donde['type'] ?? null) === 'TERMINAL'
                && ($terminalTypes[(string) ($donde['name'] ?? '')] ?? null) === 'CHAMPION'
            ) {
                $enTerminalDeCampeon++;
            }
        }

        $campeonUnico = $enTerminalDeCampeon === 1;

        foreach (
            ($state['participants'] ?? [])
            as
            $key => $participant
        ) {

            $location =
                $participant['current_location'] ?? [];

            $terminalType =
                ($location['type'] ?? null) === 'TERMINAL'
                ? ($terminalTypes[(string) ($location['name'] ?? '')] ?? null)
                : null;

            $status =
                $participant['status'] ?? null;

            $outcome =
                match (true) {

                    /*
                     * Llegó a un terminal: ahí sí hay desenlace firme.
                     *
                     * Con varios en el terminal de campeón no lo hay: son
                     * finalistas, no campeones. Ver la nota de arriba.
                     */
                    $terminalType === 'CHAMPION' && $campeonUnico =>
                    'CHAMPION',

                    $terminalType === 'CHAMPION' =>
                    'FINALIST',

                    $terminalType !== null
                        && $terminalType !== '' =>
                    'ELIMINATED',

                    $status === 'ELIMINATED' =>
                    'ELIMINATED',

                    /*
                     * Sigue jugando. No es "sin ubicar": es que la
                     * competición todavía no ha terminado para él.
                     */
                    in_array(
                        $status,
                        [
                            'COMPETING',
                            'ACTIVE',
                            'WAITING',
                        ],
                        true
                    ) =>
                    'IN_PROGRESS',

                    default =>
                    'UNPLACED',
                };

            if ($outcome === 'CHAMPION') {
                $champions[] = (string) $key;
            }

            TournamentInstanceParticipant::query()
                ->where(
                    'tournament_instance_id',
                    $instance->id
                )
                ->where(
                    'runtime_key',
                    (string) $key
                )
                ->update([

                    'outcome' =>
                    $outcome,

                    'round_reached' =>
                    $roundsReached[(string) $key] ?? null,

                    /*
                     * Solo se afirma la posición cuando es indiscutible.
                     * Un orden inventado sería peor que no tener dato.
                     */
                    'placement' =>
                    $outcome === 'CHAMPION'
                        ? 1
                        : null,

                    'updated_at' =>
                    now(),
                ]);
        }
    }

    /*
     * Ronda más lejana en la que aparece cada participante. Solo tiene
     * sentido en motores con rondas; en los demás queda a null porque no
     * habrá encuentros con round_number.
     *
     * Las batallas de PUESTOS quedan fuera a propósito. Van después de la
     * final y con números de ronda más altos, así que contarlas diría que
     * quien disputó el 13.º llegó más lejos que el campeón. Y de este orden
     * cuelgan los premios por posición.
     */
    private function roundsReached(
        TournamentInstance $instance
    ): array {

        $reached = [];

        TournamentInstanceMatch::query()
            ->where(
                'tournament_instance_id',
                $instance->id
            )
            ->whereNotNull('round_number')
            ->where('is_placement', false)
            ->get([
                'participant_a_key',
                'participant_b_key',
                'round_number',
            ])
            ->each(
                function ($match) use (&$reached) {

                    foreach (
                        [
                            $match->participant_a_key,
                            $match->participant_b_key,
                        ]
                        as
                        $key
                    ) {

                        if ($key === null) {
                            continue;
                        }

                        $reached[(string) $key] =
                            max(
                                $reached[(string) $key] ?? 0,
                                (int) $match->round_number
                            );
                    }
                }
            );

        return $reached;
    }

    /*
     * Mapa clave de runtime → id de la ENTIDAD DEL UNIVERSO.
     *
     * Nunca la de Biblioteca: las estadísticas pertenecen al Universo.
     */
    private function participantEntities(
        array $state
    ): array {

        $entities = [];

        foreach (
            ($state['participants'] ?? [])
            as
            $key => $participant
        ) {

            /*
             * universe_competitor_id es el nombre que tenía esta misma
             * clave antes de separar Biblioteca y Universo. Los estados
             * ya guardados la conservan, y sus ids son los mismos.
             */
            $entities[(string) $key] =
                $participant['universe_entity_id']
                ?? $participant['universe_competitor_id']
                ?? null;
        }

        return $entities;
    }

    private function participantNames(
        array $state
    ): array {

        $names = [];

        foreach (
            ($state['participants'] ?? [])
            as
            $key => $participant
        ) {

            $names[(string) $key] =
                $participant['name']
                ?? (string) $key;
        }

        return $names;
    }

    /*
    |--------------------------------------------------------------------------
    | Eventos
    |--------------------------------------------------------------------------
    |
    | Append-only. Se añaden únicamente los eventos del timeline que
    | todavía no están en el ledger.
    |
    */

    private function appendEvents(
        TournamentInstance $instance,
        array $state
    ): void {

        $timeline =
            array_values(
                $state['timeline'] ?? []
            );

        if ($timeline === []) {
            return;
        }

        $existing =
            (int)
            TournamentInstanceEvent::query()
            ->where(
                'tournament_instance_id',
                $instance->id
            )
            ->max('sequence');

        $rows = [];

        foreach ($timeline as $index => $event) {

            $sequence = $index + 1;

            if ($sequence <= $existing) {
                continue;
            }

            $rows[] = [

                'tournament_instance_id' =>
                $instance->id,

                'sequence' =>
                $sequence,

                'type' =>
                mb_substr(
                    (string) ($event['type'] ?? 'EVENT'),
                    0,
                    60
                ),

                'level' =>
                mb_substr(
                    (string) ($event['level'] ?? 'INFO'),
                    0,
                    20
                ),

                'message' =>
                (string) ($event['message'] ?? ''),

                'context' =>
                null,

                'created_at' =>
                now(),
            ];
        }

        if ($rows === []) {
            return;
        }

        TournamentInstanceEvent::query()
            ->insert($rows);
    }
}
