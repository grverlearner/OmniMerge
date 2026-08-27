<?php

namespace App\Services\Tournaments\SingleElimination;

/*
|--------------------------------------------------------------------------
| SingleEliminationBracketPlanner
|--------------------------------------------------------------------------
|
| El cuadro, como arbol: que puesto se enfrenta a que puesto, ronda a ronda.
|
| Existe porque no habia nada que lo dijera. SingleEliminationBracketCalculator
| cuenta cuantas rondas y cuantos enfrentamientos salen, y el generador
| persiste huecos con una REGLA de asignacion ('SEEDED', 'POSITIONAL'...) que
| solo se resuelve al ejecutar. Ninguno de los dos sabe decir "el 1 abre
| contra el 8", que es justo lo que hace falta para dibujar un arbol.
|
| Trabaja con PUESTOS del cuadro, nunca con personas: el puesto 1 es el
| primer sembrado, y quien lo ocupe se decide fuera. Es la misma costura que
| en liga y en fase de grupos, y por eso cambiar el orden de entrada o
| barajar no recalcula nada.
|
| Solo duelos 1 contra 1. Los enfrentamientos de tres o mas existen en el
| modelo (encounter_profile = MULTI_COMPETITOR) pero no se dibujan aqui
| todavia.
|
*/
class SingleEliminationBracketPlanner
{
    /*
     * El orden clasico de un cuadro.
     *
     * Se construye doblando: en un cuadro de 2 es [1, 2]; para pasar al
     * siguiente tamano, cada puesto s se acompana de su espejo (2n+1-s).
     *
     *   2   1 2
     *   4   1 4 2 3
     *   8   1 8 4 5 2 7 3 6
     *
     * Asi el 1 y el 2 solo pueden cruzarse en la final, el 1 y el 4 en
     * semifinales, y cada mitad del cuadro queda equilibrada.
     *
     * @return array<int,int>
     */
    public function seededOrder(int $size): array
    {
        $order = [1, 2];

        while (count($order) < $size) {

            $next = count($order) * 2;

            $doubled = [];

            foreach ($order as $position) {
                $doubled[] = $position;
                $doubled[] = $next + 1 - $position;
            }

            $order = $doubled;
        }

        return array_slice($order, 0, $size);
    }

    /*
     * El reparto de puestos en la primera ronda, segun como se empareje.
     *
     * @return array<int,int>
     */
    public function firstRoundOrder(int $size, string $pairingMode): array
    {
        return match ($pairingMode) {

            /* 1-2, 3-4, 5-6...: se cruzan los vecinos */
            'SEQUENTIAL' => range(1, $size),

            /*
             * Aleatorio de verdad lo hace el motor al arrancar. Aqui se
             * dibuja el cuadro clasico para que la forma sea estable
             * mientras se configura; el editor baraja QUIEN ocupa cada
             * puesto, que es lo que se puede ver sin jugar.
             */
            'RANDOM' => $this->seededOrder($size),

            default => $this->seededOrder($size),
        };
    }

