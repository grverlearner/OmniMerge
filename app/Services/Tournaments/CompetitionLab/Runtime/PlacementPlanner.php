<?php

namespace App\Services\Tournaments\CompetitionLab\Runtime;

/*
|--------------------------------------------------------------------------
| Disputar los puestos
|--------------------------------------------------------------------------
|
| Un cuadro de 16 reparte plazas así: 1.º, 2.º, dos empatados en 3.º–4.º,
| cuatro en 5.º–8.º y ocho en 9.º–16.º. Esas bandas no son un defecto del
| motor: es que nadie jugó para separarlas.
|
| Cuando una fase pide un «#3 lugar», un «#7» o un «#13», lo que falta es
| precisamente eso —jugarlo—. Aquí vive la aritmética de esas batallas.
|
| El método es el mismo que usa cualquier torneo real para el tercer puesto,
| aplicado tantas veces como haga falta: se emparejan los empatados, los que
| ganan se quedan la mitad alta de la banda y los que pierden la baja.
| Repitiendo, la banda se parte hasta que el puesto pedido cae en un borde.
|
| Solo se parten las bandas que HACEN FALTA. Pedir el #13 dentro de una banda
| de 9.º–16.º obliga a separar 9-12 de 13-16, luego 13-14 de 15-16, y luego el
| 13 del 14: siete batallas. La banda 9.º–12.º no se toca, porque nadie pidió
| distinguir dentro de ella y jugar esas batallas sería hacer competir a la
| gente para nada.
|
| Esta clase no sabe qué es un encuentro. Cada motor genera sus batallas a su
| manera —el de grafo con encuentros y slots, el clásico con rondas y
| emparejamientos—; lo que comparten es dónde hay que cortar y quién queda en
| qué banda, que es justo lo que está aquí. Tenerlo dos veces garantizaba que
| un día dijeran cosas distintas.
|
*/
class PlacementPlanner
{
    /*
     * Qué puestos pide una fase, leído de sus salidas.
     *
     * @param  array<int|string,array<string,mixed>>  $exitDefinitions
     * @return array<int,array<string,mixed>>
     */
    public function wantedFromExits(array $exitDefinitions): array
    {
        $pedidos = [];

        foreach ($exitDefinitions as $exitId => $definition) {

            $selector = $definition['selector_type'] ?? null;

            if (! in_array($selector, ['RANK_POSITION', 'RANK_RANGE'], true)) {
                continue;
            }

            $desde = (int) ($definition['selector_from'] ?? 0);

            if ($desde <= 0) {
                continue;
            }

            $hasta = $selector === 'RANK_POSITION'
                ? $desde
                : (int) ($definition['selector_to'] ?? $desde);

            if ($hasta < $desde) {
                [$desde, $hasta] = [$hasta, $desde];
            }

            $pedidos[] = [
                'exit_id' => (int) $exitId,
                'exit_name' => $definition['name'] ?? 'Salida',
                'from' => $desde,
                'to' => $hasta,
                'label' => $this->label($desde, $hasta),
            ];
        }

        return $pedidos;
    }

    /*
     * Los puntos donde una banda NO puede seguir siendo una banda.
     *
     * Una salida «#3 lugar» necesita que el 3 empiece banda y que el 4
     * empiece otra; una «puestos 5-8», que empiece el 5 y que el 9 empiece la
     * siguiente.
     *
     * @param  array<int,array<string,mixed>>  $wanted
     * @return array<int,int>
     */
    public function cuts(array $wanted): array
    {
        $cortes = [];

        foreach ($wanted as $pedido) {
            $cortes[] = (int) $pedido['from'];
            $cortes[] = (int) $pedido['to'] + 1;
        }

        /* El corte en 1 sobra: toda clasificación empieza en 1 */
        $cortes = array_values(array_unique(array_filter(
            $cortes,
            fn ($corte) => $corte > 1
        )));

        sort($cortes);

        return $cortes;
    }

    /*
     * Las bandas de una clasificación: los grupos de empatados.
     *
     * @param  array<int,array<string,mixed>>  $standings
     * @return array<int,array<string,mixed>>
     */
    public function bands(array $standings): array
    {
        $bandas = [];

        foreach ($standings as $fila) {

            $desde = (int) ($fila['position_from'] ?? 0);
            $hasta = (int) ($fila['position_to'] ?? $desde);

            if ($desde <= 0) {
                continue;
            }

            $clave = $desde . '-' . $hasta;

            $bandas[$clave] ??= [
                'from' => $desde,
                'to' => $hasta,
                'ids' => [],
            ];

            $bandas[$clave]['ids'][] = $fila['participant_id'];
        }

        return $this->sorted(array_values($bandas));
    }

