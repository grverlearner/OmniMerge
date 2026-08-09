<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\StoreEntityRequest;
use App\Http\Requests\Entities\UpdateEntityRequest;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\Collection;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\User;
use App\Services\Entities\EntityBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class EntityController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {
        $this->authorize(
            'viewAny',
            Entity::class
        );

        $user = $request->user();

        $search = trim(
            (string) $request->input(
                'search'
            )
        );

        $status = $request->input(
            'status'
        );

        $visibility = $request->input(
            'visibility'
        );

        $type = $request->input(
            'type'
        );

        $image = $request->input(
            'image'
        );

        $attributesState = $request->input(
            'attributes_state'
        );

        $collectionId = $request->filled(
            'collection'
        )
            ? $request->integer(
                'collection'
            )
            : null;

        $filterAttributeId = $request->filled(
            'filter_attribute'
        )
            ? $request->integer(
                'filter_attribute'
            )
            : null;

        $filterOptionId = $request->filled(
            'filter_option'
        )
            ? $request->integer(
                'filter_option'
            )
            : null;

        $sort = (string) $request->input(
            'sort',
            'newest'
        );

        $allowedSorts = [
            'newest',
            'oldest',
            'name_asc',
            'name_desc',
            'code_asc',
            'code_desc',
            'attributes_desc',
            'attributes_asc',
            'collections_desc',
            'collections_asc',
            'views_desc',
            'clones_desc',
        ];

        if (
            ! in_array(
                $sort,
                $allowedSorts,
                true
            )
        ) {
            $sort = 'newest';
        }

        $perPage = (int) $request->input(
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
        | Validar filtro dinámico
        |--------------------------------------------------------------------------
        */

        $filterAttribute = null;

        if ($filterAttributeId) {
            $filterAttribute = Attribute::query()
                ->ownedBy($user)
                ->active()
                ->find(
                    $filterAttributeId
                );

            if (! $filterAttribute) {
                $filterAttributeId = null;
                $filterOptionId = null;
            }
        }

        if (
            $filterOptionId
            && $filterAttribute
        ) {
            $validOption = $filterAttribute
                ->options()
                ->whereKey(
                    $filterOptionId
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->exists();

            if (! $validOption) {
                $filterOptionId = null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Estadísticas
        |--------------------------------------------------------------------------
        */

        $baseQuery = Entity::query()
            ->ownedBy($user);

        $stats = [
            'total' => (clone $baseQuery)
                ->count(),

            'public' => (clone $baseQuery)
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->count(),

            'with_attributes' => (clone $baseQuery)
                ->whereHas(
                    'entityAttributes'
                )
                ->count(),

            'archived' => (clone $baseQuery)
                ->where(
                    'status',
                    'ARCHIVED'
                )
                ->count(),

            'untyped' => (clone $baseQuery)
                ->whereNull(
                    'entity_type_id'
                )
                ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Consulta
        |--------------------------------------------------------------------------
        */

        $query = Entity::query()
            ->ownedBy($user)
            ->with(
                'entityType'
            )
            ->withCount([
                'entityAttributes',
                'collections',
            ])

            ->when(
                $search,
                fn($query) =>
                $query->where(
                    fn($subquery) =>
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
                    && $type !== 'none',
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
                    $collectionQuery->where(
                        'collections.id',
                        $collectionId
                    )
                )
            )

            /*
            |--------------------------------------------------------------------------
            | Filtro por característica
            |--------------------------------------------------------------------------
            */

            ->when(
                $filterAttributeId,
                function ($query) use (
                    $filterAttributeId,
                    $filterOptionId
                ) {
                    $query->whereHas(
                        'entityAttributes',
                        function ($attributeQuery) use (
                            $filterAttributeId,
                            $filterOptionId
                        ) {
                            $attributeQuery->where(
                                'attribute_id',
                                $filterAttributeId
                            );

                            if ($filterOptionId) {
                                $attributeQuery->whereHas(
                                    'values',
                                    fn($valueQuery) =>
                                    $valueQuery->where(
                                        'attribute_option_id',
                                        $filterOptionId
                                    )
                                );
                            }
                        }
                    );
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Orden
        |--------------------------------------------------------------------------
        */

        match ($sort) {
            'oldest' =>
            $query
                ->orderBy('created_at')
                ->orderBy('id'),

            'name_asc' =>
            $query->orderBy('name'),

            'name_desc' =>
            $query->orderByDesc('name'),

            'code_asc' =>
            $query->orderBy('code'),

            'code_desc' =>
            $query->orderByDesc('code'),

            'attributes_desc' =>
            $query
                ->orderByDesc(
                    'entity_attributes_count'
                )
                ->orderBy('name'),

            'attributes_asc' =>
            $query
                ->orderBy(
                    'entity_attributes_count'
                )
                ->orderBy('name'),

            'collections_desc' =>
            $query
                ->orderByDesc(
                    'collections_count'
                )
                ->orderBy('name'),

            'collections_asc' =>
            $query
                ->orderBy(
                    'collections_count'
                )
                ->orderBy('name'),

            'views_desc' =>
            $query
                ->orderByDesc(
                    'views_count'
                )
                ->orderBy('name'),

            'clones_desc' =>
            $query
                ->orderByDesc(
                    'clones_count'
                )
                ->orderBy('name'),

            default =>
            $query
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
        };

        $entities = $query
            ->paginate($perPage)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Filtros visuales
        |--------------------------------------------------------------------------
        */

        $entityTypes = EntityType::query()
            ->ownedBy($user)
            ->active()
            ->withCount('entities')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $collections = Collection::query()
            ->ownedBy($user)
            ->withCount('entities')
            ->orderBy('name')
            ->get();

        $filterAttributes = Attribute::query()
            ->ownedBy($user)
            ->active()
            ->where(
                'data_type',
                'OPTION'
            )
            ->with([
                'options' => fn($query) =>
                $query
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();

        return view(
            'entities.index',
            compact(
                'entities',
                'entityTypes',
                'collections',
                'filterAttributes',
                'stats',

                'search',
                'status',
                'visibility',
                'type',
                'image',
                'attributesState',
                'collectionId',

                'filterAttributeId',
                'filterOptionId',

                'sort',
                'perPage'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(
        Request $request,
        EntityBuilderService $builder
    ): View {
        $this->authorize(
            'create',
            Entity::class
        );

        $resources = $this->builderResources(
            $request->user()
        );

        $previewCode = $builder->nextCode(
            $request->user()
        );

        return view(
            'entities.create',
            array_merge(
                $resources,
                compact(
                    'previewCode'
                )
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreEntityRequest $request,
        EntityBuilderService $builder
    ): RedirectResponse {
        $data = $request->validated();

        $selectedAttributeIds =
            $data['selected_attribute_ids']
            ?? [];

        $attributeInputs =
            $data['attributes']
            ?? [];

        $collectionIds =
            $data['collection_ids']
            ?? [];

        unset(
            $data['selected_attribute_ids'],
            $data['attributes'],
            $data['collection_ids']
        );

        /*
        |--------------------------------------------------------------------------
        | Imagen
        |--------------------------------------------------------------------------
        */

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request
                ->file('image')
                ->store(
                    'entities',
                    'public'
                );

            $data['image'] =
                $imagePath;
        }

        try {
            $entity = $builder->create(
                $request->user(),
                $data,
                $selectedAttributeIds,
                $attributeInputs,
                $collectionIds
            );
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')
                    ->delete(
                        $imagePath
                    );
            }

            throw $exception;
        }

        return redirect()
            ->route(
                'entities.show',
                $entity
            )
            ->with(
                'success',
                'Entidad creada correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(
        Entity $entity
    ): View {
        $this->authorize(
            'view',
            $entity
        );

        $entity->load([
            'entityType',

            'collections',

            'entityAttributes.attribute.groups',

            'entityAttributes.values.option',
        ]);

        $entity->loadCount([
            'entityAttributes',
            'collections',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Grupos visuales
        |--------------------------------------------------------------------------
        */

        $characteristicGroups =
            collect();

        foreach (
            $entity->entityAttributes
            as $assignment
        ) {
            $groups =
                $assignment
                ->attribute
                ->groups;

            if ($groups->isEmpty()) {
                $characteristicGroups
                    ->push([
                        'name' =>
                        'Otros',

                        'assignment' =>
                        $assignment,
                    ]);

                continue;
            }

            /*
             * Si un atributo pertenece a varios grupos,
             * mostramos el primero para evitar duplicarlo.
             */

            $group = $groups
                ->sortBy(
                    fn($group) =>
                    $group->pivot
                        ->sort_order
                        ?? 0
                )
                ->first();

            $characteristicGroups
                ->push([
                    'name' =>
                    $group->name,

                    'assignment' =>
                    $assignment,
                ]);
        }

        $characteristicGroups =
            $characteristicGroups
            ->groupBy('name');

        $catalogValuesCount =
            $entity
            ->entityAttributes
            ->sum(
                fn($assignment) =>
                $assignment
                    ->values
                    ->whereNotNull(
                        'attribute_option_id'
                    )
                    ->count()
            );

        return view(
            'entities.show',
            compact(
                'entity',
                'characteristicGroups',
                'catalogValuesCount'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        Request $request,
        Entity $entity
    ): View {
        $this->authorize(
            'update',
            $entity
        );

        $entity->load([
            'collections',

            'entityAttributes.attribute',

            'entityAttributes.values.option',
        ]);

        $resources = $this->builderResources(
            $request->user()
        );

        $previewCode =
            $entity->code;

        return view(
            'entities.edit',
            array_merge(
                $resources,
                compact(
                    'entity',
                    'previewCode'
                )
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateEntityRequest $request,
        Entity $entity,
        EntityBuilderService $builder
    ): RedirectResponse {
        $data =
            $request->validated();

        $selectedAttributeIds =
            $data['selected_attribute_ids']
            ?? [];

        $attributeInputs =
            $data['attributes']
            ?? [];

        $collectionIds =
            $data['collection_ids']
            ?? [];

        unset(
            $data['selected_attribute_ids'],
            $data['attributes'],
            $data['collection_ids']
        );

        $oldImage =
            $entity->image;

        $newImagePath =
            null;

        if (
            $request->hasFile(
                'image'
            )
        ) {
            $newImagePath =
                $request
                ->file('image')
                ->store(
                    'entities',
                    'public'
                );

            $data['image'] =
                $newImagePath;
        } elseif (
            $request->boolean(
                'remove_image'
            )
        ) {
            $data['image'] =
                null;
        }

        unset(
            $data['remove_image']
        );

        try {
            $builder->update(
                $request->user(),
                $entity,
                $data,
                $selectedAttributeIds,
                $attributeInputs,
                $collectionIds
            );
        } catch (Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')
                    ->delete(
                        $newImagePath
                    );
            }

            throw $exception;
        }

        /*
        |--------------------------------------------------------------------------
        | Limpiar imagen anterior al final
        |--------------------------------------------------------------------------
        */

        if (
            $oldImage
            &&
            (
                $newImagePath
                ||
                $request->boolean(
                    'remove_image'
                )
            )
        ) {
            Storage::disk('public')
                ->delete(
                    $oldImage
                );
        }

        return redirect()
            ->route(
                'entities.show',
                $entity
            )
            ->with(
                'success',
                'Entidad actualizada correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Entity $entity
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $entity
        );

        /*
         * Como usa SoftDeletes, conservamos la imagen
         * para una posible restauración futura.
         */

        $entity->delete();

        return redirect()
            ->route(
                'entities.index'
            )
            ->with(
                'success',
                'Entidad eliminada correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Recursos del constructor
    |--------------------------------------------------------------------------
    */

    private function builderResources(
        User $user
    ): array {
        $entityTypes = EntityType::query()
            ->ownedBy($user)
            ->active()
            ->withCount('entities')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $attributes = Attribute::query()
            ->ownedBy($user)
            ->active()
            ->with([
                'groups',

                'options' => fn($query) =>
                $query
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $groups = AttributeGroup::query()
            ->ownedBy($user)
            ->where(
                'status',
                'ACTIVE'
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $collections = Collection::query()
            ->ownedBy($user)
            ->where(
                'status',
                '<>',
                'ARCHIVED'
            )
            ->withCount('entities')
            ->orderBy('name')
            ->get();

        return compact(
            'entityTypes',
            'attributes',
            'groups',
            'collections'
        );
    }
}
