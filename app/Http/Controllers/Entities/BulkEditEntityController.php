<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\BulkEditEntityRequest;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\Collection;
use App\Models\Entity;
use App\Models\EntityAttribute;
use App\Models\EntityAttributeValue;
use App\Models\EntityType;
use App\Models\User;
use App\Services\Entities\BulkEntityEditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BulkEditEntityController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CENTRO DE GESTIÓN MASIVA
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {

        $this->authorize(
            'viewAny',
            Entity::class
        );


        $user =
            $request->user();


        /*
        |--------------------------------------------------------------------------
        | Filtros generales
        |--------------------------------------------------------------------------
        */

        $search =
            trim(
                (string) $request->input(
                    'search'
                )
            );


        $status =
            (string) $request->input(
                'status'
            );


        $visibility =
            (string) $request->input(
                'visibility'
            );


        $type =
            (string) $request->input(
                'type'
            );


        $image =
            (string) $request->input(
                'image'
            );


        $attributesState =
            (string) $request->input(
                'attributes_state'
            );


        $collectionId =
            $request->filled(
                'collection'
            )
            ? $request->integer(
                'collection'
            )
            : null;


        $sort =
            (string) $request->input(
                'sort',
                'name_asc'
            );


        /*
        |--------------------------------------------------------------------------
        | Reglas dinámicas
        |--------------------------------------------------------------------------
        |
        | Máximo 3.
        |
        | attribute_filters[0][attribute_id]
        | attribute_filters[0][operator]
        | attribute_filters[0][value]
        | attribute_filters[0][value2]
        | attribute_filters[0][logic]
        |
        */

        $attributeFilters =
            collect(
                (array) $request->input(
                    'attribute_filters',
                    []
                )
            )
            ->take(3)
            ->map(
                fn($rule) => [

                    'attribute_id' =>
                    ! empty($rule['attribute_id']
                        ?? null)
                        ? (int) $rule['attribute_id']
                        : null,

                    'operator' =>
                    (string) (
                        $rule['operator']
                        ?? 'eq'
                    ),

                    'value' =>
                    $rule['value']
                        ?? '',

                    'value2' =>
                    $rule['value2']
                        ?? '',

                    'logic' =>
                    strtoupper(
                        (string) (
                            $rule['logic']
                            ?? 'AND'
                        )
                    ) === 'OR'
                        ? 'OR'
                        : 'AND',
                ]
            )
            ->filter(
                fn($rule) =>
                $rule['attribute_id']
                    !== null
            )
            ->values()
            ->all();


        /*
        |--------------------------------------------------------------------------
        | Recursos
        |--------------------------------------------------------------------------
        */

        $entityTypes =
            EntityType::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->withCount(
                'entities'
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->get();


        $attributes =
            Attribute::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->with([
                'groups',

                'options' =>
                fn($query) =>
                $query
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy(
                        'name'
                    ),
            ])
            ->withCount(
                'entityAttributes'
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->get();


        $groups =
            AttributeGroup::query()
            ->ownedBy(
                $user
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->get();


        $collections =
            Collection::query()
            ->ownedBy(
                $user
            )
            ->where(
                'status',
                '<>',
                'ARCHIVED'
            )
            ->withCount(
                'entities'
            )
            ->orderBy(
                'name'
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Estadísticas globales
        |--------------------------------------------------------------------------
        */

        $base =
            Entity::query()
            ->ownedBy(
                $user
            );


        $stats = [

            'total' => (clone $base)
                ->count(),

            'active' => (clone $base)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'public' => (clone $base)
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->count(),

            'with_attributes' => (clone $base)
                ->whereHas(
                    'entityAttributes'
                )
                ->count(),

            'without_image' => (clone $base)
                ->whereNull(
                    'image'
                )
                ->count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | Query
        |--------------------------------------------------------------------------
        */

        $query =
            Entity::query()
            ->ownedBy(
                $user
            )
            ->with([
                'entityType',
                'collections',

                'entityAttributes.attribute.groups',

                'entityAttributes.values.option',
            ])
            ->withCount([
                'entityAttributes',
                'collections',
            ]);


        /*
        |--------------------------------------------------------------------------
        | Filtros generales
        |--------------------------------------------------------------------------
        */

        $query
            ->when(
                $search,

                fn($query) =>
                $query->where(
                    function ($subquery) use ($search) {

                        $subquery
                            ->where(
                                'name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'code',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'description',
                                'like',
                                "%{$search}%"
                            );
                    }
                )
            )

            ->when(
                $status,

                fn($query) =>
                $query->where(
                    'status',
                    $status
                )
            )

            ->when(
                $visibility,

                fn($query) =>
                $query->where(
                    'visibility',
                    $visibility
                )
            )

            ->when(
                $type === 'none',

                fn($query) =>
                $query->whereNull(
                    'entity_type_id'
                )
            )

            ->when(
                $type
                    &&
                    $type !== 'none',

                fn($query) =>
                $query->where(
                    'entity_type_id',
                    $type
                )
            )

            ->when(
                $image === 'yes',

                fn($query) =>
                $query->whereNotNull(
                    'image'
                )
            )

            ->when(
                $image === 'no',

                fn($query) =>
                $query->whereNull(
                    'image'
                )
            )

            ->when(
                $attributesState === 'yes',

                fn($query) =>
                $query->whereHas(
                    'entityAttributes'
                )
            )

            ->when(
                $attributesState === 'no',

                fn($query) =>
                $query->whereDoesntHave(
                    'entityAttributes'
                )
            )

            ->when(
                $collectionId,

                fn($query) =>
                $query->whereHas(
                    'collections',

                    fn($collectionQuery) =>
                    $collectionQuery
                        ->where(
                            'collections.id',
                            $collectionId
                        )
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Reglas avanzadas AND / OR
        |--------------------------------------------------------------------------
        */

        if (
            ! empty($attributeFilters)
        ) {

            $query->where(
                function (
                    Builder $groupQuery
                ) use (
                    $attributeFilters,
                    $user
                ) {

                    foreach (
                        $attributeFilters
                        as $index => $rule
                    ) {

                        $attribute =
                            Attribute::query()
                            ->ownedBy(
                                $user
                            )
                            ->active()
                            ->find(
                                $rule['attribute_id']
                            );


                        if (! $attribute) {
                            continue;
                        }


                        $booleanMethod =
                            $index > 0
                            &&
                            $rule['logic'] === 'OR'

                            ? 'orWhere'

                            : 'where';


                        $groupQuery
                            ->{$booleanMethod}(
                                function (
                                    Builder $ruleQuery
                                ) use (
                                    $attribute,
                                    $rule
                                ) {

                                    $this->applyAttributeRule(
                                        $ruleQuery,
                                        $attribute,
                                        $rule
                                    );
                                }
                            );
                    }
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Total coincidente ANTES del límite
        |--------------------------------------------------------------------------
        */

        $matchedCount =
            (clone $query)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Orden
        |--------------------------------------------------------------------------
        */

        match ($sort) {

            'name_desc' =>
            $query->orderByDesc(
                'name'
            ),

            'newest' =>
            $query
                ->orderByDesc(
                    'created_at'
                )
                ->orderByDesc(
                    'id'
                ),

            'oldest' =>
            $query
                ->orderBy(
                    'created_at'
                )
                ->orderBy(
                    'id'
                ),

            'code_asc' =>
            $query->orderBy(
                'code'
            ),

            'code_desc' =>
            $query->orderByDesc(
                'code'
            ),

            'attributes_desc' =>
            $query
                ->orderByDesc(
                    'entity_attributes_count'
                )
                ->orderBy(
                    'name'
                ),

            default =>
            $query->orderBy(
                'name'
            ),
        };


        /*
        |--------------------------------------------------------------------------
        | Máximo 500 en el Workspace
        |--------------------------------------------------------------------------
        */

        $entities =
            $query
            ->limit(
                500
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Payload para Alpine
        |--------------------------------------------------------------------------
        */

        $entityPayload =
            $entities
            ->map(
                fn(Entity $entity) =>
                $this->entityPayload(
                    $entity
                )
            )
            ->values()
            ->all();


        $attributePayload =
            $attributes
            ->map(
                fn(Attribute $attribute) => [

                    'id' =>
                    (string) $attribute->id,

                    'code' =>
                    $attribute->code,

                    'name' =>
                    $attribute->name,

                    'data_type' =>
                    $attribute->data_type,

                    'data_type_label' =>
                    $attribute->data_type_label,

                    'allows_multiple' =>
                    (bool) $attribute->allows_multiple,

                    'is_required' =>
                    (bool) $attribute->is_required,

                    'icon' =>
                    $attribute->icon
                        ?: $attribute->data_type_icon,

                    'color' =>
                    $attribute->color
                        ?: '#6366F1',

                    'image_url' =>
                    $attribute->image_url,

                    'sort_order' =>
                    (int) $attribute->sort_order,

                    'hierarchy_level' =>
                    (int) $attribute->hierarchy_level,

                    'groups' =>
                    $attribute
                        ->groups
                        ->map(
                            fn($group) => [

                                'id' =>
                                (string) $group->id,

                                'name' =>
                                $group->name,

                                'sort_order' =>
                                (int) (
                                    $group
                                    ->pivot
                                    ->sort_order
                                    ?? 0
                                ),
                            ]
                        )
                        ->values()
                        ->all(),

                    'options' =>
                    $attribute
                        ->options
                        ->map(
                            fn($option) => [

                                'id' =>
                                (string) $option->id,

                                'name' =>
                                $option->name,

                                'code' =>
                                $option->code,

                                'image_url' =>
                                $option->image_url,

                                'icon' =>
                                $option->icon
                                    ?: '◆',

                                'color' =>
                                $option->color
                                    ?: '#7C3AED',
                            ]
                        )
                        ->values()
                        ->all(),
                ]
            )
            ->values()
            ->all();


        $typePayload =
            $entityTypes
            ->map(
                fn($type) => [

                    'id' =>
                    (string) $type->id,

                    'name' =>
                    $type->name,

                    'code' =>
                    $type->code,

                    'icon' =>
                    $type->icon
                        ?: '◇',

                    'color' =>
                    $type->color
                        ?: '#6366F1',
                ]
            )
            ->values()
            ->all();


        $collectionPayload =
            $collections
            ->map(
                fn($collection) => [

                    'id' =>
                    (string) $collection->id,

                    'name' =>
                    $collection->name,

                    'code' =>
                    $collection->code,

                    'icon' =>
                    $collection->icon
                        ?: '▤',

                    'image_url' =>
                    $collection->image_url,

                    'entities_count' =>
                    $collection->entities_count,
                ]
            )
            ->values()
            ->all();


        return view(
            'entities.bulk-edit.index',
            compact(
                'entities',
                'entityPayload',
                'attributePayload',
                'typePayload',
                'collectionPayload',

                'entityTypes',
                'attributes',
                'groups',
                'collections',

                'stats',
                'matchedCount',

                'search',
                'status',
                'visibility',
                'type',
                'image',
                'attributesState',
                'collectionId',
                'sort',
                'attributeFilters'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | APLICAR OPERACIÓN
    |--------------------------------------------------------------------------
    */

    public function update(
        BulkEditEntityRequest $request,
        BulkEntityEditService $service
    ): RedirectResponse {

        $this->authorize(
            'updateAny',
            Entity::class
        );


        $data =
            $request->validated();


        /*
        |--------------------------------------------------------------------------
        | Valor JSON de Atributo
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $data['attribute_value_json']
            )
            &&
            $data['attribute_value_json'] !== ''
        ) {

            $decoded =
                json_decode(
                    $data['attribute_value_json'],
                    true
                );


            $data['attribute_value'] =
                json_last_error()
                === JSON_ERROR_NONE

                ? $decoded

                : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Matriz JSON
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $data['matrix_payload']
            )
        ) {

            $data['matrix'] =
                json_decode(
                    $data['matrix_payload'],
                    true
                )
                ?? [];
        }


        $result =
            $service->apply(
                $request->user(),
                $data['entity_ids'],
                $data['operation'],
                $data
            );


        return redirect()
            ->back()
            ->with(
                'success',
                $this->successMessage(
                    $data['operation'],
                    $result['affected']
                )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | REGLA DINÁMICA
    |--------------------------------------------------------------------------
    */

    private function applyAttributeRule(
        Builder $query,
        Attribute $attribute,
        array $rule
    ): void {

        $operator =
            $rule['operator']
            ?? 'eq';


        $value =
            $rule['value']
            ?? null;


        $value2 =
            $rule['value2']
            ?? null;


        /*
        |--------------------------------------------------------------------------
        | Tiene / No tiene
        |--------------------------------------------------------------------------
        */

        if (
            $operator
            === 'present'
        ) {

            $query->whereHas(
                'entityAttributes',

                fn($attributeQuery) =>
                $attributeQuery->where(
                    'attribute_id',
                    $attribute->id
                )
            );


            return;
        }


        if (
            $operator
            === 'missing'
        ) {

            $query->whereDoesntHave(
                'entityAttributes',

                fn($attributeQuery) =>
                $attributeQuery->where(
                    'attribute_id',
                    $attribute->id
                )
            );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Comparar valor
        |--------------------------------------------------------------------------
        */

        $query->whereHas(
            'entityAttributes',

            function (
                $attributeQuery
            ) use (
                $attribute,
                $operator,
                $value,
                $value2
            ) {

                $attributeQuery
                    ->where(
                        'attribute_id',
                        $attribute->id
                    )
                    ->whereHas(
                        'values',

                        function (
                            $valueQuery
                        ) use (
                            $attribute,
                            $operator,
                            $value,
                            $value2
                        ) {

                            /*
                            |--------------------------------------------------------------------------
                            | OPTION
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $attribute->data_type
                                === 'OPTION'
                            ) {

                                $valueQuery->where(
                                    'attribute_option_id',
                                    (int) $value
                                );


                                return;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | INTEGER / DECIMAL
                            |--------------------------------------------------------------------------
                            */

                            if (
                                in_array(
                                    $attribute->data_type,
                                    [
                                        'INTEGER',
                                        'DECIMAL',
                                    ],
                                    true
                                )
                            ) {

                                $column =
                                    $attribute->data_type
                                    === 'INTEGER'

                                    ? 'integer_value'

                                    : 'decimal_value';


                                if (
                                    $operator
                                    === 'between'
                                ) {

                                    $valueQuery
                                        ->whereBetween(
                                            $column,
                                            [
                                                $value,
                                                $value2,
                                            ]
                                        );


                                    return;
                                }


                                $sqlOperator =
                                    match ($operator) {

                                        'gt' =>
                                        '>',

                                        'gte' =>
                                        '>=',

                                        'lt' =>
                                        '<',

                                        'lte' =>
                                        '<=',

                                        default =>
                                        '=',
                                    };


                                $valueQuery->where(
                                    $column,
                                    $sqlOperator,
                                    $value
                                );


                                return;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | BOOLEAN
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $attribute->data_type
                                === 'BOOLEAN'
                            ) {

                                $valueQuery->where(
                                    'boolean_value',
                                    filter_var(
                                        $value,
                                        FILTER_VALIDATE_BOOLEAN
                                    )
                                );


                                return;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | DATE
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $attribute->data_type
                                === 'DATE'
                            ) {

                                $sqlOperator =
                                    match ($operator) {

                                        'gt',
                                        'after' =>
                                        '>',

                                        'gte' =>
                                        '>=',

                                        'lt',
                                        'before' =>
                                        '<',

                                        'lte' =>
                                        '<=',

                                        default =>
                                        '=',
                                    };


                                $valueQuery->whereDate(
                                    'date_value',
                                    $sqlOperator,
                                    $value
                                );


                                return;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | COLOR
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $attribute->data_type
                                === 'COLOR'
                            ) {

                                $valueQuery->where(
                                    'color_value',
                                    $value
                                );


                                return;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | TEXT
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $operator
                                === 'contains'
                            ) {

                                $valueQuery->where(
                                    'text_value',
                                    'like',
                                    "%{$value}%"
                                );


                                return;
                            }


                            $valueQuery->where(
                                'text_value',
                                $value
                            );
                        }
                    );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PAYLOAD DE ENTIDAD
    |--------------------------------------------------------------------------
    */

    private function entityPayload(
        Entity $entity
    ): array {

        $attributeValues = [];

        $attributeDisplays = [];

        $attributeAssignments = [];


        foreach (
            $entity->entityAttributes
            as $assignment
        ) {

            $attribute =
                $assignment->attribute;


            if (! $attribute) {
                continue;
            }


            $rawValues =
                $assignment
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
                ->values();


            $displayValues =
                $assignment
                ->values
                ->map(
                    fn(
                        EntityAttributeValue $value
                    ) =>
                    $value->displayValue()
                )
                ->filter()
                ->values();


            $attributeValues[(string) $attribute->id] =
                $attribute->allows_multiple
                ? $rawValues
                ->map(
                    fn($value) =>
                    (string) $value
                )
                ->all()

                : (
                    $rawValues
                    ->first() !== null

                    ? (string) $rawValues
                        ->first()

                    : ''
                );


            $attributeDisplays[(string) $attribute->id] =
                $displayValues
                ->implode(
                    ', '
                );


            $group =
                $attribute
                ->groups
                ->sortBy(
                    fn($group) =>
                    $group
                        ->pivot
                        ->sort_order
                        ?? 0
                )
                ->first();


            $attributeAssignments[(string) $attribute->id] = [

                'custom_label' =>
                $assignment->custom_label,

                'is_visible' =>
                (bool) $assignment->is_visible,

                'is_featured' =>
                (bool) $assignment->is_featured,

                'sort_order' =>
                (int) $assignment->sort_order,

                'notes' =>
                $assignment->notes,

                'group_name' =>
                $group?->name
                    ?? 'Otros',

                'hierarchy_level' =>
                (int) $attribute->hierarchy_level,
            ];
        }


        return [

            'id' =>
            (string) $entity->id,

            'code' =>
            $entity->code,

            'name' =>
            $entity->name,

            'description' =>
            $entity->description
                ?? '',

            'image_url' =>
            $entity->image_url,

            'entity_type_id' =>
            $entity->entity_type_id
                ? (string) $entity->entity_type_id
                : '',

            'entity_type_name' =>
            $entity
                ->entityType
                ?->name
                ?? 'Sin tipo',

            'status' =>
            $entity->status,

            'status_label' =>
            $entity->status_label,

            'visibility' =>
            $entity->visibility,

            'visibility_label' =>
            $entity->visibility_label,

            'allow_cloning' =>
            (bool) $entity->allow_cloning,

            'collections' =>
            $entity
                ->collections
                ->map(
                    fn($collection) => [

                        'id' =>
                        (string) $collection->id,

                        'name' =>
                        $collection->name,
                    ]
                )
                ->values()
                ->all(),

            'collection_ids' =>
            $entity
                ->collections
                ->pluck(
                    'id'
                )
                ->map(
                    fn($id) =>
                    (string) $id
                )
                ->values()
                ->all(),

            'attribute_values' =>
            $attributeValues,

            'attribute_displays' =>
            $attributeDisplays,

            'attribute_assignments' =>
            $attributeAssignments,

            'attribute_ids' =>
            array_keys(
                $attributeValues
            ),

            'entity_attributes_count' =>
            $entity->entity_attributes_count,

            'collections_count' =>
            $entity->collections_count,
        ];
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
    | MENSAJES
    |--------------------------------------------------------------------------
    */

    private function successMessage(
        string $operation,
        int $affected
    ): string {

        return match ($operation) {

            'set_property' =>
            "{$affected} Entidades actualizadas.",

            'set_attribute',
            'append_attribute' =>
            "Característica aplicada a {$affected} Entidades.",

            'remove_attribute_value',
            'clear_attribute_value' =>
            "Valores actualizados en {$affected} Entidades.",

            'remove_attribute' =>
            "Atributo eliminado de {$affected} Entidades.",

            'attribute_presentation' =>
            "Presentación actualizada en {$affected} asignaciones.",

            'reorder_attributes' =>
            "{$affected} posiciones de características actualizadas.",

            'add_collection' =>
            "{$affected} Entidades añadidas a la Colección.",

            'remove_collection' =>
            "{$affected} relaciones con la Colección eliminadas.",

            'set_collections' =>
            "Colecciones reemplazadas en {$affected} Entidades.",

            'set_publication' =>
            "Publicación actualizada en {$affected} Entidades.",

            'matrix_update' =>
            "{$affected} Entidades actualizadas desde la matriz.",

            'archive' =>
            "{$affected} Entidades archivadas.",

            'delete' =>
            "{$affected} Entidades eliminadas.",

            default =>
            'Operación realizada correctamente.',
        };
    }
}
