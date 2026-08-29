<?php

namespace App\Services\Universes;

use App\Models\UniverseEntity;

/*
|--------------------------------------------------------------------------
| UniverseEntityVersionResolver
|--------------------------------------------------------------------------
|
| Con que cara sale un competidor en ESTE torneo.
|
| Un personaje no es uno solo. Naruto tiene su version de nino y su version
| Sennin, y cada una tiene su propia imagen. Un torneo de Shippuden tiene
| que ensenar la de Shippuden; uno de la primera serie, la del nino. Y si
| una fase es «solo Konoha» y otra «solo Akatsuki», cada una deberia
| ensenar la version que corresponde.
|
| Hasta ahora un competidor tenia una sola foto: la que se copio al
| importarlo. Aqui se elige, y se elige con el MISMO lenguaje con el que se
| eligen los competidores -atributos y valores de catalogo-, porque si un
| torneo se define como «los que llevan saga → shippuden», la version buena
| es exactamente la que tambien lo lleva.
|
| ---------------------------------------------------------------
|
| El orden de preferencia, de mas fuerte a mas debil:
|
|   la que casa con las reglas   y si casan varias, la de mas prioridad
|   la base activa (*)           la que el usuario marco como la buena
|   la marcada por defecto
|   ninguna                      y entonces manda la copia de la entidad
|
| Nunca devuelve una version que no exista. Si nada casa, la entidad se
| ensena como siempre: quedarse sin foto por afinar un filtro seria peor
| que ensenar la de siempre.
|
*/
class UniverseEntityVersionResolver
{
    public function __construct(
        private readonly UniverseTournamentEligibility $eligibility,
    ) {
    }

    /*
     * La version que aplica a esta entidad bajo estas reglas.
     *
     * @param  array|null  $eligibility  {mode: ALL|ANY, rules: [{attribute, values[]}]}
     * @return array|null  la version del version_snapshot, o null
     */
    public function pick(UniverseEntity $entity, ?array $eligibility = null): ?array
    {
        $versiones = $this->usable($entity);

        if ($versiones === []) {
            return null;
        }

        $reglas = $this->normalize($eligibility);

        if ($this->hasConditions($reglas)) {

            $casan = array_values(array_filter(
                $versiones,
                fn (array $v) => $this->matches($v, $reglas)
            ));

            if ($casan !== []) {
                return $this->best($casan);
            }
        }

        return $this->fallback($versiones);
    }

    /*
     * Como se vera esta entidad: nombre, imagen y de donde sale cada cosa.
     *
     * Devuelve SIEMPRE algo pintable. La vista no deberia tener que saber
     * si habia version o no para poder ensenar una cara.
     *
     * @return array{
     *     name: string,
     *     image_url: ?string,
     *     version_id: ?int,
     *     version_name: ?string,
     *     from: string
     * }
     */
    public function face(UniverseEntity $entity, ?array $eligibility = null): array
    {
        $version = $this->pick($entity, $eligibility);

        if (! $version) {
            return [
                'name' => $entity->display_label,
                'image_url' => $entity->image_url,
                'version_id' => null,
                'version_name' => null,
                'from' => 'ENTITY',
            ];
        }

        return [
            'name' => $version['name'] ?: $entity->display_label,

            /*
             * Si la version no trae imagen se cae a la de la entidad. Una
             * version sin foto sigue siendo la version correcta: lo que
             * falta es el archivo, no la eleccion.
             */
            'image_url' => $this->imageUrl($version) ?: $entity->image_url,

            'version_id' => $version['id'] ?? null,
            'version_name' => $version['version_name'] ?? $version['name'] ?? null,

            'from' => ($version['is_base'] ?? false) ? 'BASE' : 'MATCHED',
        ];
    }

