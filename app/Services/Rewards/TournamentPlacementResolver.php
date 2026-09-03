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

                /*
                 * El puesto que se GANÓ en la última fase.
                 *
                 * Va antes que la profundidad, y no después, porque es un
                 * hecho y la profundidad es una aproximación. En una fase de
                 * grupos «hasta dónde llegó» mide cuántas jornadas tuvo tu
                 * grupo, no lo bien que jugaste: con grupos de tres y de
                 * cuatro, quien ganó su grupo de tres aparecía por debajo de
                 * quien fue último en uno de cuatro.
                 */
                fn($a, $b) =>
                ($jugado[$a->runtime_key] ?? PHP_INT_MAX)
                    <=> ($jugado[$b->runtime_key] ?? PHP_INT_MAX),

                /* Y, para quien no tenga puesto de fase, lo lejos que llegó */
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

        /*
         * Y ahora sí: el campeón es el primero de este orden.
         *
         * El proyector deja a varios como FINALIST cuando el terminal de
         * campeón recibe a más de uno, porque él no sabe compararlos. Aquí
         * sí, así que aquí se cierra: uno campeón y el resto, colocados.
         */
        $terminado = $instance->isClosed()
            || $instance->status === 'COMPLETED';

        foreach ($ordered as $index => $participant) {

            $placement = $index + 1;

            $outcome = $participant->outcome;

            if ($terminado && $outcome === 'FINALIST') {
                $outcome = $placement === 1 ? 'CHAMPION' : 'ELIMINATED';
            }

            if (
                (int) $participant->placement === $placement
                && $participant->outcome === $outcome
            ) {
                continue;
            }

            $participant->forceFill([
                'placement' => $placement,
                'outcome' => $outcome,
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

        /*
         * Si esa fase produjo una lista ÚNICA, manda ella.
         *
         * Es el caso de una fase de grupos: sus posiciones son de cada grupo
         * —1, 1, 1, 1, 2, 2…— y como orden de la fase no dicen nada. El motor
         * ya construyó la lista buena con el modo que la fase tenga elegido
         * (ver GroupStageOverallRanking), así que el puesto final del torneo
         * respeta esa elección en vez de contradecirla.
         */
        $general = data_get(
            $instance->state?->state,
            'nodes.' . $ultima->node_id . '.runtime.overall_standings'
        );

        if (is_array($general) && $general !== []) {

            $mapa = [];

            foreach ($general as $fila) {

                $clave = $fila['participant_id'] ?? null;

                if ($clave !== null) {
                    $mapa[(string) $clave] = (int) ($fila['overall_position'] ?? 0);
                }
            }

            if ($mapa !== []) {
                return $mapa;
            }
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
