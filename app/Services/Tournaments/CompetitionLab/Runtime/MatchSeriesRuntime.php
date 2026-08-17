<?php

namespace App\Services\Tournaments\CompetitionLab\Runtime;

use Illuminate\Validation\ValidationException;

final class MatchSeriesRuntime
{
    /**
     * Registra un juego sin cerrar el encuentro hasta que la serie realmente
     * haya terminado. La información vive fuera de rounds para sobrevivir a
     * motores que reconstruyen sus matches a partir de otra estructura.
     *
     * @return array{runtime: array, completed: bool, engine_score_a: int, engine_score_b: int}
     */
    public function submitGame(
        array $runtime,
        string $matchId,
        int $scoreA,
        int $scoreB,
        bool $requiresWinner = false
    ): array {
        if ($scoreA < 0 || $scoreB < 0) {
            $this->fail('Los scores de un juego no pueden ser negativos.');
        }

        $match = $this->findMatch($runtime, $matchId);

        if (($match['status'] ?? null) !== 'PENDING') {
            $this->fail('El encuentro no está pendiente o ya fue completado.');
        }

        $participantA = $match['participant_a_id'] ?? ($match['participant_ids'][0] ?? null);
        $participantB = $match['participant_b_id'] ?? ($match['participant_ids'][1] ?? null);

        if (! $participantA || ! $participantB) {
            $this->fail('La serie necesita dos participantes reales.');
        }

        $format = strtoupper((string) ($match['series_format'] ?? 'BEST_OF'));
        $bestOf = max(1, (int) ($match['best_of'] ?? 1));
        $fixedGames = max(1, (int) ($match['fixed_games'] ?? 1));

        $runtime['series'] ??= [];
        $series = $runtime['series'][$matchId] ?? [
            'match_id' => $matchId,
            'participant_a_id' => $participantA,
            'participant_b_id' => $participantB,
            'series_format' => $format,
            'best_of' => $bestOf,
            'fixed_games' => $fixedGames,
            'games' => [],
            'game_wins_a' => 0,
            'game_wins_b' => 0,
            'game_draws' => 0,
            'score_for_a' => 0,
            'score_for_b' => 0,
            'status' => 'RUNNING',
            'tiebreak_required' => false,
            'winner_id' => null,
        ];

        if (($series['status'] ?? null) === 'COMPLETED') {
            $this->fail('La serie ya fue completada.');
        }

        if ($requiresWinner && $scoreA === $scoreB) {
            $this->fail('Esta serie eliminatoria necesita un ganador en cada juego.');
        }

        $winnerId = null;
        if ($scoreA > $scoreB) {
            $series['game_wins_a']++;
            $winnerId = $participantA;
        } elseif ($scoreB > $scoreA) {
            $series['game_wins_b']++;
            $winnerId = $participantB;
        } else {
            $series['game_draws']++;
        }

        $series['score_for_a'] += $scoreA;
        $series['score_for_b'] += $scoreB;
        $series['games'][] = [
            'number' => count($series['games']) + 1,
            'score_a' => $scoreA,
            'score_b' => $scoreB,
            'winner_id' => $winnerId,
            'status' => 'COMPLETED',
        ];

        $completed = false;

        if ($format === 'FIXED_GAMES') {
            if (count($series['games']) >= $fixedGames) {
                if (
                    $requiresWinner
                    && $series['game_wins_a'] === $series['game_wins_b']
                ) {
                    // La cantidad fija ya se disputó, pero una eliminatoria no
                    // puede finalizar empatada. Los juegos posteriores actúan
                    // como desempate súbito hasta producir una ventaja real.
                    $series['tiebreak_required'] = true;
                } else {
                    $completed = true;
                }
            }
        } else {
            if ($bestOf === 1) {
                // BO1 es exactamente un juego. En motores que permiten empate,
                // un 0-0/1-1 debe cerrar el encuentro como empate, no dejarlo
                // eternamente pendiente esperando una victoria inexistente.
                $completed = true;
            } else {
                $winsNeeded = intdiv($bestOf, 2) + 1;
                $completed =
                    $series['game_wins_a'] >= $winsNeeded
                    || $series['game_wins_b'] >= $winsNeeded;
            }
        }

        if (
            $series['tiebreak_required']
            && $series['game_wins_a'] !== $series['game_wins_b']
        ) {
            $completed = true;
        }

        if ($completed) {
            $series['status'] = 'COMPLETED';
            $series['winner_id'] =
                $series['game_wins_a'] === $series['game_wins_b']
                ? null
                : (
                    $series['game_wins_a'] > $series['game_wins_b']
                    ? $participantA
                    : $participantB
                );
        }

        $runtime['series'][$matchId] = $series;

        // BO1 conserva el score que ya entendían los motores existentes. Para
        // series múltiples el resultado de la serie se expresa en juegos.
        $engineScoreA = $bestOf === 1 && $format !== 'FIXED_GAMES'
            ? $scoreA
            : (int) $series['game_wins_a'];
        $engineScoreB = $bestOf === 1 && $format !== 'FIXED_GAMES'
            ? $scoreB
            : (int) $series['game_wins_b'];

        return [
            'runtime' => $runtime,
            'completed' => $completed,
            'engine_score_a' => $engineScoreA,
            'engine_score_b' => $engineScoreB,
        ];
    }

    public function participantMetrics(
        array $runtime,
        string $participantId
    ): array {
        $metrics = [
            'game_wins' => 0,
            'game_losses' => 0,
            'game_draws' => 0,
            'game_difference' => 0,
        ];

        foreach ($runtime['series'] ?? [] as $series) {
            $isA = ($series['participant_a_id'] ?? null) === $participantId;
            $isB = ($series['participant_b_id'] ?? null) === $participantId;

            if (! $isA && ! $isB) {
                continue;
            }

            $wins = (int) ($isA ? $series['game_wins_a'] : $series['game_wins_b']);
            $losses = (int) ($isA ? $series['game_wins_b'] : $series['game_wins_a']);

            $metrics['game_wins'] += $wins;
            $metrics['game_losses'] += $losses;
            $metrics['game_draws'] += (int) ($series['game_draws'] ?? 0);
        }

        $metrics['game_difference'] =
            $metrics['game_wins'] - $metrics['game_losses'];

        return $metrics;
    }

    private function findMatch(
        array $runtime,
        string $matchId
    ): array {
        foreach ($runtime['rounds'] ?? [] as $round) {
            foreach ($round['matches'] ?? [] as $match) {
                if (($match['id'] ?? null) === $matchId) {
                    return $match;
                }
            }
        }

        $this->fail('El encuentro solicitado no existe en el Runtime.');
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'match_id' => [$message],
        ]);
    }
}
