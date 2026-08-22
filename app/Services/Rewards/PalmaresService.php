<?php

namespace App\Services\Rewards;

use App\Models\TournamentInstanceParticipant;
use App\Models\UniverseEntity;
use App\Models\UniverseStatChange;
use App\Models\UniverseTrophyAward;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| PalmaresService
|--------------------------------------------------------------------------
|
| Lo que un competidor ha conseguido en su Universo.
|
| Todo derivado, nada almacenado: los títulos salen de las posiciones ya
| resueltas y los trofeos de las concesiones. Cambiar el criterio de
| posiciones o borrar una competición corrige el palmarés solo.
|
*/

class PalmaresService
{
    /**
     * Resumen de vitrina.
     */
    public function summary(UniverseEntity $entity): array
    {
        $row =
            TournamentInstanceParticipant::query()
            ->join(
                'tournament_instances',
                'tournament_instances.id',
                '=',
                'tournament_instance_participants.tournament_instance_id'
            )
            ->where(
                'tournament_instance_participants.universe_entity_id',
                $entity->id
            )
            ->where('tournament_instances.status', 'COMPLETED')
            ->selectRaw(
                'count(*) as tournaments,
                 sum(case when tournament_instance_participants.placement = 1 then 1 else 0 end) as first_places,
                 sum(case when tournament_instance_participants.placement = 2 then 1 else 0 end) as second_places,
                 sum(case when tournament_instance_participants.placement = 3 then 1 else 0 end) as third_places,
                 sum(tournament_instance_participants.wins) as wins,
                 sum(tournament_instance_participants.losses) as losses,
                 sum(tournament_instance_participants.draws) as draws'
            )
            ->first();

        $wins = (int) ($row->wins ?? 0);
        $losses = (int) ($row->losses ?? 0);
        $draws = (int) ($row->draws ?? 0);
        $decided = $wins + $draws + $losses;

        return [

            'tournaments' => (int) ($row->tournaments ?? 0),

            'first_places' => (int) ($row->first_places ?? 0),
            'second_places' => (int) ($row->second_places ?? 0),
            'third_places' => (int) ($row->third_places ?? 0),

            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,

            'win_rate' =>
            $decided > 0
                ? round($wins * 100 / $decided, 1)
                : 0.0,

            'trophies' =>
            $entity->trophyAwards()->count(),
        ];
    }

    /**
     * Los podios, con su competición y su temporada.
     */
    public function podiums(UniverseEntity $entity): Collection
    {
        return TournamentInstanceParticipant::query()
            ->where('universe_entity_id', $entity->id)
            ->whereNotNull('placement')
            ->where('placement', '<=', 3)
            ->with([
                'tournamentInstance.season',
                'tournamentInstance.universeTournament',
            ])
            ->get()
            ->filter(
                fn($participation) =>
                $participation->tournamentInstance?->status === 'COMPLETED'
            )
            ->sortByDesc(
                fn($participation) =>
                $participation->tournamentInstance?->completed_at
            )
            ->values();
    }

    public function trophies(UniverseEntity $entity): Collection
    {
        return UniverseTrophyAward::query()
            ->where('universe_entity_id', $entity->id)
            ->with([
                'trophy',
                'tournamentInstance',
                'season',
            ])
            ->orderByDesc('awarded_at')
            ->get();
    }

    /**
     * Historial de progresión: qué cambió, cuánto y por qué.
     */
    public function statHistory(
        UniverseEntity $entity,
        ?string $gameKey = null
    ): Collection {

        return UniverseStatChange::query()
            ->where('universe_entity_id', $entity->id)
            ->when(
                $gameKey,
                fn($query) => $query->where('game_key', $gameKey)
            )
            ->with([
                'tournamentInstance',
                'season',
                'reward.trophy',
            ])
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    /**
     * Cómo evolucionó una stat temporada a temporada.
     *
     * Se reconstruye desde los cambios registrados, que es la única
     * fuente que sabe CUÁNDO pasó cada cosa. El valor actual sigue
     * viviendo en las Game Stats.
     *
     * @return Collection<int, array>
     */
    public function progressionBySeason(
        UniverseEntity $entity,
        string $gameKey,
        string $statKey
    ): Collection {

        return UniverseStatChange::query()
            ->where('universe_entity_id', $entity->id)
            ->where('game_key', $gameKey)
            ->where('stat_key', $statKey)
            ->with('season')
            ->orderBy('id')
            ->get()
            ->groupBy(
                fn($change) =>
                $change->universe_season_id ?? 0
            )
            ->map(
                fn(Collection $changes) => [

                    'season' =>
                    $changes->first()->season,

                    'value_before' =>
                    (float) $changes->first()->value_before,

                    'value_after' =>
                    (float) $changes->last()->value_after,

                    'delta' =>
                    round(
                        (float) $changes->last()->value_after
                            - (float) $changes->first()->value_before,
                        4
                    ),

                    'changes' => $changes->count(),
                ]
            )
            ->values();
    }
}