    /*
     * El cuadro entero.
     *
     * Devuelve las rondas con sus enfrentamientos. Cada lado de un
     * enfrentamiento es uno de estos tres:
     *
     *   ['type' => 'SEED',   'seed' => n]   un puesto del cuadro
     *   ['type' => 'BYE']                    nadie: el otro pasa directo
     *   ['type' => 'WINNER', 'from' => k]    quien gane el enfrentamiento k
     *
     * El 'from' apunta al indice GLOBAL del enfrentamiento, para que el
     * navegador pueda resolver quien ocupa cada hueco encadenando
     * resultados sin volver al servidor.
     *
     * Ademas del arbol devuelve tres cosas que el arbol por si solo no dice:
     *
     *   groups      que puestos quedan EMPATADOS y cuales estan decididos
     *   placements  los cuadros de clasificacion que se hayan activado
     *   branches    de que trozo del cuadro sale cada superviviente
     *
     * @param  array<int,string>  $placementGroups  claves de grupo a ordenar
     *
     * @return array{
     *     valid: bool,
     *     bracket_size: int,
     *     byes: int,
     *     rounds: array,
     *     groups: array,
     *     placements: array,
     *     branches: array
     * }
     */
    public function plan(
        int $participants,
        string $pairingMode = 'STANDARD_SEEDED',
        string $byeAssignment = 'TOP_SEEDS',
        int $targetSurvivors = 1,
        array $placementGroups = []
    ): array {

        if ($participants < 2) {
            return [
                'valid' => false,
                'bracket_size' => 0,
                'byes' => 0,
                'rounds' => [],
                'groups' => [],
                'placements' => [],
                'branches' => [],
            ];
        }

        $size = $this->nextPowerOfTwo($participants);

        $byes = $size - $participants;

        /*
         * Que puestos NO tienen a nadie detras. Con el reparto clasico los
         * descansos van a los mejores sembrados, que es lo estandar: quien
         * llega primero no juega la ronda inicial.
         */
        $byeSeeds = $this->byeSeeds($participants, $size, $byes, $byeAssignment);

        $order = $this->firstRoundOrder($size, $pairingMode);

        $rounds = [];
        $matchIndex = 0;

        /* ---- Primera ronda: puestos contra puestos ---- */

        $matches = [];

        for ($i = 0; $i < $size; $i += 2) {

            $a = $order[$i];
            $b = $order[$i + 1];

            $matches[] = [
                'index' => $matchIndex++,
                'a' => in_array($a, $byeSeeds, true)
                    ? ['type' => 'BYE']
                    : ['type' => 'SEED', 'seed' => $a],
                'b' => in_array($b, $byeSeeds, true)
                    ? ['type' => 'BYE']
                    : ['type' => 'SEED', 'seed' => $b],
            ];
        }

        $rounds[] = $matches;

        /* ---- Rondas siguientes: ganadores contra ganadores ---- */

        while (count(end($rounds)) > 1) {

            $previous = end($rounds);

            $matches = [];

            for ($i = 0; $i < count($previous); $i += 2) {
                $matches[] = [
                    'index' => $matchIndex++,
                    'a' => ['type' => 'WINNER', 'from' => $previous[$i]['index']],
                    'b' => ['type' => 'WINNER', 'from' => $previous[$i + 1]['index']],
                ];
            }

            $rounds[] = $matches;
        }

        /*
         * Terminar con mas de un superviviente significa no jugar las
         * ultimas rondas: con 2, la final no se disputa y los dos
         * finalistas salen de la fase.
         */
        $rounds = $this->trimForSurvivors($rounds, $targetSurvivors);

        $labelled = $this->label($rounds, $targetSurvivors);

        /*
         * Los grupos salen del cuadro ya recortado: donde se corte cambia
         * que puestos quedan empatados y cuales no existen siquiera.
         */
        $groups = $this->positionGroups($labelled, $targetSurvivors);

        $placements = [];

        foreach ($groups as $i => $group) {

            $enabled = ! $group['auto']
                && in_array($group['key'], $placementGroups, true);

            $groups[$i]['enabled'] = $enabled;

            if ($enabled) {
                $placements[] = $this->buildPlacement($group, $matchIndex);
            }
        }

        return [
            'valid' => true,
            'bracket_size' => $size,
            'byes' => $byes,
            'rounds' => $labelled,
            'groups' => $groups,
            'placements' => $placements,
            'branches' => $this->branches($labelled),
        ];
    }

    /*
     * @return array<int,int>
     */
    private function byeSeeds(
        int $participants,
        int $size,
        int $byes,
        string $assignment
    ): array {

        if ($byes < 1) {
            return [];
        }

        /*
         * Los puestos que sobran son siempre los ultimos del cuadro: con 6
         * participantes en un cuadro de 8, los puestos 7 y 8 no existen.
         * Lo que decide `bye_assignment` es a QUIEN le toca enfrentarse a
         * ese hueco, y con TOP_SEEDS son los primeros sembrados.
         */
        return range($participants + 1, $size);
    }

    /*
     * Recorta las ultimas rondas cuando la fase termina con varios
     * supervivientes.
     */
    private function trimForSurvivors(array $rounds, int $targetSurvivors): array
    {
        $target = max(1, $targetSurvivors);

        while ($target > 1 && $rounds !== []) {

            $last = end($rounds);

            /*
             * Los supervivientes son los ganadores de la ultima ronda que
             * se juega. Si esa ronda ya deja el numero pedido, se para.
             */
            if (count($last) >= $target) {
                break;
            }

            array_pop($rounds);
        }

        return $rounds;
    }

    /*
     * Nombres de ronda leyendo desde el final: la ultima es la final -o la
     * ronda decisiva, si la fase termina con varios supervivientes-.
     */
    private function label(array $rounds, int $targetSurvivors): array
    {
        $total = count($rounds);

        $out = [];

        foreach ($rounds as $index => $matches) {

            $fromEnd = $total - $index - 1;

            $out[] = [
                'number' => $index + 1,
                'label' => $this->roundLabel($fromEnd, count($matches), $targetSurvivors),
                'matches' => $matches,
            ];
        }

        return $out;
    }

