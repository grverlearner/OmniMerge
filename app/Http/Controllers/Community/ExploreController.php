<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Collection;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\User;
use App\Services\Community\CommunityCloneService;
use App\Services\Versions\VersionResolverService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExploreController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {

        $allowedTabs = [
            'all',
            'entities',
            'collections',
            'attributes',
            'catalogs',
            'creators',
        ];

        $tab =
            (string) $request->input(
                'tab',
                'all'
            );

        if (
            ! in_array(
                $tab,
                $allowedTabs,
                true
            )
        ) {
            $tab = 'all';
        }


        /*
        |--------------------------------------------------------------------------
        | Parámetros generales
        |--------------------------------------------------------------------------
        */

        $search =
            trim(
                (string) $request->input(
                    'search'
                )
            );

        $sort =
            (string) $request->input(
                'sort',
                'popular'
            );

        $groupBy =
            (string) $request->input(
                'group_by'
            );

        $creator =
            trim(
                (string) $request->input(
                    'creator'
                )
            );

        $image =
            (string) $request->input(
                'image'
            );

        $cloning =
            (string) $request->input(
                'cloning'
            );

        $period =
            (string) $request->input(
                'period'
            );

        $perPage =
            (int) $request->input(
                'per_page',
                24
            );

        if (
            ! in_array(
                $perPage,
                [
                    12,
                    24,
                    48,
                    96,
                ],
                true
            )
        ) {
            $perPage = 24;
        }


        /*
        |--------------------------------------------------------------------------
        | Entidades
        |--------------------------------------------------------------------------
        */

        $entityTypeId =
            $request->integer(
                'entity_type'
            )
            ?: null;

        $filterAttributeId =
            $request->integer(
                'filter_attribute'
            )
            ?: null;

        $filterOptionId =
            $request->integer(
                'filter_option'
            )
            ?: null;


        /*
        |--------------------------------------------------------------------------
        | Atributos
        |--------------------------------------------------------------------------
        */

        $dataType =
            (string) $request->input(
                'data_type'
            );

        $multiple =
            (string) $request->input(
                'multiple'
            );

        $catalogState =
            (string) $request->input(
                'catalog_state'
            );


        /*
        |--------------------------------------------------------------------------
        | Catálogos
        |--------------------------------------------------------------------------
        */

        $attributeId =
            $request->integer(
                'attribute'
            )
            ?: null;

        $hierarchy =
            (string) $request->input(
                'hierarchy'
            );

        $usage =
            (string) $request->input(
                'usage'
            );


        /*
        |--------------------------------------------------------------------------
        | Colecciones
        |--------------------------------------------------------------------------
        */

        $collectionSize =
            (string) $request->input(
                'collection_size'
            );


        /*
        |--------------------------------------------------------------------------
        | Recursos auxiliares
        |--------------------------------------------------------------------------
        */

        $entityTypes =
            EntityType::query()
            ->whereHas(
                'entities',
                fn(Builder $query) =>
                $this->publicEntityScope(
                    $query
                )
            )
            ->orderBy(
                'name'
            )
            ->get();


        $publicAttributes =
            Attribute::query()
            ->where(
                'scope',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
            )
            ->with([
                'options' =>
                fn($query) =>
                $query
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->orderBy(
                        'name'
                    ),
            ])
            ->orderBy(
                'name'
            )
            ->get();


        $publicCreators =
            User::query()
            ->where(
                'status',
                'ACTIVE'
            )
            ->where(
                'profile_visibility',
                'PUBLIC'
            )
            ->where(
                function ($query) {

                    $query
                        ->whereHas(
                            'entities',
                            fn($entities) =>
                            $this->publicEntityScope(
                                $entities
                            )
                        )
                        ->orWhereHas(
                            'collections',
                            fn($collections) =>
                            $this->publicCollectionScope(
                                $collections
                            )
                        )
                        ->orWhereHas(
                            'attributes',
                            fn($attributes) =>
                            $this->publicAttributeScope(
                                $attributes
                            )
                        );
                }
            )
            ->orderBy(
                'username'
            )
            ->limit(100)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Estadísticas
        |--------------------------------------------------------------------------
        */

        $statistics = [
            'entities' =>
            $this
                ->entityQuery()
                ->count(),

            'collections' =>
            $this
                ->collectionQuery()
                ->count(),

            'attributes' =>
            $this
                ->attributeQuery()
                ->count(),

            'catalogs' =>
            $this
                ->catalogQuery()
                ->count(),

            'creators' =>
            User::query()
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->where(
                    'profile_visibility',
                    'PUBLIC'
                )
                ->where(
                    function ($query) {

                        $query
                            ->whereHas(
                                'entities',
                                fn($entities) =>
                                $this->publicEntityScope(
                                    $entities
                                )
                            )
                            ->orWhereHas(
                                'collections',
                                fn($collections) =>
                                $this->publicCollectionScope(
                                    $collections
                                )
                            )
                            ->orWhereHas(
                                'attributes',
                                fn($attributes) =>
                                $this->publicAttributeScope(
                                    $attributes
                                )
                            );
                    }
                )
                ->count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | Variables de resultados
        |--------------------------------------------------------------------------
        */

        $entities = null;
        $collections = null;
        $attributes = null;
        $catalogs = null;
        $creators = null;

        $allResults = null;


        /*
        |--------------------------------------------------------------------------
        | TODO
        |--------------------------------------------------------------------------
        */

        if ($tab === 'all') {

            $allResults = [
                'entities' =>
                $this
                    ->applyEntityFilters(
                        $this->entityQuery(),
                        $search,
                        $creator,
                        $image,
                        $cloning,
                        $period,
                        $entityTypeId,
                        $filterAttributeId,
                        $filterOptionId,
                        $sort
                    )
                    ->limit(6)
                    ->get(),

                'collections' =>
                $this
                    ->applyCollectionFilters(
                        $this->collectionQuery(),
                        $search,
                        $creator,
                        $image,
                        $cloning,
                        $period,
                        $collectionSize,
                        $sort
                    )
                    ->limit(6)
                    ->get(),

                'attributes' =>
                $this
                    ->applyAttributeFilters(
                        $this->attributeQuery(),
                        $search,
                        $creator,
                        $image,
                        $cloning,
                        $period,
                        $dataType,
                        $multiple,
                        $catalogState,
                        $sort
                    )
                    ->limit(6)
                    ->get(),

                'catalogs' =>
                $this
                    ->applyCatalogFilters(
                        $this->catalogQuery(),
                        $search,
                        $creator,
                        $image,
                        $attributeId,
                        $hierarchy,
                        $usage,
                        $sort
                    )
                    ->limit(8)
                    ->get(),

                'creators' =>
                $this
                    ->creatorQuery(
                        $search,
                        $sort
                    )
                    ->limit(6)
                    ->get(),
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | ENTIDADES
        |--------------------------------------------------------------------------
        */

        if ($tab === 'entities') {

            $entities =
                $this
                ->applyEntityFilters(
                    $this->entityQuery(),
                    $search,
                    $creator,
                    $image,
                    $cloning,
                    $period,
                    $entityTypeId,
                    $filterAttributeId,
                    $filterOptionId,
                    $sort
                )
                ->paginate(
                    $perPage
                )
                ->withQueryString();
        }


        /*
        |--------------------------------------------------------------------------
        | COLECCIONES
        |--------------------------------------------------------------------------
        */

        if ($tab === 'collections') {

            $collections =
                $this
                ->applyCollectionFilters(
                    $this->collectionQuery(),
                    $search,
                    $creator,
                    $image,
                    $cloning,
                    $period,
                    $collectionSize,
                    $sort
                )
                ->paginate(
                    $perPage
                )
                ->withQueryString();
        }


        /*
        |--------------------------------------------------------------------------
        | ATRIBUTOS
        |--------------------------------------------------------------------------
        */

        if ($tab === 'attributes') {

            $attributes =
                $this
                ->applyAttributeFilters(
                    $this->attributeQuery(),
                    $search,
                    $creator,
                    $image,
                    $cloning,
                    $period,
                    $dataType,
                    $multiple,
                    $catalogState,
                    $sort
                )
                ->paginate(
                    $perPage
                )
                ->withQueryString();
        }


        /*
        |--------------------------------------------------------------------------
        | CATÁLOGOS
        |--------------------------------------------------------------------------
        */

        if ($tab === 'catalogs') {

            $catalogs =
                $this
                ->applyCatalogFilters(
                    $this->catalogQuery(),
                    $search,
                    $creator,
                    $image,
                    $attributeId,
                    $hierarchy,
                    $usage,
                    $sort
                )
                ->paginate(
                    $perPage
                )
                ->withQueryString();
        }


        /*
        |--------------------------------------------------------------------------
        | CREADORES
        |--------------------------------------------------------------------------
        */

        if ($tab === 'creators') {

            $creators =
                $this
                ->creatorQuery(
                    $search,
                    $sort
                )
                ->paginate(
                    $perPage
                )
                ->withQueryString();
        }


        return view(
            'community.index',
            compact(
                'tab',
                'search',
                'sort',
                'groupBy',
                'creator',
                'image',
                'cloning',
                'period',
                'perPage',

                'entityTypeId',
                'filterAttributeId',
                'filterOptionId',

                'dataType',
                'multiple',
                'catalogState',

                'attributeId',
                'hierarchy',
                'usage',

                'collectionSize',

                'entityTypes',
                'publicAttributes',
                'publicCreators',

                'statistics',

                'entities',
                'collections',
                'attributes',
                'catalogs',
                'creators',

                'allResults'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SUGERENCIAS DEL BUSCADOR
    |--------------------------------------------------------------------------
    */

    public function search(
        Request $request
    ): JsonResponse {

        $search =
            trim(
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


        $results = collect();


        $this
            ->applyEntityFilters(
                $this->entityQuery(),
                $search,
                '',
                '',
                '',
                '',
                null,
                null,
                null,
                'popular'
            )
            ->limit(4)
            ->get()
            ->each(
                function (Entity $entity) use ($results) {

                    $results->push([
                        'type' =>
                        'Entidad',

                        'title' =>
                        $entity->public_display_name,

                        'subtitle' =>
                        $entity->entityType?->name
                            ?? 'Sin tipo',

                        'image' =>
                        $entity->public_image_url,

                        'icon' =>
                        $entity->entityType?->icon
                            ?: '✦',

                        'url' =>
                        route(
                            'community.entities.show',
                            $entity
                        ),
                    ]);
                }
            );


        $this
            ->applyAttributeFilters(
                $this->attributeQuery(),
                $search,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'popular'
            )
            ->limit(4)
            ->get()
            ->each(
                function (Attribute $attribute) use ($results) {

                    $results->push([
                        'type' =>
                        'Atributo',

                        'title' =>
                        $attribute->name,

                        'subtitle' =>
                        $attribute->data_type_label,

                        'image' =>
                        $attribute->image_url,

                        'icon' =>
                        $attribute->icon
                            ?: $attribute->data_type_icon,

                        'url' =>
                        route(
                            'community.attributes.show',
                            $attribute
                        ),
                    ]);
                }
            );


        $this
            ->applyCatalogFilters(
                $this->catalogQuery(),
                $search,
                '',
                '',
                null,
                '',
                '',
                'popular'
            )
            ->limit(4)
            ->get()
            ->each(
                function (AttributeOption $option) use ($results) {

                    $results->push([
                        'type' =>
                        'Catálogo',

                        'title' =>
                        $option->name,

                        'subtitle' =>
                        $option->attribute?->name,

                        'image' =>
                        $option->image_url,

                        'icon' =>
                        $option->icon
                            ?: '◆',

                        'url' =>
                        route(
                            'community.catalogs.show',
                            $option
                        ),
                    ]);
                }
            );


        $this
            ->creatorQuery(
                $search,
                'popular'
            )
            ->limit(4)
            ->get()
            ->each(
                function (User $creator) use ($results) {

                    $results->push([
                        'type' =>
                        'Creador',

                        'title' =>
                        $creator->name,

                        'subtitle' =>
                        '@' . $creator->username,

                        'image' =>
                        $creator->avatar_url,

                        'icon' =>
                        $creator->initials,

                        'url' =>
                        route(
                            'community.creators.show',
                            $creator->username
                        ),
                    ]);
                }
            );


        return response()->json([
            'results' =>
            $results
                ->take(16)
                ->values(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DETALLE ENTIDAD
    |--------------------------------------------------------------------------
    */

    public function entity(
        Request $request,
        Entity $entity,
        VersionResolverService $resolver
    ): View {

        $this->ensurePublicEntity(
            $entity
        );

        $entity->load([
            'creator',
            'entityType',
            'entityAttributes.attribute',
            'entityAttributes.values.option',
            'presentation.entityVersion.version',
            'presentation.entityVersion.parent',
            'presentation.entityVersion.versionAttributes.attribute.groups',
            'presentation.entityVersion.versionAttributes.values.option',
            'presentation.mediaImage',
            'collections' =>
            fn($query) =>
            $query
                ->where(
                    'collections.visibility',
                    'PUBLIC'
                )
                ->where(
                    'collections.status',
                    'ACTIVE'
                ),
        ]);

        /*
|--------------------------------------------------------------------------
| Características públicas
|--------------------------------------------------------------------------
*/

        $publicEffectiveAttributes =
            null;


        $publicEntityVersion =
            $entity
            ->public_entity_version;


        if (
            $publicEntityVersion
        ) {

            $publicEffectiveAttributes =
                $resolver
                ->effectiveAttributes(
                    $publicEntityVersion
                );
        }


        $this->recordView(
            $request,
            'ENTITY',
            $entity->id
        );

        $entity->increment(
            'views_count'
        );


        $relatedEntities =
            $this
            ->entityQuery()
            ->whereKeyNot(
                $entity->id
            )
            ->when(
                $entity->entity_type_id,

                fn($query) =>
                $query->where(
                    'entity_type_id',
                    $entity->entity_type_id
                )
            )
            ->orderByDesc(
                'clones_count'
            )
            ->limit(6)
            ->get();


        return view(
            'community.entity',
            compact(
                'entity',
                'relatedEntities',
                'publicEntityVersion',
                'publicEffectiveAttributes'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DETALLE COLECCIÓN
    |--------------------------------------------------------------------------
    */

    public function collection(
        Request $request,
        Collection $collection
    ): View {

        $this->ensurePublicCollection(
            $collection
        );

        $collection->load([
            'creator',

            'entities' =>
            fn($query) =>
            $query
                ->where(
                    'entities.visibility',
                    'PUBLIC'
                )
                ->where(
                    'entities.status',
                    'ACTIVE'
                )
                ->whereNotNull(
                    'entities.published_at'
                )
                ->with(
                    'entityType'
                ),
        ]);


        $this->recordView(
            $request,
            'COLLECTION',
            $collection->id
        );

        $collection->increment(
            'views_count'
        );


        return view(
            'community.collection',
            compact(
                'collection'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DETALLE ATRIBUTO
    |--------------------------------------------------------------------------
    */

    public function attribute(
        Request $request,
        Attribute $attribute
    ): View {

        $this->ensurePublicAttribute(
            $attribute
        );

        $attribute->load([
            'creator',

            'options' =>
            fn($query) =>
            $query
                ->where(
                    'status',
                    'ACTIVE'
                ),

            'groups',
        ])
            ->loadCount(
                'entityAttributes'
            );


        $this->recordView(
            $request,
            'ATTRIBUTE',
            $attribute->id
        );

        $attribute->increment(
            'views_count'
        );


        return view(
            'community.attribute',
            compact(
                'attribute'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DETALLE CATÁLOGO
    |--------------------------------------------------------------------------
    */

    public function catalog(
        Request $request,
        AttributeOption $attributeOption
    ): View {

        $this->ensurePublicOption(
            $attributeOption
        );


        $attributeOption->load([
            'attribute.creator',
            'user',
            'parent',
            'children',
        ])
            ->loadCount([
                'values',
                'children',
            ]);


        $relatedOptions =
            AttributeOption::query()
            ->where(
                'attribute_id',
                $attributeOption->attribute_id
            )
            ->whereKeyNot(
                $attributeOption->id
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->with([
                'attribute',
                'user',
            ])
            ->withCount(
                'values'
            )
            ->orderByDesc(
                'values_count'
            )
            ->limit(8)
            ->get();


        $this->recordView(
            $request,
            'CATALOG_OPTION',
            $attributeOption->id
        );


        return view(
            'community.catalog',
            [
                'option' =>
                $attributeOption,

                'relatedOptions' =>
                $relatedOptions,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CLONAR ENTIDAD
    |--------------------------------------------------------------------------
    */

    public function cloneEntity(
        Request $request,
        Entity $entity,
        CommunityCloneService $service
    ): RedirectResponse {

        $this->ensurePublicEntity(
            $entity
        );

        /** @var User $user */
        $user =
            $request->user();


        abort_if(
            $entity->user_id
                ===
                $user->id,

            422,

            'No necesitas copiar tu propia entidad.'
        );


        $clone =
            $service->cloneEntity(
                $entity,
                $user
            );


        return redirect()
            ->route(
                'entities.show',
                $clone
            )
            ->with(
                'success',
                'Entidad copiada a tu Biblioteca.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | CLONAR COLECCIÓN
    |--------------------------------------------------------------------------
    */

    public function cloneCollection(
        Request $request,
        Collection $collection,
        CommunityCloneService $service
    ): RedirectResponse {

        $this->ensurePublicCollection(
            $collection
        );

        /** @var User $user */
        $user =
            $request->user();


        abort_if(
            $collection->user_id
                ===
                $user->id,

            422,

            'No necesitas copiar tu propia colección.'
        );


        $clone =
            $service->cloneCollection(
                $collection,
                $user
            );


        return redirect()
            ->route(
                'collections.show',
                $clone
            )
            ->with(
                'success',
                'Colección copiada a tu Biblioteca.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | CLONAR ATRIBUTO
    |--------------------------------------------------------------------------
    */

    public function cloneAttribute(
        Request $request,
        Attribute $attribute,
        CommunityCloneService $service
    ): RedirectResponse {

        $this->ensurePublicAttribute(
            $attribute
        );

        /** @var User $user */
        $user =
            $request->user();


        abort_if(
            $attribute->user_id
                ===
                $user->id,

            422,

            'No necesitas copiar tu propio atributo.'
        );


        $clone =
            $service->cloneAttribute(
                $attribute,
                $user
            );


        return redirect()
            ->route(
                'attributes.show',
                $clone
            )
            ->with(
                'success',
                'Atributo copiado a tu Biblioteca.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | CLONAR ELEMENTO DE CATÁLOGO
    |--------------------------------------------------------------------------
    */

    public function cloneCatalog(
        Request $request,
        AttributeOption $attributeOption,
        CommunityCloneService $service
    ): RedirectResponse {

        $this->ensurePublicOption(
            $attributeOption
        );

        /** @var User $user */
        $user =
            $request->user();


        abort_if(
            $attributeOption->user_id
                ===
                $user->id,

            422,

            'No necesitas copiar tu propio elemento.'
        );


        $clone =
            $service->cloneOption(
                $attributeOption,
                $user
            );


        return redirect()
            ->route(
                'attribute-options.show',
                $clone
            )
            ->with(
                'success',
                'Elemento copiado a tu Biblioteca.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | CONSULTA BASE ENTIDADES
    |--------------------------------------------------------------------------
    */

    private function entityQuery(): Builder
    {
        return Entity::query()
            ->where(
                'visibility',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
            )
            ->with([
                'creator',
                'entityType',
                'presentation.entityVersion.version',
                'presentation.mediaImage',

                'clones' =>
                fn($query) =>
                $query->where(
                    'user_id',
                    auth()->id()
                ),
            ])
            ->withCount([
                'entityAttributes',
                'collections',
            ]);
    }


    /*
    |--------------------------------------------------------------------------
    | FILTROS ENTIDAD
    |--------------------------------------------------------------------------
    */

    private function applyEntityFilters(
        Builder $query,
        string $search,
        string $creator,
        string $image,
        string $cloning,
        string $period,
        ?int $entityTypeId,
        ?int $filterAttributeId,
        ?int $filterOptionId,
        string $sort
    ): Builder {

        $query
            ->when(
                $search,

                function ($query) use ($search) {

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
                                )

                                ->orWhereHas(
                                    'creator',

                                    fn($creatorQuery) =>
                                    $creatorQuery
                                        ->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                        ->orWhere(
                                            'username',
                                            'like',
                                            "%{$search}%"
                                        )
                                )

                                ->orWhereHas(
                                    'entityType',

                                    fn($type) =>
                                    $type
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
                                )

                                ->orWhereHas(
                                    'entityAttributes',

                                    function ($assignment) use ($search) {

                                        $assignment
                                            ->whereHas(
                                                'attribute',

                                                fn($attribute) =>
                                                $attribute
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
                                            )

                                            ->orWhereHas(
                                                'values',

                                                function ($values) use ($search) {

                                                    $values
                                                        ->where(
                                                            'text_value',
                                                            'like',
                                                            "%{$search}%"
                                                        )
                                                        ->orWhere(
                                                            'custom_value',
                                                            'like',
                                                            "%{$search}%"
                                                        )
                                                        ->orWhereHas(
                                                            'option',

                                                            fn($option) =>
                                                            $option->where(
                                                                'name',
                                                                'like',
                                                                "%{$search}%"
                                                            )
                                                        );
                                                }
                                            );
                                    }
                                );
                        }
                    );
                }
            )

            ->when(
                $creator,

                fn($query) =>
                $query->whereHas(
                    'creator',

                    fn($creatorQuery) =>
                    $creatorQuery->where(
                        'username',
                        $creator
                    )
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
                $cloning === 'yes',

                fn($query) =>
                $query->where(
                    'allow_cloning',
                    true
                )
            )

            ->when(
                $cloning === 'no',

                fn($query) =>
                $query->where(
                    'allow_cloning',
                    false
                )
            )

            ->when(
                $entityTypeId,

                fn($query) =>
                $query->where(
                    'entity_type_id',
                    $entityTypeId
                )
            )

            ->when(
                $filterAttributeId,

                function ($query) use (
                    $filterAttributeId,
                    $filterOptionId
                ) {

                    $query->whereHas(
                        'entityAttributes',

                        function ($assignment) use (
                            $filterAttributeId,
                            $filterOptionId
                        ) {

                            $assignment->where(
                                'attribute_id',
                                $filterAttributeId
                            );


                            if ($filterOptionId) {

                                $assignment->whereHas(
                                    'values',

                                    fn($values) =>
                                    $values->where(
                                        'attribute_option_id',
                                        $filterOptionId
                                    )
                                );
                            }
                        }
                    );
                }
            );


        $this->applyPeriod(
            $query,
            $period
        );

        $this->applyStandardSort(
            $query,
            $sort
        );


        return $query;
    }


    /*
    |--------------------------------------------------------------------------
    | COLECCIONES
    |--------------------------------------------------------------------------
    */

    private function collectionQuery(): Builder
    {
        return Collection::query()
            ->where(
                'visibility',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
            )
            ->with([
                'creator',

                'clones' =>
                fn($query) =>
                $query->where(
                    'user_id',
                    auth()->id()
                ),
            ])
            ->withCount(
                'entities'
            );
    }


    private function applyCollectionFilters(
        Builder $query,
        string $search,
        string $creator,
        string $image,
        string $cloning,
        string $period,
        string $collectionSize,
        string $sort
    ): Builder {

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
                            )
                            ->orWhereHas(
                                'creator',

                                fn($creatorQuery) =>
                                $creatorQuery
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'username',
                                        'like',
                                        "%{$search}%"
                                    )
                            )
                            ->orWhereHas(
                                'entities',

                                fn($entities) =>
                                $entities->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                            );
                    }
                )
            )

            ->when(
                $creator,

                fn($query) =>
                $query->whereHas(
                    'creator',

                    fn($creatorQuery) =>
                    $creatorQuery->where(
                        'username',
                        $creator
                    )
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
                $cloning === 'yes',

                fn($query) =>
                $query->where(
                    'allow_cloning',
                    true
                )
            )

            ->when(
                $cloning === 'no',

                fn($query) =>
                $query->where(
                    'allow_cloning',
                    false
                )
            )

            ->when(
                $collectionSize === 'small',

                fn($query) =>
                $query->has(
                    'entities',
                    '<=',
                    10
                )
            )

            ->when(
                $collectionSize === 'medium',

                fn($query) =>
                $query
                    ->has(
                        'entities',
                        '>=',
                        11
                    )
                    ->has(
                        'entities',
                        '<=',
                        50
                    )
            )

            ->when(
                $collectionSize === 'large',

                fn($query) =>
                $query->has(
                    'entities',
                    '>',
                    50
                )
            );


        $this->applyPeriod(
            $query,
            $period
        );


        if ($sort === 'size_desc') {

            $query->orderByDesc(
                'entities_count'
            );
        } elseif ($sort === 'size_asc') {

            $query->orderBy(
                'entities_count'
            );
        } else {

            $this->applyStandardSort(
                $query,
                $sort
            );
        }


        return $query;
    }


    /*
    |--------------------------------------------------------------------------
    | ATRIBUTOS
    |--------------------------------------------------------------------------
    */

    private function attributeQuery(): Builder
    {
        return Attribute::query()
            ->where(
                'scope',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
            )
            ->with([
                'creator',

                'clones' =>
                fn($query) =>
                $query->where(
                    'user_id',
                    auth()->id()
                ),
            ])
            ->withCount([
                'options',
                'entityAttributes',
            ]);
    }


    private function applyAttributeFilters(
        Builder $query,
        string $search,
        string $creator,
        string $image,
        string $cloning,
        string $period,
        string $dataType,
        string $multiple,
        string $catalogState,
        string $sort
    ): Builder {

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
                            )
                            ->orWhereHas(
                                'creator',

                                fn($creatorQuery) =>
                                $creatorQuery
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'username',
                                        'like',
                                        "%{$search}%"
                                    )
                            )
                            ->orWhereHas(
                                'options',

                                fn($options) =>
                                $options->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                            );
                    }
                )
            )

            ->when(
                $creator,

                fn($query) =>
                $query->whereHas(
                    'creator',

                    fn($creatorQuery) =>
                    $creatorQuery->where(
                        'username',
                        $creator
                    )
                )
            )

            ->when(
                $dataType,

                fn($query) =>
                $query->where(
                    'data_type',
                    $dataType
                )
            )

            ->when(
                $multiple === 'yes',

                fn($query) =>
                $query->where(
                    'allows_multiple',
                    true
                )
            )

            ->when(
                $multiple === 'no',

                fn($query) =>
                $query->where(
                    'allows_multiple',
                    false
                )
            )

            ->when(
                $catalogState === 'with',

                fn($query) =>
                $query->whereHas(
                    'options'
                )
            )

            ->when(
                $catalogState === 'empty',

                fn($query) =>
                $query->whereDoesntHave(
                    'options'
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
                $cloning === 'yes',

                fn($query) =>
                $query->where(
                    'allow_cloning',
                    true
                )
            )

            ->when(
                $cloning === 'no',

                fn($query) =>
                $query->where(
                    'allow_cloning',
                    false
                )
            );


        $this->applyPeriod(
            $query,
            $period
        );


        if ($sort === 'usage_desc') {

            $query->orderByDesc(
                'entity_attributes_count'
            );
        } elseif ($sort === 'catalog_desc') {

            $query->orderByDesc(
                'options_count'
            );
        } else {

            $this->applyStandardSort(
                $query,
                $sort
            );
        }


        return $query;
    }


    /*
    |--------------------------------------------------------------------------
    | CATÁLOGOS
    |--------------------------------------------------------------------------
    */

    private function catalogQuery(): Builder
    {
        return AttributeOption::query()
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereHas(
                'attribute',

                fn($attribute) =>
                $attribute
                    ->where(
                        'scope',
                        'PUBLIC'
                    )
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->whereNotNull(
                        'published_at'
                    )
            )
            ->with([
                'attribute.creator',
                'user',
                'parent',

                'clones' =>
                fn($query) =>
                $query->where(
                    'user_id',
                    auth()->id()
                ),
            ])
            ->withCount([
                'values',
                'children',
            ]);
    }


    private function applyCatalogFilters(
        Builder $query,
        string $search,
        string $creator,
        string $image,
        ?int $attributeId,
        string $hierarchy,
        string $usage,
        string $sort
    ): Builder {

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
                            )
                            ->orWhereHas(
                                'attribute',

                                fn($attribute) =>
                                $attribute
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
                            )
                            ->orWhereHas(
                                'user',

                                fn($user) =>
                                $user
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'username',
                                        'like',
                                        "%{$search}%"
                                    )
                            );
                    }
                )
            )

            ->when(
                $creator,

                fn($query) =>
                $query->whereHas(
                    'user',

                    fn($user) =>
                    $user->where(
                        'username',
                        $creator
                    )
                )
            )

            ->when(
                $attributeId,

                fn($query) =>
                $query->where(
                    'attribute_id',
                    $attributeId
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
                $hierarchy === 'root',

                fn($query) =>
                $query->whereNull(
                    'parent_option_id'
                )
            )

            ->when(
                $hierarchy === 'child',

                fn($query) =>
                $query->whereNotNull(
                    'parent_option_id'
                )
            )

            ->when(
                $hierarchy === 'has_children',

                fn($query) =>
                $query->whereHas(
                    'children'
                )
            )

            ->when(
                $usage === 'used',

                fn($query) =>
                $query->whereHas(
                    'values'
                )
            )

            ->when(
                $usage === 'unused',

                fn($query) =>
                $query->whereDoesntHave(
                    'values'
                )
            );


        match ($sort) {

            'newest' =>
            $query->orderByDesc(
                'created_at'
            ),

            'oldest' =>
            $query->orderBy(
                'created_at'
            ),

            'name_asc' =>
            $query->orderBy(
                'name'
            ),

            'name_desc' =>
            $query->orderByDesc(
                'name'
            ),

            'usage_desc' =>
            $query->orderByDesc(
                'values_count'
            ),

            'children_desc' =>
            $query->orderByDesc(
                'children_count'
            ),

            default =>
            $query
                ->orderByDesc(
                    'values_count'
                )
                ->orderByDesc(
                    'created_at'
                ),
        };


        return $query;
    }


    /*
    |--------------------------------------------------------------------------
    | CREADORES
    |--------------------------------------------------------------------------
    */

    private function creatorQuery(
        string $search,
        string $sort
    ): Builder {

        $query =
            User::query()
            ->where(
                'status',
                'ACTIVE'
            )
            ->where(
                'profile_visibility',
                'PUBLIC'
            )

            ->withCount([
                'entities as public_entities_count' =>
                fn($query) =>
                $this->publicEntityScope(
                    $query
                ),

                'collections as public_collections_count' =>
                fn($query) =>
                $this->publicCollectionScope(
                    $query
                ),

                'attributes as public_attributes_count' =>
                fn($query) =>
                $this->publicAttributeScope(
                    $query
                ),
            ])

            ->where(
                function ($query) {

                    $query
                        ->whereHas(
                            'entities',

                            fn($entities) =>
                            $this->publicEntityScope(
                                $entities
                            )
                        )

                        ->orWhereHas(
                            'collections',

                            fn($collections) =>
                            $this->publicCollectionScope(
                                $collections
                            )
                        )

                        ->orWhereHas(
                            'attributes',

                            fn($attributes) =>
                            $this->publicAttributeScope(
                                $attributes
                            )
                        );
                }
            )

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
                                'username',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'headline',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'bio',
                                'like',
                                "%{$search}%"
                            );
                    }
                )
            );


        match ($sort) {

            'newest' =>
            $query->orderByDesc(
                'created_at'
            ),

            'oldest' =>
            $query->orderBy(
                'created_at'
            ),

            'name_asc',
            'name' =>
            $query->orderBy(
                'name'
            ),

            'name_desc' =>
            $query->orderByDesc(
                'name'
            ),

            default =>
            $query
                ->orderByDesc(
                    'public_entities_count'
                )
                ->orderByDesc(
                    'public_attributes_count'
                ),
        };


        return $query;
    }


    /*
    |--------------------------------------------------------------------------
    | ORDEN GENERAL
    |--------------------------------------------------------------------------
    */

    private function applyStandardSort(
        Builder $query,
        string $sort
    ): void {

        match ($sort) {

            'newest' =>
            $query->orderByDesc(
                'published_at'
            ),

            'oldest' =>
            $query->orderBy(
                'published_at'
            ),

            'name',
            'name_asc' =>
            $query->orderBy(
                'name'
            ),

            'name_desc' =>
            $query->orderByDesc(
                'name'
            ),

            'cloned' =>
            $query->orderByDesc(
                'clones_count'
            ),

            'viewed' =>
            $query->orderByDesc(
                'views_count'
            ),

            'trending' =>
            $query
                ->orderByDesc(
                    'published_at'
                )
                ->orderByDesc(
                    'clones_count'
                )
                ->orderByDesc(
                    'views_count'
                ),

            default =>
            $query
                ->orderByDesc(
                    'clones_count'
                )
                ->orderByDesc(
                    'views_count'
                )
                ->orderByDesc(
                    'published_at'
                ),
        };
    }


    /*
    |--------------------------------------------------------------------------
    | PERIODO
    |--------------------------------------------------------------------------
    */

    private function applyPeriod(
        Builder $query,
        string $period
    ): void {

        match ($period) {

            'today' =>
            $query->where(
                'published_at',
                '>=',
                now()->startOfDay()
            ),

            'week' =>
            $query->where(
                'published_at',
                '>=',
                now()->subDays(7)
            ),

            'month' =>
            $query->where(
                'published_at',
                '>=',
                now()->subDays(30)
            ),

            default =>
            null,
        };
    }


    /*
    |--------------------------------------------------------------------------
    | PUBLIC SCOPES
    |--------------------------------------------------------------------------
    */

    private function publicEntityScope(
        Builder $query
    ): Builder {

        return $query
            ->where(
                'visibility',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
            );
    }


    private function publicCollectionScope(
        Builder $query
    ): Builder {

        return $query
            ->where(
                'visibility',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
            );
    }


    private function publicAttributeScope(
        Builder $query
    ): Builder {

        return $query
            ->where(
                'scope',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | SEGURIDAD
    |--------------------------------------------------------------------------
    */

    private function ensurePublicEntity(
        Entity $entity
    ): void {

        abort_unless(
            $entity->visibility === 'PUBLIC'
                &&
                $entity->status === 'ACTIVE'
                &&
                $entity->published_at,

            404
        );
    }


    private function ensurePublicCollection(
        Collection $collection
    ): void {

        abort_unless(
            $collection->visibility === 'PUBLIC'
                &&
                $collection->status === 'ACTIVE'
                &&
                $collection->published_at,

            404
        );
    }


    private function ensurePublicAttribute(
        Attribute $attribute
    ): void {

        abort_unless(
            $attribute->scope === 'PUBLIC'
                &&
                $attribute->status === 'ACTIVE'
                &&
                $attribute->published_at,

            404
        );
    }


    private function ensurePublicOption(
        AttributeOption $option
    ): void {

        $option->loadMissing(
            'attribute'
        );

        abort_unless(
            $option->status === 'ACTIVE'
                &&
                $option->attribute
                &&
                $option->attribute->scope === 'PUBLIC'
                &&
                $option->attribute->status === 'ACTIVE'
                &&
                $option->attribute->published_at,

            404
        );
    }


    /*
    |--------------------------------------------------------------------------
    | INTERACCIONES
    |--------------------------------------------------------------------------
    */

    private function recordView(
        Request $request,
        string $contentType,
        int $contentId
    ): void {

        DB::table(
            'community_interactions'
        )
            ->insert([
                'user_id' =>
                $request->user()?->id,

                'content_type' =>
                $contentType,

                'content_id' =>
                $contentId,

                'interaction_type' =>
                'VIEW',

                'metadata' =>
                json_encode([
                    'ip' =>
                    $request->ip(),
                ]),

                'created_at' =>
                now(),

                'updated_at' =>
                now(),
            ]);
    }
}
