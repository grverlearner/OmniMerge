<?php

namespace App\Services\Tournaments\RoundRobin;

use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| RoundRobinSeedRuleResolver
|--------------------------------------------------------------------------
|
| Qué puestos de la parrilla inicial reclama cada puerta de entrada.
|
| En una liga todos se enfrentan a todos, así que una puerta no puede
| decidir QUIÉN pasa —pasan todos— ni CUÁNTOS juegan. Lo único que le queda
| por decidir, y no es poco, es DÓNDE ENTRA cada uno: qué números de la
| parrilla ocupa la gente que llega por ella.
|
| Eso cambia el calendario de verdad. El puesto 1 abre contra el último y
| el 2 contra el penúltimo, así que meter a alguien por la puerta de
| cabezas de serie o por la de repesca le cambia el rival de la primera
| jornada, y las siguientes.
|
| No se confunde con una puerta de SALIDA: la salida habla del puesto
| FINAL, cuando ya hay clasificación. Esta habla del puesto INICIAL, que
| existe antes de jugar nada.
|
| Vive aparte porque lo usan dos sitios que no se pueden permitir
| discrepar: la Super Edición al dibujar la parrilla, y RoundRobinLabEngine
| al sembrar de verdad cuando la fase corre.
|
*/
class RoundRobinSeedRuleResolver
{
    public const TYPES = [
        'FIRST_N' => [
            'label' => 'Los primeros N',
            'hint' => 'Ocupan los primeros puestos de la parrilla.',
            'needs' => ['count'],
        ],

        'LAST_N' => [
            'label' => 'Los últimos N',
            'hint' => 'Ocupan los últimos puestos de la parrilla.',
            'needs' => ['count'],
        ],

        'RANGE' => [
            'label' => 'Un tramo de puestos',
            'hint' => 'Del puesto X al Y, ambos incluidos.',
            'needs' => ['from', 'to'],
        ],

        'POSITION' => [
            'label' => 'Un puesto concreto',
            'hint' => 'Un solo número de la parrilla.',
            'needs' => ['from'],
        ],

        'REMAINING' => [
            'label' => 'Los puestos que sobren',
            'hint' => 'Recoge lo que no haya reclamado ninguna otra puerta.',
            'needs' => [],
        ],
    ];

    /*
     * Lee la regla guardada de una puerta.
     *
     * Vive en `settings` y no en columnas propias porque es vocabulario de
     * un solo motor: una puerta de Fase de grupos apunta a un grupo, y una
     * de Eliminación Directa a un slot del cuadro. Darle columnas a cada
     * una llenaría la tabla de campos nulos.
     */
    public function ruleOf($gate): array
    {
        $rule = $gate->settings['seed_rule'] ?? null;

        if (! is_array($rule) || ! isset(self::TYPES[$rule['type'] ?? ''])) {
            return ['type' => 'REMAINING', 'count' => null, 'from' => null, 'to' => null];
        }

        return [
            'type' => $rule['type'],
            'count' => isset($rule['count']) ? (int) $rule['count'] : null,
            'from' => isset($rule['from']) ? (int) $rule['from'] : null,
            'to' => isset($rule['to']) ? (int) $rule['to'] : null,
        ];
    }

    /*
     * Cómo se lee una regla en una línea.
     */
    public function summarize(array $rule, int $participants): string
    {
        return match ($rule['type']) {
            'FIRST_N' => 'puestos 1–' . min((int) $rule['count'], $participants),

            'LAST_N' => 'puestos '
                . max(1, $participants - (int) $rule['count'] + 1)
                . '–' . $participants,

            'RANGE' => 'puestos ' . $rule['from'] . '–' . $rule['to'],

            'POSITION' => 'puesto ' . $rule['from'],

            default => 'los que sobren',
        };
    }

