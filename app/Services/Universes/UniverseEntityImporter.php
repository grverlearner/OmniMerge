<?php

namespace App\Services\Universes;

use App\Models\Entity;
use App\Models\EntityVersion;
use App\Models\Universe;
use App\Models\UniverseEntity;
use App\Services\Versions\VersionResolverService;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| UniverseEntityImporter
|--------------------------------------------------------------------------
|
| Copia una Entidad de la Biblioteca dentro de un Universo.
|
| IMPORTAR NO ES SINCRONIZAR: se copia una vez y ahí acaba la relación.
| No hay listeners, ni recálculos, ni actualizaciones automáticas.
| Editar la Entidad de Biblioteca después no toca la copia del Universo.
|
| Qué se copia:
|   · identidad (nombre, descripción, imagen, tipo)
|   · atributos EFECTIVOS, con la herencia BASE → padres → versión ya
|     aplicada por VersionResolverService
|   · versiones (nombre, descripción, imagen, cuál es la base activa)
|
| Ver docs/md/27-Entidades-Propias-Del-Universo.md
|
*/

class UniverseEntityImporter
{
    private const MAX_ATTRIBUTES = 24;

    public function __construct(
        private readonly
        VersionResolverService $versionResolver,

        private readonly
        UniverseProgressionService $progression,

        private readonly
        UniverseActivityRecorder $activity,

        /* Fase 11: el competidor llega listo para jugar */
        private readonly
        \App\Services\Games\GameStatsService $gameStats
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Importación masiva
    |--------------------------------------------------------------------------
    |
    | Idempotente: las Entidades ya importadas se ignoran en silencio, de
    | modo que reenviar el formulario no crea duplicados.
    |
    */

    public function import(
        Universe $universe,
        array $entityIds
    ): int {

        $entityIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map('intval', $entityIds)
                    )
                )
            );

        if ($entityIds === []) {
            return 0;
        }

        /*
         * Solo Entidades del propietario del Universo.
         */
        $entities =
            Entity::query()
            ->where('user_id', $universe->user_id)
            ->whereIn('id', $entityIds)
            ->with([
                'entityType',
                'baseVersionSetting.entityVersion',
            ])
            ->get();

        if ($entities->isEmpty()) {
            return 0;
        }

        $already =
            $universe
            ->entities()
            ->whereIn('source_entity_id', $entities->pluck('id'))
            ->pluck('source_entity_id')
            ->all();

        $pending =
            $entities->reject(
                fn($entity) =>
                in_array($entity->id, $already, true)
            );

        if ($pending->isEmpty()) {
            return 0;
        }

        return DB::transaction(
            function () use ($universe, $pending) {

                $sequence =
                    $this->nextSequence($universe);

                $imported = 0;

                foreach ($pending as $entity) {

                    $created =
                        $universe
                        ->entities()
                        ->create(
                            $this->copyOf(
                                $entity,
                                $sequence
                            )
                        );

                    /*
                     * Línea base de progresión sobre los atributos
                     * numéricos ya copiados. Preparación: ningún motor
                     * la modifica todavía.
                     */
                    $created->update([
                        'progression' =>
                        $this->progression->initialize($created),
                    ]);

                    /*
                     * Estadisticas de juego iniciales. Sin esto el
                     * competidor existiria pero no podria competir, y el
                     * usuario tendria que ir a rellenarlas a mano antes
                     * de su primer torneo.
                     */
                    $this->gameStats->ensureAll($created);

                    $sequence++;
                    $imported++;
                }

                $this->activity->entitiesImported(
                    $universe,
                    $imported
                );

                return $imported;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | La copia
    |--------------------------------------------------------------------------
    */

    public function copyOf(
        Entity $entity,
        int $sequence
    ): array {

        $version =
            $this->resolveVersion($entity);

        return [

            'sequence_number' =>
            $sequence,

            'code' =>
            UniverseEntity::formatCode($sequence),

            /*
             * El nombre y la descripcion son los de la entidad. La version
             * base solo pone la cara: es lo que dice la Biblioteca
             * (Entity::getBaseDisplayImageUrlAttribute) y lo que el usuario
             * ve al elegirla. Copiar el nombre de la version convertia a
             * «Naruto Uzumaki» en «Naruto Shippuden Basico», que es la
             * etiqueta de una version y no el nombre de nadie.
             */
            'name' =>
            $entity->name,

            'description' =>
            $entity->description ?: $version?->description,

            /*
             * Se copia la RUTA de la imagen, no el archivo: el disco es
             * el mismo y duplicar binarios no aporta independencia real,
             * solo peso. Si el archivo desapareciera, la ficha degrada
             * al icono, igual que en el resto del proyecto.
             */
            'image' =>
            $version?->image ?: $entity->image,

            'entity_type_name' =>
            $entity->entityType?->name,

            'attribute_snapshot' =>
            $this->attributesOf($entity, $version),

            'version_snapshot' =>
            $this->versionsOf($entity),

            'source_entity_id' =>
            $entity->id,

            'source_entity_version_id' =>
            $version?->id,

            'imported_at' =>
            now(),

            'status' =>
            'ACTIVE',
        ];
    }

    /*
     * Misma cadena que usa el torneo: Base activa (★) → versión por
     * defecto → sin versión.
     */
    private function resolveVersion(
        Entity $entity
    ): ?EntityVersion {

        return $entity->activeBaseVersion()
            ?? $this->versionResolver->resolve($entity);
    }

    /*
    |--------------------------------------------------------------------------
    | Atributos
    |--------------------------------------------------------------------------
    |
    | Se respeta lo que el usuario ya decidió en la Biblioteca: los
    | ocultos no se copian, los destacados van primero y la etiqueta
    | personalizada gana al nombre del atributo.
    |
    */

    private function attributesOf(
        Entity $entity,
        ?EntityVersion $version
    ): array {

        if (! $version) {
            return $this->fromEntity($entity);
        }

        return collect(
            $this->versionResolver
                ->effectiveAttributes($version)
        )
            ->filter(
                fn($row) =>
                ($row['is_visible'] ?? true) !== false
            )
            ->sortByDesc(
                fn($row) =>
                ($row['is_featured'] ?? false) ? 1 : 0
            )
            ->take(self::MAX_ATTRIBUTES)
            ->map(
                fn($row) =>
                $this->attribute(
                    $row['custom_label'] ?: $row['attribute']?->name,
                    (string) ($row['display'] ?? ''),
                    $this->valueNames($row['attribute'] ?? null, array_values((array) ($row['values'] ?? []))),
                    (bool) ($row['is_featured'] ?? false)
                )
            )
            ->filter()
            ->values()
            ->all();
    }

    private function fromEntity(
        Entity $entity
    ): array {

        $entity->loadMissing([
            'entityAttributes.attribute',
            'entityAttributes.values',
        ]);

        return collect($entity->entityAttributes)
            ->filter(
                fn($assignment) =>
                $assignment->attribute
                    && $assignment->is_visible !== false
            )
            ->sortByDesc(
                fn($assignment) =>
                $assignment->is_featured ? 1 : 0
            )
            ->take(self::MAX_ATTRIBUTES)
            ->map(
                function ($assignment) {

                    $values =
                        collect($assignment->values)
                        ->map(fn($value) => $value->displayValue())
                        ->filter(fn($value) => $value !== null && $value !== '')
                        ->values()
                        ->all();

                    return $this->attribute(
                        $assignment->custom_label
                            ?: $assignment->attribute->name,
                        implode(', ', $values),
                        $values,
                        (bool) $assignment->is_featured
                    );
                }
            )
            ->filter()
            ->values()
            ->all();
    }

    private function attribute(
        ?string $name,
        string $display,
        array $values,
        bool $featured
    ): ?array {

        $name = trim((string) $name);

        if ($name === '' || $display === '') {
            return null;
        }

        $first = $values[0] ?? null;

        return [
            'name' => mb_substr($name, 0, 80),
            'values' => $values,
            'display' => mb_substr($display, 0, 120),
            'featured' => $featured,

            /*
             * Disponible para el futuro motor de simulación por
             * atributos; esta capa no lo usa para decidir nada.
             */
            'numeric' => is_numeric($first) ? (float) $first : null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Versiones
    |--------------------------------------------------------------------------
    |
    | Se copia lo necesario para mostrarlas dentro del Universo. No se
    | replican sus valores de atributo uno a uno: el que juega es el
    | conjunto efectivo, que ya está en attribute_snapshot.
    |
    */

    private function versionsOf(
        Entity $entity
    ): array {

        $entity->loadMissing([
            'entityVersions.version',
            'entityVersions.version.catalogLinks.attribute',
            'entityVersions.version.catalogLinks.option',
        ]);

        $activeId =
            $entity->activeBaseVersion()?->id;

        return collect($entity->entityVersions)
            ->filter(fn ($version) => $version->status !== 'ARCHIVED')
            ->sortBy([
                ['priority', 'desc'],
                ['sort_order', 'asc'],
            ])
            ->map(
                fn ($version) => [

                    /*
                     * El id de la EntityVersion, que antes no se copiaba.
                     *
                     * Sin el, una version del Universo era un nombre y una
                     * foto sueltos: no se podia volver a ella para saber
                     * que atributos la definen, ni enlazarla con la
                     * Biblioteca, ni elegirla para un torneo.
                     */
                    'id' => (int) $version->id,

                    'version_id' => $version->version_id
                        ? (int) $version->version_id
                        : null,

                    /*
                     * Dos nombres distintos y los dos hacen falta: el de la
                     * version de la Biblioteca -"Sennin"- y el que el
                     * usuario le puso a ESTA entidad en ella -"Naruto
                     * Sennin"-.
                     */
                    'version_name' => $version->version?->name,

                    'name' => $version->name,
                    'description' => $version->description,
                    'image' => $version->image,
                    'code' => $version->code,

                    'priority' => (int) ($version->priority ?? 0),
                    'is_default' => (bool) $version->is_default,

                    'is_base' =>
                    $activeId !== null
                        && (int) $version->id === (int) $activeId,

                    /*
                     * Los atributos que definen esta version.
                     *
                     * Es lo que permite decir "en el torneo de Shippuden
                     * sale con la cara de Shippuden": la version se elige
                     * casando SUS atributos con los del torneo, igual que
                     * se eligen los competidores.
                     */
                    'attributes' => $this->versionAttributes($version),

                    /*
                     * Con que elementos de catalogo la activa la Biblioteca.
                     *
                     * «Naruto clásico» se activa con «Anime → Naruto». Es el
                     * vinculo que dice que cara ponerle en un torneo de
                     * Naruto, y sin copiarlo el universo no podia saberlo.
                     */
                    'activation' => $this->activationOf($version),
                ]
            )
            ->values()
            ->all();
    }

    /*
     * Los atributos efectivos de una version, en la misma forma que los de
     * la entidad. Que compartan forma no es cosmetica: quien decide si una
     * version encaja en un torneo es el mismo codigo que decide si encaja
     * un competidor, y solo puede serlo si lee lo mismo.
     */
    private function versionAttributes(EntityVersion $version): array
    {
        return collect(
            $this->versionResolver->effectiveAttributes($version)
        )
            ->filter(fn ($row) => ($row['is_visible'] ?? true) !== false)
            ->map(
                fn ($row) => $this->attribute(
                    $row['custom_label'] ?: $row['attribute']?->name,
                    (string) ($row['display'] ?? ''),
                    $this->valueNames($row['attribute'] ?? null, array_values((array) ($row['values'] ?? []))),
                    (bool) ($row['is_featured'] ?? false)
                )
            )
            ->filter()
            ->values()
            ->all();
    }

    private function nextSequence(
        Universe $universe
    ): int {

        return (
            (int) UniverseEntity::query()
                ->where('universe_id', $universe->id)
                ->max('sequence_number')
        ) + 1;
    }

    /*
     * Los valores de un atributo de catalogo, por NOMBRE.
     *
     * La cadena de versiones devuelve los ids de los elementos -«aldea:
     * [12]»-, y las reglas de participacion se escriben con nombres. Sin
     * traducirlos aqui, una entidad importada desde su version base no
     * casaba con «aldea → hoja» aunque fuese de la Hoja.
     */
    private function valueNames(?\App\Models\Attribute $attribute, array $values): array
    {
        if (! $attribute || $attribute->data_type !== 'OPTION') {
            return $values;
        }

        $ids = array_values(array_filter($values, fn ($v) => is_numeric($v)));

        if ($ids === []) {
            return $values;
        }

        $nombres = \App\Models\AttributeOption::query()
            ->where('attribute_id', $attribute->id)
            ->whereIn('id', $ids)
            ->pluck('name', 'id');

        return array_values(array_map(
            fn ($v) => is_numeric($v) && isset($nombres[(int) $v]) ? $nombres[(int) $v] : $v,
            $values
        ));
    }

    private function activationOf(EntityVersion $version): array
    {
        return collect($version->version?->catalogLinks ?? [])
            ->where('relation_type', 'ACTIVATES')
            ->filter(fn ($l) => $l->attribute && $l->option)
            ->sortBy([['condition_group', 'asc'], ['id', 'asc']])
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
}