    /*
     * Una banda hay que partirla cuando algún corte cae dentro de ella.
     */
    public function needsSplit(array $band, array $cuts): bool
    {
        if (count($band['ids']) < 2) {
            return false;
        }

        foreach ($cuts as $corte) {
            if ((int) $band['from'] < $corte && $corte <= (int) $band['to']) {
                return true;
            }
        }

        return false;
    }

    /*
     * Cómo se reparte una banda en emparejamientos.
     *
     * Con un número impar de empatados, uno pasa a la mitad alta sin jugar.
     * Es el mismo BYE que usa cualquier cuadro cuando los participantes no
     * son potencia de dos, y es preferible a inventarle un rival.
     *
     * @return array{pairs: array<int,array<int,mixed>>, byes: array<int,mixed>}
     */
    public function pairings(array $band): array
    {
        $ids = array_values($band['ids']);
        $byes = [];

        if (count($ids) % 2 === 1) {
            $byes[] = array_pop($ids);
        }

        $parejas = [];

        for ($i = 0; $i < count($ids); $i += 2) {
            $parejas[] = [$ids[$i], $ids[$i + 1]];
        }

        return ['pairs' => $parejas, 'byes' => $byes];
    }

    /*
     * Sustituye una banda por sus dos mitades una vez jugadas.
     *
     * @param  array<int,array<string,mixed>>  $bands
     * @return array<int,array<string,mixed>>
     */
    public function replaceBand(
        array $bands,
        int $from,
        int $to,
        array $arriba,
        array $abajo
    ): array {

        $salida = [];

        foreach ($bands as $band) {

            if ((int) $band['from'] !== $from || (int) $band['to'] !== $to) {
                $salida[] = $band;

                continue;
            }

            $corte = $from + count($arriba) - 1;

            if ($arriba !== []) {
                $salida[] = ['from' => $from, 'to' => $corte, 'ids' => array_values($arriba)];
            }

            if ($abajo !== []) {
                $salida[] = ['from' => $corte + 1, 'to' => $to, 'ids' => array_values($abajo)];
            }
        }

        return $this->sorted($salida);
    }

    /*
     * La clasificación final, con los desempates ya aplicados.
     *
     * El cuadro sigue siendo quien reparte las bandas; esto solo cambia el
     * puesto de quien jugó para salir de una.
     *
     * @param  array<int,array<string,mixed>>  $base
     * @param  array<int,array<string,mixed>>  $bands
     * @return array<int,array<string,mixed>>
     */
    public function standings(array $base, array $bands): array
    {
        if ($bands === []) {
            return $base;
        }

        $porId = [];

        foreach ($base as $fila) {
            $porId[$fila['participant_id']] = $fila;
        }

        $salida = [];

        foreach ($bands as $banda) {

            foreach ($banda['ids'] as $participantId) {

                $fila = $porId[$participantId] ?? [
                    'participant_id' => $participantId,
                    'status' => 'ELIMINATED',
                ];

                $fila['position'] = (int) $banda['from'];
                $fila['position_from'] = (int) $banda['from'];
                $fila['position_to'] = (int) $banda['to'];

                $fila['placement_status'] = (int) $banda['from'] === (int) $banda['to']
                    ? 'RANKED'
                    : (
                        ($fila['status'] ?? null) === 'SURVIVOR'
                            ? 'UNRANKED_SURVIVOR'
                            : 'TIED_BAND'
                    );

                $salida[] = $fila;
            }
        }

        return $salida;
    }

    public function label(int $from, int $to): string
    {
        return $from === $to
            ? 'Puesto ' . $from . '.º'
            : 'Puestos ' . $from . '.º–' . $to . '.º';
    }

    /*
     * @param  array<int,array<string,mixed>>  $bands
     * @return array<int,array<string,mixed>>
     */
    private function sorted(array $bands): array
    {
        usort(
            $bands,
            fn ($izquierda, $derecha) =>
            (int) $izquierda['from'] <=> (int) $derecha['from']
        );

        return array_values($bands);
    }
}
