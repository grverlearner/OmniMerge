<?php

namespace App\Services\Tournaments\GroupStage;

/*
|--------------------------------------------------------------------------
| El orden general de una fase de grupos
|--------------------------------------------------------------------------
|
| Una fase de grupos produce varias tablas, no una. Pero casi siempre hace
| falta UNA sola lista —para repartir plazas, para sembrar el cuadro que
| viene, para entregar premios por puesto— y esa lista no es obvia: hay más
| de una forma legítima de construirla, y cada una da un ganador distinto.
|
| Dos ejemplos con los mismos resultados:
|
|   El 1.º del Grupo A hizo 7 puntos. El 2.º del Grupo B hizo 9.
|
|   Comparando a todos      el del Grupo B va delante: hizo más.
|   Comparando por puesto   el del Grupo A va delante: ganó su grupo.
|
| Ninguna es «la correcta». Por eso se elige, y por eso se enseña: quien
| monta el torneo tiene que ver qué lista produce su elección antes de
| jugarla.
|
| ---------------------------------------------------------------
|
| Esta clase no sabe comparar. El desempate —puntos, diferencia, anotados,
| enfrentamiento directo…— es del motor de la fase y ya está resuelto allí,
| con los criterios que cada plantilla tenga configurados. Aquí se recibe
| esa comparación y solo se decide EN QUÉ ORDEN se aplica.
|
*/
class GroupStageOverallRanking
{
    /*
     * Los modos, con lo que hay que saber para elegir.
     *
     * El texto vive aquí y no en la vista porque lo usan tres pantallas —el
     * editor, el torneo real y la competición— y tres copias del mismo
     * párrafo acaban diciendo tres cosas distintas.
     */
    public const MODES = [

        'GLOBAL' => [
            'label' => 'Todos contra todos',
            'short' => 'Global',
            'help' => 'Una sola tabla con todo el mundo, sin mirar de qué grupo '
                . 'viene. Manda el rendimiento: el 2.º de un grupo fuerte puede '
                . 'ir por delante del 1.º de uno flojo.',
        ],

        'BY_POSITION' => [
            'label' => 'Por puesto en su grupo',
            'short' => 'Por puesto',
            'help' => 'Primero todos los 1.º de cada grupo, luego todos los 2.º, '
                . 'y así. Dentro de cada bloque se ordenan por rendimiento. '
                . 'Ganar tu grupo vale más que hacer muchos puntos.',
        ],

        'BY_POSITION_GROUP_ORDER' => [
            'label' => 'Por puesto y orden de grupo',
            'short' => 'Por puesto y grupo',
            'help' => 'Como el anterior, pero dentro de cada bloque manda el '
                . 'orden de los grupos: 1.º de A, 1.º de B, 1.º de C… Es el que '
                . 'da un cuadro siempre igual, sin depender de los resultados.',
        ],
    ];

    public const DEFAULT = 'GLOBAL';

    public static function isValid(?string $mode): bool
    {
        return $mode !== null && array_key_exists($mode, self::MODES);
    }

    public static function label(?string $mode): string
    {
        return self::MODES[$mode]['label']
            ?? self::MODES[self::DEFAULT]['label'];
    }

    /*
     * La lista única.
     *
     * @param  array<int,array<string,mixed>>  $groups  cada uno con 'name' y
     *         'standings' YA ordenadas por el motor de la fase
     * @param  callable(array,array):int  $compare  el desempate del motor
     *
     * @return array<int,array<string,mixed>>
     */
    public function build(
        array $groups,
        string $mode,
        callable $compare
    ): array {

        $mode = self::isValid($mode) ? $mode : self::DEFAULT;

        $filas = [];

        foreach ($groups as $indice => $grupo) {

            foreach (array_values($grupo['standings'] ?? []) as $puesto => $fila) {

                $filas[] = $fila + [
                    'group_name' => $grupo['name'] ?? ('Grupo ' . ($indice + 1)),
                    'group_order' => $indice,
                    'group_position' => $puesto + 1,
                ];
            }
        }

        if ($filas === []) {
            return [];
        }

        $ordenadas = match ($mode) {

            'BY_POSITION' => $this->byPosition($filas, $compare),

            'BY_POSITION_GROUP_ORDER' => $this->byPosition(
                $filas,
                /* Dentro del bloque manda el grupo, no el rendimiento */
                fn (array $izquierda, array $derecha) =>
                $izquierda['group_order'] <=> $derecha['group_order']
            ),

            default => $this->global($filas, $compare),
        };

        /* El puesto general, que es lo que todo el mundo viene a leer */
        foreach ($ordenadas as $indice => &$fila) {
            $fila['overall_position'] = $indice + 1;
        }

        return $ordenadas;
    }

    /*
     * @param  array<int,array<string,mixed>>  $filas
     * @return array<int,array<string,mixed>>
     */
    private function global(array $filas, callable $compare): array
    {
        usort($filas, $compare);

        return $filas;
    }

    /*
     * Por bloques: todos los primeros, luego todos los segundos…
     *
     * @param  array<int,array<string,mixed>>  $filas
     * @return array<int,array<string,mixed>>
     */
    private function byPosition(array $filas, callable $compare): array
    {
        $bloques = [];

        foreach ($filas as $fila) {
            $bloques[(int) $fila['group_position']][] = $fila;
        }

        ksort($bloques);

        $salida = [];

        foreach ($bloques as $bloque) {

            usort($bloque, $compare);

            foreach ($bloque as $fila) {
                $salida[] = $fila;
            }
        }

        return $salida;
    }
}
