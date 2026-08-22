<?php

namespace App\Services\Tournaments\History;

use App\Models\UniverseEntity;
use App\Models\TournamentInstanceMatch;
use App\Models\TournamentInstanceParticipant;
use App\Models\TournamentInstancePhaseParticipant;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| EntityCompetitionStatsService
|--------------------------------------------------------------------------
|
| Responde "¿cómo le fue a esta Entidad?".
|
| Agrega SIEMPRE por entidad del Universo, nunca por entidad de
| Biblioteca: lo jugado en el Universo A no se mezcla con lo del
| Universo B, y la Biblioteca no acumula historial competitivo.
|
| Regla: si un dato no se puede calcular con fiabilidad, no se devuelve.
|
*/

class EntityCompetitionStatsService
{
    /*
    |--------------------------------------------------------------------------
    | Resumen general
    |--------------------------------------------------------------------------
    */

    public function summary(
        UniverseEntity $entity
    ): array {

        $participations =
            TournamentInstanceParticipant::query()
            ->where(
                'universe_entity_id',
                $entity->id
            )
            ->get();

        $wins = (int) $participations->sum('wins');
        $draws = (int) $participations->sum('draws');
        $losses = (int) $participations->sum('losses');

        $decided = $wins + $losses + $draws;

        return [

            'tournaments' =>
            $participations->count(),

            'championships' =>
            $participations
                ->where('outcome', 'CHAMPION')
                ->count(),

            'wins' => $wins,
            'draws' => $draws,
            'losses' => $losses,

            'matches' =>
            (int) $participations->sum('matches'),

            /*
             * Los empates cuentan como jugados, no como victorias.
             */
            'win_rate' =>
            $decided > 0
                ? round($wins / $decided * 100, 1)
                : null,

            'best_result' =>
            $this->bestResult($participations),
        ];
    }

    /*
     * Mejor resultado alcanzado, en orden de mérito.
     */
    private function bestResult(
        Collection $participations
    ): ?string {

        if ($participations->isEmpty()) {
            return null;
        }

        if (
            $participations
                ->where('outcome', 'CHAMPION')
                ->isNotEmpty()
        ) {
            return 'Campeón';
        }

        $qualified =
            $participations
            ->where('outcome', 'QUALIFIED')
            ->isNotEmpty();

        if ($qualified) {
            return 'Clasificado';
        }

        $round =
            $participations
            ->max('round_reached');

        return $round
            ? "Ronda {$round}"
            : 'Participante';
    }

    /*
    |--------------------------------------------------------------------------
    | Rendimiento por motor
    |--------------------------------------------------------------------------
    |
    | Se agrupa por el tipo de fase disputada: es la única forma honesta
    | de decir "en Round Robin rinde mejor que en eliminatorias".
    |
    */