    /*
     * Todas las versiones de una entidad, ya legibles, diciendo cual
     * aplicaria bajo unas reglas dadas. Es lo que la ficha necesita para
     * poder ensenarlas todas y marcar la que manda.
     */
    public function all(UniverseEntity $entity, ?array $eligibility = null): array
    {
        $elegida = $this->pick($entity, $eligibility);

        return collect($this->usable($entity))
            ->map(fn (array $v) => [
                'id' => $v['id'] ?? null,
                'name' => $v['name'] ?? 'Versión',
                'version_name' => $v['version_name'] ?? null,
                'description' => $v['description'] ?? null,
                'code' => $v['code'] ?? null,
                'image_url' => $this->imageUrl($v),
                'is_base' => (bool) ($v['is_base'] ?? false),
                'is_default' => (bool) ($v['is_default'] ?? false),
                'priority' => (int) ($v['priority'] ?? 0),
                'attributes' => $v['attributes'] ?? [],

                'active' => $elegida !== null
                    && ($elegida['id'] ?? null) === ($v['id'] ?? null),
            ])
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Detalles
    |--------------------------------------------------------------------------
    */

    /*
     * Las versiones que se pueden usar: las que tienen id.
     *
     * Las copiadas antes de que se guardase el id no se pueden identificar
     * ni emparejar, asi que no se eligen. Se siguen viendo en la ficha
     * -existieron-, pero no deciden nada.
     */
    private function usable(UniverseEntity $entity): array
    {
        return array_values(array_filter(
            (array) ($entity->version_snapshot ?? []),
            fn ($v) => is_array($v) && isset($v['id'])
        ));
    }

    /*
     * Si una version cumple las reglas.
     *
     * Se delega en UniverseTournamentEligibility: es el mismo criterio con
     * el que se eligen los competidores, y tiene que serlo. Dos
     * implementaciones de "cumple" acaban discrepando el dia que una gane
     * un modo nuevo y la otra no, que es justo lo que pasaria al anadir
     * NONE, ONE y los grupos.
     */
    private function matches(array $version, array $reglas): bool
    {
        return $this->eligibility->evaluate(
            $this->attributesOf($version),
            $reglas
        );
    }

    /* Los atributos de una version, aplanados y en minusculas */
    private function attributesOf(array $version): array
    {
        $out = [];

        foreach ((array) ($version['attributes'] ?? []) as $fila) {

            if (! is_array($fila)) {
                continue;
            }

            $nombre = $this->key((string) ($fila['name'] ?? ''));

            if ($nombre === '') {
                continue;
            }

            $valores = array_map(
                fn ($v) => $this->key((string) $v),
                (array) ($fila['values'] ?? [])
            );

            $out[$nombre] = array_values(array_unique(
                array_merge($out[$nombre] ?? [], array_filter($valores))
            ));
        }

        return $out;
    }

    /* Entre varias que casan, la de mas prioridad; a igualdad, la base */
    private function best(array $versiones): array
    {
        usort($versiones, function (array $a, array $b) {

            $porPrioridad = ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0);

            if ($porPrioridad !== 0) {
                return $porPrioridad;
            }

            return (($b['is_base'] ?? false) ? 1 : 0)
                <=> (($a['is_base'] ?? false) ? 1 : 0);
        });

        return $versiones[0];
    }

    /* Sin reglas, o sin nada que case: la base, la de defecto, o nada */
    private function fallback(array $versiones): ?array
    {
        foreach ($versiones as $v) {
            if ($v['is_base'] ?? false) {
                return $v;
            }
        }

        foreach ($versiones as $v) {
            if ($v['is_default'] ?? false) {
                return $v;
            }
        }

        return null;
    }

    private function imageUrl(array $version): ?string
    {
        $ruta = $version['image'] ?? null;

        if (! $ruta) {
            return null;
        }

        $disco = \Illuminate\Support\Facades\Storage::disk('public');

        return $disco->exists($ruta) ? $disco->url($ruta) : null;
    }

    private function normalize(?array $eligibility): array
    {
        return $this->eligibility->normalize($eligibility);
    }

    /* Si la regla dice algo que pueda elegir una version */
    private function hasConditions(array $reglas): bool
    {
        return $reglas['rules'] !== [] || $reglas['groups'] !== [];
    }

    private function key(string $valor): string
    {
        return mb_strtolower(trim($valor));
    }
}