    /*
     * El nombre sale de CUANTOS enfrentamientos tiene la ronda, no de su
     * distancia al final: recortando el cuadro para terminar con varios
     * supervivientes, la ultima ronda que se juega puede tener cuatro
     * partidos, y llamarla "semifinales" por estar la penultima seria
     * mentir.
     */
    private function roundLabel(int $fromEnd, int $matches, int $targetSurvivors): string
    {
        if ($fromEnd === 0 && $targetSurvivors > 1) {
            return 'Ronda decisiva';
        }

        return match ($matches) {
            1 => 'Final',
            2 => 'Semifinales',
            4 => 'Cuartos de final',
            default => 'Ronda de ' . ($matches * 2),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Grupos de puestos
    |--------------------------------------------------------------------------
    |
    | Un cuadro decide MENOS de lo que parece. Decide el primero y el segundo,
    | y a partir de ahi solo sabe agrupar: los dos que caen en semifinales
    | comparten el tercer puesto, los cuatro que caen en cuartos comparten del
    | quinto al octavo. No hay nada en el arbol que los separe, porque nunca
    | se han jugado entre ellos.
    |
    | Cada uno de esos bloques es un GRUPO DE PUESTOS. La clave es el puesto
    | en el que empieza -P3, P5, P9- porque eso es estable: no depende de
    | cuantas rondas tenga el cuadro ni de donde se corte.
    |
    |   P1  los que sobreviven      (el campeon, o los N que quedan vivos)
    |   P2  el finalista            (solo existe si se juega la final)
    |   P3  los que caen en semis   -> puestos 3 y 4
    |   P5  los que caen en cuartos -> puestos 5 a 8
    |   P9  los que caen antes      -> puestos 9 a 16
    |
    | Un grupo de un solo miembro ya esta decidido y no hay nada que activar.
    | Los demas se pueden ordenar activandolos, y entonces se juega un cuadro
    | de clasificacion entre ellos.
    |
    | @return array<int,array>
    */
    private function positionGroups(array $rounds, int $targetSurvivors): array
    {
        if ($rounds === []) {
            return [];
        }

        $groups = [];

        $last = end($rounds);
        $survivors = count($last['matches']);

        /* Arriba del todo: los que siguen en pie cuando la fase termina */
        $groups[] = $this->group(
            1,
            $survivors,
            'SURVIVORS',
            null,
            array_map(
                fn (array $match) => ['type' => 'WINNER', 'from' => $match['index']],
                $last['matches']
            )
        );

        /*
         * Y hacia abajo, ronda a ronda desde el final: los que pierden en la
         * ultima ronda ocupan el bloque siguiente, los de la penultima el
         * siguiente, y asi hasta la primera.
         */
        $next = $survivors + 1;

        for ($i = count($rounds) - 1; $i >= 0; $i--) {

            $round = $rounds[$i];
            $count = count($round['matches']);

            $groups[] = $this->group(
                $next,
                $next + $count - 1,
                'LOSERS',
                $round,
                array_map(
                    /*
                     * El perdedor de un descanso no existe: nadie jugaba en
                     * ese lado. Marcarlo como hueco deja que el cuadro de
                     * clasificacion lo trate igual que cualquier otro
                     * descanso, en vez de esperar eternamente a alguien.
                     */
                    fn (array $match) => $this->isByeMatch($match)
                        ? ['type' => 'BYE']
                        : ['type' => 'LOSER', 'from' => $match['index']],
                    $round['matches']
                )
            );

            $next += $count;
        }

        return $groups;
    }

    private function group(int $from, int $to, string $source, ?array $round, array $entrants): array
    {
        /*
         * Los huecos no son personas.
         *
         * Con 12 participantes en un cuadro de 16, la primera ronda tiene
         * ocho enfrentamientos pero cuatro son descansos, asi que ese grupo
         * no reparte del 9 al 16: reparte del 9 al 12. Contar los huecos
         * como si fueran gente prometia cuatro puestos que nadie ocupa.
         *
         * El cuadro de clasificacion SI se construye con todos los huecos
         * -los necesita para tener forma-, pero lo que se cuenta y lo que se
         * ensena es solo lo real.
         */
        $real = count(array_filter(
            $entrants,
            fn (array $side) => ($side['type'] ?? null) !== 'BYE'
        ));

        $count = $real;
        $to = $from + max(0, $real - 1);

        return [
            'key' => 'P' . $from,
            'from' => $from,
            'to' => $to,
            'entrants' => $count,
            'source' => $source,
            'round' => $round['number'] ?? null,
            'round_label' => $round['label'] ?? null,

            'label' => $this->groupLabel($from, $to, $source, $count),
            'hint' => $this->groupHint($source, $round, $count),

            /* Un solo miembro: el puesto ya esta decidido, no hay que jugar nada */
            'auto' => $count < 2,

            /* Cuantos duelos costaria ordenarlo entero */
            'cost' => $this->placementCost($count),

            'sides' => $entrants,
        ];
    }

    private function groupLabel(int $from, int $to, string $source, int $count): string
    {
        if ($source === 'SURVIVORS') {
            return $count < 2
                ? 'El campeón'
                : 'Los ' . $count . ' que sobreviven';
        }

        if ($from === $to) {
            return $from === 2 ? 'El finalista' : 'El puesto ' . $from;
        }

        if ($from === 3 && $to === 4) {
            return 'Tercer y cuarto puesto';
        }

        return 'Puestos ' . $from . '–' . $to;
    }

    private function groupHint(string $source, ?array $round, int $count): string
    {
        if ($source === 'SURVIVORS') {
            return $count < 2
                ? 'Lo decide la final.'
                : 'Salen los ' . $count . ' con vida, sin orden entre ellos.';
        }

        $where = $round['label'] ?? 'esa ronda';

        $donde = mb_strtolower($where);

        /*
         * "Lo decide final" no es castellano. El articulo depende del
         * nombre de la ronda, no de la frase: la final, los cuartos, la
         * ronda de 16.
         */
        $articulo = str_starts_with($donde, 'cuartos') ? 'los ' : 'la ';

        return $count < 2
            ? 'Lo decide ' . $articulo . $donde . '.'
            : 'Los ' . $count . ' que caen en ' . $articulo . $donde . '.';
    }

    /*
     * Ordenar M de arriba abajo cuesta T(M) duelos: una ronda de M/2, y
     * despues hay que ordenar tanto a los que ganan como a los que pierden.
     *
     *   T(1) = 0    T(2) = 1    T(4) = 4    T(8) = 12    T(16) = 32
     */
    private function placementCost(int $entrants): int
    {
        if ($entrants < 2) {
            return 0;
        }

        return intdiv($entrants, 2) + 2 * $this->placementCost(intdiv($entrants, 2));
    }

    private function isByeMatch(array $match): bool
    {
        return ($match['a']['type'] ?? null) === 'BYE'
            || ($match['b']['type'] ?? null) === 'BYE';
    }

    /*
    |--------------------------------------------------------------------------
    | Cuadros de clasificacion
    |--------------------------------------------------------------------------
    |
    | Para separar a los que un grupo dejo empatados hay que hacerles jugar
    | entre ellos. El partido por el tercer puesto es el caso mas conocido,
    | pero no es un caso especial: es este mismo mecanismo con dos.
    |
    | Con mas de dos, ordenar del todo es recursivo: se juega una ronda, y
    | despues hay que ordenar a los que ganaron -que se llevan la mitad de
    | arriba de los puestos- y a los que perdieron -la mitad de abajo-.
    |
    |   4 en juego, puestos 5 a 8
    |     ronda 1    A vs B, C vs D
    |     ganadores  -> se ordenan entre si: puestos 5 y 6
    |     perdedores -> se ordenan entre si: puestos 7 y 8
    |
    | Cada puesto lo decide EXACTAMENTE un duelo, el de dos. Por eso los
    | duelos base llevan `awards`: el que gana se lleva el puesto de arriba
    | y el que pierde el de abajo.
    |
    */
    private function buildPlacement(array $group, int &$matchIndex): array
    {
        $levels = [];

        $this->placementLevels($group['sides'], $group['from'], $matchIndex, $levels, 0);

        ksort($levels);

        return [
            'key' => $group['key'],
            'label' => $group['label'],
            'from' => $group['from'],
            'to' => $group['to'],
            'source' => $group['source'],
            'entrants' => $group['entrants'],

            'rounds' => array_values(array_map(
                fn (array $matches, int $depth) => [
                    'number' => $depth + 1,
                    'label' => $this->placementRoundLabel($matches, $group),
                    'matches' => array_values($matches),
                ],
                $levels,
                array_keys($levels)
            )),
        ];
    }

    /*
     * Reparte a los que entran en niveles. Cada nivel es una ronda del cuadro
     * de clasificacion, y las dos ramas -ganadores y perdedores- avanzan en
     * paralelo, asi que comparten nivel.
     */
    private function placementLevels(
        array $sides,
        int $from,
        int &$matchIndex,
        array &$levels,
        int $depth
    ): void {

        $count = count($sides);

        if ($count < 2) {
            return;
        }

        $matches = [];

        for ($i = 0; $i < $count; $i += 2) {
            $matches[] = [
                'index' => $matchIndex++,
                'a' => $sides[$i],
                'b' => $sides[$i + 1],

                /*
                 * Un duelo de dos ya no reparte a nadie mas abajo: decide el
                 * puesto directamente. Los de arriba solo encaminan.
                 */
                'awards' => $count === 2
                    ? ['win' => $from, 'lose' => $from + 1]
                    : null,
            ];
        }

        $levels[$depth] = [...($levels[$depth] ?? []), ...$matches];

        if ($count === 2) {
            return;
        }

        $winners = array_map(
            fn (array $match) => ['type' => 'WINNER', 'from' => $match['index']],
            $matches
        );

        $losers = array_map(
            fn (array $match) => ['type' => 'LOSER', 'from' => $match['index']],
            $matches
        );

        /* Los que ganan se reparten la mitad de arriba de los puestos */
        $this->placementLevels($winners, $from, $matchIndex, $levels, $depth + 1);

        /* Y los que pierden, la de abajo */
        $this->placementLevels($losers, $from + intdiv($count, 2), $matchIndex, $levels, $depth + 1);
    }

    private function placementRoundLabel(array $matches, array $group): string
    {
        return count($matches) === 1 && $group['entrants'] === 2
            ? $group['label']
            : 'Ronda de ' . (count($matches) * 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Ramas
    |--------------------------------------------------------------------------
    |
    | Cuando la fase termina con varios en pie, cada superviviente sale de un
    | trozo distinto del cuadro. Eso importa: no es lo mismo "salen cuatro"
    | que "sale uno de cada cuarto", y la fase siguiente puede querer recoger
    | a cada uno por una puerta distinta -que el de arriba vaya a un sitio y
    | el de abajo a otro-.
    |
    | Una rama es el subarbol que cuelga de cada enfrentamiento de la ultima
    | ronda que se juega. Con un solo superviviente hay una sola rama, que es
    | el cuadro entero, y entonces no dice nada: por eso no se emiten.
    |
    | @return array<int,array>
    */
    private function branches(array $rounds): array
    {
        if ($rounds === []) {
            return [];
        }

        $last = end($rounds);

        if (count($last['matches']) < 2) {
            return [];
        }

        $byIndex = [];

        foreach ($rounds as $round) {
            foreach ($round['matches'] as $match) {
                $byIndex[$match['index']] = $match;
            }
        }

        return array_values(array_map(
            function (array $match, int $position) use ($byIndex) {

                $seeds = $this->seedsUnder($match, $byIndex);

                sort($seeds);

                return [
                    'number' => $position + 1,
                    'letter' => chr(65 + ($position % 26)),
                    'label' => 'Rama ' . chr(65 + ($position % 26)),
                    'root' => $match['index'],
                    'seeds' => $seeds,
                    'top_seed' => $seeds[0] ?? null,
                    'size' => count($seeds),
                ];
            },
            $last['matches'],
            array_keys($last['matches'])
        ));
    }

    /*
     * Que puestos del cuadro cuelgan de un enfrentamiento, bajando por sus
     * dos lados hasta llegar a las hojas.
     *
     * @return array<int,int>
     */
    private function seedsUnder(array $match, array $byIndex): array
    {
        $seeds = [];

        foreach (['a', 'b'] as $side) {

            $node = $match[$side] ?? null;

            if (($node['type'] ?? null) === 'SEED') {
                $seeds[] = $node['seed'];

                continue;
            }

            if (($node['type'] ?? null) === 'WINNER' && isset($byIndex[$node['from']])) {
                $seeds = [...$seeds, ...$this->seedsUnder($byIndex[$node['from']], $byIndex)];
            }
        }

        return $seeds;
    }

    public function nextPowerOfTwo(int $value): int
    {
        $power = 2;

        while ($power < $value) {
            $power *= 2;
        }

        return $power;
    }
}
