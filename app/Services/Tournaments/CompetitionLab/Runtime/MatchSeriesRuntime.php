<?php

namespace App\Services\Tournaments\CompetitionLab\Runtime;

use Illuminate\Validation\ValidationException;

final class MatchSeriesRuntime
{
    private const BEST_OF_VALUES = [
        1,
        3,
        5,
        7,
        9,
    ];

    /**
     * Registra un juego sin cerrar el encuentro hasta que la serie realmente
     * haya terminado. La información vive fuera de rounds para sobrevivir a
     * motores que reconstruyen sus matches a partir de otra estructura.
     *
     * @return array{
     *     runtime: array,
     *     series: array,
     *     completed: bool,
     *     engine_score_a: int,
     *     engine_score_b: int
     * }
     */
    /**
     * @param  float $pointsA  puntos REALES del juego, si los tiene.
     * @param  float $pointsB  Sirven para decidir una serie de cantidad
     *                         fija: dos enfrentamientos empatados a uno
     *                         los gana quien mas puntos sumo, no un
     *                         tercer enfrentamiento.
     */
    public function submitGame(
        array $runtime,
        string $matchId,
        int $scoreA,
        int $scoreB,
        bool $requiresWinner = false,
        float $pointsA = 0.0,
        float $pointsB = 0.0
    ): array {

        /*
         * Como decide esta edicion quien gana una serie.
         *
         * SERIES_THEN_POINTS  manda el marcador -quien gano mas juegos- y
         *                     solo si empata deciden las anotaciones.
         * POINTS_ONLY         el marcador no cuenta: gana quien mas sumo,
         *                     aunque haya perdido mas juegos.
         *
         * Lo escribe CompetitionPhasePlan en el nodo antes de cada accion.
         * Sin nada escrito se comporta como siempre se comporto.
         */
        $pointsOnly =
            ($runtime['competition']['decision_mode'] ?? null)
            === 'POINTS_ONLY';
        if ($scoreA < 0 || $scoreB < 0) {
            $this->fail(
                'Los scores de un juego no pueden ser negativos.'
            );
        }

        $match =
            $this->findMatch(
                $runtime,
                $matchId
            );

        if (
            ($match['status'] ?? null)
            !==
            'PENDING'
        ) {
            $this->fail(
                'El encuentro no está pendiente o ya fue completado.'
            );
        }

        $participantA =
            $match['participant_a_id']
            ??
            ($match['participant_ids'][0] ?? null);

        $participantB =
            $match['participant_b_id']
            ??
            ($match['participant_ids'][1] ?? null);

        if (
            ! $participantA
            ||
            ! $participantB
        ) {
            $this->fail(
                'La serie necesita dos participantes reales.'
            );
        }

        [
            $format,
            $bestOf,
            $fixedGames,
        ] =
            $this->seriesConfiguration(
                $match
            );

        $runtime['series'] ??= [];

        $series =
            $runtime['series'][$matchId]
            ??
            $this->newSeries(
                $matchId,
                $participantA,
                $participantB,
                $format,
                $bestOf,
                $fixedGames
            );

        $this->assertSeriesCompatibility(
            $series,
            $participantA,
            $participantB,
            $format,
            $bestOf,
            $fixedGames
        );

        if (
            ($series['status'] ?? null)
            ===
            'COMPLETED'
        ) {
            $this->fail(
                'La serie ya fue completada.'
            );
        }

        /*
         * Un empate dentro de la serie.
         *
         * En cantidad fija se acepta: se juegan los N pactados y lo que
         * decide es el conjunto, no cada uno por separado. Si al terminar
         * los N sigue todo igualado -mismos enfrentamientos y mismo
         * acumulado- entonces si se pide un desempate, y ese ya no puede
         * quedar en tablas.
         *
         * Al mejor de N no: ahi un empate no suma para ningun lado y la
         * serie no avanzaria nunca hacia los ganados que hacen falta.
         */
        $drawAllowedHere =
            $format === 'FIXED_GAMES'
            &&
            (int) ($series['games_played'] ?? 0) < $fixedGames;

        if (
            $requiresWinner
            &&
            $scoreA === $scoreB
            &&
            ! $drawAllowedHere
        ) {
            $this->fail(
                'Esta serie eliminatoria necesita un ganador: '
                    . 'los enfrentamientos pactados ya se jugaron y '
                    . 'siguen igualados.'
            );
        }

        $gameNumber =
            count(
                $series['games']
            )
            +
            1;

        $winnerId =
            null;

        if ($scoreA > $scoreB) {
            $series['game_wins_a']++;
            $winnerId =
                $participantA;
        } elseif ($scoreB > $scoreA) {
            $series['game_wins_b']++;
            $winnerId =
                $participantB;
        } else {
            $series['game_draws']++;
        }

        /*
         * Puntos reales acumulados. Van aparte del marcador de la serie
         * porque son cosas distintas: el marcador cuenta enfrentamientos
         * ganados, esto cuenta lo que se hizo dentro de ellos.
         */
        $series['points_for_a'] =
            round((float) ($series['points_for_a'] ?? 0) + $pointsA, 4);

        $series['points_for_b'] =
            round((float) ($series['points_for_b'] ?? 0) + $pointsB, 4);

        $series['score_for_a'] +=
            $scoreA;

        $series['score_for_b'] +=
            $scoreB;

        $isTiebreakGame =
            $format === 'FIXED_GAMES'
            &&
            $gameNumber > $fixedGames;

        if ($isTiebreakGame) {
            $series['tiebreak_games'] =
                (int)
                ($series['tiebreak_games'] ?? 0)
                +
                1;
        }

        $series['games'][] = [
            'number' =>
            $gameNumber,

            'score_a' =>
            $scoreA,

            'score_b' =>
            $scoreB,

            'winner_id' =>
            $winnerId,

            'is_tiebreak' =>
            $isTiebreakGame,

            'status' =>
            'COMPLETED',
        ];

        $completed =
            false;

        if ($format === 'FIXED_GAMES') {
            $nominalCompleted =
                $gameNumber
                >=
                $fixedGames;

            if ($nominalCompleted) {

                /*
                 * Quien gana una serie de cantidad fija.
                 *
                 * Primero los enfrentamientos ganados. Si estan igualados,
                 * decide la SUMA DE PUNTOS: "dos enfrentamientos" significa
                 * exactamente dos, y el resultado sale de lo que se hizo en
                 * ellos, no de anadir un tercero.
                 *
                 * Solo si tambien los puntos empatan, y ademas la fase
                 * exige un ganador -eliminacion directa-, se juega el
                 * desempate. En liga o grupos un empate se queda en empate.
                 */
                $tiedOnGames =
                    $series['game_wins_a']
                    ===
                    $series['game_wins_b'];

                $pointsA = (float) ($series['points_for_a'] ?? 0);
                $pointsB = (float) ($series['points_for_b'] ?? 0);

                $hasPoints = $pointsA > 0 || $pointsB > 0;

                $tied =
                    $tiedOnGames
                    && (! $hasPoints || $pointsA === $pointsB);

                /* Quien gano por acumulado, cuando los juegos empataron */
                $series['decided_on_points'] =
                    $tiedOnGames && $hasPoints && $pointsA !== $pointsB;

                if (
                    $requiresWinner
                    &&
                    $tied
                ) {
                    $series['tiebreak_required'] =
                        true;
                } else {
                    $series['tiebreak_required'] =
                        false;

                    $completed =
                        true;
                }
            }
        } else {
            $winsRequired =
                intdiv(
                    $bestOf,
                    2
                )
                +
                1;

            if ($bestOf === 1) {
                /*
                 * BO1 es exactamente un juego. Otros motores pueden permitir
                 * empate; Single Elimination no, porque requiere winner.
                 */
                $completed =
                    true;
            } else {
                $completed =
                    $series['game_wins_a']
                        >=
                        $winsRequired
                    ||
                    $series['game_wins_b']
                        >=
                        $winsRequired;
            }

            $series['tiebreak_required'] =
                false;
        }

        if ($completed) {
            $series['status'] =
                'COMPLETED';

            if ($pointsOnly) {

                /*
                 * Solo el acumulado. Se marca decided_on_points para que
                 * la ficha del enfrentamiento pueda explicar por que gano
                 * quien gano, que si no parece un error.
                 */
                $pointsA = (float) ($series['points_for_a'] ?? 0);
                $pointsB = (float) ($series['points_for_b'] ?? 0);

                $series['decided_on_points'] = $pointsA !== $pointsB;

                $series['winner_id'] = match (true) {
                    $pointsA > $pointsB => $participantA,
                    $pointsB > $pointsA => $participantB,
                    default => null,
                };

                /*
                 * Empatados en acumulado dentro de un cuadro.
                 *
                 * Dejarlo en nulo pararia el torneo: la ronda siguiente se
                 * quedaria sin nadie a quien colocar. Se cae al marcador,
                 * que es el criterio que esta edicion aparco pero no
                 * borro, y solo si tambien empata se pide desempate.
                 */
                if ($series['winner_id'] === null && $requiresWinner) {

                    if ($series['game_wins_a'] !== $series['game_wins_b']) {

                        $series['decided_on_points'] = false;

                        $series['winner_id'] =
                            $series['game_wins_a'] > $series['game_wins_b']
                            ? $participantA
                            : $participantB;
                    } else {
                        $series['status'] = 'RUNNING';
                        $series['tiebreak_required'] = true;
                    }
                }

            } elseif (
                $series['game_wins_a']
                !==
                $series['game_wins_b']
            ) {
                $series['winner_id'] =
                    $series['game_wins_a'] > $series['game_wins_b']
                    ? $participantA
                    : $participantB;
            } elseif ($series['decided_on_points'] ?? false) {

                /* Empatados en enfrentamientos: decide el acumulado */
                $series['winner_id'] =
                    (float) $series['points_for_a'] > (float) $series['points_for_b']
                    ? $participantA
                    : $participantB;
            } else {
                $series['winner_id'] = null;
            }
        } else {
            $series['status'] =
                'RUNNING';

            $series['winner_id'] =
                null;
        }

        $series =
            $this->refreshProgress(
                $series
            );

        $runtime['series'][$matchId] =
            $series;

        /*
         * BO1 conserva el score original que ya entienden los motores.
         * BO3+ y FIXED_GAMES entregan el score de juegos de la serie.
         */
        $engineScoreA =
            $bestOf === 1
            &&
            $format !== 'FIXED_GAMES'
            ? $scoreA
            : (int)
            $series['game_wins_a'];

        $engineScoreB =
            $bestOf === 1
            &&
            $format !== 'FIXED_GAMES'
            ? $scoreB
            : (int)
            $series['game_wins_b'];

        /*
         * Igualados en enfrentamientos, decidida por el acumulado.
         *
         * El motor de fase decide mirando el marcador, y en cantidad fija
         * ese marcador son los enfrentamientos ganados: una serie de 4 que
         * acaba 2-2 le llegaba como un EMPATE aunque el acumulado ya
         * hubiera dado un ganador, y Single Elimination la rechazaba con
         * "no permite empates". La batalla se quedaba sin poder cerrarse.
         *
         * Se le entrega lo que de verdad decidio: el acumulado. Los
         * enfrentamientos ganados se siguen viendo aparte, en el marcador
         * de la serie.
         */
        if (
            ($series['decided_on_points'] ?? false)
            &&
            $engineScoreA === $engineScoreB
        ) {
            $pointsForA = (float) ($series['points_for_a'] ?? 0);
            $pointsForB = (float) ($series['points_for_b'] ?? 0);

            $engineScoreA = (int) round($pointsForA);
            $engineScoreB = (int) round($pointsForB);

            /*
             * Si el redondeo vuelve a igualarlos -acumulados con decimales
             * muy cerca- se separan por uno, del lado que gano. Nunca se
             * devuelve un empate cuando ya hay ganador.
             */
            if ($engineScoreA === $engineScoreB) {

                if ($pointsForA > $pointsForB) {
                    $engineScoreA++;
                } else {
                    $engineScoreB++;
                }
            }
        }

        return [
            'runtime' =>
            $runtime,

            'series' =>
            $series,

            'completed' =>
            $completed,

            'engine_score_a' =>
            $engineScoreA,

            'engine_score_b' =>
            $engineScoreB,
        ];
    }

