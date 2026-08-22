<?php

namespace App\Services\Rewards;

use App\Models\TournamentInstance;
use App\Models\TournamentInstanceMatch;
use App\Models\TournamentInstanceParticipant;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| TournamentPlacementResolver
|--------------------------------------------------------------------------
|
| Quién quedó primero, segundo, tercero.
|
| El proyector de la Fase 8 solo marca placement = 1 para el campeón, y lo
| hace a propósito: "solo se afirma la posición cuando es indiscutible".
| Esa cautela era correcta mientras la posición solo se mostraba. Deja de
| serlo en cuanto una recompensa dice "2.º puesto → +0.3", porque entonces
| alguien tiene que decidirlo.
|
| Se decide así, de lo más firme a lo más aproximado:
|
|   1.º  el campeón que ya marcó el proyector.
|   2.º  quien perdió la última batalla que ganó el campeón. Es exacto en
|        eliminación directa, que es donde la distinción importa.
|   3.º+ ordenados por profundidad alcanzada, puntos, victorias y seed.
|
| Los dos primeros puestos son exactos; del tercero en adelante es la
| mejor ordenación posible con lo que el motor registró. Se documenta
| porque una recompensa de tercer puesto en una liguilla es una decisión
| de criterio, no un hecho.
|
*/

class TournamentPlacementResolver
{
    /**
     * Calcula y guarda las posiciones finales.
     *
     * @return Collection<int, TournamentInstanceParticipant> ordenada por posición
     */
    public function resolve(
        TournamentInstance $instance
    ): Collection {

        $participants =
            $instance->participants()->get();

        if ($participants->isEmpty()) {
            return $participants;
        }

        $championKey =
            $participants
            ->firstWhere('outcome', 'CHAMPION')
            ?->runtime_key;

        $runnerUpKey =
            $championKey
            ? $this->runnerUpKey($instance, $championKey)
            : null;

        $ordered =
            $participants
            ->sortBy([

                /* El campeón, primero */
                fn($a, $b) =>
                ($a->runtime_key === $championKey ? 0 : 1)
                    <=> ($b->runtime_key === $championKey ? 0 : 1),

                /* Su víctima en la última batalla, segundo */
                fn($a, $b) =>
                ($a->runtime_key === $runnerUpKey ? 0 : 1)
                    <=> ($b->runtime_key === $runnerUpKey ? 0 : 1),

                /* Lo lejos que llegó */
                fn($a, $b) =>
                (int) $b->round_reached <=> (int) $a->round_reached,

                fn($a, $b) =>
                (int) $b->points <=> (int) $a->points,

                fn($a, $b) =>
                (int) $b->wins <=> (int) $a->wins,

                fn($a, $b) =>
                (int) $a->seed <=> (int) $b->seed,
            ])
            ->values();

        foreach ($ordered as $index => $participant) {

            $placement = $index + 1;

            if ((int) $participant->placement === $placement) {
                continue;
            }

            $participant->forceFill([
                'placement' => $placement,
            ])->save();
        }

        return $ordered;
    }

    /**
     * Quién perdió la última batalla que ganó el campeón.
     */
    private function runnerUpKey(
        TournamentInstance $instance,
        string $championKey
    ): ?string {

        $finalMatch =
            TournamentInstanceMatch::query()
            ->where('tournament_instance_id', $instance->id)
            ->where('status', 'COMPLETED')
            ->where('winner_key', $championKey)
            ->whereNotNull('loser_key')
            ->orderByDesc('round_number')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->first();

        return $finalMatch?->loser_key;
    }
}
