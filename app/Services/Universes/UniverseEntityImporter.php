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
        VersionResolverService $versionResolver
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

                    $universe
                        ->entities()
                        ->create(
                            $this->copyOf(
                                $entity,
                                $sequence
                            )
                        );

                    $sequence++;
                    $imported++;
                }

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

            'name' =>
            $version?->name ?: $entity->name,

            'description' =>
            $version?->description ?: $entity->description,

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
                    array_values((array) ($row['values'] ?? [])),
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

        $entity->loadMissing('entityVersions');

        $activeId =
            $entity->activeBaseVersion()?->id;

        return collect($entity->entityVersions)
            ->map(
                fn($version) => [

                    'name' =>
                    $version->name,

                    'description' =>
                    $version->description,

                    'image' =>
                    $version->image,

                    'code' =>
                    $version->code,

                    'is_base' =>
                    $activeId !== null
                        && (int) $version->id === (int) $activeId,
                ]
            )
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
}
