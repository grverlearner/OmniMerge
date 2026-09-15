<?php

namespace App\Services\Universes;

use App\Models\UniverseEntity;
use App\Models\VersionCatalogLink;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| UniverseEntityVersionResolver
|--------------------------------------------------------------------------
|
| Con que cara sale un competidor en ESTE torneo.
|
| Un personaje no es uno solo. Naruto tiene su version de nino y su version
| Shippuden, y cada una tiene su propia imagen. Un torneo de los ninos de
| Konoha tiene que ensenar al nino; uno de Shippuden, la de Shippuden. Y si
| la puerta 1 es «aldea → hoja» y la 2 «saga → shippuden», cada puerta
| deberia ensenar la version que le corresponde.
|
| ---------------------------------------------------------------
|
| El orden de preferencia, de mas fuerte a mas debil:
|
|   MANUAL      la que se eligio a mano para ese competidor en la sala
|   CATALOG     la que la Biblioteca activa con un elemento de catalogo que
|               pide la regla. Es el vinculo de verdad: en la Biblioteca,
|               «Naruto clásico» se activa con «Anime → Naruto».
|   ATTRIBUTES  la unica cuyos atributos casan con la regla, cuando las
|               versiones se distinguen por sus atributos
|   BASE        la base activa (★)
|   DEFAULT     la marcada por defecto
|   ENTITY      ninguna: la imagen con la que se importo
|
| Primero se prueba con la regla de la PUERTA por la que entra y despues
| con la del torneo: la puerta es mas concreta.
|
| Nunca devuelve una version que no exista. Si nada casa, la entidad se
| ensena como siempre.
|
| Ver docs/md/79-Sala-De-Participantes.md
|
*/
class UniverseEntityVersionResolver
{
    /** @var array<int,array> vinculos de catalogo por version de Biblioteca */
    private static array $vinculos = [];

    public const FROM = [
        'MANUAL' => 'Elegida a mano',
        'CATALOG' => 'Vinculada al catálogo que pide la regla',
        'ATTRIBUTES' => 'Sus atributos casan con la regla',
        'BASE' => 'Su versión base',
        'DEFAULT' => 'Su versión por defecto',
        'ENTITY' => 'Su imagen de siempre',
    ];

    public function __construct(
        private readonly UniverseTournamentEligibility $eligibility,
    ) {
    }

    /*
     * La version que aplica a esta entidad bajo estas reglas.
     *
     * @return array{version: ?array, from: string}
     */
    public function choose(UniverseEntity $entity, ?array $eligibility = null, ?array $door = null): array
    {
        $this->eligibility->forUniverse((int) $entity->universe_id);

        $versiones = $this->usable($entity);
        $torneo = $this->eligibility->normalize($eligibility);
        $puerta = $door ? $this->eligibility->normalize($door) : null;

        /* ---------------------------------------------- a mano */

        $caras = ($puerta['faces'] ?? []) + $torneo['faces'];

        if (array_key_exists((int) $entity->id, $caras)) {

            $elegida = $caras[(int) $entity->id];

            if ($elegida === 0) {
                return ['version' => null, 'from' => 'MANUAL'];
            }

            foreach ($versiones as $v) {
                if ((int) $v['id'] === $elegida) {
                    return ['version' => $v, 'from' => 'MANUAL'];
                }
            }
        }

        if ($versiones === []) {
            return ['version' => null, 'from' => 'ENTITY'];
        }

        if ($torneo['face_mode'] !== 'BASE') {

            foreach (array_filter([$puerta, $torneo]) as $reglas) {

                /* ------------------------------------ por catalogo */

                $pedidos = $this->eligibility->positiveSelections($reglas);

                if ($pedidos !== []) {

                    $activadas = [];

                    foreach ($versiones as $v) {
                        $puntos = $this->activationScore($v, $pedidos);

                        if ($puntos > 0) {
                            $activadas[] = ['v' => $v, 'puntos' => $puntos];
                        }
                    }

                    if ($activadas !== []) {
                        usort($activadas, fn ($a, $b) => [$b['puntos'], $b['v']['priority'] ?? 0, (int) ($b['v']['is_base'] ?? false)]
                            <=> [$a['puntos'], $a['v']['priority'] ?? 0, (int) ($a['v']['is_base'] ?? false)]);

                        return ['version' => $activadas[0]['v'], 'from' => 'CATALOG'];
                    }
                }

                /* ---------------------------------- por atributos */

                /*
                 * Solo cuando distinguen. Si todas las versiones heredan los
                 * mismos atributos -lo normal-, todas «casan» y elegir por
                 * eso seria elegir al azar llamandolo coincidencia.
                 */
                if ($this->hasConditions($reglas)) {

                    $casan = array_values(array_filter(
                        $versiones,
                        fn (array $v) => $this->eligibility->evaluate($this->eligibility->ownedFrom((array) ($v['attributes'] ?? [])), $reglas)
                    ));

                    if ($casan !== [] && count($casan) < count($versiones)) {
                        return ['version' => $this->best($casan), 'from' => 'ATTRIBUTES'];
                    }
                }
            }
        }

        foreach ($versiones as $v) {
            if ($v['is_base'] ?? false) {
                return ['version' => $v, 'from' => 'BASE'];
            }
        }

        foreach ($versiones as $v) {
            if ($v['is_default'] ?? false) {
                return ['version' => $v, 'from' => 'DEFAULT'];
            }
        }

        return ['version' => null, 'from' => 'ENTITY'];
    }

