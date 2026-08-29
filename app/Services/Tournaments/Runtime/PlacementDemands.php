<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentInstance;
use App\Services\Tournaments\CompetitionLab\Runtime\PlacementPlanner;

/*
|--------------------------------------------------------------------------
| Qué puestos hay que decidir, y por qué
|--------------------------------------------------------------------------
|
| Un cuadro no decide quién es tercero: dos pierden en semifinales y ahí
| acaban los dos. Para tener un tercero hay que jugarlo, y jugarlo cuesta
| batallas, así que solo se juegan los puestos que alguien pide.
|
| Pedirlos tiene dos formas, y las dos cuentan:
|
|   una SALIDA de la fase   «#3 lugar» — por ahí sale quien acabe tercero
|   un PREMIO por posición  «puesto 5.º → Medalla» — hay que saber quién es
|
| La segunda es la que faltaba. Un premio que entrega algo al 5.º está
| diciendo que el 5.º tiene que existir; sin jugarlo, ese premio o no se
| entrega o se le da a uno de los cuatro empatados elegido a dedo. Cuentan
| tanto los premios del TORNEO —que heredan todas sus ediciones— como los de
| la EDICIÓN, que solo existen en esta.
|
| ---------------------------------------------------------------
|
| A qué fase se le pide.
|
| Un puesto de premio es un puesto FINAL del torneo, y una fase solo puede
| decidir entre los suyos. Así que se le pide a las fases finales: aquellas
| cuyas salidas no alimentan a otra fase. En un torneo de una sola fase —lo
| habitual— es esa misma.
|
| Un premio de edición atado a una fase concreta se le pide a esa fase, que
| es exactamente lo que significa «quien gane los grupos se lleva esto».
|
| Todo se lee del SNAPSHOT, nunca de la plantilla viva: una competición en
| curso se juega con la forma que tenía al empezar.
|
*/
class PlacementDemands
{
    public function __construct(
        private readonly PlacementPlanner $placement,
    ) {
    }

    /*
     * Los puestos que cada nodo del grafo tiene que decidir.
     *
     * @return array<int,array<int,array<string,mixed>>>  nodeId => pedidos
     */
    public function forCompetition(TournamentInstance $competition): array
    {
        $porNodo = [];

        $nodos = $this->snapshotNodes($competition);

        /* 1. Lo que piden las salidas de cada fase */
        foreach ($nodos as $nodeId => $nodo) {

            foreach ($this->placement->wantedFromExits($nodo['exits']) as $pedido) {

                $porNodo[$nodeId][] = $pedido + [
                    'reason' => 'la salida «' . $pedido['exit_name'] . '»',
                ];
            }
        }

        /* 2. Lo que piden los premios */
        $finales = $this->finalNodeIds($nodos);

        foreach ($this->rewardDemands($competition) as $premio) {

            $destinos = $premio['node_id'] !== null
                ? [$premio['node_id']]
                : $finales;

            foreach ($destinos as $nodeId) {

                if (! isset($nodos[$nodeId])) {
                    continue;
                }

                $porNodo[$nodeId][] = [
                    'from' => $premio['position'],
                    'to' => $premio['position'],
                    'label' => $this->placement->label($premio['position'], $premio['position']),
                    'reason' => $premio['reason'],
                ];
            }
        }

        /* 3. Un mismo puesto pedido dos veces es un puesto, con dos motivos */
        foreach ($porNodo as $nodeId => $pedidos) {
            $porNodo[$nodeId] = $this->merge($pedidos);
        }

        return $porNodo;
    }

