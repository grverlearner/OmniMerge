<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\GameEncounterParticipant;
use App\Models\TournamentInstance;
use App\Services\Games\GameRegistry;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| PhasePointsService
|--------------------------------------------------------------------------
|
| Los puntos que un competidor hizo y encajó dentro de una fase.
|
| Por qué existe
| --------------
| A la serie se le entrega 1-0 —MatchSeriesRuntime recibe enteros y un
| 7.82 se truncaría a 7, creando empates falsos—, así que la clasificación
| solo sabía de victorias y derrotas. En una liga eso trata igual a quien
| gana 8-1 que a quien gana 4-3, y no es lo mismo: el primero dominó y el
| segundo sobrevivió.
|
| Los números reales sí quedaron guardados en game_encounters. Aquí se
| suman y se devuelven para enriquecer la tabla, SIN tocar el motor: los
| puntos de clasificación los sigue calculando la fase, y esto se muestra
| al lado como lo que es, el rendimiento bruto.
|
| Depende del juego: solo tiene sentido si el engine declara
| `tracks_points`. Un juego cuyo resultado sea "ganó / perdió" y nada más
| no muestra estas columnas.
|
*/

class PhasePointsService
{
    public function __construct(
        private readonly GameRegistry $registry
    ) {}

    /**
     * ¿Este juego tiene puntos que valga la pena mostrar?
     */
    public function tracksPoints(?string $gameKey): bool
    {
        return (bool) (
            $this->registry->definition($gameKey)['tracks_points'] ?? false
        );
    }

    public function label(?string $gameKey): string
    {
        return (string) (
            $this->registry->definition($gameKey)['points_label'] ?? 'Puntos'
        );
    }

    /**
     * Puntos a favor y en contra por competidor, dentro de una fase.
     *
     * Se acota por nodo porque un competidor puede pasar por varias fases
     * de la misma competición y sus números no deben mezclarse.
     *
     * @return Collection<int, array{for: float, against: float, difference: float}>
     *         indexada por universe_entity_id
     */
    public function forPhase(
        TournamentInstance $instance,
        ?string $nodeId = null
    ): Collection {

        if (! $this->tracksPoints($instance->game_key)) {
            return collect();
        }

        /*
         * Todo lo generado en los enfrentamientos de esta fase. Se traen
         * las filas y se agregan en memoria: calcular "lo que encajó" en
         * SQL exigiría un self-join por enfrentamiento, y un torneo no
         * tiene tantos.
         */
        $rows =
            GameEncounterParticipant::query()
            ->join(
                'game_encounters',
                'game_encounters.id',
                '=',
                'game_encounter_participants.game_encounter_id'
            )
            ->where(
                'game_encounters.tournament_instance_id',
                $instance->id
            )
            ->when(
                $nodeId,
                fn($query) => $query->where('game_encounters.node_id', $nodeId)
            )
            ->whereNotNull('game_encounter_participants.universe_entity_id')
            ->select([
                'game_encounter_participants.game_encounter_id as encounter_id',
                'game_encounter_participants.universe_entity_id as entity_id',
                'game_encounter_participants.value as value',
            ])
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $totals = [];

        foreach ($rows->groupBy('encounter_id') as $encounter) {

            $sum = $encounter->sum(fn($row) => (float) $row->value);

            foreach ($encounter as $row) {

                $entityId = (int) $row->entity_id;
                $value = (float) $row->value;

                $totals[$entityId] ??= ['for' => 0.0, 'against' => 0.0];

                $totals[$entityId]['for'] += $value;

                /*
                 * Lo que encajó es lo que sacaron los DEMÁS en ese mismo
                 * enfrentamiento. Con dos participantes es el rival; con
                 * tres o más, la suma de todos los rivales.
                 */
                $totals[$entityId]['against'] += $sum - $value;
            }
        }

        return collect($totals)
            ->map(
                fn(array $row) => [
                    'for' => round($row['for'], 2),
                    'against' => round($row['against'], 2),
                    'difference' => round($row['for'] - $row['against'], 2),
                ]
            );
    }
}
