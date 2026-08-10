<?php

namespace App\Services\Entities;

use App\Models\Attribute;
use App\Models\Collection;
use App\Models\Entity;
use App\Models\EntityAttribute;
use App\Models\EntityAttributeValue;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BulkEntityEditService
{
    public function __construct(
        private readonly EntityAttributeValueService $attributeValueService
    ) {}


    /*
    |--------------------------------------------------------------------------
    | PUNTO DE ENTRADA
    |--------------------------------------------------------------------------
    */

    public function apply(
        User $user,
        array $entityIds,
        string $operation,
        array $data = []
    ): array {

        $entityIds =
            collect(
                $entityIds
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->filter()
            ->unique()
            ->values();


        if ($entityIds->isEmpty()) {

            throw ValidationException::withMessages([
                'entity_ids' =>
                'Selecciona al menos una Entidad.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Solo Entidades del propietario
        |--------------------------------------------------------------------------
        */

        $entities =
            Entity::query()
            ->ownedBy(
                $user
            )
            ->whereIn(
                'id',
                $entityIds
            )
            ->with([
                'collections',
                'entityAttributes.attribute',
                'entityAttributes.values.option',
            ])
            ->get();


        if (
            $entities->count()
            !==
            $entityIds->count()
        ) {

            throw ValidationException::withMessages([
                'entity_ids' =>
                'Una o más Entidades seleccionadas no son válidas.',
            ]);
        }


        return DB::transaction(
            function () use (
                $user,
                $entities,
                $operation,
                $data
            ) {

                $affected =
                    match ($operation) {

                        'set_property' =>
                        $this->setProperty(
                            $user,
                            $entities,
                            $data
                        ),

                        'set_attribute' =>
                        $this->setAttribute(
                            $user,
                            $entities,
                            $data,
                            false
                        ),

                        'append_attribute' =>
                        $this->setAttribute(
                            $user,
                            $entities,
                            $data,
                            true
                        ),

                        'remove_attribute_value' =>
                        $this->removeAttributeValue(
                            $user,
                            $entities,
                            $data
                        ),

                        'clear_attribute_value' =>
                        $this->clearAttributeValue(
                            $user,
                            $entities,
                            $data
                        ),

                        'remove_attribute' =>
                        $this->removeAttribute(
                            $user,
                            $entities,
                            $data
                        ),

                        'attribute_presentation' =>
                        $this->updateAttributePresentation(
                            $user,
                            $entities,
                            $data
                        ),

                        'reorder_attributes' =>
                        $this->reorderAttributes(
                            $user,
                            $entities,
                            $data
                        ),

                        'add_collection' =>
                        $this->addCollection(
                            $user,
                            $entities,
                            $data
                        ),

                        'remove_collection' =>
                        $this->removeCollection(
                            $user,
                            $entities,
                            $data
                        ),

                        'set_collections' =>
                        $this->setCollections(
                            $user,
                            $entities,
                            $data
                        ),

                        'set_publication' =>
                        $this->setPublication(
                            $entities,
                            $data
                        ),

                        'matrix_update' =>
                        $this->matrixUpdate(
                            $user,
                            $entities,
                            $data
                        ),

                        'archive' =>
                        $this->archive(
                            $entities
                        ),

                        'delete' =>
                        $this->delete(
                            $entities
                        ),

                        default =>
                        throw ValidationException::withMessages([
                            'operation' =>
                            'La operación solicitada no es válida.',
                        ]),
                    };


                return [
                    'affected' =>
                    $affected,

                    'selected' =>
                    $entities->count(),

                    'operation' =>
                    $operation,
                ];
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PROPIEDADES
    |--------------------------------------------------------------------------
    */

    private function setProperty(
        User $user,
        SupportCollection $entities,
        array $data
    ): int {

        $property =
            $data['property']
            ?? null;


        $value =
            $data['property_value']
            ?? null;


        $allowed = [
            'entity_type_id',
            'description',
            'status',
            'visibility',
            'allow_cloning',
        ];


        if (
            ! in_array(
                $property,
                $allowed,
                true
            )
        ) {

            throw ValidationException::withMessages([
                'property' =>
                'La propiedad seleccionada no puede modificarse de forma masiva.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Validaciones
        |--------------------------------------------------------------------------
        */

        if (
            $property
            === 'entity_type_id'
        ) {

            $value =
                $value !== null
                &&
                $value !== ''
                ? (int) $value
                : null;


            if ($value !== null) {

                $this->entityType(
                    $user,
                    $value
                );
            }
        }


        if (
            $property
            === 'status'
            &&
            ! in_array(
                $value,
                [
                    'ACTIVE',
                    'INACTIVE',
                    'ARCHIVED',
                ],
                true
            )
        ) {

            throw ValidationException::withMessages([
                'property_value' =>
                'El estado no es válido.',
            ]);
        }


        if (
            $property
            === 'visibility'
            &&
            ! in_array(
                $value,
                [
                    'PUBLIC',
                    'PRIVATE',
                    'UNLISTED',
                ],
                true
            )
        ) {

            throw ValidationException::withMessages([
                'property_value' =>
                'La visibilidad no es válida.',
            ]);
        }


        if (
            $property
            === 'allow_cloning'
        ) {

            $value =
                filter_var(
                    $value,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );


            if ($value === null) {

                throw ValidationException::withMessages([
                    'property_value' =>
                    'El valor de clonación no es válido.',
                ]);
            }
        }


        if (
            $property
            === 'description'
        ) {

            $value =
                trim(
                    (string) (
                        $value
                        ?? ''
                    )
                );


            $value =
                $value !== ''
                ? $value
                : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Aplicar
        |--------------------------------------------------------------------------
        */

        foreach (
            $entities
            as $entity
        ) {

            $entity->{$property} =
                $value;


            $this->refreshPublicationState(
                $entity
            );


            $entity->save();
        }


        return $entities->count();
    }


    /*
    |--------------------------------------------------------------------------
    | ESTABLECER / AÑADIR VALOR
    |--------------------------------------------------------------------------
    */

    private function setAttribute(
        User $user,
        SupportCollection $entities,
        array $data,
        bool $append
    ): int {

        $attribute =
            $this->attribute(
                $user,
                (int) (
                    $data['attribute_id']
                    ?? 0
                )
            );


        $newValue =
            $data['attribute_value']
            ?? null;


        $affected =
            0;


        foreach (
            $entities
            as $entity
        ) {

            $value =
                $newValue;


            /*
            |--------------------------------------------------------------------------
            | APPEND
            |--------------------------------------------------------------------------
            */

            if (
                $append
                &&
                $attribute->allows_multiple
            ) {

                $current =
                    $this->currentRawValues(
                        $entity,
                        $attribute
                    );


                $incoming =
                    is_array(
                        $newValue
                    )
                    ? $newValue
                    : [
                        $newValue,
                    ];


                $value =
                    collect(
                        array_merge(
                            $current,
                            $incoming
                        )
                    )
                    ->filter(
                        fn($item) =>
                        $item !== null
                            &&
                            $item !== ''
                    )
                    ->map(
                        fn($item) =>
                        (string) $item
                    )
                    ->unique()
                    ->values()
                    ->all();
            }


            $this
                ->attributeValueService
                ->save(
                    $entity,
                    $attribute,
                    $value
                );


            $affected++;
        }


        return $affected;
    }


    /*
    |--------------------------------------------------------------------------
    | QUITAR VALORES
    |--------------------------------------------------------------------------
    */

    private function removeAttributeValue(
        User $user,
        SupportCollection $entities,
        array $data
    ): int {

        $attribute =
            $this->attribute(
                $user,
                (int) (
                    $data['attribute_id']
                    ?? 0
                )
            );


        $toRemove =
            $data['attribute_value']
            ?? null;


        $removeValues =
            collect(
                is_array(
                    $toRemove
                )
                    ? $toRemove
                    : [
                        $toRemove,
                    ]
            )
            ->filter(
                fn($value) =>
                $value !== null
                    &&
                    $value !== ''
            )
            ->map(
                fn($value) =>
                (string) $value
            )
            ->values();


        $affected =
            0;


        foreach (
            $entities
            as $entity
        ) {

            $assignment =
                $entity
                ->entityAttributes
                ->firstWhere(
                    'attribute_id',
                    $attribute->id
                );


            if (! $assignment) {
                continue;
            }


            $current =
                collect(
                    $this->currentRawValues(
                        $entity,
                        $attribute
                    )
                )
                ->map(
                    fn($value) =>
                    (string) $value
                );


            $remaining =
                $current
                ->reject(
                    fn($value) =>
                    $removeValues
                        ->contains(
                            $value
                        )
                )
                ->values()
                ->all();


            $input =
                $attribute->allows_multiple
                ? $remaining
                : (
                    $remaining[0]
                    ?? null
                );


            $this
                ->attributeValueService
                ->save(
                    $entity,
                    $attribute,
                    $input
                );


            $affected++;
        }


        return $affected;
    }


    /*
    |--------------------------------------------------------------------------
    | LIMPIAR VALOR
    |--------------------------------------------------------------------------
    */

    private function clearAttributeValue(
        User $user,
        SupportCollection $entities,
        array $data
    ): int {

        $attribute =
            $this->attribute(
                $user,
                (int) (
                    $data['attribute_id']
                    ?? 0
                )
            );


        $affected =
            0;


        foreach (
            $entities
            as $entity
        ) {

            $assignment =
                $entity
                ->entityAttributes
                ->firstWhere(
                    'attribute_id',
                    $attribute->id
                );


            if (! $assignment) {
                continue;
            }


            $this
                ->attributeValueService
                ->save(
                    $entity,
                    $attribute,
                    $attribute->allows_multiple
                        ? []
                        : null
                );


            $affected++;
        }


        return $affected;
    }


    /*
    |--------------------------------------------------------------------------
    | QUITAR ATRIBUTO COMPLETO
    |--------------------------------------------------------------------------
    */

    private function removeAttribute(
        User $user,
        SupportCollection $entities,
        array $data
    ): int {

        $attribute =
            $this->attribute(
                $user,
                (int) (
                    $data['attribute_id']
                    ?? 0
                )
            );


        $affected =
            0;


        foreach (
            $entities
            as $entity
        ) {

            $assignment =
                EntityAttribute::query()
                ->where(
                    'entity_id',
                    $entity->id
                )
                ->where(
                    'attribute_id',
                    $attribute->id
                )
                ->first();


            if (! $assignment) {
                continue;
            }


            $assignment
                ->values()
                ->delete();


            $assignment->delete();


            $affected++;
        }


        return $affected;
    }


    /*
    |--------------------------------------------------------------------------
    | PRESENTACIÓN
    |--------------------------------------------------------------------------
    */

    private function updateAttributePresentation(
        User $user,
        SupportCollection $entities,
        array $data
    ): int {

        $attribute =
            $this->attribute(
                $user,
                (int) (
                    $data['attribute_id']
                    ?? 0
                )
            );


        $updates = [];


        if (
            array_key_exists(
                'custom_label',
                $data
            )
        ) {

            $label =
                trim(
                    (string) (
                        $data['custom_label']
                        ?? ''
                    )
                );


            $updates['custom_label'] =
                $label !== ''
                ? $label
                : null;
        }


        if (
            isset(
                $data['presentation_visibility']
            )
            &&
            $data['presentation_visibility'] !== ''
        ) {

            $updates['is_visible'] =
                $data['presentation_visibility'] === '1';
        }


        if (
            isset(
                $data['presentation_featured']
            )
            &&
            $data['presentation_featured'] !== ''
        ) {

            $updates['is_featured'] =
                $data['presentation_featured'] === '1';
        }


        if (
            isset(
                $data['presentation_sort_order']
            )
            &&
            $data['presentation_sort_order'] !== null
            &&
            $data['presentation_sort_order'] !== ''
        ) {

            $updates['sort_order'] =
                (int) $data['presentation_sort_order'];
        }


        if (
            array_key_exists(
                'notes',
                $data
            )
        ) {

            $notes =
                trim(
                    (string) (
                        $data['notes']
                        ?? ''
                    )
                );


            $updates['notes'] =
                $notes !== ''
                ? $notes
                : null;
        }


        if (empty($updates)) {

            throw ValidationException::withMessages([
                'attribute_id' =>
                'No indicaste ningún cambio de presentación.',
            ]);
        }


        $affected =
            0;


        foreach (
            $entities
            as $entity
        ) {

            /*
             * No creamos un Atributo vacío solo para
             * cambiar presentación.
             */
            $assignment =
                EntityAttribute::query()
                ->where(
                    'entity_id',
                    $entity->id
                )
                ->where(
                    'attribute_id',
                    $attribute->id
                )
                ->first();


            if (! $assignment) {
                continue;
            }


            $assignment->update(
                $updates
            );


            $affected++;
        }


        return $affected;
    }


    /*
    |--------------------------------------------------------------------------
    | ORDEN DE ATRIBUTOS
    |--------------------------------------------------------------------------
    */

    private function reorderAttributes(
        User $user,
        SupportCollection $entities,
        array $data
    ): int {

        $order =
            collect(
                $data['attribute_order']
                    ?? []
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->filter()
            ->unique()
            ->values();


        if ($order->isEmpty()) {

            throw ValidationException::withMessages([
                'attribute_order' =>
                'Debes indicar el orden de los Atributos.',
            ]);
        }


        $validCount =
            Attribute::query()
            ->ownedBy(
                $user
            )
            ->whereIn(
                'id',
                $order
            )
            ->count();


        if (
            $validCount
            !==
            $order->count()
        ) {

            throw ValidationException::withMessages([
                'attribute_order' =>
                'Uno de los Atributos del orden no es válido.',
            ]);
        }


        $affected =
            0;


        foreach (
            $entities
            as $entity
        ) {

            foreach (
                $order
                as $index => $attributeId
            ) {

                $updated =
                    EntityAttribute::query()
                    ->where(
                        'entity_id',
                        $entity->id
                    )
                    ->where(
                        'attribute_id',
                        $attributeId
                    )
                    ->update([
                        'sort_order' => ($index + 1) * 10,
                    ]);


                if ($updated) {
                    $affected++;
                }
            }
        }


        return $affected;
    }


    /*
    |--------------------------------------------------------------------------
    | AÑADIR COLECCIÓN
    |--------------------------------------------------------------------------
    */

    private function addCollection(
        User $user,
        SupportCollection $entities,
        array $data
    ): int {

        $collection =
            $this->collection(
                $user,
                (int) (
                    $data['collection_id']
                    ?? 0
                )
            );


        $affected =
            0;


        foreach (
            $entities
            as $entity
        ) {

            $exists =
                $entity
                ->collections()
                ->where(
                    'collections.id',
                    $collection->id
                )
                ->exists();


            if ($exists) {
                continue;
            }


            $maxOrder =
                (int) $entity
                    ->collections()
                    ->max(
                        'collection_entity.sort_order'
                    );


            $entity
                ->collections()
                ->attach(
                    $collection->id,
                    [
                        'sort_order' =>
                        $maxOrder + 10,

                        'added_at' =>
                        now(),
                    ]
                );


            $affected++;
        }


        return $affected;
    }


    /*
    |--------------------------------------------------------------------------
    | QUITAR COLECCIÓN
    |--------------------------------------------------------------------------
    */

    private function removeCollection(
        User $user,
        SupportCollection $entities,
        array $data
    ): int {

        $collection =
            $this->collection(
                $user,
                (int) (
                    $data['collection_id']
                    ?? 0
                )
            );


        $affected =
            0;


        foreach (
            $entities
            as $entity
        ) {

            $detached =
                $entity
                ->collections()
                ->detach(
                    $collection->id
                );


            $affected +=
                (int) $detached;
        }


        return $affected;
    }


    /*
    |--------------------------------------------------------------------------
    | REEMPLAZAR COLECCIONES
    |--------------------------------------------------------------------------
    */

    private function setCollections(
        User $user,
        SupportCollection $entities,
        array $data
    ): int {

        $collectionIds =
            collect(
                $data['collection_ids']
                    ?? []
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->filter()
            ->unique()
            ->values();


        if ($collectionIds->isNotEmpty()) {

            $valid =
                Collection::query()
                ->ownedBy(
                    $user
                )
                ->whereIn(
                    'id',
                    $collectionIds
                )
                ->count();


            if (
                $valid
                !==
                $collectionIds->count()
            ) {

                throw ValidationException::withMessages([
                    'collection_ids' =>
                    'Una de las Colecciones no es válida.',
                ]);
            }
        }


        foreach (
            $entities
            as $entity
        ) {

            $existing =
                $entity
                ->collections()
                ->get()
                ->keyBy(
                    'id'
                );


            $sync = [];


            foreach (
                $collectionIds
                as $index => $collectionId
            ) {

                $old =
                    $existing->get(
                        $collectionId
                    );


                $sync[$collectionId] = [
                    'sort_order' => ($index + 1) * 10,

                    'added_at' =>
                    $old
                        ?->pivot
                        ?->added_at
                        ?? now(),
                ];
            }


            $entity
                ->collections()
                ->sync(
                    $sync
                );
        }


        return $entities->count();
    }


    /*
    |--------------------------------------------------------------------------
    | PUBLICACIÓN
    |--------------------------------------------------------------------------
    */

    private function setPublication(
        SupportCollection $entities,
        array $data
    ): int {

        $status =
            $data['publication_status']
            ?? null;


        $visibility =
            $data['publication_visibility']
            ?? null;


        $allowCloning =
            $data['publication_allow_cloning']
            ?? '';


        if (
            ! $status
            &&
            ! $visibility
            &&
            $allowCloning === ''
        ) {

            throw ValidationException::withMessages([
                'publication_status' =>
                'Selecciona al menos una propiedad de publicación.',
            ]);
        }


        foreach (
            $entities
            as $entity
        ) {

            if ($status) {

                $entity->status =
                    $status;
            }


            if ($visibility) {

                $entity->visibility =
                    $visibility;
            }


            if (
                $allowCloning !== ''
            ) {

                $entity->allow_cloning =
                    $allowCloning === '1';
            }


            $this->refreshPublicationState(
                $entity
            );


            $entity->save();
        }


        return $entities->count();
    }


    /*
    |--------------------------------------------------------------------------
    | MATRIZ
    |--------------------------------------------------------------------------
    */

    private function matrixUpdate(
        User $user,
        SupportCollection $entities,
        array $data
    ): int {

        $payload =
            $data['matrix']
            ?? [];


        if (
            ! is_array(
                $payload
            )
        ) {

            throw ValidationException::withMessages([
                'matrix_payload' =>
                'La matriz no es válida.',
            ]);
        }


        $entitiesById =
            $entities->keyBy(
                'id'
            );


        $affected =
            0;


        foreach (
            $payload
            as $entityId => $changes
        ) {

            $entity =
                $entitiesById->get(
                    (int) $entityId
                );


            if (
                ! $entity
                ||
                ! is_array(
                    $changes
                )
            ) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Propiedades
            |--------------------------------------------------------------------------
            */

            $properties =
                $changes['properties']
                ?? [];


            if (
                array_key_exists(
                    'name',
                    $properties
                )
            ) {

                $name =
                    trim(
                        (string) $properties['name']
                    );


                if ($name === '') {

                    throw ValidationException::withMessages([
                        'matrix_payload' =>
                        "La Entidad {$entity->code} no puede quedarse sin nombre.",
                    ]);
                }


                if (
                    mb_strlen(
                        $name
                    ) > 150
                ) {

                    throw ValidationException::withMessages([
                        'matrix_payload' =>
                        "El nombre de {$entity->code} supera los 150 caracteres.",
                    ]);
                }


                if (
                    $name
                    !==
                    $entity->name
                ) {

                    $entity->name =
                        $name;


                    $entity->slug =
                        $this->uniqueSlug(
                            $user,
                            $entity,
                            $name
                        );
                }
            }


            if (
                array_key_exists(
                    'description',
                    $properties
                )
            ) {

                $description =
                    trim(
                        (string) (
                            $properties['description']
                            ?? ''
                        )
                    );


                $entity->description =
                    $description !== ''
                    ? $description
                    : null;
            }


            if (
                array_key_exists(
                    'entity_type_id',
                    $properties
                )
            ) {

                $typeId =
                    $properties['entity_type_id'];


                if (
                    $typeId === ''
                    ||
                    $typeId === null
                ) {

                    $entity->entity_type_id =
                        null;
                } else {

                    $type =
                        $this->entityType(
                            $user,
                            (int) $typeId
                        );


                    $entity->entity_type_id =
                        $type->id;
                }
            }


            if (
                isset(
                    $properties['status']
                )
            ) {

                $status =
                    $properties['status'];


                if (
                    ! in_array(
                        $status,
                        [
                            'ACTIVE',
                            'INACTIVE',
                            'ARCHIVED',
                        ],
                        true
                    )
                ) {

                    throw ValidationException::withMessages([
                        'matrix_payload' =>
                        'Uno de los estados no es válido.',
                    ]);
                }


                $entity->status =
                    $status;
            }


            if (
                isset(
                    $properties['visibility']
                )
            ) {

                $visibility =
                    $properties['visibility'];


                if (
                    ! in_array(
                        $visibility,
                        [
                            'PUBLIC',
                            'PRIVATE',
                            'UNLISTED',
                        ],
                        true
                    )
                ) {

                    throw ValidationException::withMessages([
                        'matrix_payload' =>
                        'Una de las visibilidades no es válida.',
                    ]);
                }


                $entity->visibility =
                    $visibility;
            }


            if (
                array_key_exists(
                    'allow_cloning',
                    $properties
                )
            ) {

                $entity->allow_cloning =
                    (bool) $properties['allow_cloning'];
            }


            $this->refreshPublicationState(
                $entity
            );


            $entity->save();


            /*
            |--------------------------------------------------------------------------
            | Valores de Atributos
            |--------------------------------------------------------------------------
            */

            $attributeChanges =
                $changes['attributes']
                ?? [];


            foreach (
                $attributeChanges
                as $attributeId => $value
            ) {

                $attribute =
                    $this->attribute(
                        $user,
                        (int) $attributeId
                    );


                $this
                    ->attributeValueService
                    ->save(
                        $entity,
                        $attribute,
                        $value
                    );
            }


            $affected++;
        }


        return $affected;
    }


    /*
    |--------------------------------------------------------------------------
    | ARCHIVAR
    |--------------------------------------------------------------------------
    */

    private function archive(
        SupportCollection $entities
    ): int {

        foreach (
            $entities
            as $entity
        ) {

            $entity->status =
                'ARCHIVED';


            $entity->published_at =
                null;


            $entity->save();
        }


        return $entities->count();
    }


    /*
    |--------------------------------------------------------------------------
    | BORRADO LÓGICO
    |--------------------------------------------------------------------------
    */

    private function delete(
        SupportCollection $entities
    ): int {

        foreach (
            $entities
            as $entity
        ) {

            /*
             * Entity utiliza SoftDeletes.
             * Conservamos imagen y relaciones históricas.
             */
            $entity->delete();
        }


        return $entities->count();
    }


    /*
    |--------------------------------------------------------------------------
    | RAW VALUES
    |--------------------------------------------------------------------------
    */

    private function currentRawValues(
        Entity $entity,
        Attribute $attribute
    ): array {

        $assignment =
            $entity
            ->entityAttributes
            ->firstWhere(
                'attribute_id',
                $attribute->id
            );


        if (! $assignment) {
            return [];
        }


        return $assignment
            ->values
            ->map(
                fn(
                    EntityAttributeValue $value
                ) =>
                $this->rawValue(
                    $attribute,
                    $value
                )
            )
            ->filter(
                fn($value) =>
                $value !== null
                    &&
                    $value !== ''
            )
            ->values()
            ->all();
    }


    private function rawValue(
        Attribute $attribute,
        EntityAttributeValue $value
    ): mixed {

        return match ($attribute->data_type) {

            'OPTION' =>
            $value->attribute_option_id,

            'INTEGER' =>
            $value->integer_value,

            'DECIMAL' =>
            $value->decimal_value,

            'BOOLEAN' =>
            $value->boolean_value === null
                ? null
                : (
                    $value->boolean_value
                    ? '1'
                    : '0'
                ),

            'DATE' =>
            $value
                ->date_value
                ?->format(
                    'Y-m-d'
                ),

            'COLOR' =>
            $value->color_value,

            default =>
            $value->text_value
                ??
                $value->custom_value,
        };
    }


    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function attribute(
        User $user,
        int $attributeId
    ): Attribute {

        return Attribute::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->with([
                'options' =>
                fn($query) =>
                $query->where(
                    'status',
                    'ACTIVE'
                ),
            ])
            ->findOrFail(
                $attributeId
            );
    }


    private function collection(
        User $user,
        int $collectionId
    ): Collection {

        return Collection::query()
            ->ownedBy(
                $user
            )
            ->findOrFail(
                $collectionId
            );
    }


    private function entityType(
        User $user,
        int $entityTypeId
    ): EntityType {

        return EntityType::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->findOrFail(
                $entityTypeId
            );
    }


    /*
    |--------------------------------------------------------------------------
    | PUBLICACIÓN
    |--------------------------------------------------------------------------
    */

    private function refreshPublicationState(
        Entity $entity
    ): void {

        if (
            $entity->visibility
            === 'PUBLIC'
            &&
            $entity->status
            === 'ACTIVE'
        ) {

            $entity->published_at =
                $entity->published_at
                ?? now();

            return;
        }


        $entity->published_at =
            null;
    }


    /*
    |--------------------------------------------------------------------------
    | SLUG PARA MATRIX
    |--------------------------------------------------------------------------
    */

    private function uniqueSlug(
        User $user,
        Entity $entity,
        string $name
    ): string {

        $base =
            Str::slug(
                $name
            );


        if ($base === '') {

            $base =
                'entidad';
        }


        $slug =
            $base;


        $counter =
            2;


        while (
            Entity::withTrashed()
            ->where(
                'user_id',
                $user->id
            )
            ->whereKeyNot(
                $entity->id
            )
            ->where(
                'slug',
                $slug
            )
            ->exists()
        ) {

            $slug =
                $base
                . '-'
                . $counter;


            $counter++;
        }


        return $slug;
    }
}