    public function participantMetrics(
        array $runtime,
        string $participantId
    ): array {
        $metrics = [
            'games_played' =>
            0,

            'game_wins' =>
            0,

            'game_losses' =>
            0,

            'game_draws' =>
            0,

            'game_difference' =>
            0,

            'score_for' =>
            0,

            'score_against' =>
            0,

            'score_difference' =>
            0,
        ];

        foreach (
            $runtime['series'] ?? []
            as
            $series
        ) {
            $isA =
                ($series['participant_a_id'] ?? null)
                ===
                $participantId;

            $isB =
                ($series['participant_b_id'] ?? null)
                ===
                $participantId;

            if (
                ! $isA
                &&
                ! $isB
            ) {
                continue;
            }

            $wins =
                (int)
                (
                    $isA
                    ? $series['game_wins_a']
                    : $series['game_wins_b']
                );

            $losses =
                (int)
                (
                    $isA
                    ? $series['game_wins_b']
                    : $series['game_wins_a']
                );

            $scoreFor =
                (int)
                (
                    $isA
                    ? $series['score_for_a']
                    : $series['score_for_b']
                );

            $scoreAgainst =
                (int)
                (
                    $isA
                    ? $series['score_for_b']
                    : $series['score_for_a']
                );

            $metrics['games_played'] +=
                count(
                    $series['games'] ?? []
                );

            $metrics['game_wins'] +=
                $wins;

            $metrics['game_losses'] +=
                $losses;

            $metrics['game_draws'] +=
                (int)
                ($series['game_draws'] ?? 0);

            $metrics['score_for'] +=
                $scoreFor;

            $metrics['score_against'] +=
                $scoreAgainst;
        }

        $metrics['game_difference'] =
            $metrics['game_wins']
            -
            $metrics['game_losses'];

        $metrics['score_difference'] =
            $metrics['score_for']
            -
            $metrics['score_against'];

        return $metrics;
    }

