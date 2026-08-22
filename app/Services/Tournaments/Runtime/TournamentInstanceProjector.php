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
                    $runtime['series'] ?? []
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
        ?string $groupLabel = null
    ): array {

        $found = [];

        if ($this->looksLikeMatch($runtime)) {

            $runtime['__round_number'] = $roundNumber;
            $runtime['__round_label'] = $roundLabel;
            $runtime['__group_label'] = $groupLabel;

            return [$runtime];
        }

        foreach ($runtime as $key => $value) {

            if (! is_array($value)) {
                continue;
            }

            $childRound = $roundNumber;
            $childLabel = $roundLabel;
            $childGroup = $groupLabel;

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

            $found = array_merge(
                $found,
                $this->collectMatches(
                    $value,
                    $childRound,
                    $childLabel,
                    $childGroup
                )
            );
        }

        return $found;
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

    private function storeMatch(
        TournamentInstance $instance,
        int $nodeId,
        array $match,
        array $names,
        array $entities = [],
        array $seriesByMatch = []
    ): void {

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

        TournamentInstanceMatch::query()
            ->updateOrCreate(
                [
                    'tournament_instance_id' =>
                    $instance->id,

                    'runtime_match_id' =>
                    (string) $match['id'],
                ],
                [
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

                    'series' =>
                    $seriesByMatch[(string) ($match['id'] ?? '')]
                        ?? $match['games']
                        ?? $match['series']
                        ?? null,

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

                    /*
                     * Se sella la primera vez que el encuentro aparece
                     * terminado; a partir de ahí no se toca, para que el
                     * orden cronológico sea estable.
                     */
                    'completed_at' =>
                    ($match['status'] ?? null) === 'COMPLETED'
                        ? (
                            TournamentInstanceMatch::query()
                            ->where(
                                'tournament_instance_id',
                                $instance->id
                            )
                            ->where(
                                'runtime_match_id',
                                (string) $match['id']
                            )
                            ->value('completed_at')
                            ?? now()
                        )
                        : null,
                ]
            );
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

                            'status' =>
                            ! $hasSurvivors
                                ? 'PLAYED'
                                : (
                                    in_array($key, $survivors, true)
                                    ? 'ADVANCED'
                                    : 'ELIMINATED'
                                ),
                        ]
                    );
            }
        }
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
                     */
                    $terminalType === 'CHAMPION' =>
                    'CHAMPION',

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
