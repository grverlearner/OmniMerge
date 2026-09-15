<?php

namespace App\Services\Universes;

use App\Models\Universe;
use App\Models\UniverseEntity;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| UniverseTournamentEligibility
|--------------------------------------------------------------------------
|
| Quien puede competir en un torneo oficial.
|
| Las reglas se escriben con los ATRIBUTOS de los competidores, y el
| catalogo de atributos posibles no se inventa: sale de las entidades que ya
| viven en ese universo. Si nadie tiene "doujutsu", "doujutsu" no aparece
| como opcion. Ofrecer un filtro que no puede casar con nadie es ofrecer un
| callejon sin salida.
|
| ---------------------------------------------------------------
|
| Una regla tiene dos formas:
|
|   { attribute: doujutsu, values: [] }
|       cualquiera que TENGA ese atributo, con el valor que sea.
|
|   { attribute: doujutsu, values: [sharingan] }
|       solo los que lo tengan con ese valor. Varios valores en la lista se
|       leen como "cualquiera de estos".
|
| Y un valor de catalogo arrastra a sus hijos salvo que se diga lo
| contrario (`descendants: false`): pedir «País del Fuego» trae tambien a
| los de «Hoja». Ver UniverseCatalogIndex.
|
| ---------------------------------------------------------------
|
| Se lee del `attribute_snapshot` de cada UniverseEntity, que es una copia
| congelada de sus atributos al importarla. Va por NOMBRE y no por id a
| proposito: el snapshot ya guarda nombres, y ademas asi un torneo no se
| rompe porque alguien reordene la Biblioteca. Cuando el snapshot guardo el
| id de un elemento de catalogo, se traduce a su nombre al leer.
|
*/
class UniverseTournamentEligibility
{
    /* El catalogo del dueño del universo que se esta mirando */
    private array $indice = ['attributes' => []];

    public function __construct(
        private readonly UniverseCatalogIndex $catalogIndex,
    ) {
    }

    /*
     * Fija el universo cuyo catalogo se usa para leer y expandir valores.
     *
     * Las entradas que reciben un Universe lo hacen solas; quien evalua
     * atributos sueltos -el que elige la version de un competidor- lo
     * llama antes.
     */
    public function forUniverse(Universe|int $universe): static
    {
        $this->indice = $this->catalogIndex->forUniverseId(
            $universe instanceof Universe ? (int) $universe->id : (int) $universe
        );

        return $this;
    }

    /*
     * El catalogo de lo que se puede filtrar en este universo.
     *
     * Cada valor dice cuantos lo llevan (`entities`), cuantos lo llevan o
     * llevan a alguno de sus hijos (`total`), y donde esta en el arbol. Un
     * padre que nadie lleva directamente aparece igual si alguno de sus
     * hijos si: es la forma de poder pedir «todo el País del Fuego».
     *
     * @return array<int,array{name:string,label:string,entities:int,hierarchical:bool,values:array}>
     */
    public function catalog(Universe $universe): array
    {
        $entities = $this->entitiesOf($universe);

        /* atributo => [ valor => cuantos lo tienen ] */
        $index = [];

        /* atributo => cuantas entidades lo tienen, sea cual sea el valor */
        $holders = [];

        /* atributo => valor => [ids de entidades que lo llevan o llevan a un hijo] */
        $alcance = [];

        foreach ($entities as $entity) {
            foreach ($this->attributesOf($entity) as $name => $values) {

                $holders[$name] = ($holders[$name] ?? 0) + 1;

                $index[$name] ??= [];

                foreach ($values as $value) {
                    $index[$name][$value] = ($index[$name][$value] ?? 0) + 1;

                    foreach ([$value, ...$this->catalogIndex->ancestors($this->indice, $name, $value)] as $arriba) {
                        $alcance[$name][$arriba][$entity->id] = true;
                    }
                }
            }
        }

        ksort($index);

        return collect($index)
            ->map(function (array $values, string $name) use ($holders, $alcance) {

                $datos = $this->indice['attributes'][$name] ?? null;

                /* Los padres que nadie lleva pero cuyos hijos si */
                foreach (array_keys($alcance[$name] ?? []) as $valor) {
                    $values[$valor] ??= 0;
                }

                $filas = collect($values)
                    ->map(function (int $count, string $value) use ($name, $alcance) {

                        $opcion = $this->catalogIndex->option($this->indice, $name, $value);

                        return [
                            'value' => $value,
                            'label' => $opcion['label'] ?? $this->humanize($value),
                            'entities' => $count,
                            'total' => count($alcance[$name][$value] ?? []),
                            'parent' => $opcion['parent'] ?? null,
                            'depth' => $opcion['depth'] ?? 0,
                            'image' => $opcion['image'] ?? null,
                            'color' => $opcion['color'] ?? null,
                        ];
                    });

                return [
                    'name' => $name,
                    'label' => $datos['label'] ?? $this->humanize($name),
                    'entities' => $holders[$name] ?? 0,
                    'hierarchical' => (bool) ($datos['hierarchical'] ?? false),
                    'values' => $this->treeOrder($filas->keyBy('value')->all()),
                ];
            })
            ->values()
            ->all();
    }

