<?php

namespace App\Services\Games\Engines;

use App\Services\Games\Contracts\GameEngine;

/*
|--------------------------------------------------------------------------
| Rounded Number
|--------------------------------------------------------------------------
|
| La variante entera de Highest Number.
|
| Cada competidor genera un número dentro de su rango, igual que allí,
| pero antes de comparar nada el resultado se REDONDEA: 3.2 vale 3, y 3.5
| vale 4. Se compite con enteros.
|
| Por qué es un juego distinto y no una opción de Highest Number
| --------------------------------------------------------------
| Porque cambia lo que significa competir. Con decimales el empate es casi
| imposible y el rango premia la precisión; con enteros los empates son
| frecuentes y buscados, y un rango de 0–3 solo tiene cuatro resultados
| posibles. Son dos juegos con estrategias distintas, y cada competidor
| lleva sus propias estadísticas en cada uno.
|
| Añadir este engine al registro es todo lo que hace falta: aparece solo
| en el catálogo, en la ficha del competidor, en el simulador y en la
| elección de juego de cada torneo.
|
*/

class RoundedNumberGameEngine implements GameEngine
{
    public const KEY = 'ROUNDED_NUMBER';

    public function definition(): array
    {
        return [

            'key' =>
            self::KEY,

            'name' =>
            'Rounded Number',

            'tagline' =>
            'Como Highest Number, pero el resultado se redondea a entero antes de comparar.',

            'description' =>
            'Cada competidor genera un número dentro de su rango y ese número '
                . 'se redondea al entero más cercano: 3.2 vale 3, 3.5 vale 4. '
                . 'Se compite con enteros, así que los empates son frecuentes y '
                . 'un rango estrecho pesa mucho más que en Highest Number.',

            'icon' =>
            '🎯',

            'accent' =>
            'amber',

            'type' =>
            'NUMERIC',

            'type_label' =>
            'Numérico entero',

            'minimum_participants' =>
            2,

            'maximum_participants' =>
            null,

            'interaction' =>
            'SIMULTANEOUS',

            'interaction_label' =>
            'Todos en el mismo enfrentamiento',

            'win_condition' =>
            'Gana quien saque el entero más alto del enfrentamiento.',

            'tiebreak' =>
            'Los empates son habituales al competir con enteros. Si el primer '
                . 'puesto queda empatado, los implicados repiten la tirada.',

            /*
             * Compitiendo con enteros el empate es frecuente y legitimo:
             * es parte del juego, no un accidente. Si se queda en empate o
             * se repite la tirada lo decide la FASE, no el juego: una liga
             * admite empates y una eliminacion directa no.
             */
            'allows_draws' =>
            true,

            /*
             * Lo que saca cada uno cuenta como PUNTOS, no solo como
             * victoria o derrota.
             *
             * En una liga no es lo mismo ganar 8-1 que 4-3: el primero
             * dominó y el segundo sobrevivió. Sin esto, la clasificación
             * los trata igual.
             *
             * Un juego futuro cuyo resultado sea "ganó / perdió" y nada
             * más pondría esto en false y su tabla no mostraría columnas
             * de puntos.
             */
            'tracks_points' =>
            true,

            'points_label' =>
            'Puntos',

            'rules' => [

                'Cada competidor tiene un rango propio: un valor mínimo y uno máximo.',

                'Genera un número al azar dentro de ese rango.',

                'Ese número se redondea al entero más cercano antes de comparar.',

                'Gana el entero más alto.',

                'Si hay empate en el primer puesto, los empatados repiten la tirada.',

                'El formato de la batalla decide cuántos enfrentamientos hacen falta.',
            ],

            'stats' => [

                [
                    'key' => 'min_value',
                    'label' => 'Rango mínimo',
                    'help' => 'Lo peor que puede sacar antes de redondear.',
                    'type' => 'decimal',
                    'min' => 0,
                    'max' => 9999,
                    'step' => 0.1,
                    'default' => 0.0,
                ],

                [
                    'key' => 'max_value',
                    'label' => 'Rango máximo',
                    'help' => 'Su techo antes de redondear. Debe ser mayor que el mínimo.',
                    'type' => 'decimal',
                    'min' => 0,
                    'max' => 9999,
                    'step' => 0.1,
                    'default' => 3.0,
                ],
            ],

            'controls' => [

                'per_participant' => true,
                'all' => true,

                'roll_label' => 'Generar',
                'all_label' => 'Generar todos',
                'value_label' => 'Entero',
                'pending_label' => '?',
            ],
        ];
    }