    /**
     * @return array{0: string, 1: int, 2: int}
     */
    private function seriesConfiguration(
        array $match
    ): array {
        $format =
            strtoupper(
                trim(
                    (string)
                    (
                        $match['series_format']
                        ??
                        'BEST_OF'
                    )
                )
            );

        if (
            ! in_array(
                $format,
                [
                    'BEST_OF',
                    'FIXED_GAMES',
                ],
                true
            )
        ) {
            $this->fail(
                'El encuentro usa un formato de serie no soportado.'
            );
        }

        $bestOf =
            array_key_exists(
                'best_of',
                $match
            )
            &&
            $match['best_of'] !== null
            ? (int)
            $match['best_of']
            : 1;

        $fixedGames =
            array_key_exists(
                'fixed_games',
                $match
            )
            &&
            $match['fixed_games'] !== null
            ? (int)
            $match['fixed_games']
            : 1;

        if (
            $format === 'BEST_OF'
            &&
            ! in_array(
                $bestOf,
                self::BEST_OF_VALUES,
                true
            )
        ) {
            $this->fail(
                'Best of debe ser BO1, BO3, BO5, BO7 o BO9.'
            );
        }

        if (
            $format === 'FIXED_GAMES'
            &&
            (
                $fixedGames < 1
                ||
                $fixedGames > 99
            )
        ) {
            $this->fail(
                'La cantidad fija debe estar entre 1 y 99 juegos.'
            );
        }

        return [
            $format,
            $bestOf,
            $fixedGames,
        ];
    }