    public function byEngine(
        UniverseEntity $entity
    ): Collection {

        return TournamentInstancePhaseParticipant::query()
            ->where(
                'tournament_instance_phase_participants.universe_entity_id',
                $entity->id
            )
            ->join(
                'tournament_instance_phases',
                'tournament_instance_phases.id',
                '=',
                'tournament_instance_phase_participants.tournament_instance_phase_id'
            )
            ->selectRaw(
                'tournament_instance_phases.phase_type as phase_type,
                 count(*) as phases,
                 sum(tournament_instance_phase_participants.wins) as wins,
                 sum(tournament_instance_phase_participants.draws) as draws,
                 sum(tournament_instance_phase_participants.losses) as losses,
                 sum(tournament_instance_phase_participants.matches) as matches,
                 sum(case when tournament_instance_phase_participants.status = \'ADVANCED\'
                     then 1 else 0 end) as advanced'
            )
            ->groupBy(
                'tournament_instance_phases.phase_type'
            )
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Historial cronológico
    |--------------------------------------------------------------------------
    */

    public function history(
        UniverseEntity $entity,
        int $limit = 20
    ): Collection {

        return TournamentInstanceParticipant::query()
            ->where(
                'universe_entity_id',
                $entity->id
            )
            ->with([
                'tournamentInstance.universe',
                'tournamentInstance.season',
            ])
            ->get()
            ->sortByDesc(
                fn($participation) =>
                $participation->tournamentInstance?->started_at
                    ?? $participation->created_at
            )
            ->take($limit)
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Rivales
    |--------------------------------------------------------------------------
    |
    | Un encuentro guarda las dos Entidades desnormalizadas, así que el
    | rival es "la otra columna". Se recorre una sola tabla.
    |
    */

    public function rivals(
        UniverseEntity $entity,
        int $limit = 10
    ): Collection {

        $matches =
            $this->matchesOf($entity)
            ->get();

        return $matches
            ->map(
                function ($match) use ($entity) {

                    $isA =
                        (int) $match->participant_a_universe_entity_id
                        === (int) $entity->id;

                    return [

                        'entity_id' =>
                        $isA
                            ? $match->participant_b_universe_entity_id
                            : $match->participant_a_universe_entity_id,

                        'name' =>
                        $isA
                            ? $match->participant_b_name
                            : $match->participant_a_name,

                        'won' =>
                        (int) $match->winner_universe_entity_id === (int) $entity->id,

                        'lost' =>
                        $match->winner_universe_entity_id !== null
                            && (int) $match->winner_universe_entity_id !== (int) $entity->id,

                        'drawn' =>
                        (bool) $match->is_draw,
                    ];
                }
            )
            ->filter(
                fn($row) =>
                $row['entity_id'] !== null
            )
            ->groupBy('entity_id')
            ->map(
                fn($rows, $entityId) => [

                    'entity_id' =>
                    (int) $entityId,

                    'name' =>
                    $rows->first()['name'],

                    'matches' =>
                    $rows->count(),

                    'wins' =>
                    $rows->where('won', true)->count(),

                    'losses' =>
                    $rows->where('lost', true)->count(),

                    'draws' =>
                    $rows->where('drawn', true)->count(),
                ]
            )
            ->sortByDesc('matches')
            ->take($limit)
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Head-to-head
    |--------------------------------------------------------------------------
    */

    /**
     * @param  ?int $excludeMatchId  encuentro a excluir. Lo usa la pantalla
     *                              de batalla: contar la batalla en curso
     *                              como precedente de si misma seria falso.
     */
    public function headToHead(
        UniverseEntity $left,
        UniverseEntity $right,
        ?int $excludeMatchId = null
    ): array {

        $matches =
            TournamentInstanceMatch::query()
            ->when(
                $excludeMatchId,
                fn($query, $id) => $query->whereKeyNot($id)
            )
            ->where(
                function ($query) use ($left, $right) {

                    $query
                        ->where(
                            fn($sub) =>
                            $sub
                                ->where('participant_a_universe_entity_id', $left->id)
                                ->where('participant_b_universe_entity_id', $right->id)
                        )
                        ->orWhere(
                            fn($sub) =>
                            $sub
                                ->where('participant_a_universe_entity_id', $right->id)
                                ->where('participant_b_universe_entity_id', $left->id)
                        );
                }
            )
            ->with([
                'tournamentInstance',
            ])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->get();

        return [

            'matches' =>
            $matches,

            'total' =>
            $matches->count(),

            'left_wins' =>
            $matches
                ->where('winner_universe_entity_id', $left->id)
                ->count(),

            'right_wins' =>
            $matches
                ->where('winner_universe_entity_id', $right->id)
                ->count(),

            'draws' =>
            $matches
                ->where('is_draw', true)
                ->count(),

            'last' =>
            $matches->first(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Racha más larga
    |--------------------------------------------------------------------------
    |
    | Orden cronológico estable: fecha del encuentro y, a igualdad, su id.
    | Los empates cortan la racha sin contar para ninguna.
    |
    */

    public function streaks(
        UniverseEntity $entity
    ): array {

        $matches =
            $this->matchesOf($entity)
            ->whereNotNull('completed_at')
            ->orderBy('completed_at')
            ->orderBy('id')
            ->get([
                'winner_universe_entity_id',
                'is_draw',
            ]);

        $best = 0;
        $worst = 0;
        $currentWin = 0;
        $currentLoss = 0;

        foreach ($matches as $match) {

            /*
             * Un encuentro sin ganador y sin empate no es una derrota:
             * es un BYE o un resultado que nunca se resolvió. Contarlo
             * inflaría las rachas con partidos que no se jugaron.
             */
            if (
                $match->winner_universe_entity_id === null
                &&
                ! $match->is_draw
            ) {
                continue;
            }

            if ($match->is_draw) {

                $currentWin = 0;
                $currentLoss = 0;

                continue;
            }

            if (
                (int) $match->winner_universe_entity_id
                === (int) $entity->id
            ) {
                $currentWin++;
                $currentLoss = 0;
            } else {
                $currentLoss++;
                $currentWin = 0;
            }

            $best = max($best, $currentWin);
            $worst = max($worst, $currentLoss);
        }

        return [
            'best_win_streak' => $best,
            'worst_loss_streak' => $worst,
        ];
    }

    private function matchesOf(
        UniverseEntity $entity
    ) {

        return TournamentInstanceMatch::query()
            ->where(
                fn($query) =>
                $query
                    ->where('participant_a_universe_entity_id', $entity->id)
                    ->orWhere('participant_b_universe_entity_id', $entity->id)
            )
            ->where(
                'status',
                'COMPLETED'
            );
    }
}
