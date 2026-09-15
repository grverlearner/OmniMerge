<?php

namespace App\Services\Universes;

use App\Models\Universe;
use App\Models\UniverseEntity;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| CompetitionStartRouting
|--------------------------------------------------------------------------
|
| Quien entra por cada puerta.
|
| Un recorrido puede tener varios puntos de entrada -"los de la Hoja por la
| puerta 1, los de la Lluvia por la 2"-. Tres formas de decirlo:
|
|   RULES   una regla por puerta. La primera puerta que reclama a alguien
|           se lo queda. Con `fill_rest`, los que ninguna regla reclamo se
|           reparten entre las puertas que aun tienen sitio.
|
|   AUTO    se reparte solo:
|             BALANCED      por turnos, una puerta cada vez
|             IN_ORDER      se llena la primera, luego la segunda...
|             RANDOM        al azar, pero con semilla: la misma semilla da
|                           el mismo reparto en la pantalla y al guardar
|             BY_ATTRIBUTE  cada valor de un atributo a una puerta
|
|   MANUAL  uno a uno.
|
| En todas, el punto de partida son los que el TORNEO deja competir. Antes
| se partia del universo entero, asi que una puerta sin regla metia a gente
| que el torneo habia dejado fuera.
|
| Forma de `doors`:
|
|   { mode, strategy, attribute, value_doors: {valor: startId}, seed,
|     fill_rest, rules: [ {start_id, ...regla} ], manual: {startId: [ids]},
|     template_id }
|
| El resultado es siempre `assignments[startId][] = entityId`, lo que ya
| entendia el servicio de creacion.
|
| Ver docs/md/79-Sala-De-Participantes.md
|
*/
class CompetitionStartRouting
{
    public const MODES = ['RULES', 'AUTO', 'MANUAL'];

    public const STRATEGIES = ['BALANCED', 'IN_ORDER', 'RANDOM', 'BY_ATTRIBUTE'];

    public function __construct(
        private readonly UniverseTournamentEligibility $eligibility,
    ) {
    }

    /*
     * Limpia las reglas por puerta que llegaron del formulario.
     *
     * @return array<int,array{start_id:int,mode:string,rules:array}>
     */
    public function normalize(?array $startRules): array
    {
        return collect($startRules ?? [])
            ->map(function ($row) {

                if (! is_array($row)) {
                    return null;
                }

                $startId = (int) ($row['start_id'] ?? 0);

                if ($startId <= 0) {
                    return null;
                }

                /*
                 * La fila entera, no solo mode y rules: una puerta habla el
                 * mismo lenguaje que un torneo -grupos, mano y caras
                 * incluidos-.
                 */
                return ['start_id' => $startId] + $this->eligibility->normalize($row);
            })
            ->filter()
            /* Una puerta, una regla: la ultima gana */
            ->keyBy('start_id')
            ->values()
            ->all();
    }