    /*
     * Quien cumple las reglas.
     *
     * Sin reglas compite todo el mundo, que es lo razonable: un torneo sin
     * filtros es un torneo abierto, no un torneo vacio.
     */
    public function matching(Universe $universe, ?array $eligibility): Collection
    {
        $rules = $this->normalize($eligibility);

        $entities = $this->entitiesOf($universe);

        if ($this->isOpen($rules)) {
            return $entities;
        }

        return $entities
            ->filter(fn (UniverseEntity $entity) => $this->passes($entity, $rules))
            ->values();
    }

    /*
     * Lo mismo, pero sobre una lista que ya se tiene en la mano.
     *
     * Repartir competidores entre las puertas de entrada evalua una regla
     * por puerta sobre el mismo universo: volver a la base de datos en
     * cada una seria pedir lo mismo cinco veces.
     */
    public function matchingWithin(Collection $entities, ?array $eligibility): Collection
    {
        $rules = $this->normalize($eligibility);

        if ($first = $entities->first()) {
            $this->forUniverse((int) $first->universe_id);
        }

        if ($this->isOpen($rules)) {
            return $entities->values();
        }

        return $entities
            ->filter(fn (UniverseEntity $entity) => $this->passes($entity, $rules))
            ->values();
    }

    /*
     * TODOS los competidores del universo, con sus atributos ya aplanados
     * y en minusculas -las mismas claves con las que se escriben las
     * reglas-.
     *
     * Se manda entero a la pantalla para que pueda filtrar sin ir al
     * servidor: al marcar un valor del catalogo la galeria tiene que
     * responder en el acto, y un viaje de ida y vuelta por cada clic no es
     * "en el acto".
     *
     * @return array<int,array<string,mixed>>
     */
    public function roster(Universe $universe): array
    {
        return $this->entitiesOf($universe)
            ->map(fn (UniverseEntity $e) => [
                'id' => $e->id,
                'name' => $e->display_name ?: $e->name,
                'image_url' => $e->image_url,
                'type' => $e->entity_type_name,

                /*
                 * Dos caras del mismo atributo: la clave con la que casa
                 * una regla, y el texto con el que se lee.
                 */
                'attributes' => collect($this->attributesOf($e))
                    ->map(fn (array $values, string $name) => [
                        'name' => $name,
                        'label' => $this->attributeLabel($name),
                        'values' => $values,
                        'labels' => array_map(fn ($v) => $this->valueLabel($name, $v), $values),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    public function preview(Universe $universe, ?array $eligibility, int $sample = 24): array
    {
        $all = $this->entitiesOf($universe);
        $matching = $this->matching($universe, $eligibility);

        return [
            'total' => $all->count(),
            'matching' => $matching->count(),
            'rules' => $this->normalize($eligibility),

            'sample' => $matching
                ->take($sample)
                ->map(fn (UniverseEntity $e) => [
                    'id' => $e->id,
                    'name' => $e->display_name ?: $e->name,
                    'image_url' => $e->image_url,
                    'attributes' => collect($this->attributesOf($e))
                        ->map(fn (array $values, string $name) => [
                            'name' => $name,
                            'label' => $this->attributeLabel($name),
                            'values' => array_map(fn ($v) => $this->valueLabel($name, $v), $values),
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | La forma de una regla de participacion
    |--------------------------------------------------------------------------
    |
    |   {
    |     mode:      ALL | ANY | NONE | ONE
    |     rules:     [ {attribute, values[], descendants} ]
    |     groups:    [ {mode, rules[]} ]
    |     include:   [universeEntityId]   siempre dentro
    |     exclude:   [universeEntityId]   siempre fuera
    |     faces:     { universeEntityId: entityVersionId | 0 }
    |     face_mode: AUTO | BASE
    |     doors:     el reparto por puertas, ver CompetitionStartRouting
    |   }
    |
    | El `mode` combina TODAS las condiciones -las reglas sueltas y el
    | resultado de cada grupo-, asi que un grupo es una condicion mas:
    |
    |   ALL   hay que cumplirlas todas          (Y)
    |   ANY   basta con una                     (O)
    |   NONE  no se puede cumplir ninguna       (NI)
    |   ONE   exactamente una, ni mas ni menos  (O exclusivo)
    |
    | Un solo nivel de anidamiento a proposito. Con grupos se escribe
    | «(aldea hoja Y anime naruto) O (aldea arena)», que es hasta donde llega
    | lo que alguien quiere expresar de verdad.
    |
    | Y por encima de todo, la mano: include mete a alguien pase lo que pase,
    | exclude lo saca pase lo que pase.
    |
    | `faces` y `face_mode` no deciden quien entra: deciden con que cara sale.
    | Viven aqui porque se configuran en la misma sala. Ver
    | UniverseEntityVersionResolver.
    |
    */

    public const MODES = [
        'ALL' => 'Cumple TODAS',
        'ANY' => 'Cumple ALGUNA',
        'NONE' => 'No cumple NINGUNA',
        'ONE' => 'Cumple EXACTAMENTE UNA',
    ];

    public function normalize(?array $eligibility): array
    {
        return [
            'mode' => $this->mode($eligibility['mode'] ?? null),

            'rules' => $this->normalizeRules($eligibility['rules'] ?? []),

            'groups' => collect($eligibility['groups'] ?? [])
                ->map(function ($group) {

                    if (! is_array($group)) {
                        return null;
                    }

                    $rules = $this->normalizeRules($group['rules'] ?? []);

                    /* Un grupo sin reglas no es una condicion, es un hueco */
                    if ($rules === []) {
                        return null;
                    }

                    return [
                        'mode' => $this->mode($group['mode'] ?? null),
                        'rules' => $rules,
                    ];
                })
                ->filter()
                ->values()
                ->all(),

            'include' => $this->ids($eligibility['include'] ?? []),
            'exclude' => $this->ids($eligibility['exclude'] ?? []),

            'faces' => $this->faces($eligibility['faces'] ?? []),
            'face_mode' => strtoupper((string) ($eligibility['face_mode'] ?? 'AUTO')) === 'BASE' ? 'BASE' : 'AUTO',
        ];
    }

    private function mode(?string $value): string
    {
        $mode = strtoupper((string) $value);

        return array_key_exists($mode, self::MODES) ? $mode : 'ALL';
    }

    private function ids(mixed $lista): array
    {
        return collect((array) $lista)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /* universeEntityId => entityVersionId, donde 0 es «su imagen de siempre» */
    private function faces(mixed $lista): array
    {
        $out = [];

        foreach ((array) $lista as $entidad => $version) {
            if ((int) $entidad > 0 && is_numeric($version) && (int) $version >= 0) {
                $out[(int) $entidad] = (int) $version;
            }
        }

        return $out;
    }

    private function normalizeRules(mixed $rules): array
    {
        return collect((array) $rules)
            ->map(function ($rule) {

                if (! is_array($rule)) {
                    return null;
                }

                $attribute = $this->key((string) ($rule['attribute'] ?? ''));

                if ($attribute === '') {
                    return null;
                }

                return [
                    'attribute' => $attribute,

                    'values' => collect($rule['values'] ?? [])
                        ->map(fn ($v) => $this->key((string) $v))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),

                    'descendants' => filter_var($rule['descendants'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ];
            })
            ->filter()
            /* Dos reglas sobre el mismo atributo se funden en una */
            ->groupBy('attribute')
            ->map(fn (Collection $group, string $attribute) => [
                'attribute' => $attribute,
                'values' => $group->pluck('values')->flatten()->unique()->values()->all(),
                'descendants' => $group->every(fn ($r) => $r['descendants']),
            ])
            ->values()
            ->all();
    }

    /*
     * Todos los atributos que menciona una regla, grupos incluidos.
     *
     * Hace falta para saber de que depende un torneo: sin mirar dentro de
     * los grupos, sincronizar una entidad podria retirar un atributo que una
     * condicion anidada seguia usando.
     */
    public function attributesUsed(?array $eligibility): array
    {
        $reglas = $this->normalize($eligibility);

        return collect($reglas['rules'])
            ->concat(collect($reglas['groups'])->pluck('rules')->flatten(1))
            ->pluck('attribute')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /*
     * Los valores que una regla pide DE FORMA POSITIVA, ya expandidos.
     *
     * Es lo que usa quien elige la version de un competidor: un torneo
     * «anime → Naruto» pide Naruto, y la version que se activa con Naruto es
     * la buena. Lo que se pide con NI no cuenta: «nadie de Akatsuki» no dice
     * que cara ponerle a nadie.
     *
     * @return array<string,array<int,string>>  atributo => valores
     */
    public function positiveSelections(?array $eligibility): array
    {
        $reglas = $this->normalize($eligibility);
        $out = [];

        $recoger = function (array $lista) use (&$out) {
            foreach ($lista as $regla) {
                if ($regla['values'] === []) {
                    continue;
                }

                $valores = $regla['descendants']
                    ? $this->catalogIndex->expand($this->indice, $regla['attribute'], $regla['values'])
                    : $regla['values'];

                $out[$regla['attribute']] = array_values(array_unique([
                    ...($out[$regla['attribute']] ?? []),
                    ...$valores,
                ]));
            }
        };

        if ($reglas['mode'] !== 'NONE') {

            $recoger($reglas['rules']);

            foreach ($reglas['groups'] as $grupo) {
                if ($grupo['mode'] !== 'NONE') {
                    $recoger($grupo['rules']);
                }
            }
        }

        return $out;
    }

    /*
     * Como se lee una regla, para poder ensenarla sin releer el JSON.
     */
    public function describe(array $rules, array $catalog): array
    {
        $labels = collect($catalog)->keyBy('name');

        return collect($rules['rules'] ?? [])
            ->map(function (array $rule) use ($labels) {

                $attribute = $labels[$rule['attribute']] ?? null;

                $valueLabels = collect($attribute['values'] ?? [])
                    ->whereIn('value', $rule['values'])
                    ->pluck('label')
                    ->all();

                $label = $attribute['label'] ?? $this->humanize($rule['attribute']);

                return [
                    'attribute' => $rule['attribute'],
                    'label' => $label,
                    'values' => $rule['values'],
                    'value_labels' => $valueLabels,

                    'text' => $rule['values'] === []
                        ? 'Tiene ' . $label
                        : $label . ' · ' . implode(' o ', $valueLabels ?: $rule['values']),
                ];
            })
            ->values()
            ->all();
    }


    /*
    |--------------------------------------------------------------------------
    | Interno
    |--------------------------------------------------------------------------
    */

    private function entitiesOf(Universe $universe): Collection
    {
        $this->forUniverse($universe);

        return UniverseEntity::query()
            ->where('universe_id', $universe->id)
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /*
     * Los atributos de una entidad, como nombre => [valores].
     *
     * Publico: el reparto por puertas «por atributo» y la sala de
     * participantes leen exactamente lo mismo que las reglas.
     *
     * @return array<string,array<int,string>>
     */
    public function attributesOf(UniverseEntity $entity): array
    {
        return $this->ownedFrom((array) ($entity->attribute_snapshot ?? []));
    }

    /*
     * Lo mismo sobre una lista de filas {name, values[]} -la de una entidad
     * o la de una de sus versiones-.
     *
     * Aqui se traducen los ids de catalogo a sus nombres. Sin esto, una
     * entidad importada desde su version base guardaba «aldea: [12]» y
     * ninguna regla escrita por nombre casaba con ella.
     */
    public function ownedFrom(array $rows): array
    {
        $out = [];

        foreach ($rows as $row) {

            if (! is_array($row)) {
                continue;
            }

            $name = $this->key((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $values = collect($row['values'] ?? [])
                ->map(fn ($v) => $this->catalogIndex->valueKey($this->indice, $name, $v))
                ->filter(fn ($v) => $v !== '')
                ->values()
                ->all();

            $out[$name] = array_values(array_unique(
                array_merge($out[$name] ?? [], $values)
            ));
        }

        return $out;
    }

    /*
     * Si unos atributos cumplen una regla.
     *
     * Publico y sin UniverseEntity a proposito: quien elige la VERSION con
     * la que sale un competidor evalua exactamente lo mismo sobre los
     * atributos de la version.
     *
     * @param  array<string,array<int,string>>  $owned  atributo => valores
     */
    public function evaluate(array $owned, ?array $eligibility): bool
    {
        $rules = $this->normalize($eligibility);

        $results = array_map(
            fn (array $rule) => $this->ruleHolds($rule, $owned),
            $rules['rules']
        );

        foreach ($rules['groups'] as $group) {

            $inner = array_map(
                fn (array $rule) => $this->ruleHolds($rule, $owned),
                $group['rules']
            );

            $results[] = $this->combine($group['mode'], $inner);
        }

        return $results === []
            ? true
            : $this->combine($rules['mode'], $results);
    }

    /*
     * Por que alguien esta dentro o fuera, en una palabra.
     *
     *   HAND_IN   metido a mano       HAND_OUT  sacado a mano
     *   OPEN      no hay reglas       RULES     cumple las reglas
     *   NO_MATCH  no las cumple
     */
    public function reason(UniverseEntity $entity, ?array $eligibility): string
    {
        $rules = $this->normalize($eligibility);

        $this->forUniverse((int) $entity->universe_id);

        return match (true) {
            in_array((int) $entity->id, $rules['exclude'], true) => 'HAND_OUT',
            in_array((int) $entity->id, $rules['include'], true) => 'HAND_IN',
            $this->isOpen($rules) => 'OPEN',
            $this->evaluate($this->attributesOf($entity), $rules) => 'RULES',
            default => 'NO_MATCH',
        };
    }

    private function passes(UniverseEntity $entity, array $rules): bool
    {
        /*
         * La mano gana siempre, y excluir gana sobre incluir.
         */
        if (in_array((int) $entity->id, $rules['exclude'] ?? [], true)) {
            return false;
        }

        if (in_array((int) $entity->id, $rules['include'] ?? [], true)) {
            return true;
        }

        return $this->evaluate($this->attributesOf($entity), $rules);
    }

    /*
     * Si una entidad cumple UNA regla.
     *
     * Sin valores concretos basta con tener el atributo; con valores, hay
     * que llevarlo con alguno de ellos -o con alguno de sus hijos, si la
     * regla los arrastra-.
     */
    private function ruleHolds(array $rule, array $owned): bool
    {
        if (! array_key_exists($rule['attribute'], $owned)) {
            return false;
        }

        if (($rule['values'] ?? []) === []) {
            return true;
        }

        $valores = ($rule['descendants'] ?? true)
            ? $this->catalogIndex->expand($this->indice, $rule['attribute'], $rule['values'])
            : $rule['values'];

        return array_intersect($valores, $owned[$rule['attribute']]) !== [];
    }

    /*
     * Como se combinan varias condiciones.
     *
     * @param  array<int,bool>  $results
     */
    private function combine(string $mode, array $results): bool
    {
        if ($results === []) {
            return true;
        }

        return match ($mode) {
            'ANY' => in_array(true, $results, true),
            'NONE' => ! in_array(true, $results, true),

            /* Exactamente una: ni ninguna ni dos */
            'ONE' => count(array_filter($results)) === 1,

            default => ! in_array(false, $results, true),
        };
    }

    /*
     * Si la regla no filtra NADA.
     */
    private function isOpen(array $rules): bool
    {
        return ($rules['rules'] ?? []) === []
            && ($rules['groups'] ?? []) === []
            && ($rules['include'] ?? []) === []
            && ($rules['exclude'] ?? []) === [];
    }

    /* Padres antes que sus hijos; entre hermanos, los mas poblados primero */
    private function treeOrder(array $filas): array
    {
        $hijos = [];

        foreach ($filas as $valor => $fila) {
            $padre = ($fila['parent'] !== null && isset($filas[$fila['parent']])) ? $fila['parent'] : '';
            $hijos[$padre][] = $valor;
        }

        $out = [];
        $visitados = [];

        $bajar = function (string $padre, int $nivel) use (&$bajar, &$out, &$visitados, $hijos, $filas) {

            $lista = $hijos[$padre] ?? [];

            usort($lista, fn ($a, $b) => [$filas[$b]['total'], $filas[$a]['label']] <=> [$filas[$a]['total'], $filas[$b]['label']]);

            foreach ($lista as $valor) {
                if (isset($visitados[$valor]) || $nivel > 12) {
                    continue;
                }

                $visitados[$valor] = true;
                $out[] = ['depth' => $nivel] + $filas[$valor];
                $bajar($valor, $nivel + 1);
            }
        };

        $bajar('', 0);

        return $out;
    }

    private function attributeLabel(string $name): string
    {
        return $this->indice['attributes'][$name]['label'] ?? $this->humanize($name);
    }

    private function valueLabel(string $attribute, string $value): string
    {
        return $this->catalogIndex->option($this->indice, $attribute, $value)['label'] ?? $this->humanize($value);
    }

    private function key(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    private function humanize(string $value): string
    {
        return mb_convert_case(str_replace(['_', '-'], ' ', $value), MB_CASE_TITLE, 'UTF-8');
    }
}
