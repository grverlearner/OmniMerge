<?php

namespace App\Services\Games\Runtime;

use App\Services\Games\GameRegistry;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| EncounterRuntime
|--------------------------------------------------------------------------
|
| Resuelve UN enfrentamiento. Nada más.
|
| No sabe de fases, de series ni de grafos: recibe un match y el estado
| congelado del torneo, pide al Game Engine que genere y adjudique, y
| devuelve el estado con el enfrentamiento resuelto.
|
| Es una FUNCIÓN PURA, como todo el motor desde la Fase 6: no toca base de
| datos. Los enfrentamientos resueltos se acumulan en $state['game_log'] y
| es el TournamentInstanceRuntimeService —que sí tiene base de datos— quien
| los vuelca después.
|
| Sobre lo que se entrega a la serie
| ----------------------------------
| Se entregan DOS cosas distintas:
|
|   MARCADOR  1-0. Dice quien gano ESE enfrentamiento. Va como entero
|             porque submitGame() recibe enteros, y pasar 7.82 lo
|             truncaria a 7 creando empates falsos (7.1 y 7.9 serian 7).
|
|   PUNTOS    los numeros reales, sin truncar. Deciden una serie de
|             cantidad fija empatada en enfrentamientos: "dos" significa
|             dos, y gana quien mas sumo dentro de ellos, sin anadir un
|             tercero.
|
| Los numeros completos se conservan ademas en el log, que es de donde
| salen las columnas de puntos de la clasificacion.
|
*/

class EncounterRuntime
{
    private const MAX_TIEBREAKS = 8;

    public function __construct(
        private readonly GameRegistry $registry
    ) {}

    /*
    |--------------------------------------------------------------------------
    | ¿Hay juego?
    |--------------------------------------------------------------------------
    |
    | El Competition Lab de diseño juega con participantes sintéticos que no
    | tienen Game Stats. Ahí no hay juego que aplicar y el motor sigue
    | resolviendo al azar como siempre.
    |
    */

    public function isPlayable(array $state, array $match): bool
    {
        if (($state['game']['key'] ?? null) === null) {
            return false;
        }

        return count(
            $this->participantKeys($match)
        ) >= 2;
    }

    /*
    |--------------------------------------------------------------------------
    | Preparar
    |--------------------------------------------------------------------------
    */