    public function defaultStats(array $context = []): array
    {
        return [
            'min_value' => 0.0,
            'max_value' => 3.0,
        ];
    }

    public function normalizeStats(array $stats): array
    {
        $minimum = round((float) ($stats['min_value'] ?? 0.0), 2);
        $maximum = round((float) ($stats['max_value'] ?? 3.0), 2);

        $minimum = max(0.0, min(9999.0, $minimum));
        $maximum = max(0.0, min(9999.0, $maximum));

        if ($maximum < $minimum) {
            [$minimum, $maximum] = [$maximum, $minimum];
        }

        /*
         * Un rango de amplitud cero convertiria cada enfrentamiento en el
         * mismo entero siempre. Se le da el minimo margen jugable.
         */
        if ($maximum === $minimum) {
            $maximum = min(9999.0, $minimum + 0.5);
        }

        return [
            'min_value' => $minimum,
            'max_value' => $maximum,
        ];
    }

    public function roll(array $participant, array $config = []): array
    {
        $stats =
            $this->normalizeStats(
                $participant['stats'] ?? []
            );

        /*
         * Se genera con dos decimales y DESPUES se redondea, que es lo que
         * distingue a este juego: el valor con el que se compite es el
         * entero, pero de donde sale importa.
         */
        $raw =
            random_int(
                (int) round($stats['min_value'] * 100),
                (int) round($stats['max_value'] * 100)
            )
            / 100;

        $rounded = (int) round($raw);

        return [

            'value' =>
            (float) $rounded,

            'display' =>
            (string) $rounded,

            'detail' => [

                'range' =>
                number_format($stats['min_value'], 1, '.', '')
                    . ' – '
                    . number_format($stats['max_value'], 1, '.', ''),

                /* De donde salio el entero: util para entender el resultado */
                'raw' =>
                number_format($raw, 2, '.', ''),
            ],

            'stats_used' =>
            $stats,
        ];
    }

    public function adjudicate(array $rolls, array $config = []): array
    {
        $ranking =
            collect($rolls)
            ->map(
                fn(array $roll) =>
                $roll + ['value' => (float) ($roll['value'] ?? 0)]
            )
            ->sortByDesc('value')
            ->values();

        if ($ranking->isEmpty()) {

            return [
                'ranking' => [],
                'winner_id' => null,
                'tied_ids' => [],
                'is_draw' => false,
                'summary' => 'Sin participantes.',
            ];
        }

        $best = (float) $ranking->first()['value'];

        $tied =
            $ranking
            ->filter(fn(array $roll) => (float) $roll['value'] === $best)
            ->pluck('id')
            ->all();

        $isDraw = count($tied) > 1;

        /* Posiciones compartidas: dos primeros hacen tercero al siguiente */
        $position = 0;
        $seen = 0;
        $previous = null;

        $ranked =
            $ranking
            ->map(
                function (array $roll) use (&$position, &$seen, &$previous) {

                    $seen++;

                    if ((float) $roll['value'] !== $previous) {
                        $position = $seen;
                        $previous = (float) $roll['value'];
                    }

                    return $roll + ['position' => $position];
                }
            )
            ->all();

        return [

            'ranking' => $ranked,

            'winner_id' =>
            $isDraw ? null : ($ranking->first()['id'] ?? null),

            'tied_ids' =>
            $isDraw ? $tied : [],

            'is_draw' => $isDraw,

            'summary' =>
            $isDraw
                ? 'Empate a ' . (int) $best . '.'
                : ($ranking->first()['name'] ?? 'El líder')
                    . ' saca ' . (int) $best . '.',
        ];
    }
}
