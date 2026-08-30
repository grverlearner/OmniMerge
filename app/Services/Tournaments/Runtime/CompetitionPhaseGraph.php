<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentInstance;

/*
|--------------------------------------------------------------------------
| La forma del recorrido: qué va antes, qué va a la vez
|--------------------------------------------------------------------------
|
| La arena siempre trató una competición como una fila: fase 1, fase 2, fase
| 3. Con eso bastaba mientras los torneos fueran una fila.
|
| Un recorrido de verdad no lo es. Puede tener siete fases arrancando a la
| vez desde sus propias entradas, y una octava más adelante que las junta.
| Presentarlas como una fila obliga a mentir en dos sitios: la fase que va
| «después» de otra no es la siguiente de la lista, y decir «abrir la fase
| siguiente» cuando hay tres a la vez no significa nada.
|
| Aquí se lee la forma real, del SNAPSHOT —nunca de la plantilla viva, que
| cambiaría la forma de una partida en curso—, y de cada fase se sabe:
|
|   nivel        a qué altura del recorrido está; las del mismo nivel van a
|                la vez
|   depende_de   qué fases tienen que darle participantes
|   alimenta_a   a qué fases manda los suyos
|   espera_todo  si su entrada exige que TODAS las anteriores terminen, o le
|                basta con que llegue alguien
|
| Con eso la pantalla puede decir la verdad: «esta fase va a la vez que
| otras dos», o «para abrir la final faltan por terminar Grupos B y C».
|
*/
class CompetitionPhaseGraph
{
    /*
     * @return array<int,array<string,mixed>>  nodeId => forma
     */
    public function forCompetition(TournamentInstance $competition): array
    {
        $competition->loadMissing('snapshot');

        $raiz = $competition->snapshot?->snapshot;

        $nodos = [];
        $puertoDeNodo = [];

        foreach ((array) data_get($raiz, 'root.relations.graphNodes', []) as $nodo) {

            $nodeId = (int) data_get($nodo, 'attributes.id');

            if ($nodeId <= 0) {
                continue;
            }

            $nodos[$nodeId] = [
                'node_id' => $nodeId,
                'name' => data_get($nodo, 'attributes.name'),
                'level' => 1,
                'depends_on' => [],
                'feeds' => [],
                'waits_for_all' => false,
                'parallel_with' => [],
            ];

            foreach ((array) data_get($nodo, 'relations.entryPorts', []) as $puerto) {

                $portId = (int) data_get($puerto, 'attributes.id');

                if ($portId <= 0) {
                    continue;
                }

                $puertoDeNodo[$portId] = $nodeId;

                /*
                 * WAIT_ALL es la única política que de verdad retiene: las
                 * demás dejan pasar a quien vaya llegando.
                 */
                if (data_get($puerto, 'attributes.merge_policy') === 'WAIT_ALL') {
                    $nodos[$nodeId]['waits_for_all'] = true;
                }
            }
        }

        /* Quién alimenta a quién */
        foreach ((array) data_get($raiz, 'root.relations.graphConnections', []) as $conexion) {

            $atributos = (array) data_get($conexion, 'attributes', []);

            if (($atributos['target_type'] ?? null) !== 'ENTRY_PORT') {
                continue;
            }

            $origen = (int) ($atributos['source_node_id'] ?? 0);
            $destino = $puertoDeNodo[(int) ($atributos['target_entry_port_id'] ?? 0)] ?? null;

            if ($origen <= 0 || $destino === null || $origen === $destino) {
                continue;
            }

            if (! in_array($destino, $nodos[$origen]['feeds'], true)) {
                $nodos[$origen]['feeds'][] = $destino;
            }

            if (! in_array($origen, $nodos[$destino]['depends_on'], true)) {
                $nodos[$destino]['depends_on'][] = $origen;
            }
        }

        $nodos = $this->levels($nodos);

        /* Las que van a la vez: mismo nivel */
        foreach ($nodos as $nodeId => $nodo) {

            $nodos[$nodeId]['parallel_with'] =
                collect($nodos)
                ->filter(
                    fn ($otro) =>
                    $otro['node_id'] !== $nodeId
                        && $otro['level'] === $nodo['level']
                )
                ->pluck('node_id')
                ->values()
                ->all();
        }

        return $nodos;
    }

    /*
     * El nivel de cada fase: el camino más largo desde una entrada.
     *
     * Se usa el más LARGO y no el más corto a propósito. Una fase que recibe
     * de la primera ronda y también de la segunda no puede empezar hasta que
     * la segunda acabe, así que pertenece al nivel de después, no al de
     * antes.
     *
     * @param  array<int,array<string,mixed>>  $nodos
     * @return array<int,array<string,mixed>>
     */
    private function levels(array $nodos): array
    {
        /*
         * Recorrido por capas. El cortafuegos existe porque un grafo con un
         * ciclo —que el validador no debería dejar crear— colgaría el bucle,
         * y una pantalla que no responde es peor que un nivel mal puesto.
         */
        for ($vuelta = 0; $vuelta < count($nodos) + 1; $vuelta++) {

            $cambio = false;

            foreach ($nodos as $nodeId => $nodo) {

                $nivel = 1;

                foreach ($nodo['depends_on'] as $anterior) {
                    $nivel = max($nivel, ($nodos[$anterior]['level'] ?? 1) + 1);
                }

                if ($nivel !== $nodo['level']) {
                    $nodos[$nodeId]['level'] = $nivel;
                    $cambio = true;
                }
            }

            if (! $cambio) {
                break;
            }
        }

        return $nodos;
    }
}