    /*
     * Los premios por posición, de las dos procedencias.
     *
     * @return array<int,array<string,mixed>>
     */
    private function rewardDemands(TournamentInstance $competition): array
    {
        $pedidos = [];

        $competition->loadMissing('universeTournament');

        $delTorneo =
            $competition->universeTournament
                ? $competition->universeTournament
                    ->rewards()
                    ->where('is_active', true)
                    ->where('trigger', 'POSITION')
                    ->get()
                : collect();

        foreach ($delTorneo as $premio) {

            $posicion = (int) $premio->threshold;

            if ($posicion < 1) {
                continue;
            }

            $pedidos[] = [
                'position' => $posicion,
                'node_id' => null,
                'reason' => 'el premio del torneo «'
                    . ($premio->label ?: $premio->condition_label) . '»',
            ];
        }

        $deLaEdicion =
            $competition
            ->rewards()
            ->where('is_active', true)
            ->where('trigger', 'POSITION')
            ->get();

        foreach ($deLaEdicion as $premio) {

            $posicion = (int) $premio->threshold;

            if ($posicion < 1) {
                continue;
            }

            $pedidos[] = [
                'position' => $posicion,
                'node_id' => $premio->node_id === null ? null : (int) $premio->node_id,
                'reason' => 'el premio de esta edición «'
                    . ($premio->label ?: $premio->condition_label) . '»',
            ];
        }

        return $pedidos;
    }

    /*
     * Las fases cuyas salidas no alimentan a ninguna otra: las que deciden
     * el resultado final.
     *
     * @param  array<int,array<string,mixed>>  $nodos
     * @return array<int,int>
     */
    private function finalNodeIds(array $nodos): array
    {
        $finales = [];

        foreach ($nodos as $nodeId => $nodo) {
            if (! $nodo['feeds_another']) {
                $finales[] = $nodeId;
            }
        }

        /*
         * Un grafo sin ninguna fase final no debería existir, pero si el
         * snapshot llegara raro es mejor pedirle el puesto a todas que a
         * ninguna: entregar el premio importa más que ahorrar batallas.
         */
        return $finales !== [] ? $finales : array_keys($nodos);
    }

    /*
     * Los nodos del grafo tal como estaban al empezar la competición, con
     * sus salidas y con si alimentan a otra fase.
     *
     * @return array<int,array<string,mixed>>
     */
    private function snapshotNodes(TournamentInstance $competition): array
    {
        $competition->loadMissing('snapshot');

        $raiz = $competition->snapshot?->snapshot;

        $nodos = [];

        foreach ((array) data_get($raiz, 'root.relations.graphNodes', []) as $nodo) {

            $nodeId = (int) data_get($nodo, 'attributes.id');

            if ($nodeId <= 0) {
                continue;
            }

            $salidas = [];

            foreach ((array) data_get($nodo, 'relations.phaseTemplate.relations.exits', []) as $salida) {

                $atributos = (array) data_get($salida, 'attributes', []);

                if ($atributos === []) {
                    continue;
                }

                $salidas[(int) ($atributos['id'] ?? 0)] = $atributos;
            }

            $nodos[$nodeId] = [
                'exits' => $salidas,
                'feeds_another' => false,
            ];
        }

        foreach ((array) data_get($raiz, 'root.relations.graphConnections', []) as $conexion) {

            $atributos = (array) data_get($conexion, 'attributes', []);

            $origen = (int) ($atributos['source_node_id'] ?? 0);

            if ($origen <= 0 || ! isset($nodos[$origen])) {
                continue;
            }

            if (($atributos['target_type'] ?? null) === 'ENTRY_PORT') {
                $nodos[$origen]['feeds_another'] = true;
            }
        }

        return $nodos;
    }

    /*
     * Un puesto pedido por una salida y por un premio sigue siendo un
     * puesto. Se juega una vez y se dicen los dos motivos.
     *
     * @param  array<int,array<string,mixed>>  $pedidos
     * @return array<int,array<string,mixed>>
     */
    private function merge(array $pedidos): array
    {
        $porRango = [];

        foreach ($pedidos as $pedido) {

            $clave = $pedido['from'] . '-' . $pedido['to'];

            if (! isset($porRango[$clave])) {

                $porRango[$clave] = $pedido + ['reasons' => []];

                unset($porRango[$clave]['reason']);
            }

            $motivo = $pedido['reason'] ?? null;

            if ($motivo !== null && ! in_array($motivo, $porRango[$clave]['reasons'], true)) {
                $porRango[$clave]['reasons'][] = $motivo;
            }
        }

        $salida = array_values($porRango);

        usort(
            $salida,
            fn ($izquierda, $derecha) =>
            [(int) $izquierda['from'], (int) $izquierda['to']]
            <=> [(int) $derecha['from'], (int) $derecha['to']]
        );

        return $salida;
    }
}
