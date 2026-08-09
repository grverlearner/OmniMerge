<?php

namespace App\Services\Community;

use App\Models\Attribute;
use App\Models\Collection;
use App\Models\Entity;
use App\Models\EntityAttribute;
use App\Models\User;
use App\Models\AttributeOption;
use App\Models\EntityType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommunityCloneService
{
    public function cloneEntity(
        Entity $source,
        User $user
    ): Entity {
        if (! $source->canBeCloned()) {
            throw ValidationException::withMessages([
                'entity' =>
                'Esta entidad no está disponible para clonación.',
            ]);
        }

        return DB::transaction(function () use (
            $source,
            $user
        ) {
            $source->load([
                'entityType',
                'entityAttributes.attribute',
                'entityAttributes.values.option',
            ]);

            $entityTypeId = null;

            if ($source->entityType) {

                $entityType =
                    $this->cloneOrReuseEntityType(
                        $source->entityType,
                        $user
                    );

                $entityTypeId =
                    $entityType->id;
            }

            $name = $this->uniqueEntityName(
                $source->name,
                $user
            );

            $lastEntitySequence =
                (int) Entity::withTrashed()
                    ->where(
                        'user_id',
                        $user->id
                    )
                    ->max(
                        'sequence_number'
                    );


            $entitySequence =
                $lastEntitySequence + 1;


            $code =
                Entity::formatCode(
                    $entitySequence
                );

            $slug = $this->uniqueSlug(
                'entities',
                $source->slug,
                $user->id
            );

            $entity = $user->entities()->create([
                'source_entity_id' =>
                $source->id,

                'sequence_number' =>
                $entitySequence,

                'entity_type_id' =>
                $entityTypeId,

                'code' =>
                $code,

                'name' =>
                $name,

                'slug' =>
                $slug,

                'description' =>
                $source->description,

                'image' =>
                $this->copyPublicFile(
                    $source->image,
                    'entities'
                ),

                'status' =>
                'ACTIVE',

                'visibility' =>
                'PRIVATE',

                'allow_cloning' =>
                true,

                'metadata' =>
                array_merge(
                    $source->metadata ?? [],
                    [
                        'cloned_from_user_id' =>
                        $source->user_id,
                        'cloned_at' =>
                        now()->toISOString(),
                    ]
                ),
            ]);

            foreach (
                $source->entityAttributes
                as $sourceAssignment
            ) {
                $sourceAttribute =
                    $sourceAssignment->attribute;

                $attribute = $this->cloneOrReuseAttribute(
                    $sourceAttribute,
                    $user
                );

                $assignment = EntityAttribute::create([
                    'entity_id' =>
                    $entity->id,

                    'attribute_id' =>
                    $attribute->id,

                    'custom_label' =>
                    $sourceAssignment->custom_label,

                    'is_visible' =>
                    $sourceAssignment->is_visible,

                    'is_featured' =>
                    $sourceAssignment->is_featured,

                    'sort_order' =>
                    $sourceAssignment->sort_order,

                    'notes' =>
                    $sourceAssignment->notes,
                ]);

                foreach (
                    $sourceAssignment->values
                    as $sourceValue
                ) {
                    $optionId = null;

                    if ($sourceValue->option) {

                        /*
                        * Primero buscamos por procedencia.
                        *
                        * Esta es la forma nueva y correcta.
                        */

                        $option =
                            $attribute
                            ->options()
                            ->where(
                                'source_attribute_option_id',
                                $sourceValue->option->id
                            )
                            ->first();


                        /*
                        * Compatibilidad con copias antiguas.
                        */

                        if (! $option) {

                            $option =
                                $attribute
                                ->options()
                                ->where(
                                    'code',
                                    $sourceValue
                                        ->option
                                        ->code
                                )
                                ->first();
                        }


                        $optionId =
                            $option?->id;
                    }

                    $assignment->values()->create([
                        'attribute_option_id' =>
                        $optionId,

                        'text_value' =>
                        $sourceValue->text_value,

                        'integer_value' =>
                        $sourceValue->integer_value,

                        'decimal_value' =>
                        $sourceValue->decimal_value,

                        'boolean_value' =>
                        $sourceValue->boolean_value,

                        'date_value' =>
                        $sourceValue->date_value,

                        'color_value' =>
                        $sourceValue->color_value,

                        'custom_value' =>
                        $sourceValue->custom_value,

                        'json_value' =>
                        $sourceValue->json_value,

                        'sort_order' =>
                        $sourceValue->sort_order,
                    ]);
                }
            }

            $source->increment('clones_count');

            $this->recordInteraction(
                $user,
                'ENTITY',
                $source->id,
                'CLONE'
            );

            return $entity;
        });
    }

    public function cloneCollection(
        Collection $source,
        User $user
    ): Collection {
        if (! $source->canBeCloned()) {
            throw ValidationException::withMessages([
                'collection' =>
                'Esta colección no está disponible para clonación.',
            ]);
        }

        return DB::transaction(function () use (
            $source,
            $user
        ) {
            $source->load('entities');

            $name = $this->uniqueCollectionName(
                $source->name,
                $user
            );

            $lastCollectionSequence =
                (int) Collection::withTrashed()
                    ->where(
                        'user_id',
                        $user->id
                    )
                    ->max(
                        'sequence_number'
                    );


            $collectionSequence =
                $lastCollectionSequence + 1;


            $code =
                Collection::formatCode(
                    $collectionSequence
                );

            $slug = $this->uniqueSlug(
                'collections',
                $source->slug,
                $user->id
            );

            $collection = $user
                ->collections()
                ->create([
                    'source_collection_id' =>
                    $source->id,

                    'sequence_number' =>
                    $collectionSequence,

                    'code' =>
                    $code,

                    'name' =>
                    $name,

                    'slug' =>
                    $slug,

                    'description' =>
                    $source->description,

                    'image' =>
                    $this->copyPublicFile(
                        $source->image,
                        'collections'
                    ),

                    'icon' =>
                    $source->icon,

                    'color' =>
                    $source->color,

                    'visibility' =>
                    'PRIVATE',

                    'allow_cloning' =>
                    true,

                    'status' =>
                    'ACTIVE',

                    'sort_order' =>
                    $source->sort_order,

                    'metadata' => [
                        'cloned_from_user_id' =>
                        $source->user_id,
                        'cloned_at' =>
                        now()->toISOString(),
                    ],
                ]);

            foreach (
                $source->entities
                as $index => $sourceEntity
            ) {
                $newEntity = $this->cloneEntity(
                    $sourceEntity,
                    $user
                );

                $collection
                    ->entities()
                    ->attach(
                        $newEntity->id,
                        [
                            'sort_order' =>
                            $index,

                            'added_at' =>
                            now(),
                        ]
                    );
            }

            $source->increment('clones_count');

            $this->recordInteraction(
                $user,
                'COLLECTION',
                $source->id,
                'CLONE'
            );

            return $collection;
        });
    }

    public function cloneAttribute(
        Attribute $source,
        User $user
    ): Attribute {
        if (! $source->canBeCloned()) {
            throw ValidationException::withMessages([
                'attribute' =>
                'Este atributo no está disponible para clonación.',
            ]);
        }

        $attribute = DB::transaction(
            fn() => $this->cloneOrReuseAttribute(
                $source,
                $user
            )
        );

        $source->increment('clones_count');

        $this->recordInteraction(
            $user,
            'ATTRIBUTE',
            $source->id,
            'CLONE'
        );

        return $attribute;
    }

    private function cloneOrReuseEntityType(
        EntityType $source,
        User $user
    ): EntityType {

        /*
        |--------------------------------------------------------------------------
        | Reutilizar una copia correcta
        |--------------------------------------------------------------------------
        */

        $existing =
            $user
            ->entityTypes()
            ->where(
                'source_entity_type_id',
                $source->id
            )
            ->first();

        if ($existing) {
            return $existing;
        }


        /*
        |--------------------------------------------------------------------------
        | Bloquear usuario
        |--------------------------------------------------------------------------
        */

        /** @var User $lockedUser */
        $lockedUser =
            User::query()
            ->whereKey(
                $user->id
            )
            ->lockForUpdate()
            ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Secuencia local
        |--------------------------------------------------------------------------
        */

        $sequence =
            (
                (int) EntityType::withTrashed()
                    ->where(
                        'user_id',
                        $lockedUser->id
                    )
                    ->max(
                        'sequence_number'
                    )
            )
            + 1;


        $sortOrder =
            (
                (int) EntityType::withTrashed()
                    ->where(
                        'user_id',
                        $lockedUser->id
                    )
                    ->max(
                        'sort_order'
                    )
            )
            + 10;


        /*
        |--------------------------------------------------------------------------
        | Crear Tipo independiente
        |--------------------------------------------------------------------------
        */

        return $lockedUser
            ->entityTypes()
            ->create([
                'source_entity_type_id' =>
                $source->id,

                'sequence_number' =>
                $sequence,

                'code' =>
                EntityType::formatCode(
                    $sequence
                ),

                'name' =>
                $source->name,

                'description' =>
                $source->description,

                'image' =>
                $this->copyPublicFile(
                    $source->image,
                    'entity-types'
                ),

                'icon' =>
                $source->icon,

                'color' =>
                $source->color,

                'status' =>
                'ACTIVE',

                'sort_order' =>
                $sortOrder,
            ]);
    }

    public function cloneOption(
        AttributeOption $source,
        User $user
    ): AttributeOption {

        $source->load([
            'attribute',
            'parent',
        ]);

        $sourceAttribute =
            $source->attribute;

        if (
            ! $sourceAttribute
            ||
            ! $sourceAttribute->canBeCloned()
        ) {
            throw ValidationException::withMessages([
                'catalog' =>
                'Este elemento no está disponible para clonación.',
            ]);
        }


        return DB::transaction(
            function () use (
                $source,
                $sourceAttribute,
                $user
            ) {

                /*
            |--------------------------------------------------------------------------
            | Buscar Atributo que ya copiamos
            |--------------------------------------------------------------------------
            */

                $attribute =
                    $user
                    ->attributes()
                    ->where(
                        'source_attribute_id',
                        $sourceAttribute->id
                    )
                    ->first();


                /*
            |--------------------------------------------------------------------------
            | Si no existe, copiar Catálogo completo
            |--------------------------------------------------------------------------
            |
            | Así conservamos correctamente contexto y jerarquías.
            |
            */

                if (! $attribute) {

                    $attribute =
                        $this->cloneOrReuseAttribute(
                            $sourceAttribute,
                            $user
                        );

                    $option =
                        $attribute
                        ->options()
                        ->where(
                            'source_attribute_option_id',
                            $source->id
                        )
                        ->firstOrFail();

                    $this->recordInteraction(
                        $user,
                        'CATALOG_OPTION',
                        $source->id,
                        'CLONE'
                    );

                    return $option;
                }


                /*
            |--------------------------------------------------------------------------
            | Ya fue copiado
            |--------------------------------------------------------------------------
            */

                $existing =
                    $attribute
                    ->options()
                    ->where(
                        'source_attribute_option_id',
                        $source->id
                    )
                    ->first();

                if ($existing) {
                    return $existing;
                }


                /*
            |--------------------------------------------------------------------------
            | Bloqueo para código CAT
            |--------------------------------------------------------------------------
            */

                User::query()
                    ->whereKey(
                        $user->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                /*
            |--------------------------------------------------------------------------
            | Copiar padre si corresponde
            |--------------------------------------------------------------------------
            */

                $parentId = null;

                if ($source->parent) {

                    $parentClone =
                        $this->cloneOption(
                            $source->parent,
                            $user
                        );

                    $parentId =
                        $parentClone->id;
                }


                /*
            |--------------------------------------------------------------------------
            | Secuencia
            |--------------------------------------------------------------------------
            */

                $sequence =
                    (
                        (int) AttributeOption::withTrashed()
                            ->where(
                                'user_id',
                                $user->id
                            )
                            ->max(
                                'sequence_number'
                            )
                    )
                    + 1;


                $newOption =
                    $attribute
                    ->options()
                    ->create([
                        'user_id' =>
                        $user->id,

                        'source_attribute_option_id' =>
                        $source->id,

                        'parent_option_id' =>
                        $parentId,

                        'sequence_number' =>
                        $sequence,

                        'code' =>
                        AttributeOption::formatCode(
                            $sequence
                        ),

                        'name' =>
                        $source->name,

                        'description' =>
                        $source->description,

                        'image' =>
                        $this->copyPublicFile(
                            $source->image,
                            'attribute-options'
                        ),

                        'icon' =>
                        $source->icon,

                        'color' =>
                        $source->color,

                        'numeric_value' =>
                        $source->numeric_value,

                        'sort_order' =>
                        $source->sort_order,

                        'metadata' =>
                        $source->metadata,

                        'status' =>
                        'ACTIVE',
                    ]);


                $this->recordInteraction(
                    $user,
                    'CATALOG_OPTION',
                    $source->id,
                    'CLONE'
                );


                return $newOption;
            }
        );
    }

    private function cloneOrReuseAttribute(
        Attribute $source,
        User $user
    ): Attribute {

        /*
    |--------------------------------------------------------------------------
    | Reutilizar una copia previa
    |--------------------------------------------------------------------------
    */

        $existing =
            $user
            ->attributes()
            ->where(
                'source_attribute_id',
                $source->id
            )
            ->first();


        if ($existing) {
            return $existing;
        }


        $source->load(
            'options'
        );


    /*
    |--------------------------------------------------------------------------
    | Bloquear usuario para generar código secuencial
    |--------------------------------------------------------------------------
    */

        /** @var User $lockedUser */
        $lockedUser =
            User::query()
            ->whereKey(
                $user->id
            )
            ->lockForUpdate()
            ->firstOrFail();


        $lastSequence =
            (int) Attribute
                ::withTrashed()
                ->where(
                    'user_id',
                    $lockedUser->id
                )
                ->max(
                    'sequence_number'
                );


        $sequence =
            $lastSequence + 1;


        /*
    |--------------------------------------------------------------------------
    | Slug único
    |--------------------------------------------------------------------------
    */

        $slug =
            $this->uniqueSlug(
                'attributes',
                $source->slug
                    ?: $source->name,
                $lockedUser->id
            );


        /*
    |--------------------------------------------------------------------------
    | Orden
    |--------------------------------------------------------------------------
    */

        $lastSortOrder =
            (int) Attribute
                ::withTrashed()
                ->where(
                    'user_id',
                    $lockedUser->id
                )
                ->max(
                    'sort_order'
                );


        /*
    |--------------------------------------------------------------------------
    | Crear copia
    |--------------------------------------------------------------------------
    */

        $attribute =
            $lockedUser
            ->attributes()
            ->create([

                'source_attribute_id' =>
                $source->id,


                /*
                |--------------------------------------------------------------------------
                | Identidad propia del nuevo usuario
                |--------------------------------------------------------------------------
                */

                'sequence_number' =>
                $sequence,

                'code' =>
                Attribute::formatCode(
                    $sequence
                ),

                'name' =>
                $source->name,

                'slug' =>
                $slug,


                /*
                |--------------------------------------------------------------------------
                | Información
                |--------------------------------------------------------------------------
                */

                'description' =>
                $source->description,

                'help_text' =>
                $source->help_text,

                'placeholder' =>
                $source->placeholder,


                /*
                |--------------------------------------------------------------------------
                | Apariencia
                |--------------------------------------------------------------------------
                */

                'image' =>
                $this->copyPublicFile(
                    $source->image,
                    'attributes'
                ),

                'icon' =>
                $source->icon,

                'color' =>
                $source->color,


                /*
                |--------------------------------------------------------------------------
                | Tipo
                |--------------------------------------------------------------------------
                */

                'data_type' =>
                $source->data_type,

                'value_source' =>
                $source->value_source,

                'display_style' =>
                $source->display_style,

                'allows_multiple' =>
                $source->allows_multiple,

                'allows_custom_values' =>
                $source->allows_custom_values,


                /*
                |--------------------------------------------------------------------------
                | Comportamiento
                |--------------------------------------------------------------------------
                */

                'is_required' =>
                $source->is_required,

                'is_filterable' =>
                $source->is_filterable,

                'is_comparable' =>
                $source->is_comparable,

                'is_searchable' =>
                $source->is_searchable,

                'is_visible' =>
                $source->is_visible,

                'is_featured' =>
                $source->is_featured,


                /*
                |--------------------------------------------------------------------------
                | Restricciones
                |--------------------------------------------------------------------------
                */

                'min_numeric_value' =>
                $source->min_numeric_value,

                'max_numeric_value' =>
                $source->max_numeric_value,

                'min_length' =>
                $source->min_length,

                'max_length' =>
                $source->max_length,

                'unit' =>
                $source->unit,


                /*
                |--------------------------------------------------------------------------
                | Organización
                |--------------------------------------------------------------------------
                */

                'sort_order' =>
                $lastSortOrder + 10,

                'hierarchy_level' =>
                $source->hierarchy_level,


                /*
                |--------------------------------------------------------------------------
                | Copia comunitaria privada
                |--------------------------------------------------------------------------
                */

                'scope' =>
                'PRIVATE',

                'allow_cloning' =>
                true,

                'status' =>
                'ACTIVE',


                /*
                |--------------------------------------------------------------------------
                | Configuración
                |--------------------------------------------------------------------------
                */

                'default_value' =>
                $source->default_value,

                'validation_rules' =>
                $source->validation_rules,

                'configuration' =>
                $source->configuration,
            ]);


        /*
        |--------------------------------------------------------------------------
        | Copiar Catálogo
        |--------------------------------------------------------------------------
        */
        /*
        |--------------------------------------------------------------------------
        | Siguiente código de Catálogo del usuario
        |--------------------------------------------------------------------------
        */

        $nextCatalogSequence =
            (int) AttributeOption
                ::withTrashed()
                ->where(
                    'user_id',
                    $user->id
                )
                ->max(
                    'sequence_number'
                );


        $nextCatalogSequence++;

        $optionMap = [];


        foreach (
            $source->options
            as $sourceOption
        ) {

            $newOption =
                $attribute
                ->options()
                ->create([

                    'user_id' =>
                    $user->id,

                    'source_attribute_option_id' =>
                    $sourceOption->id,

                    'parent_option_id' =>
                    null,

                    'sequence_number' =>
                    $nextCatalogSequence,

                    'code' =>
                    AttributeOption::formatCode(
                        $nextCatalogSequence
                    ),

                    'name' =>
                    $sourceOption->name,

                    'description' =>
                    $sourceOption->description,

                    'image' =>
                    $this->copyPublicFile(
                        $sourceOption->image,
                        'attribute-options'
                    ),

                    'icon' =>
                    $sourceOption->icon,

                    'color' =>
                    $sourceOption->color,

                    'numeric_value' =>
                    $sourceOption->numeric_value,

                    'sort_order' =>
                    $sourceOption->sort_order,

                    'metadata' =>
                    $sourceOption->metadata,

                    'status' =>
                    $sourceOption->status,
                ]);
            $nextCatalogSequence++;

            $optionMap[$sourceOption->id] =
                $newOption->id;
        }


        /*
    |--------------------------------------------------------------------------
    | Reconstruir jerarquías
    |--------------------------------------------------------------------------
    */

        foreach (
            $source->options
            as $sourceOption
        ) {

            if (
                $sourceOption
                ->parent_option_id

                &&

                isset(
                    $optionMap[$sourceOption
                        ->parent_option_id]
                )
            ) {

                $attribute
                    ->options()
                    ->whereKey(
                        $optionMap[$sourceOption->id]
                    )
                    ->update([

                        'parent_option_id' =>
                        $optionMap[$sourceOption
                            ->parent_option_id],
                    ]);
            }
        }

        return $attribute;
    }

    private function copyPublicFile(
        ?string $sourcePath,
        string $targetDirectory
    ): ?string {
        if (! $sourcePath) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (! $disk->exists($sourcePath)) {
            return null;
        }

        $extension = pathinfo(
            $sourcePath,
            PATHINFO_EXTENSION
        );

        $targetPath =
            $targetDirectory
            . '/'
            . Str::uuid()
            . ($extension ? '.' . $extension : '');

        $disk->copy(
            $sourcePath,
            $targetPath
        );

        return $targetPath;
    }

    private function uniqueCode(
        string $table,
        string $baseCode,
        int $userId
    ): string {
        $baseCode = Str::upper(
            Str::slug($baseCode, '_')
        );

        $code = $baseCode;
        $counter = 2;

        while (
            DB::table($table)
            ->where('user_id', $userId)
            ->where('code', $code)
            ->exists()
        ) {
            $code = $baseCode . '_' . $counter;
            $counter++;
        }

        return $code;
    }

    private function uniqueSlug(
        string $table,
        string $baseSlug,
        int $userId
    ): string {
        $baseSlug = Str::slug($baseSlug);
        $slug = $baseSlug;
        $counter = 2;

        while (
            DB::table($table)
            ->where('user_id', $userId)
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function uniqueEntityName(
        string $name,
        User $user
    ): string {
        if (
            ! $user->entities()
                ->where('name', $name)
                ->exists()
        ) {
            return $name;
        }

        return $name . ' (copia)';
    }

    private function uniqueCollectionName(
        string $name,
        User $user
    ): string {
        if (
            ! $user->collections()
                ->where('name', $name)
                ->exists()
        ) {
            return $name;
        }

        return $name . ' (copia)';
    }

    private function recordInteraction(
        User $user,
        string $contentType,
        int $contentId,
        string $interactionType
    ): void {
        DB::table(
            'community_interactions'
        )->insert([
            'user_id' =>
            $user->id,

            'content_type' =>
            $contentType,

            'content_id' =>
            $contentId,

            'interaction_type' =>
            $interactionType,

            'metadata' =>
            null,

            'created_at' =>
            now(),

            'updated_at' =>
            now(),
        ]);
    }
}
