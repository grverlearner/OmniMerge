<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\AttributeOption;
use App\Models\Collection as LibraryCollection;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function __invoke(
        Request $request
    ): View {
        /** @var User $user */
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Estadísticas principales
        |--------------------------------------------------------------------------
        */

        $statistics = [
            'entities' =>
            Entity::query()
                ->ownedBy($user)
                ->count(),

            'active_entities' =>
            Entity::query()
                ->ownedBy($user)
                ->active()
                ->count(),

            'public_entities' =>
            Entity::query()
                ->ownedBy($user)
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->count(),

            'entity_types' =>
            EntityType::query()
                ->ownedBy($user)
                ->count(),

            'attributes' =>
            Attribute::query()
                ->ownedBy($user)
                ->count(),

            'catalog_options' =>
            AttributeOption::query()
                ->ownedBy($user)
                ->count(),

            'catalog_attributes' =>
            Attribute::query()
                ->ownedBy($user)
                ->where(
                    'data_type',
                    'OPTION'
                )
                ->count(),

            'attribute_groups' =>
            AttributeGroup::query()
                ->ownedBy($user)
                ->count(),

            'collections' =>
            LibraryCollection::query()
                ->ownedBy($user)
                ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Total de recursos
        |--------------------------------------------------------------------------
        */

        $statistics['resources_total'] =
            $statistics['entities']
            + $statistics['entity_types']
            + $statistics['attributes']
            + $statistics['catalog_options']
            + $statistics['attribute_groups']
            + $statistics['collections'];

        /*
        |--------------------------------------------------------------------------
        | Salud / organización de la Biblioteca
        |--------------------------------------------------------------------------
        */

        $entitiesWithoutType =
            Entity::query()
            ->ownedBy($user)
            ->whereNull(
                'entity_type_id'
            )
            ->count();

        $entitiesWithoutAttributes =
            Entity::query()
            ->ownedBy($user)
            ->whereDoesntHave(
                'entityAttributes'
            )
            ->count();

        $entitiesWithoutImage =
            Entity::query()
            ->ownedBy($user)
            ->whereNull(
                'image'
            )
            ->count();

        $attributesWithoutGroups =
            Attribute::query()
            ->ownedBy($user)
            ->whereDoesntHave(
                'groups'
            )
            ->count();

        $emptyGroups =
            AttributeGroup::query()
            ->ownedBy($user)
            ->whereDoesntHave(
                'attributes'
            )
            ->count();

        $emptyCollections =
            LibraryCollection::query()
            ->ownedBy($user)
            ->whereDoesntHave(
                'entities'
            )
            ->count();

        $emptyCatalogs =
            Attribute::query()
            ->ownedBy($user)
            ->where(
                'data_type',
                'OPTION'
            )
            ->whereDoesntHave(
                'options',
                fn($query) =>
                $query->where(
                    'status',
                    'ACTIVE'
                )
            )
            ->count();

        $healthItems = collect([
            [
                'label' =>
                'Entidades sin tipo',

                'description' =>
                'Creaciones todavía sin clasificación.',

                'count' =>
                $entitiesWithoutType,

                'icon' =>
                '◇',

                'url' =>
                route(
                    'entities.index'
                ),
            ],

            [
                'label' =>
                'Entidades sin características',

                'description' =>
                'Entidades que aún no utilizan atributos.',

                'count' =>
                $entitiesWithoutAttributes,

                'icon' =>
                '☷',

                'url' =>
                route(
                    'entities.index'
                ),
            ],

            [
                'label' =>
                'Entidades sin imagen',

                'description' =>
                'Pueden mejorarse visualmente.',

                'count' =>
                $entitiesWithoutImage,

                'icon' =>
                '▧',

                'url' =>
                route(
                    'entities.index'
                ),
            ],

            [
                'label' =>
                'Atributos sin grupo',

                'description' =>
                'Características todavía sin organización.',

                'count' =>
                $attributesWithoutGroups,

                'icon' =>
                '▥',

                'url' =>
                route(
                    'attributes.index'
                ),
            ],

            [
                'label' =>
                'Catálogos vacíos',

                'description' =>
                'Atributos seleccionables sin elementos.',

                'count' =>
                $emptyCatalogs,

                'icon' =>
                '◆',

                'url' =>
                route(
                    'attributes.index',
                    [
                        'data_type' =>
                        'OPTION',
                    ]
                ),
            ],

            [
                'label' =>
                'Grupos vacíos',

                'description' =>
                'Grupos que todavía no contienen atributos.',

                'count' =>
                $emptyGroups,

                'icon' =>
                '▥',

                'url' =>
                route(
                    'attribute-groups.index'
                ),
            ],

            [
                'label' =>
                'Colecciones vacías',

                'description' =>
                'Colecciones sin entidades asignadas.',

                'count' =>
                $emptyCollections,

                'icon' =>
                '▤',

                'url' =>
                route(
                    'collections.index'
                ),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Entidades recientes
        |--------------------------------------------------------------------------
        */

        $recentEntities =
            Entity::query()
            ->ownedBy($user)
            ->with(
                'entityType'
            )
            ->withCount([
                'entityAttributes',
                'collections',
            ])
            ->latest(
                'updated_at'
            )
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Atributos recientes
        |--------------------------------------------------------------------------
        */

        $recentAttributes =
            Attribute::query()
            ->ownedBy($user)
            ->withCount([
                'options',
                'entityAttributes',
                'groups',
            ])
            ->latest(
                'updated_at'
            )
            ->limit(8)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Elementos de Catálogo recientes
        |--------------------------------------------------------------------------
        */

        $recentOptions =
            AttributeOption::query()
            ->ownedBy($user)
            ->with(
                'attribute'
            )
            ->latest(
                'updated_at'
            )
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Colecciones recientes
        |--------------------------------------------------------------------------
        */

        $recentCollections =
            LibraryCollection::query()
            ->ownedBy($user)
            ->withCount(
                'entities'
            )
            ->latest(
                'updated_at'
            )
            ->limit(6)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Grupos recientes
        |--------------------------------------------------------------------------
        */

        $recentGroups =
            AttributeGroup::query()
            ->ownedBy($user)
            ->with([
                'attributes',
            ])
            ->withCount(
                'attributes'
            )
            ->latest(
                'updated_at'
            )
            ->limit(6)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Tipos recientes
        |--------------------------------------------------------------------------
        */

        $recentTypes =
            EntityType::query()
            ->ownedBy($user)
            ->withCount(
                'entities'
            )
            ->latest(
                'updated_at'
            )
            ->limit(6)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Continuar trabajando
        |--------------------------------------------------------------------------
        */

        $workspaceItems =
            $this
            ->workspaceItems(
                $user
            )
            ->take(10)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Actividad reciente
        |--------------------------------------------------------------------------
        |
        | Por ahora se infiere usando created_at / updated_at.
        |
        | Más adelante podrá ser reemplazada por activity_logs.
        |
        */

        $activityItems =
            $workspaceItems
            ->take(8)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Distribución por Tipo de Entidad
        |--------------------------------------------------------------------------
        */

        $typeDistribution =
            EntityType::query()
            ->ownedBy($user)
            ->withCount(
                'entities'
            )
            ->orderByDesc(
                'entities_count'
            )
            ->limit(7)
            ->get()
            ->map(
                fn(EntityType $type) => [
                    'name' =>
                    $type->name,

                    'count' =>
                    $type->entities_count,

                    'icon' =>
                    $type->icon
                        ?: '◇',

                    'color' =>
                    $type->color
                        ?: '#6366F1',

                    'url' =>
                    route(
                        'entity-types.show',
                        $type
                    ),
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Añadir "Sin tipo"
        |--------------------------------------------------------------------------
        */

        if ($entitiesWithoutType > 0) {
            $typeDistribution->push([
                'name' =>
                'Sin tipo',

                'count' =>
                $entitiesWithoutType,

                'icon' =>
                '?',

                'color' =>
                '#94A3B8',

                'url' =>
                route(
                    'entities.index'
                ),
            ]);
        }

        $distributionMax =
            max(
                1,
                (int) $typeDistribution
                    ->max('count')
            );

        $typeDistribution =
            $typeDistribution
            ->map(
                function (
                    array $item
                ) use (
                    $distributionMax
                ) {
                    $item['percentage'] =
                        round(
                            (
                                $item['count']
                                /
                                $distributionMax
                            )
                                * 100,
                            1
                        );

                    return $item;
                }
            )
            ->sortByDesc(
                'count'
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Catálogos más grandes
        |--------------------------------------------------------------------------
        */

        $topCatalogs =
            Attribute::query()
            ->ownedBy($user)
            ->where(
                'data_type',
                'OPTION'
            )
            ->withCount([
                'options as active_options_count' =>
                fn($query) =>
                $query->where(
                    'status',
                    'ACTIVE'
                ),
            ])
            ->orderByDesc(
                'active_options_count'
            )
            ->limit(7)
            ->get();

        $catalogMax =
            max(
                1,
                (int) $topCatalogs
                    ->max(
                        'active_options_count'
                    )
            );

        /*
        |--------------------------------------------------------------------------
        | Vista
        |--------------------------------------------------------------------------
        */

        return view(
            'dashboard.index',
            compact(
                'user',

                'statistics',
                'healthItems',

                'recentEntities',
                'recentAttributes',
                'recentOptions',
                'recentCollections',
                'recentGroups',
                'recentTypes',

                'workspaceItems',
                'activityItems',

                'typeDistribution',
                'distributionMax',

                'topCatalogs',
                'catalogMax'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BÚSQUEDA RÁPIDA DEL DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function search(
        Request $request
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $search = trim(
            (string) $request->input(
                'q'
            )
        );

        if (
            mb_strlen(
                $search
            ) < 2
        ) {
            return response()->json([
                'results' => [],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Entidades
        |--------------------------------------------------------------------------
        */

        $entities =
            Entity::query()
            ->ownedBy($user)
            ->with(
                'entityType'
            )
            ->where(
                function ($query) use (
                    $search
                ) {
                    $query
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'code',
                            'like',
                            "%{$search}%"
                        );
                }
            )
            ->limit(5)
            ->get()
            ->map(
                fn(Entity $entity) => [
                    'id' =>
                    $entity->id,

                    'kind' =>
                    'entity',

                    'kind_label' =>
                    'Entidad',

                    'title' =>
                    $entity->name,

                    'subtitle' =>
                    $entity->entityType?->name
                        ?? 'Sin tipo',

                    'code' =>
                    $entity->code,

                    'image_url' =>
                    $entity->image_url,

                    'icon' =>
                    $entity->entityType?->icon
                        ?: '✦',

                    'url' =>
                    route(
                        'entities.show',
                        $entity
                    ),
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Atributos
        |--------------------------------------------------------------------------
        */

        $attributes =
            Attribute::query()
            ->ownedBy($user)
            ->where(
                function ($query) use (
                    $search
                ) {
                    $query
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'code',
                            'like',
                            "%{$search}%"
                        );
                }
            )
            ->limit(5)
            ->get()
            ->map(
                fn(Attribute $attribute) => [
                    'id' =>
                    $attribute->id,

                    'kind' =>
                    'attribute',

                    'kind_label' =>
                    'Atributo',

                    'title' =>
                    $attribute->name,

                    'subtitle' =>
                    $attribute->data_type_label,

                    'code' =>
                    $attribute->code,

                    'image_url' =>
                    $attribute->image_url,

                    'icon' =>
                    $attribute->icon
                        ?: $attribute->data_type_icon,

                    'url' =>
                    route(
                        'attributes.show',
                        $attribute
                    ),
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Elementos del Catálogo
        |--------------------------------------------------------------------------
        */

        $options =
            AttributeOption::query()
            ->ownedBy($user)
            ->with(
                'attribute'
            )
            ->where(
                function ($query) use (
                    $search
                ) {
                    $query
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'code',
                            'like',
                            "%{$search}%"
                        );
                }
            )
            ->limit(5)
            ->get()
            ->map(
                fn(AttributeOption $option) => [
                    'id' =>
                    $option->id,

                    'kind' =>
                    'catalog',

                    'kind_label' =>
                    'Catálogo',

                    'title' =>
                    $option->name,

                    'subtitle' =>
                    $option->attribute?->name
                        ?? 'Sin atributo',

                    'code' =>
                    $option->code,

                    'image_url' =>
                    $option->image_url,

                    'icon' =>
                    $option->icon
                        ?: '◆',

                    'url' =>
                    route(
                        'attribute-options.show',
                        $option
                    ),
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Colecciones
        |--------------------------------------------------------------------------
        */

        $collections =
            LibraryCollection::query()
            ->ownedBy($user)
            ->where(
                function ($query) use (
                    $search
                ) {
                    $query
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'code',
                            'like',
                            "%{$search}%"
                        );
                }
            )
            ->limit(5)
            ->get()
            ->map(
                fn(
                    LibraryCollection $collection
                ) => [
                    'id' =>
                    $collection->id,

                    'kind' =>
                    'collection',

                    'kind_label' =>
                    'Colección',

                    'title' =>
                    $collection->name,

                    'subtitle' =>
                    'Colección',

                    'code' =>
                    $collection->code,

                    'image_url' =>
                    $collection->image_url,

                    'icon' =>
                    $collection->icon
                        ?: '▤',

                    'url' =>
                    route(
                        'collections.show',
                        $collection
                    ),
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Tipos
        |--------------------------------------------------------------------------
        */

        $types =
            EntityType::query()
            ->ownedBy($user)
            ->where(
                function ($query) use (
                    $search
                ) {
                    $query
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'code',
                            'like',
                            "%{$search}%"
                        );
                }
            )
            ->limit(5)
            ->get()
            ->map(
                fn(EntityType $type) => [
                    'id' =>
                    $type->id,

                    'kind' =>
                    'type',

                    'kind_label' =>
                    'Tipo',

                    'title' =>
                    $type->name,

                    'subtitle' =>
                    'Tipo de entidad',

                    'code' =>
                    $type->code,

                    'image_url' =>
                    $type->image_url,

                    'icon' =>
                    $type->icon
                        ?: '◇',

                    'url' =>
                    route(
                        'entity-types.show',
                        $type
                    ),
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Grupos
        |--------------------------------------------------------------------------
        */

        $groups =
            AttributeGroup::query()
            ->ownedBy($user)
            ->where(
                function ($query) use (
                    $search
                ) {
                    $query
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'code',
                            'like',
                            "%{$search}%"
                        );
                }
            )
            ->limit(5)
            ->get()
            ->map(
                fn(
                    AttributeGroup $group
                ) => [
                    'id' =>
                    $group->id,

                    'kind' =>
                    'group',

                    'kind_label' =>
                    'Grupo',

                    'title' =>
                    $group->name,

                    'subtitle' =>
                    $group->layout_label,

                    'code' =>
                    $group->code,

                    'image_url' =>
                    null,

                    'icon' =>
                    $group->icon
                        ?: '▥',

                    'url' =>
                    route(
                        'attribute-groups.show',
                        $group
                    ),
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Combinar
        |--------------------------------------------------------------------------
        */

        $results = collect()
            ->concat($entities)
            ->concat($attributes)
            ->concat($options)
            ->concat($collections)
            ->concat($types)
            ->concat($groups)
            ->take(24)
            ->values();

        return response()->json([
            'results' =>
            $results,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | WORKSPACE / CONTINUAR TRABAJANDO
    |--------------------------------------------------------------------------
    */

    private function workspaceItems(
        User $user
    ): Collection {
        $items = collect();

        /*
        |--------------------------------------------------------------------------
        | Entidades
        |--------------------------------------------------------------------------
        */

        Entity::query()
            ->ownedBy($user)
            ->with(
                'entityType'
            )
            ->latest(
                'updated_at'
            )
            ->limit(4)
            ->get()
            ->each(
                function (
                    Entity $entity
                ) use (
                    $items
                ) {
                    $items->push([
                        'type' =>
                        'Entidad',

                        'icon' =>
                        $entity->entityType?->icon
                            ?: '✦',

                        'color' =>
                        $entity->entityType?->color
                            ?: '#6366F1',

                        'name' =>
                        $entity->name,

                        'code' =>
                        $entity->code,

                        'subtitle' =>
                        $entity->entityType?->name
                            ?? 'Sin tipo',

                        'image_url' =>
                        $entity->image_url,

                        'url' =>
                        route(
                            'entities.show',
                            $entity
                        ),

                        'created_at' =>
                        $entity->created_at,

                        'updated_at' =>
                        $entity->updated_at,

                        'action' =>
                        $this->activityAction(
                            $entity->created_at,
                            $entity->updated_at
                        ),
                    ]);
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Atributos
        |--------------------------------------------------------------------------
        */

        Attribute::query()
            ->ownedBy($user)
            ->latest(
                'updated_at'
            )
            ->limit(4)
            ->get()
            ->each(
                function (
                    Attribute $attribute
                ) use (
                    $items
                ) {
                    $items->push([
                        'type' =>
                        'Atributo',

                        'icon' =>
                        $attribute->icon
                            ?: $attribute->data_type_icon,

                        'color' =>
                        $attribute->color
                            ?: '#6366F1',

                        'name' =>
                        $attribute->name,

                        'code' =>
                        $attribute->code,

                        'subtitle' =>
                        $attribute->data_type_label,

                        'image_url' =>
                        $attribute->image_url,

                        'url' =>
                        route(
                            'attributes.show',
                            $attribute
                        ),

                        'created_at' =>
                        $attribute->created_at,

                        'updated_at' =>
                        $attribute->updated_at,

                        'action' =>
                        $this->activityAction(
                            $attribute->created_at,
                            $attribute->updated_at
                        ),
                    ]);
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Catálogos
        |--------------------------------------------------------------------------
        */

        AttributeOption::query()
            ->ownedBy($user)
            ->with(
                'attribute'
            )
            ->latest(
                'updated_at'
            )
            ->limit(4)
            ->get()
            ->each(
                function (
                    AttributeOption $option
                ) use (
                    $items
                ) {
                    $items->push([
                        'type' =>
                        'Catálogo',

                        'icon' =>
                        $option->icon
                            ?: '◆',

                        'color' =>
                        $option->color
                            ?: '#7C3AED',

                        'name' =>
                        $option->name,

                        'code' =>
                        $option->code,

                        'subtitle' =>
                        $option->attribute?->name
                            ?? 'Elemento de Catálogo',

                        'image_url' =>
                        $option->image_url,

                        'url' =>
                        route(
                            'attribute-options.show',
                            $option
                        ),

                        'created_at' =>
                        $option->created_at,

                        'updated_at' =>
                        $option->updated_at,

                        'action' =>
                        $this->activityAction(
                            $option->created_at,
                            $option->updated_at
                        ),
                    ]);
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Colecciones
        |--------------------------------------------------------------------------
        */

        LibraryCollection::query()
            ->ownedBy($user)
            ->latest(
                'updated_at'
            )
            ->limit(4)
            ->get()
            ->each(
                function (
                    LibraryCollection $collection
                ) use (
                    $items
                ) {
                    $items->push([
                        'type' =>
                        'Colección',

                        'icon' =>
                        $collection->icon
                            ?: '▤',

                        'color' =>
                        $collection->color
                            ?: '#0891B2',

                        'name' =>
                        $collection->name,

                        'code' =>
                        $collection->code,

                        'subtitle' =>
                        'Colección',

                        'image_url' =>
                        $collection->image_url,

                        'url' =>
                        route(
                            'collections.show',
                            $collection
                        ),

                        'created_at' =>
                        $collection->created_at,

                        'updated_at' =>
                        $collection->updated_at,

                        'action' =>
                        $this->activityAction(
                            $collection->created_at,
                            $collection->updated_at
                        ),
                    ]);
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Grupos
        |--------------------------------------------------------------------------
        */

        AttributeGroup::query()
            ->ownedBy($user)
            ->latest(
                'updated_at'
            )
            ->limit(3)
            ->get()
            ->each(
                function (
                    AttributeGroup $group
                ) use (
                    $items
                ) {
                    $items->push([
                        'type' =>
                        'Grupo',

                        'icon' =>
                        $group->icon
                            ?: '▥',

                        'color' =>
                        $group->color
                            ?: '#6366F1',

                        'name' =>
                        $group->name,

                        'code' =>
                        $group->code,

                        'subtitle' =>
                        $group->layout_label,

                        'image_url' =>
                        null,

                        'url' =>
                        route(
                            'attribute-groups.show',
                            $group
                        ),

                        'created_at' =>
                        $group->created_at,

                        'updated_at' =>
                        $group->updated_at,

                        'action' =>
                        $this->activityAction(
                            $group->created_at,
                            $group->updated_at
                        ),
                    ]);
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Tipos
        |--------------------------------------------------------------------------
        */

        EntityType::query()
            ->ownedBy($user)
            ->latest(
                'updated_at'
            )
            ->limit(3)
            ->get()
            ->each(
                function (
                    EntityType $type
                ) use (
                    $items
                ) {
                    $items->push([
                        'type' =>
                        'Tipo',

                        'icon' =>
                        $type->icon
                            ?: '◇',

                        'color' =>
                        $type->color
                            ?: '#6366F1',

                        'name' =>
                        $type->name,

                        'code' =>
                        $type->code,

                        'subtitle' =>
                        'Tipo de entidad',

                        'image_url' =>
                        $type->image_url,

                        'url' =>
                        route(
                            'entity-types.show',
                            $type
                        ),

                        'created_at' =>
                        $type->created_at,

                        'updated_at' =>
                        $type->updated_at,

                        'action' =>
                        $this->activityAction(
                            $type->created_at,
                            $type->updated_at
                        ),
                    ]);
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Recientes primero
        |--------------------------------------------------------------------------
        */

        return $items
            ->sortByDesc(
                fn(array $item) =>
                $item['updated_at']
                    ?->timestamp
                    ?? 0
            )
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Crear / actualizar
    |--------------------------------------------------------------------------
    */

    private function activityAction(
        $createdAt,
        $updatedAt
    ): string {
        if (
            ! $createdAt
            ||
            ! $updatedAt
        ) {
            return 'Modificado';
        }

        return $createdAt
            ->diffInSeconds(
                $updatedAt
            ) <= 2
            ? 'Creado'
            : 'Actualizado';
    }
}