    private function newSeries(
        string $matchId,
        string $participantA,
        string $participantB,
        string $format,
        int $bestOf,
        int $fixedGames
    ): array {
        return $this->refreshProgress([
            'match_id' =>
            $matchId,

            'participant_a_id' =>
            $participantA,

            'participant_b_id' =>
            $participantB,

            'series_format' =>
            $format,

            'best_of' =>
            $bestOf,

            'fixed_games' =>
            $fixedGames,

            'games' =>
            [],

            'game_wins_a' =>
            0,

            'game_wins_b' =>
            0,

            'game_draws' =>
            0,

            'score_for_a' =>
            0,

            'score_for_b' =>
            0,

            /* Puntos reales del juego, si los registra */
            'points_for_a' =>
            0.0,

            'points_for_b' =>
            0.0,

            'decided_on_points' =>
            false,

            'status' =>
            'RUNNING',

            'tiebreak_required' =>
            false,

            'tiebreak_games' =>
            0,

            'winner_id' =>
            null,
        ]);
    }

    private function assertSeriesCompatibility(
        array $series,
        string $participantA,
        string $participantB,
        string $format,
        int $bestOf,
        int $fixedGames
    ): void {
        if (
            ($series['participant_a_id'] ?? null)
            !==
            $participantA
            ||
            ($series['participant_b_id'] ?? null)
            !==
            $participantB
        ) {
            $this->fail(
                'La serie ya no coincide con los participantes del encuentro.'
            );
        }

        if (
            strtoupper(
                (string)
                ($series['series_format'] ?? '')
            )
            !==
            $format
        ) {
            $this->fail(
                'La configuración de la serie cambió durante la ejecución.'
            );
        }

        if (
            $format === 'BEST_OF'
            &&
            (int)
            ($series['best_of'] ?? 0)
            !==
            $bestOf
        ) {
            $this->fail(
                'El Best of de la serie cambió durante la ejecución.'
            );
        }

        if (
            $format === 'FIXED_GAMES'
            &&
            (int)
            ($series['fixed_games'] ?? 0)
            !==
            $fixedGames
        ) {
            $this->fail(
                'La cantidad fija de la serie cambió durante la ejecución.'
            );
        }
    }

