<?php

namespace App\Services\Games\Engines;

use App\Services\Games\Contracts\GameEngine;

/*
|--------------------------------------------------------------------------
| Highest Number
|--------------------------------------------------------------------------
|
| Primer juego de OmniMerge y referencia de cómo se escribe un engine.
|
| Cada competidor tiene un rango propio. En cada enfrentamiento genera un
| número dentro de ese rango y gana el número más alto.
|
| Es deliberadamente simple: su valor está en demostrar que el motor
| funciona de punta a punta, no en la profundidad del juego.
|
*/

class HighestNumberGameEngine implements GameEngine
{
    public const KEY = 'HIGHEST_NUMBER';

    private const DECIMALS = 2;

    public function definition(): array
    {
        return [

            'key' =>
            self::KEY,

            'name' =>
            'Highest Number',

            'tagline' =>
            'Cada participante genera un número dentro de su rango. Gana el más alto.',

            'description' =>
            'El juego más directo de OmniMerge. Cada competidor tiene un rango '
                . 'propio que define de qué es capaz: un rango alto y estrecho es '
                . 'un competidor fiable, uno amplio es impredecible. En cada '
                . 'enfrentamiento cada participante saca un número dentro de su '
                . 'rango y el más alto gana.',

            'icon' =>
            '🔢',

            'accent' =>
            'emerald',

            'type' =>
            'NUMERIC',

            'type_label' =>
            'Numérico',

            'minimum_participants' =>
            2,

            /* Sin techo: soporta A vs B vs C vs D y más. */
            'maximum_participants' =>
            null,

            'interaction' =>
            'SIMULTANEOUS',

            'interaction_label' =>
            'Todos en el mismo enfrentamiento',

            'win_condition' =>
            'Gana quien saque el número más alto del enfrentamiento.',

            'tiebreak' =>
            'Si dos competidores empatan en el número más alto, se repite la '
                . 'tirada solo entre ellos.',

            'allows_draws' =>
            false,

            'rules' => [

                'Cada competidor tiene un rango propio: un valor mínimo y uno máximo.',

                'En cada enfrentamiento genera un número al azar dentro de ese rango.',

                'El número más alto gana el enfrentamiento.',

                'Si hay empate en el primer puesto, los empatados repiten la tirada.',

                'El formato de la batalla (BO1, BO3, BO5…) decide cuántos '
                    . 'enfrentamientos hacen falta para ganarla.',
            ],

            /*
             * Esquema de estadísticas. La ficha del competidor y sus
             * formularios se dibujan desde aquí: otro juego declarará
             * fuerza, velocidad o lo que necesite, sin tocar las vistas.
             */
            'stats' => [

                [
                    'key' => 'min_value',
                    'label' => 'Rango mínimo',
                    'help' => 'Lo peor que puede sacar este competidor.',
                    'type' => 'decimal',
                    'min' => 0,
                    'max' => 9999,
                    'step' => 0.1,
                    'default' => 1.0,
                ],

                [
                    'key' => 'max_value',
                    'label' => 'Rango máximo',
                    'help' => 'Su techo. Debe ser mayor que el mínimo.',
                    'type' => 'decimal',
                    'min' => 0,
                    'max' => 9999,
                    'step' => 0.1,
                    'default' => 10.0,
                ],
            ],

            /*
             * Controles que el simulador debe ofrecer. La pantalla no sabe
             * qué es Highest Number: solo lee esto.
             */
            'controls' => [

                'per_participant' => true,
                'all' => true,

                'roll_label' => 'Generar',
                'all_label' => 'Generar todos',
                'value_label' => 'Número',
                'pending_label' => '?',
            ],
        ];
    }

    public function defaultStats(array $context = []): array
    {
        return [
            'min_value' => 1.0,
            'max_value' => 10.0,
        ];
    }

    public function normalizeStats(array $stats): array
    {
        $minimum =
            $this->decimal(
                $stats['min_value'] ?? 1.0
            );

        $maximum =
            $this->decimal(
                $stats['max_value'] ?? 10.0
            );

        $minimum = max(0.0, min(9999.0, $minimum));
        $maximum = max(0.0, min(9999.0, $maximum));

        /*
         * Un rango invertido es un error de captura, no un competidor
         * inválido: se endereza en vez de rechazarlo.
         */
        if ($maximum < $minimum) {
            [$minimum, $maximum] = [$maximum, $minimum];
        }

        /*
         * Un rango de amplitud cero convertiría cada enfrentamiento en un
         * empate perpetuo. Se le da el mínimo margen jugable.
         */
        if ($maximum === $minimum) {
            $maximum = min(9999.0, $minimum + 0.1);
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

        $factor =
            10 ** self::DECIMALS;

        $value =
            random_int(
                (int) round($stats['min_value'] * $factor),
                (int) round($stats['max_value'] * $factor)
            )
            / $factor;

        return [

            'value' =>
            (float) $value,

            'display' =>
            number_format(
                $value,
                self::DECIMALS,
                '.',
                ''
            ),

            'detail' => [

                'range' =>
                number_format($stats['min_value'], 1, '.', '')
                    . ' – '
                    . number_format($stats['max_value'], 1, '.', ''),
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
                $roll + [
                    'value' => (float) ($roll['value'] ?? 0),
                ]
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

        $best =
            (float) $ranking->first()['value'];

        $tied =
            $ranking
            ->filter(
                fn(array $roll) =>
                (float) $roll['value'] === $best
            )
            ->pluck('id')
            ->all();

        $isDraw =
            count($tied) > 1;

        /*
         * La posición empatada se comparte: dos primeros hacen que el
         * siguiente sea tercero, no segundo.
         */
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

                    return $roll + [
                        'position' => $position,
                    ];
                }
            )
            ->all();

        return [

            'ranking' =>
            $ranked,

            'winner_id' =>
            $isDraw
                ? null
                : ($ranking->first()['id'] ?? null),

            'tied_ids' =>
            $isDraw
                ? $tied
                : [],

            'is_draw' =>
            $isDraw,

            'summary' =>
            $isDraw
                ? 'Empate en ' . number_format($best, self::DECIMALS, '.', '') . '.'
                : ($ranking->first()['name'] ?? 'El líder')
                    . ' saca '
                    . number_format($best, self::DECIMALS, '.', '')
                    . '.',
        ];
    }

    private function decimal(mixed $value): float
    {
        return round(
            (float) $value,
            self::DECIMALS
        );
    }
}