    public function pick(UniverseEntity $entity, ?array $eligibility = null, ?array $door = null): ?array
    {
        return $this->choose($entity, $eligibility, $door)['version'];
    }

    /*
     * Como se vera esta entidad: nombre, imagen y de donde sale cada cosa.
     *
     * Devuelve SIEMPRE algo pintable.
     *
     * @return array{name:string, image_url:?string, version_id:?int, version_name:?string, from:string, reason:string, image_missing:bool}
     */
    public function face(UniverseEntity $entity, ?array $eligibility = null, ?array $door = null): array
    {
        ['version' => $version, 'from' => $from] = $this->choose($entity, $eligibility, $door);

        if (! $version) {
            return [
                'name' => $entity->display_label,
                'image_url' => $entity->image_url,
                'version_id' => null,
                'version_name' => null,
                'from' => $from,
                'reason' => self::FROM[$from],
                'image_missing' => $entity->image_url === null,
            ];
        }

        $imagen = $this->imageUrl($version);

        return [
            'name' => $version['name'] ?: $entity->display_label,

            /*
             * Si la version no trae imagen se cae a la de la entidad. Una
             * version sin foto sigue siendo la version correcta: lo que
             * falta es el archivo, no la eleccion. Y se avisa.
             */
            'image_url' => $imagen ?: $entity->image_url,

            'version_id' => $version['id'] ?? null,
            'version_name' => $version['version_name'] ?? $version['name'] ?? null,

            'from' => $from,
            'reason' => self::FROM[$from],
            'image_missing' => $imagen === null,
        ];
    }

    /*
     * Todas las versiones de una entidad, ya legibles, con lo que las activa
     * en la Biblioteca. Es lo que la sala de participantes necesita para
     * calcular la cara en el acto y dejar elegirla a mano.
     */
    public function options(UniverseEntity $entity): array
    {
        $this->eligibility->forUniverse((int) $entity->universe_id);

        return collect($this->usable($entity))
            ->map(fn (array $v) => [
                'id' => (int) $v['id'],
                'name' => $v['name'] ?? 'Versión',
                'version_name' => $v['version_name'] ?? null,
                'image_url' => $this->imageUrl($v),
                'has_image' => ! empty($v['image']),
                'is_base' => (bool) ($v['is_base'] ?? false),
                'is_default' => (bool) ($v['is_default'] ?? false),
                'priority' => (int) ($v['priority'] ?? 0),
                'activation' => $this->activation($v),
                'owned' => $this->eligibility->ownedFrom((array) ($v['attributes'] ?? [])),
            ])
            ->values()
            ->all();
    }