    private function refreshProgress(
        array $series
    ): array {
        $format =
            strtoupper(
                (string)
                ($series['series_format'] ?? 'BEST_OF')
            );

        $bestOf =
            (int)
            ($series['best_of'] ?? 1);

        $fixedGames =
            (int)
            ($series['fixed_games'] ?? 1);

        $gamesPlayed =
            count(
                $series['games'] ?? []
            );

        $completed =
            ($series['status'] ?? null)
            ===
            'COMPLETED';

        $series['games_played'] =
            $gamesPlayed;

        $series['series_score_a'] =
            (int)
            ($series['game_wins_a'] ?? 0);

        $series['series_score_b'] =
            (int)
            ($series['game_wins_b'] ?? 0);

        $series['wins_required'] =
            $format === 'BEST_OF'
            ? intdiv(
                $bestOf,
                2
            )
            +
            1
            : null;

        $series['nominal_games'] =
            $format === 'BEST_OF'
            ? $bestOf
            : $fixedGames;

        $series['nominal_games_remaining'] =
            $completed
            ? 0
            : max(
                0,
                $series['nominal_games']
                    -
                    $gamesPlayed
            );

        $series['next_game_number'] =
            $completed
            ? null
            : $gamesPlayed + 1;

        $series['tiebreak_games'] =
            (int)
            ($series['tiebreak_games'] ?? 0);

        return $series;
    }

    private function findMatch(
        array $runtime,
        string $matchId
    ): array {
        foreach (
            $runtime['rounds'] ?? []
            as
            $round
        ) {
            foreach (
                $round['matches'] ?? []
                as
                $match
            ) {
                if (
                    ($match['id'] ?? null)
                    ===
                    $matchId
                ) {
                    return $match;
                }
            }
        }

        $this->fail(
            'El encuentro solicitado no existe en el Runtime.'
        );
    }

    private function fail(
        string $message
    ): never {
        throw ValidationException::withMessages([
            'match_id' => [
                $message,
            ],
        ]);
    }
}