    /*
     * El reparto completo, en su forma canonica.
     */
    public function normalizeDoors(?array $doors): array
    {
        $doors ??= [];

        $modo = strtoupper((string) ($doors['mode'] ?? 'AUTO'));
        $estrategia = strtoupper((string) ($doors['strategy'] ?? 'BALANCED'));

        return [
            'mode' => in_array($modo, self::MODES, true) ? $modo : 'AUTO',
            'strategy' => in_array($estrategia, self::STRATEGIES, true) ? $estrategia : 'BALANCED',
            'attribute' => mb_strtolower(trim((string) ($doors['attribute'] ?? ''))),

            'value_doors' => collect((array) ($doors['value_doors'] ?? []))
                ->mapWithKeys(fn ($puerta, $valor) => [mb_strtolower(trim((string) $valor)) => (int) $puerta])
                ->filter(fn ($puerta, $valor) => $valor !== '' && $puerta > 0)
                ->all(),

            'seed' => max(1, (int) ($doors['seed'] ?? 1)),
            'fill_rest' => filter_var($doors['fill_rest'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'rules' => $this->normalize((array) ($doors['rules'] ?? [])),

            'manual' => collect((array) ($doors['manual'] ?? []))
                ->mapWithKeys(fn ($ids, $puerta) => [
                    (int) $puerta => collect((array) $ids)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values()->all(),
                ])
                ->filter(fn ($ids, $puerta) => $puerta > 0)
                ->all(),

            'template_id' => ($doors['template_id'] ?? null) ? (int) $doors['template_id'] : null,
        ];
    }

    /*
     * A quien manda cada regla, sin repetir a nadie.
     *
     * Un competidor solo entra por una puerta. Cuando dos reglas lo
     * reclaman gana la PRIMERA, que es el orden en el que estan escritas.
     *
     * @param  array<int,int>  $capacities  start_id => plazas
     * @param  array|null  $eligibility  las reglas del torneo: de ahi se parte
     * @return array{assignments: array<int,array<int,int>>, leftovers: array<int,int>, overflow: array<int,array<int,int>>}
     */
    public function route(
        Universe $universe,
        array $startRules,
        array $capacities = [],
        ?array $eligibility = null
    ): array {

        $pool = $this->eligibility->matching($universe, $eligibility)->keyBy('id');

        return $this->routeWithin($pool, $this->normalize($startRules), $capacities);
    }

    /*
     * El reparto entero de un torneo, con cualquiera de los tres modos.
     *
     * @param  array<int,?int>  $starts  start_id => plazas (o null), en el orden de las puertas
     */
    public function plan(
        Universe $universe,
        ?array $doors,
        array $starts,
        ?array $eligibility = null
    ): array {

        return $this->planWithin($this->eligibility->matching($universe, $eligibility), $doors, $starts);
    }

    /*
     * El mismo reparto, sobre una lista de competidores ya decidida. Lo usa
     * una edicion con condiciones propias: primero se decide quien entra y
     * despues se reparte.
     */
    public function planWithin(Collection $pool, ?array $doors, array $starts): array
    {
        $doors = $this->normalizeDoors($doors);
        $pool = $pool->keyBy('id');

        $capacidades = array_filter($starts, fn ($c) => $c !== null && $c > 0);

        if ($starts === []) {
            return ['assignments' => [], 'leftovers' => $pool->keys()->map(fn ($id) => (int) $id)->all(), 'overflow' => []];
        }

        /* Una sola puerta: todos por ella, hasta donde quepan */
        if (count($starts) === 1) {
            $doors['mode'] = $doors['mode'] === 'MANUAL' ? 'MANUAL' : 'AUTO';
            $doors['strategy'] = $doors['strategy'] === 'BY_ATTRIBUTE' ? 'IN_ORDER' : $doors['strategy'];
        }

        return match ($doors['mode']) {

            'MANUAL' => $this->manual($pool, $doors['manual'], $starts, $capacidades),

            'RULES' => (function () use ($pool, $doors, $starts, $capacidades) {

                $reparto = $this->routeWithin($pool, $doors['rules'], $capacidades);

                if (! $doors['fill_rest'] || $reparto['leftovers'] === []) {
                    return $reparto;
                }

                /* Lo que sobraba se reparte: deja de estar fuera y de sobrar */
                $restantes = $reparto['leftovers'];
                $reparto['leftovers'] = [];
                $reparto['overflow'] = [];

                return $this->spread($pool, $reparto, $restantes, $starts, 'BALANCED', 1);
            })(),

            default => $this->auto($pool, $doors, $starts),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Las formas de repartir
    |--------------------------------------------------------------------------
    */

    private function routeWithin(Collection $pool, array $rules, array $capacities): array
    {
        $taken = [];
        $assignments = [];
        $overflow = [];

        foreach ($rules as $row) {

            $startId = $row['start_id'];

            $matched = $this->eligibility->matchingWithin(
                $pool->reject(fn (UniverseEntity $entity) => isset($taken[$entity->id])),
                $row
            );

            $capacity = $capacities[$startId] ?? null;

            $ids = $matched->pluck('id')->map(fn ($id) => (int) $id)->all();

            /*
             * Sobrar no es un error que deba tragarse en silencio: la
             * pantalla tiene que poder decir "caben 8 y la regla trae 11".
             */
            if ($capacity !== null && count($ids) > $capacity) {
                $overflow[$startId] = array_slice($ids, $capacity);
                $ids = array_slice($ids, 0, $capacity);
            }

            foreach ($ids as $id) {
                $taken[$id] = true;
            }

            $assignments[$startId] = $ids;
        }

        return [
            'assignments' => $assignments,
            'leftovers' => $pool
                ->reject(fn (UniverseEntity $entity) => isset($taken[$entity->id]))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
            'overflow' => $overflow,
        ];
    }

    private function manual(Collection $pool, array $manual, array $starts, array $capacidades): array
    {
        $vistos = [];
        $assignments = [];
        $overflow = [];

        foreach (array_keys($starts) as $startId) {

            $ids = array_values(array_filter(
                $manual[$startId] ?? [],
                fn ($id) => $pool->has($id) && ! isset($vistos[$id])
            ));

            $capacidad = $capacidades[$startId] ?? null;

            if ($capacidad !== null && count($ids) > $capacidad) {
                $overflow[$startId] = array_slice($ids, $capacidad);
                $ids = array_slice($ids, 0, $capacidad);
            }

            foreach ($ids as $id) {
                $vistos[$id] = true;
            }

            $assignments[$startId] = $ids;
        }

        return [
            'assignments' => $assignments,
            'leftovers' => $pool->keys()->map(fn ($id) => (int) $id)->reject(fn ($id) => isset($vistos[$id]))->values()->all(),
            'overflow' => $overflow,
        ];
    }

    private function auto(Collection $pool, array $doors, array $starts): array
    {
        $vacio = ['assignments' => array_fill_keys(array_keys($starts), []), 'leftovers' => [], 'overflow' => []];

        $ids = $pool->keys()->map(fn ($id) => (int) $id)->all();

        if ($doors['strategy'] !== 'BY_ATTRIBUTE') {
            return $this->spread($pool, $vacio, $ids, $starts, $doors['strategy'], $doors['seed']);
        }

        /* ---------------------------------------- por atributo */

        $reparto = $vacio;
        $puertas = array_keys($starts);
        $grupos = [];
        $sinValor = [];

        foreach ($ids as $id) {
            $valor = $this->eligibility->attributesOf($pool->get($id))[$doors['attribute']][0] ?? null;

            if ($valor === null) {
                $sinValor[] = $id;
            } else {
                $grupos[$valor][] = $id;
            }
        }

        /* Los grupos grandes eligen primero cuando no se dijo a donde van */
        uksort($grupos, fn ($a, $b) => [count($grupos[$b]), $a] <=> [count($grupos[$a]), $b]);

        $turno = 0;

        foreach ($grupos as $valor => $miembros) {

            $puerta = $doors['value_doors'][$valor] ?? null;

            if (! in_array($puerta, $puertas, true)) {
                $puerta = $puertas[$turno % count($puertas)];
                $turno++;
            }

            foreach ($miembros as $id) {
                $capacidad = $starts[$puerta] ?? null;

                if ($capacidad !== null && count($reparto['assignments'][$puerta]) >= $capacidad) {
                    $reparto['overflow'][$puerta][] = $id;
                } else {
                    $reparto['assignments'][$puerta][] = $id;
                }
            }
        }

        $reparto['leftovers'] = [...collect($reparto['overflow'])->flatten()->all(), ...$sinValor];

        if ($doors['fill_rest'] && $reparto['leftovers'] !== []) {
            $restantes = $reparto['leftovers'];
            $reparto['leftovers'] = [];
            $reparto['overflow'] = [];

            return $this->spread($pool, $reparto, $restantes, $starts, 'BALANCED', 1);
        }

        return $reparto;
    }

    /*
     * Reparte unos ids entre las puertas que tienen sitio.
     *
     * Parte de un reparto ya empezado para poder «rellenar con el resto».
     */
    private function spread(Collection $pool, array $reparto, array $ids, array $starts, string $estrategia, int $semilla): array
    {
        if ($estrategia === 'RANDOM') {
            usort($ids, fn ($a, $b) => self::hash($semilla . ':' . $a) <=> self::hash($semilla . ':' . $b) ?: $a <=> $b);
        }

        $puertas = array_keys($starts);
        $restantes = [];
        $turno = 0;

        foreach ($ids as $id) {

            $colocado = false;

            for ($intento = 0; $intento < count($puertas); $intento++) {

                $indice = $estrategia === 'IN_ORDER' ? $intento : ($turno + $intento) % count($puertas);
                $puerta = $puertas[$indice];
                $capacidad = $starts[$puerta] ?? null;

                if ($capacidad === null || count($reparto['assignments'][$puerta] ?? []) < $capacidad) {
                    $reparto['assignments'][$puerta][] = $id;
                    $colocado = true;
                    $turno = $indice + 1;
                    break;
                }
            }

            if (! $colocado) {
                $restantes[] = $id;
            }
        }

        $reparto['leftovers'] = [...$reparto['leftovers'] ?? [], ...$restantes];

        return $reparto;
    }

    /*
     * FNV-1a de 32 bits. La pantalla usa exactamente la misma funcion, y por
     * eso «al azar» da el mismo reparto alli que aqui.
     */
    public static function hash(string $texto): int
    {
        $h = 2166136261;

        foreach (unpack('C*', $texto) as $byte) {
            $h ^= $byte;
            $h = ($h * 16777619) & 0xFFFFFFFF;
        }

        return $h;
    }
}