    /*
     * Todas las versiones con la que aplicaria marcada. La usa la ficha de
     * un competidor.
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
     * Los vinculos de catalogo que activan una version, por nombre.
     *
     * Se copian al importar (`activation`). Las entidades importadas antes
     * no los tienen, y para ellas se leen de la Biblioteca por el id de la
     * version: solo se consulta, y el resultado se congela en el torneo.
     *
     * @return array<int,array{group:int, operator:string, attribute:string, value:string, attribute_label:string, value_label:string}>
     */
    public function activation(array $version): array
    {
        if (isset($version['activation']) && is_array($version['activation'])) {
            return $version['activation'];
        }

        $id = (int) ($version['version_id'] ?? 0);

        if ($id <= 0) {
            return [];
        }

        return self::$vinculos[$id] ??= VersionCatalogLink::query()
            ->where('version_id', $id)
            ->where('relation_type', 'ACTIVATES')
            ->with(['attribute:id,name', 'option:id,name'])
            ->orderBy('condition_group')
            ->orderBy('id')
            ->get()
            ->filter(fn ($l) => $l->attribute && $l->option)
            ->map(fn ($l) => [
                'group' => (int) $l->condition_group,
                'operator' => strtoupper((string) ($l->logical_operator ?: 'AND')),
                'attribute' => mb_strtolower(trim($l->attribute->name)),
                'value' => mb_strtolower(trim($l->option->name)),
                'attribute_label' => $l->attribute->name,
                'value_label' => $l->option->name,
            ])
            ->values()
            ->all();
    }

    /*
     * Cuanto activa una version lo que pide la regla.
     *
     * Igual que en la Biblioteca: los grupos son alternativas (O) y dentro
     * de cada grupo los vinculos se encadenan con su operador. Devuelve
     * cuantos vinculos casaron en el mejor grupo que se cumple, o 0.
     */
    private function activationScore(array $version, array $pedidos): int
    {
        $mejor = 0;

        foreach (collect($this->activation($version))->groupBy('group') as $grupo) {

            $resultado = null;
            $casados = 0;

            foreach ($grupo as $vinculo) {

                $casa = in_array($vinculo['value'], $pedidos[$vinculo['attribute']] ?? [], true);
                $casados += $casa ? 1 : 0;

                $resultado = $resultado === null
                    ? $casa
                    : ($vinculo['operator'] === 'OR' ? ($resultado || $casa) : ($resultado && $casa));
            }

            if ($resultado) {
                $mejor = max($mejor, $casados);
            }
        }

        return $mejor;
    }

    /*
     * Las versiones que se pueden usar: las que tienen id.
     */
    private function usable(UniverseEntity $entity): array
    {
        return array_values(array_filter(
            (array) ($entity->version_snapshot ?? []),
            fn ($v) => is_array($v) && isset($v['id'])
        ));
    }

    /* Entre varias que casan, la de mas prioridad; a igualdad, la base */
    private function best(array $versiones): array
    {
        usort($versiones, fn (array $a, array $b) => [$b['priority'] ?? 0, (int) ($b['is_base'] ?? false)]
            <=> [$a['priority'] ?? 0, (int) ($a['is_base'] ?? false)]);

        return $versiones[0];
    }

    private function imageUrl(array $version): ?string
    {
        $ruta = $version['image'] ?? null;

        if (! $ruta) {
            return null;
        }

        $disco = Storage::disk('public');

        return $disco->exists($ruta) ? $disco->url($ruta) : null;
    }

    /* Si la regla dice algo que pueda elegir una version */
    private function hasConditions(array $reglas): bool
    {
        return $reglas['rules'] !== [] || $reglas['groups'] !== [];
    }
}