    /*
     * El reparto completo de la parrilla.
     *
     * Devuelve, para N participantes y una lista ordenada de puertas:
     *
     *   assignments   [indice de puerta => [puestos]]
     *   seedMap       [puesto => indice de puerta]
     *   conflicts     puestos que reclama mas de una puerta
     *   orphans       puestos que no reclama nadie
     *
     * Los conflictos NO se resuelven en silencio: gana la primera puerta y
     * el choque se devuelve para que la pantalla lo enseñe. Repartir a
     * escondidas produciría una parrilla que nadie pidió.
     */
    public function resolve(
        int $participants,
        Collection $gates
    ): array {

        $assignments = [];
        $seedMap = [];
        $conflicts = [];

        $remainingGates = [];

        foreach ($gates->values() as $index => $gate) {

            $rule = $this->ruleOf($gate);

            if ($rule['type'] === 'REMAINING') {
                $remainingGates[] = $index;
                $assignments[$index] = [];

                continue;
            }

            $seeds = $this->seedsFor($rule, $participants);

            $assignments[$index] = [];

            foreach ($seeds as $seed) {

                if (isset($seedMap[$seed])) {
                    $conflicts[] = $seed;

                    continue;
                }

                $seedMap[$seed] = $index;
                $assignments[$index][] = $seed;
            }
        }

        /*
         * Lo que sobra se reparte entre las puertas de resto, en orden y a
         * partes iguales. Sin ninguna, sobra y ya: la parrilla se completa
         * con quien no entró por puerta.
         */
        $orphans = [];

        for ($seed = 1; $seed <= $participants; $seed++) {
            if (! isset($seedMap[$seed])) {
                $orphans[] = $seed;
            }
        }

        if ($remainingGates !== [] && $orphans !== []) {

            foreach ($orphans as $position => $seed) {

                $index = $remainingGates[$position % count($remainingGates)];

                $seedMap[$seed] = $index;
                $assignments[$index][] = $seed;
            }

            foreach ($remainingGates as $index) {
                sort($assignments[$index]);
            }

            $orphans = [];
        }

        return [
            'assignments' => $assignments,
            'seed_map' => $seedMap,
            'conflicts' => array_values(array_unique($conflicts)),
            'orphans' => $orphans,
        ];
    }

    /**
     * @return array<int,int>
     */
    private function seedsFor(array $rule, int $participants): array
    {
        [$from, $to] = match ($rule['type']) {

            'FIRST_N' => [1, min((int) $rule['count'], $participants)],

            'LAST_N' => [
                max(1, $participants - (int) $rule['count'] + 1),
                $participants,
            ],

            'RANGE' => [(int) $rule['from'], (int) $rule['to']],

            'POSITION' => [(int) $rule['from'], (int) $rule['from']],

            default => [0, -1],
        };

        if ($from < 1 || $to < $from) {
            return [];
        }

        return range($from, min($to, $participants));
    }

    /*
     * El orden inicial que producen las puertas.
     *
     * Los que llegan se van colocando en los puestos que reclama cada
     * puerta, en el orden de las puertas. Es lo que ejecuta el modo
     * BY_GATE, tanto en el preview como en el motor.
     *
     * @param  array<int,mixed>  $arrivals  en su orden de llegada
     * @return array<int,mixed>  indexado por puesto - 1
     */
    public function seatArrivals(
        array $arrivals,
        Collection $gates
    ): array {

        $participants = count($arrivals);

        $resolution = $this->resolve($participants, $gates);

        $grid = array_fill(0, $participants, null);

        $queue = array_values($arrivals);

        /*
         * Puerta por puerta y puesto por puesto: el primero que llega ocupa
         * el primer puesto que reclama la primera puerta.
         */
        foreach ($resolution['assignments'] as $seeds) {

            foreach ($seeds as $seed) {

                if ($queue === []) {
                    break 2;
                }

                $grid[$seed - 1] = array_shift($queue);
            }
        }

        /*
         * Sin puertas suficientes, el resto conserva su orden de llegada en
         * los huecos libres.
         */
        for ($i = 0; $i < $participants && $queue !== []; $i++) {

            if ($grid[$i] === null) {
                $grid[$i] = array_shift($queue);
            }
        }

        return $grid;
    }
}