    public function prepare(
        array $state,
        int $nodeId,
        array $match,
        ?string $phaseName = null
    ): array {

        /*
         * Que juego se juega AQUI.
         *
         * Normalmente el de la competicion, que es uno para todas sus
         * fases. Pero una edicion puede haber bajado esa decision a las
         * fases -"los grupos a numero mas alto, la final a otra cosa"-, y
         * entonces manda lo que diga el nodo.
         *
         * Lo escribe CompetitionPhasePlan antes de cada accion.
         */
        $gameKey =
            (string) (
                $state['competition_plan'][$nodeId]['game_key']
                ?? $state['game']['key']
                ?? GameRegistry::DEFAULT_KEY
            );

        $definition =
            $this->registry->definition($gameKey);

        $series =
            $state['nodes'][$nodeId]['runtime']['series'][$match['id']]
            ?? null;

        $roundNumber =
            $this->roundNumberOf(
                $state,
                $nodeId,
                (string) $match['id']
            );

        $number =
            (int) ($series['games_played'] ?? 0) + 1;

        $participants = [];

        foreach ($this->participantKeys($match) as $key) {

            $participant =
                $state['participants'][$key] ?? null;

            if (! $participant) {
                continue;
            }

            $stats =
                $participant['game_stats'][$gameKey]
                ?? $this->registry->engine($gameKey)->defaultStats();

            /*
             * Bonus temporales (Fase 12). Modifican lo que este
             * competidor puede hacer AQUI, en este enfrentamiento, sin
             * tocar nada guardado: cuando el torneo acabe, desaparecen.
             */
            [$stats, $activeModifiers] =
                $this->applyModifiers(
                    $state,
                    $stats,
                    $gameKey,
                    $participant['universe_entity_id'] ?? null,
                    $phaseName,
                    $roundNumber
                );

            $participants[] = [

                'id' => $key,

                'name' =>
                $participant['name'] ?? $key,

                'image_url' =>
                $participant['image_url'] ?? null,

                'universe_entity_id' =>
                $participant['universe_entity_id'] ?? null,

                'stats' =>
                $stats,

                'stats_label' =>
                $this->statsLabel($definition, $stats),

                'modifiers' =>
                $activeModifiers,

                'value' => null,
                'display' => null,
                'detail' => [],
                'rolled' => false,
                'position' => null,
                'is_winner' => false,
            ];
        }

        $state['encounter'] = [

            'battle_key' => (string) $match['id'],
            'node_id' => $nodeId,
            'phase_name' => $phaseName,

            'game_key' => $gameKey,

            'game' => [
                'name' => $definition['name'] ?? $gameKey,
                'icon' => $definition['icon'] ?? '🎲',
                'accent' => $definition['accent'] ?? 'violet',
                'win_condition' => $definition['win_condition'] ?? null,
            ],

            'controls' =>
            $definition['controls'] ?? [],

            'number' => $number,

            /*
             * En FIXED_GAMES, pasada la cuenta pactada, cualquier juego
             * extra es un DESEMPATE: el motor lo añade porque eliminación
             * directa exige un ganador. Sin decirlo, unos "2 fijos" que
             * acaban en tres parecen un BO3 mal configurado.
             */
            'is_tiebreak' =>
            strtoupper((string) ($match['series_format'] ?? '')) === 'FIXED_GAMES'
                && $number > (int) ($match['fixed_games'] ?? 1),

            /*
             * Si esta fase admite un empate o necesita un ganador.
             *
             * Lo decide la FASE, no el juego: en una liga un empate es un
             * resultado legitimo que da un punto a cada uno; en eliminacion
             * directa alguien tiene que pasar de ronda.
             */
            'requires_winner' =>
            $this->encounterRequiresWinner(
                $state,
                $nodeId,
                $match,
                $number
            ),

            'series' =>
            $this->seriesSummary($series, $match),

            'participants' => $participants,

            'status' => 'ROLLING',

            'winner_id' => null,
            'is_draw' => false,
            'summary' => null,
            'tiebreaks' => 0,
        ];

        return $state;
    }

    /*
    |--------------------------------------------------------------------------
    | Generar
    |--------------------------------------------------------------------------
    */

    /**
     * Genera el resultado de un participante, o de todos los que falten.
     * Cuando ya no queda nadie por generar, adjudica automáticamente.
     */
    public function roll(
        array $state,
        ?string $participantId = null,
        bool $all = false
    ): array {

        $encounter =
            $state['encounter'] ?? null;

        if (! $encounter) {
            $this->fail(
                'No hay ningún enfrentamiento preparado.'
            );
        }

        if ($encounter['status'] !== 'ROLLING') {
            $this->fail(
                'Este enfrentamiento ya está resuelto. Avanza al siguiente.'
            );
        }

        $engine =
            $this->registry->engine($encounter['game_key']);

        $config =
            $state['game']['configuration'] ?? [];

        $touched = false;

        foreach ($encounter['participants'] as $index => $participant) {

            $isTarget =
                $all
                || $participant['id'] === $participantId;

            if (! $isTarget || $participant['rolled']) {
                continue;
            }

            $roll =
                $engine->roll($participant, $config);

            $encounter['participants'][$index]['value'] =
                (float) $roll['value'];

            $encounter['participants'][$index]['display'] =
                (string) $roll['display'];

            $encounter['participants'][$index]['detail'] =
                $roll['detail'] ?? [];

            $encounter['participants'][$index]['stats_used'] =
                $roll['stats_used'] ?? $participant['stats'];

            $encounter['participants'][$index]['rolled'] =
                true;

            $touched = true;
        }

        if (! $touched) {
            $this->fail(
                'Ese participante ya generó su resultado.'
            );
        }

        $state['encounter'] = $encounter;

        $pending =
            collect($encounter['participants'])
            ->contains(
                fn(array $participant) =>
                ! $participant['rolled']
            );

        return $pending
            ? $state
            : $this->adjudicate($state);
    }

