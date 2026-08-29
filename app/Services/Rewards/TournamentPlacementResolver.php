<?php

namespace App\Services\Rewards;

use App\Models\TournamentInstance;
use App\Models\TournamentInstanceMatch;
use App\Models\TournamentInstanceParticipant;
use App\Models\TournamentInstancePhaseParticipant;
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
|   3.º+ por profundidad alcanzada, LO QUE SE JUGÓ PARA DESEMPATAR, puntos,
|        victorias y seed.
|
| El tercer criterio es el importante. Una fase puede disputar sus puestos
| —jugar el 3.º contra el 4.º, el 7.º contra el 8.º— y cuando lo hace su
| clasificación deja de ser una banda de empatados y pasa a ser un orden
| ganado en el campo. Ese orden manda sobre los criterios de más abajo, que
| son aproximaciones: sería absurdo hacer jugar a dos por el 7.º puesto y
| después repartir el premio por número de victorias.
|
| Cuando ese desempate no se jugó, la fase deja a los dos en la misma
| posición, las claves empatan y se sigue como siempre. Es decir: se afina
| exactamente donde hay un hecho, y no se inventa nada donde no lo hay.
|
| Los dos primeros puestos son exactos; del tercero en adelante es lo
| jugado, y si no se jugó, la mejor ordenación posible con lo que el motor
| registró. Se documenta porque una recompensa de tercer puesto en una
| liguilla es una decisión de criterio, no un hecho.
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

        /*
         * El puesto que cada uno se ganó en la última fase, cuando esa fase
         * lo disputó. Ver la nota de arriba.
         */
        $jugado = $this->playedPositions($instance);

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

                /* Y lo que se jugó para separar a los que llegaron igual */
                fn($a, $b) =>
                ($jugado[$a->runtime_key] ?? PHP_INT_MAX)
                    <=> ($jugado[$b->runtime_key] ?? PHP_INT_MAX),

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

    /*
     * El puesto de cada competidor en la ÚLTIMA fase de la competición.
     *
     * Solo la última: una posición de la fase de grupos y una del cuadro no
     * son comparables, y mezclarlas ordenaría por casualidad. Quien no llegó
     * a esa fase no aparece, y cae de vuelta en los criterios de siempre.
     *
     * @return array<string,int>
     */
    private function playedPositions(TournamentInstance $instance): array
    {
        $ultima =
            $instance
            ->phases()
            ->orderByDesc('id')
            ->first();

        if (! $ultima) {
            return [];
        }

        return TournamentInstancePhaseParticipant::query()
            ->where('tournament_instance_phase_id', $ultima->id)
            ->whereNotNull('position')
            ->pluck('position', 'runtime_key')
            ->map(fn ($position) => (int) $position)
            ->all();
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
