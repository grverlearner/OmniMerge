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
| Y las reglas se combinan de dos maneras:
|
|   ALL   hay que cumplirlas todas   -"doujutsu Y de la Hoja"-
|   ANY   basta con una              -"doujutsu O kekkei genkai"-
|
| ---------------------------------------------------------------
|
| Se lee del `attribute_snapshot` de cada UniverseEntity, que es una copia
| congelada de sus atributos al importarla. Va por NOMBRE y no por id a
| proposito: el snapshot ya guarda nombres, y ademas asi un torneo no se
| rompe porque alguien reordene la Biblioteca.
|
*/
class UniverseTournamentEligibility
{
    /*
     * El catalogo de lo que se puede filtrar en este universo.
     *
     * @return array<int,array{name:string,label:string,entities:int,values:array}>
     */
    public function catalog(Universe $universe): array
    {
        $entities = $this->entitiesOf($universe);

        /* atributo => [ valor => cuantos lo tienen ] */
        $index = [];

        /* atributo => cuantas entidades lo tienen, sea cual sea el valor */
        $holders = [];

        foreach ($entities as $entity) {
            foreach ($this->attributesOf($entity) as $name => $values) {

                $holders[$name] = ($holders[$name] ?? 0) + 1;

                $index[$name] ??= [];

                foreach ($values as $value) {
                    $index[$name][$value] = ($index[$name][$value] ?? 0) + 1;
                }
            }
        }

        ksort($index);

        return collect($index)
            ->map(function (array $values, string $name) use ($holders) {

                arsort($values);

                return [
                    'name' => $name,
                    'label' => $this->humanize($name),
                    'entities' => $holders[$name] ?? 0,
                    'values' => collect($values)
                        ->map(fn (int $count, string $value) => [
                            'value' => $value,
                            'label' => $this->humanize($value),
                            'entities' => $count,
                        ])
                        ->values()
                        ->all(),
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
     * El resumen que se ensena junto al filtro: cuantos caben y quienes son
     * los primeros, para poder comprobar de un vistazo que la regla dice lo
     * que se creia que decia.
     */
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
     * El servidor sigue siendo quien manda -preview() se sigue llamando y
     * es su recuento el que se ensena si discrepan-, pero lo que se ve
     * mientras se escribe se calcula aqui mismo.
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
                 * una regla, y el texto con el que se lee. Guardar solo la
                 * clave obligaria a la pantalla a rehacer el humanize, y
                 * guardar solo el texto haria imposible casar nada.
                 */
                'attributes' => collect($this->attributesOf($e))
                    ->map(fn (array $values, string $name) => [
                        'name' => $name,
                        'label' => $this->humanize($name),
                        'values' => $values,
                        'labels' => array_map(fn ($v) => $this->humanize($v), $values),
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
                            'label' => $this->humanize($name),
                            'values' => array_map(fn ($v) => $this->humanize($v), $values),
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    /*
     * Deja unas reglas en su forma canonica, vengan de donde vengan.
     *
     * Un formulario manda cadenas y huecos; esto se encarga de que el resto
     * del servicio pueda dar por hecho que hay listas y nombres.
     *
     * @return array{mode:string,rules:array<int,array{attribute:string,values:array<int,string>}>}
     */
    /*
    |--------------------------------------------------------------------------
    | La forma de una regla de participacion
    |--------------------------------------------------------------------------
    |
    |   {
    |     mode:    ALL | ANY | NONE | ONE
    |     rules:   [ {attribute, values[]} ]
    |     groups:  [ {mode, rules[]} ]
    |     include: [universeEntityId]   siempre dentro
    |     exclude: [universeEntityId]   siempre fuera
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
    | lo que alguien quiere expresar de verdad; permitir grupos dentro de
    | grupos daria una pantalla que nadie sabria leer.
    |
    | Y por encima de todo, la mano: include mete a alguien pase lo que pase,
    | exclude lo saca pase lo que pase. Ninguna regla escrita con atributos
    | va a capturar «este si, porque lo digo yo».
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
                ];
            })
            ->filter()
            /* Dos reglas sobre el mismo atributo se funden en una */
            ->groupBy('attribute')
            ->map(fn (Collection $group, string $attribute) => [
                'attribute' => $attribute,
                'values' => $group->pluck('values')->flatten()->unique()->values()->all(),
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
        return UniverseEntity::query()
            ->where('universe_id', $universe->id)
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();
    }

    /*
     * Los atributos de una entidad, como nombre => [valores].
     *
     * @return array<string,array<int,string>>
     */
    private function attributesOf(UniverseEntity $entity): array
    {
        $out = [];

        foreach ((array) ($entity->attribute_snapshot ?? []) as $row) {

            if (! is_array($row)) {
                continue;
            }

            $name = $this->key((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $values = collect($row['values'] ?? [])
                ->map(fn ($v) => $this->key((string) $v))
                ->filter()
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
     * atributos de la version. Tener dos implementaciones de "cumple" era
     * garantizar que un dia dijeran cosas distintas.
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

    private function passes(UniverseEntity $entity, array $rules): bool
    {
        /*
         * La mano gana siempre, y excluir gana sobre incluir.
         *
         * Es el orden que espera cualquiera: si alguien esta en las dos
         * listas es porque se le metio y despues se le saco.
         */
        if (in_array((int) $entity->id, $rules['exclude'] ?? [], true)) {
            return false;
        }

        if (in_array((int) $entity->id, $rules['include'] ?? [], true)) {
            return true;
        }

        /*
         * Y lo demas, con el mismo evaluador que usa todo el proyecto.
         *
         * Sin ninguna condicion compite todo el mundo: un torneo sin filtros
         * es un torneo abierto, no un torneo vacio -y eso vale tambien para
         * NONE, porque "no cumple ninguna de nada" lo cumple cualquiera-.
         */
        return $this->evaluate($this->attributesOf($entity), $rules);
    }

    /*
     * Si una entidad cumple UNA regla.
     *
     * Sin valores concretos basta con tener el atributo; con valores, hay
     * que llevarlo con alguno de ellos.
     */
    private function ruleHolds(array $rule, array $owned): bool
    {
        if (! array_key_exists($rule['attribute'], $owned)) {
            return false;
        }

        if (($rule['values'] ?? []) === []) {
            return true;
        }

        return array_intersect($rule['values'], $owned[$rule['attribute']]) !== [];
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
     *
     * No basta con mirar las reglas de primer nivel: con esa comprobacion
     * sola, una regla hecha solo de grupos -o solo de exclusiones- se
     * saltaba entera y dejaba pasar a todo el mundo. Un torneo de
     * «(anime naruto Y aldea) O continente» admitia a los 21.
     */
    private function isOpen(array $rules): bool
    {
        return ($rules['rules'] ?? []) === []
            && ($rules['groups'] ?? []) === []
            && ($rules['include'] ?? []) === []
            && ($rules['exclude'] ?? []) === [];
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