    /**
     * Resuelve el enfrentamiento entero de una vez. Es lo que usa el modo
     * automático del motor.
     */
    public function resolveNow(
        array $state,
        int $nodeId,
        array $match,
        ?string $phaseName = null
    ): array {

        $state =
            $this->prepare(
                $state,
                $nodeId,
                $match,
                $phaseName
            );

        return $this->roll(
            $state,
            null,
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Adjudicar
    |--------------------------------------------------------------------------
    */

    private function adjudicate(array $state): array
    {
        $encounter = $state['encounter'];

        $engine =
            $this->registry->engine($encounter['game_key']);

        $config =
            $state['game']['configuration'] ?? [];

        /*
         * ¿Se repite la tirada o se acepta el empate?
         *
         * Manda la FASE. Una liga o unos grupos admiten empate y ahí un
         * empate es un resultado real —un punto para cada uno—; una
         * eliminación directa necesita que alguien pase, así que los
         * empatados repiten.
         *
         * Antes lo decidía el juego, y como los juegos numéricos venían
         * marcados como "sin empates", un Rounded Number nunca llegaba a
         * empatar aunque empatase el 30% de las veces: el motor volvía a
         * tirar en silencio.
         *
         * Un juego que de verdad no pueda empatar lo sigue diciendo con
         * allows_draws = false, y entonces se repite siempre.
         */
        $gameAllowsDraws =
            (bool) ($this->registry->definition($encounter['game_key'])['allows_draws'] ?? false);

        $requiresWinner =
            ! $gameAllowsDraws
            || ($encounter['requires_winner'] ?? true);

        $attempts = 0;

        while (true) {

            $outcome =
                $engine->adjudicate(
                    $this->rollsOf($encounter),
                    $config
                );

            if (
                ! $outcome['is_draw']
                || ! $requiresWinner
                || $attempts >= self::MAX_TIEBREAKS
            ) {
                break;
            }

            $attempts++;

            foreach ($encounter['participants'] as $index => $participant) {

                if (
                    ! in_array(
                        $participant['id'],
                        $outcome['tied_ids'],
                        true
                    )
                ) {
                    continue;
                }

                $roll =
                    $engine->roll($participant, $config);

                $encounter['participants'][$index]['value'] =
                    (float) $roll['value'];

                $encounter['participants'][$index]['display'] =
                    (string) $roll['display'];
            }

            $encounter['tiebreaks'] = $attempts;
        }

        $positions =
            collect($outcome['ranking'] ?? [])
            ->keyBy('id');

        foreach ($encounter['participants'] as $index => $participant) {

            $row =
                $positions->get($participant['id']);

            $encounter['participants'][$index]['position'] =
                (int) ($row['position'] ?? 0);

            $encounter['participants'][$index]['is_winner'] =
                $participant['id'] === ($outcome['winner_id'] ?? null);
        }

        /*
         * NO se reordenan los participantes.
         *
         * Ordenarlos por posición hacía que, al resolverse, el ganador
         * saltara al primer hueco y el perdedor al segundo: las tarjetas
         * se intercambiaban de sitio en pantalla justo cuando el usuario
         * estaba mirando el resultado. Cada uno se queda donde estaba y
         * `position` basta para saber quién quedó por delante.
         */

        $encounter['winner_id'] = $outcome['winner_id'] ?? null;
        $encounter['is_draw'] = (bool) ($outcome['is_draw'] ?? false);
        $encounter['summary'] = $outcome['summary'] ?? null;
        $encounter['status'] = 'RESOLVED';

        $state['encounter'] = $encounter;

        /*
         * El log es lo que después se persiste. Se escribe aquí y no en la
         * capa de base de datos para que el resultado quede registrado
         * exactamente como lo produjo el motor.
         */
        $state['game_log'][] =
            $this->logEntry($encounter);

        return $state;
    }

    /*
    |--------------------------------------------------------------------------
    | Salida hacia la serie
    |--------------------------------------------------------------------------
    */

    /**
     * Marcador que se entrega a MatchSeriesRuntime para los encuentros de
     * dos participantes: 1-0 al ganador, 0-0 si el juego admitió empate.
     *
     * @return array{0: int, 1: int}
     */
    public function seriesScores(
        array $encounter,
        array $match
    ): array {

        $a = $match['participant_a_id'] ?? null;
        $b = $match['participant_b_id'] ?? null;

        $winner = $encounter['winner_id'] ?? null;

        if ($winner === null) {
            return [0, 0];
        }

        return $winner === $a
            ? [1, 0]
            : ($winner === $b ? [0, 1] : [0, 0]);
    }

    /**
     * Puntos reales que hizo cada lado en ESTE enfrentamiento.
     *
     * Son los que deciden una serie de cantidad fija cuando los
     * enfrentamientos quedan igualados: dos son dos, y gana quien mas
     * sumo dentro de ellos.
     *
     * @return array{0: float, 1: float}
     */
    public function encounterPoints(
        array $encounter,
        array $match
    ): array {

        if (
            ! ($this->registry->definition($encounter['game_key'] ?? null)['tracks_points'] ?? false)
        ) {
            return [0.0, 0.0];
        }

        $a = $match['participant_a_id'] ?? null;
        $b = $match['participant_b_id'] ?? null;

        $valueOf = function (?string $key) use ($encounter): float {

            if ($key === null) {
                return 0.0;
            }

            $participant = collect($encounter['participants'] ?? [])
                ->firstWhere('id', $key);

            return (float) ($participant['value'] ?? 0);
        };

        return [$valueOf($a), $valueOf($b)];
    }

    /*
    |--------------------------------------------------------------------------
    | Interno
    |--------------------------------------------------------------------------
    */

    private function logEntry(array $encounter): array
    {
        return [

            'battle_key' => $encounter['battle_key'],
            'node_id' => $encounter['node_id'],
            'phase_name' => $encounter['phase_name'],
            'game_key' => $encounter['game_key'],
            'encounter_number' => $encounter['number'],
            'is_draw' => $encounter['is_draw'],
            'winner_id' => $encounter['winner_id'],
            'summary' => $encounter['summary'],
            'tiebreaks' => $encounter['tiebreaks'] ?? 0,

            'participants' =>
            collect($encounter['participants'])
                ->map(
                    fn(array $participant) => [
                        'id' => $participant['id'],
                        'name' => $participant['name'],
                        'universe_entity_id' => $participant['universe_entity_id'] ?? null,
                        'value' => $participant['value'],
                        'display' => $participant['display'],
                        'position' => $participant['position'],
                        'is_winner' => $participant['is_winner'],
                        'stats_used' => $participant['stats_used'] ?? $participant['stats'],
                    ]
                )
                ->all(),
        ];
    }

    private function rollsOf(array $encounter): array
    {
        return collect($encounter['participants'])
            ->map(
                fn(array $participant) => [
                    'id' => $participant['id'],
                    'name' => $participant['name'],
                    'value' => $participant['value'],
                    'display' => $participant['display'],
                ]
            )
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function participantKeys(array $match): array
    {
        $explicit =
            array_values(
                array_filter([
                    $match['participant_a_id'] ?? null,
                    $match['participant_b_id'] ?? null,
                ])
            );

        if ($explicit !== []) {
            return $explicit;
        }

        /*
         * Encuentros de selección: N participantes en la misma tirada.
         */
        return array_values(
            array_filter(
                $match['participant_ids'] ?? []
            )
        );
    }

    private function statsLabel(
        array $definition,
        array $stats
    ): ?string {

        $parts =
            collect($definition['stats'] ?? [])
            ->map(
                fn(array $schema) =>
                $stats[$schema['key']] ?? null
            )
            ->filter(
                fn($value) =>
                $value !== null
            )
            ->map(
                fn($value) =>
                is_float($value) || is_int($value)
                    ? rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.')
                    : (string) $value
            );

        return $parts->isEmpty()
            ? null
            : $parts->implode(' – ');
    }

    private function seriesSummary(
        ?array $series,
        array $match
    ): array {

        $format =
            strtoupper(
                (string) ($match['series_format'] ?? 'BEST_OF')
            );

        $bestOf =
            (int) ($match['best_of'] ?? 1);

        $fixed =
            (int) ($match['fixed_games'] ?? 1);

        return [

            'format' => $format,

            'label' =>
            $format === 'FIXED_GAMES'
                ? $fixed . ' juegos'
                : 'BO' . max(1, $bestOf),

            'score_a' =>
            (int) ($series['game_wins_a'] ?? 0),

            'score_b' =>
            (int) ($series['game_wins_b'] ?? 0),

            'wins_required' =>
            $series['wins_required']
                ?? ($format === 'BEST_OF' ? intdiv(max(1, $bestOf), 2) + 1 : null),

            'games_played' =>
            (int) ($series['games_played'] ?? 0),

            /*
             * Cuantos enfrentamientos tiene la batalla de largo.
             *
             * En BEST_OF es el maximo que se pueden llegar a jugar; en
             * FIXED_GAMES es la cuenta pactada, que se juega entera. Sirve
             * para PINTAR como va la batalla: sin saber el total, un "2
             * ganados" no dice si falta uno o si ya esta decidido.
             */
            'total_games' =>
            $format === 'FIXED_GAMES'
                ? max(1, $fixed)
                : max(1, $bestOf),

            /*
             * Los empates no son de nadie, pero se jugaron: sin contarlos
             * aparte, un 1-1 con un empate parece una batalla de dos
             * enfrentamientos cuando fueron tres.
             */
            'draws' =>
            (int) ($series['game_draws'] ?? 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Modificadores temporales (Fase 12)
    |--------------------------------------------------------------------------
    |
    | Se aplican sobre una COPIA de las stats congeladas. El competidor no
    | se entera: al terminar el torneo, sus stats siguen siendo las que
    | eran. Eso es exactamente lo que separa un bonus de una recompensa.
    |
    */

    /**
     * @return array{0: array, 1: array}
     */
    private function applyModifiers(
        array $state,
        array $stats,
        string $gameKey,
        ?int $entityId,
        ?string $phaseName,
        ?int $roundNumber
    ): array {

        $applied = [];

        foreach ($state['modifiers'] ?? [] as $modifier) {

            if (
                ! $this->modifierApplies(
                    $modifier,
                    $gameKey,
                    $entityId,
                    $phaseName,
                    $roundNumber
                )
            ) {
                continue;
            }

            $key =
                (string) $modifier['stat_key'];

            /*
             * Un bonus solo puede mover una stat que el juego declare.
             * Si la regla quedo obsoleta, se ignora en silencio en vez
             * de inventar una estadistica nueva.
             */
            if (! array_key_exists($key, $stats)) {
                continue;
            }

            $before = (float) $stats[$key];
            $amount = (float) ($modifier['amount'] ?? 0);

            $stats[$key] = match ($modifier['operation'] ?? 'ADD') {
                'ADD' => $before + $amount,
                'SUBTRACT' => $before - $amount,
                'MULTIPLY' => $before * $amount,
                'SET' => $amount,
                default => $before,
            };

            $applied[] = [
                'label' =>
                $modifier['label']
                    ?: $this->modifierLabel($modifier),

                'stat_key' => $key,
                'delta' => round($stats[$key] - $before, 4),
            ];
        }

        if ($applied !== []) {

            $stats =
                $this->registry
                ->engine($gameKey)
                ->normalizeStats($stats);
        }

        return [$stats, $applied];
    }

    private function modifierApplies(
        array $modifier,
        string $gameKey,
        ?int $entityId,
        ?string $phaseName,
        ?int $roundNumber
    ): bool {

        /*
         * PHASE_PODIUM no es un bonus: es la REGLA que dice a quien se le
         * concedera uno cuando la fase termine. Vive en la misma lista
         * porque se congela con todo lo demas, pero no se aplica a nadie.
         * Lo que si se aplica es lo que PhaseBonusGranter genera a partir
         * de ella, que ya viene con target ENTITY y un competidor
         * concreto.
         */
        if (($modifier['target'] ?? 'ALL') === 'PHASE_PODIUM') {
            return false;
        }

        $modifierGame = $modifier['game_key'] ?? null;

        if ($modifierGame && strtoupper((string) $modifierGame) !== $gameKey) {
            return false;
        }

        if (($modifier['target'] ?? 'ALL') === 'ENTITY') {

            if (
                $entityId === null
                || (int) $modifier['universe_entity_id'] !== $entityId
            ) {
                return false;
            }
        }

        return match ($modifier['scope'] ?? 'TOURNAMENT') {

            'PHASE' =>
            $phaseName !== null
                && mb_strtolower(trim((string) $modifier['scope_value']))
                    === mb_strtolower(trim($phaseName)),

            'ROUND' =>
            $roundNumber !== null
                && (int) $modifier['scope_value'] === $roundNumber,

            default => true,
        };
    }

    private function modifierLabel(array $modifier): string
    {
        $amount =
            rtrim(rtrim(number_format((float) ($modifier['amount'] ?? 0), 3, '.', ''), '0'), '.');

        $sign = match ($modifier['operation'] ?? 'ADD') {
            'SUBTRACT' => '-',
            'MULTIPLY' => 'x',
            'SET' => '=',
            default => '+',
        };

        return $sign . $amount . ' ' . ($modifier['stat_key'] ?? '');
    }

    private function roundNumberOf(
        array $state,
        int $nodeId,
        string $matchId
    ): ?int {

        foreach (
            $state['nodes'][$nodeId]['runtime']['rounds'] ?? []
            as $round
        ) {
            foreach ($round['matches'] ?? [] as $match) {

                if ((string) ($match['id'] ?? '') === $matchId) {

                    return isset($round['number'])
                        ? (int) $round['number']
                        : null;
                }
            }
        }

        return null;
    }

    /**
     * ¿La fase donde se juega este enfrentamiento necesita un ganador?
     *
     * Eliminación directa siempre; el resto, según su configuración. Si no
     * se sabe nada de la fase se asume que sí, que es lo prudente: dejar
     * un empate donde hacía falta un ganador bloquearía el torneo.
     */
    /**
     * Si ESTE enfrentamiento tiene que acabar con un ganador.
     *
     * No es lo mismo que la batalla. En una serie de cantidad fija -"se
     * juegan 4"- lo que decide es lo que pase en esos cuatro: un empate
     * dentro de uno de ellos es un resultado legitimo, no algo que haya
     * que repetir hasta romperlo. Solo cuando los cuatro terminan y la
     * batalla sigue igualada se juega un desempate, y ESE si necesita
     * ganador porque es el que decide quien pasa.
     *
     * Antes la exigencia de la fase se aplicaba a cada enfrentamiento: en
     * eliminacion directa, cada uno de los cuatro repetia la tirada hasta
     * deshacer el empate, asi que el acumulado nunca reflejaba lo que
     * habia pasado de verdad.
     *
     * Al mejor de N no entra aqui: ahi un empate no suma para nadie y la
     * serie se quedaria sin avanzar, asi que se sigue repitiendo.
     */
    private function encounterRequiresWinner(
        array $state,
        int $nodeId,
        array $match,
        int $number
    ): bool {

        if (! $this->phaseRequiresWinner($state, $nodeId)) {
            return false;
        }

        if (
            strtoupper((string) ($match['series_format'] ?? ''))
            === 'FIXED_GAMES'
        ) {
            return $number > (int) ($match['fixed_games'] ?? 1);
        }

        return true;
    }

    private function phaseRequiresWinner(array $state, int $nodeId): bool
    {
        $runtime = $state['nodes'][$nodeId]['runtime'] ?? null;

        if (! is_array($runtime)) {
            return true;
        }

        /*
         * Un cuadro necesita que alguien pase, diga lo que diga la
         * competicion. Aceptar un empate aqui dejaria la ronda siguiente
         * sin nadie a quien colocar.
         */
        if (($runtime['engine'] ?? null) === 'SINGLE_ELIMINATION') {
            return true;
        }

        /*
         * Lo que decidio la edicion. Manda sobre el defecto de la fase
         * porque admitir tablas o no es de como se juega este ano, no de
         * la forma del torneo.
         */
        if (array_key_exists('allow_draws', $state['competition_plan'][$nodeId] ?? [])) {
            return ! (bool) $state['competition_plan'][$nodeId]['allow_draws'];
        }

        if (! array_key_exists('allow_draws', $runtime)) {
            return true;
        }

        return ! (bool) $runtime['allow_draws'];
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'encounter' => [$message],
        ]);
    }
}
