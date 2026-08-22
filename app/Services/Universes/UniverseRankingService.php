<?php

namespace App\Services\Universes;

use App\Models\TournamentInstance;
use App\Models\TournamentInstanceParticipant;
use App\Models\Universe;
use App\Models\UniverseEntity;
use App\Support\Universes\UniverseSettings;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| UniverseRankingService
|--------------------------------------------------------------------------
|
| Clasificación del Universo.
|
| Se DERIVA de las participaciones ya proyectadas; no se almacena. Una
| tabla de ranking habría que mantenerla sincronizada con cada resultado,
| y bastaría un fallo para que mintiera. Derivarla la hace siempre
| correcta, y a la escala del proyecto es una sola consulta agregada.
|
| Es contextual por definición: agrega por universe_entity_id, así que
| la misma Entidad de Biblioteca puede ser #1 aquí y #18 en otro
| Universo sin que ninguno sepa del otro.
|
| Ver docs/md/28-Fase-10-Universo-Vivo.md
|
*/

class UniverseRankingService
{
    /*
    |--------------------------------------------------------------------------
    | Clasificación
    |--------------------------------------------------------------------------
    |
    | $seasonId acota a una temporada concreta; null = histórico.
    |
    */

    /**
     * @param  array{game_key?: ?string, universe_tournament_id?: ?int} $filters
     */
    public function ranking(
        Universe $universe,
        ?int $seasonId = null,
        array $filters = []
    ): Collection {

        $settings = new UniverseSettings($universe);

        $rows =
            TournamentInstanceParticipant::query()
            ->join(
                'tournament_instances',
                'tournament_instances.id',
                '=',
                'tournament_instance_participants.tournament_instance_id'
            )
            ->where(
                'tournament_instances.universe_id',
                $universe->id
            )
            ->when(
                $seasonId,

                fn($query) =>
                $query->where(
                    'tournament_instances.universe_season_id',
                    $seasonId
                )
            )
            /*
             * Ranking por juego (Fase 12): la misma entidad puede ser
             * dominante en un juego y mediocre en otro, y mezclarlos
             * ocultaria justo eso.
             */
            ->when(
                $filters['game_key'] ?? null,

                fn($query, $gameKey) =>
                $query->where(
                    'tournament_instances.game_key',
                    $gameKey
                )
            )
            /*
             * Ranking de un torneo concreto a lo largo de sus ediciones.
             */
            ->when(
                $filters['universe_tournament_id'] ?? null,

                fn($query, $tournamentId) =>
                $query->where(
                    'tournament_instances.universe_tournament_id',
                    $tournamentId
                )
            )
            ->whereNotNull(
                'tournament_instance_participants.universe_entity_id'
            )
            ->selectRaw(
                'tournament_instance_participants.universe_entity_id as universe_entity_id,
                 count(*) as tournaments,
                 sum(tournament_instance_participants.wins) as wins,
                 sum(tournament_instance_participants.draws) as draws,
                 sum(tournament_instance_participants.losses) as losses,
                 sum(tournament_instance_participants.matches) as matches,
                 sum(case when tournament_instance_participants.outcome = \'CHAMPION\'
                     then 1 else 0 end) as titles'
            )
            ->groupBy(
                'tournament_instance_participants.universe_entity_id'
            )
            ->get();

        $entities =
            UniverseEntity::query()
            ->whereIn('id', $rows->pluck('universe_entity_id'))
            ->get()
            ->keyBy('id');

        return $rows
            ->map(
                function ($row) use ($settings, $entities) {

                    $wins = (int) $row->wins;
                    $draws = (int) $row->draws;
                    $losses = (int) $row->losses;
                    $titles = (int) $row->titles;
                    $tournaments = (int) $row->tournaments;

                    $decided = $wins + $draws + $losses;

                    $points =
                        $titles * $settings->int('points_champion')
                        + $wins * $settings->int('points_win')
                        + $draws * $settings->int('points_draw')
                        + $losses * $settings->int('points_loss')
                        + $tournaments * $settings->int('points_participation');

                    return (object) [

                        'entity' =>
                        $entities->get($row->universe_entity_id),

                        'universe_entity_id' =>
                        (int) $row->universe_entity_id,

                        'tournaments' => $tournaments,
                        'titles' => $titles,
                        'wins' => $wins,
                        'draws' => $draws,
                        'losses' => $losses,
                        'matches' => (int) $row->matches,
                        'points' => $points,

                        'win_rate' =>
                        $decided > 0
                            ? round($wins / $decided * 100, 1)
                            : null,
                    ];
                }
            )
            /*
             * Una entidad retirada del Universo deja de aparecer, pero
             * su historial en las competiciones se conserva.
             */
            ->filter(
                fn($row) =>
                $row->entity !== null
            )
            ->sortByDesc('points')
            ->values()
            ->map(
                function ($row, $index) {

                    $row->position = $index + 1;

                    return $row;
                }
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Posición de una entidad concreta
    |--------------------------------------------------------------------------
    */

    public function positionOf(
        Universe $universe,
        UniverseEntity $entity
    ): ?object {

        return $this->ranking($universe)
            ->firstWhere(
                'universe_entity_id',
                $entity->id
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Campeones recientes
    |--------------------------------------------------------------------------
    */

    public function recentChampions(
        Universe $universe,
        int $limit = 5
    ): Collection {

        return TournamentInstanceParticipant::query()
            ->join(
                'tournament_instances',
                'tournament_instances.id',
                '=',
                'tournament_instance_participants.tournament_instance_id'
            )
            ->where(
                'tournament_instances.universe_id',
                $universe->id
            )
            ->where(
                'tournament_instance_participants.outcome',
                'CHAMPION'
            )
            ->orderByDesc('tournament_instances.completed_at')
            ->orderByDesc('tournament_instances.id')
            ->limit($limit)
            ->with([
                'universeEntity',
                'tournamentInstance.season',
            ])
            ->select('tournament_instance_participants.*')
            ->get();
    }
}
