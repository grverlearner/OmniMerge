<?php

namespace App\Http\Controllers\Attributes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attributes\StoreAttributeRequest;
use App\Http\Requests\Attributes\UpdateAttributeRequest;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\AttributeOption;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AttributeController extends Controller
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
            Attribute::class
        );


        $search = trim(
            (string) $request->input(
                'search'
            )
        );


        $dataType =
            $request->input(
                'data_type'
            );


        $status =
            $request->input(
                'status'
            );


        $scope =
            $request->input(
                'scope'
            );


        $multiple =
            $request->input(
                'multiple'
            );


        $groupId =
            $request->filled('group')
            ? $request->integer('group')
            : null;


        $sort =
            (string) $request->input(
                'sort',
                'manual'
            );


        $allowedSorts = [
            'manual',
            'newest',
            'oldest',
            'name_asc',
            'name_desc',
            'code_asc',
            'code_desc',
            'catalog_desc',
            'catalog_asc',
            'usage_desc',
            'usage_asc',
        ];


        if (
            ! in_array(
                $sort,
                $allowedSorts,
                true
            )
        ) {
            $sort = 'manual';
        }


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
                ],
                true
            )
        ) {
            $perPage = 24;
        }


        /*
        |--------------------------------------------------------------------------
        | Estadísticas
        |--------------------------------------------------------------------------
        */

        $baseQuery =
            Attribute::query()
            ->ownedBy(
                $request->user()
            );


        $stats = [

            'total' => (clone $baseQuery)
                ->count(),

            'catalog' => (clone $baseQuery)
                ->where(
                    'data_type',
                    'OPTION'
                )
                ->count(),

            'boolean' => (clone $baseQuery)
                ->where(
                    'data_type',
                    'BOOLEAN'
                )
                ->count(),

            'public' => (clone $baseQuery)
                ->where(
                    'scope',
                    'PUBLIC'
                )
                ->count(),

            'used' => (clone $baseQuery)
                ->whereHas(
                    'entityAttributes'
                )
                ->count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | Consulta
        |--------------------------------------------------------------------------
        */

        $query =
            Attribute::query()
            ->ownedBy(
                $request->user()
            )
            ->withCount([
                'options',
                'entityAttributes',
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
                $dataType,
                fn($query) =>
                $query->where(
                    'data_type',
                    $dataType
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
                $scope,
                fn($query) =>
                $query->where(
                    'scope',
                    $scope
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
                $groupId,
                fn($query) =>
                $query->whereHas(
                    'groups',
                    fn($groupQuery) =>
                    $groupQuery->where(
                        'attribute_groups.id',
                        $groupId
                    )
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Ordenamiento
        |--------------------------------------------------------------------------
        */

        switch ($sort) {

            case 'newest':

                $query
                    ->orderByDesc(
                        'created_at'
                    )
                    ->orderByDesc('id');

                break;


            case 'oldest':

                $query
                    ->orderBy(
                        'created_at'
                    )
                    ->orderBy('id');

                break;


            case 'name_asc':

                $query->orderBy(
                    'name'
                );

                break;


            case 'name_desc':

                $query->orderByDesc(
                    'name'
                );

                break;


            case 'code_asc':

                $query->orderBy(
                    'code'
                );

                break;


            case 'code_desc':

                $query->orderByDesc(
                    'code'
                );

                break;


            case 'catalog_desc':

                $query
                    ->orderByDesc(
                        'options_count'
                    )
                    ->orderBy('name');

                break;


            case 'catalog_asc':

                $query
                    ->orderBy(
                        'options_count'
                    )
                    ->orderBy('name');

                break;


            case 'usage_desc':

                $query
                    ->orderByDesc(
                        'entity_attributes_count'
                    )
                    ->orderBy('name');

                break;


            case 'usage_asc':

                $query
                    ->orderBy(
                        'entity_attributes_count'
                    )
                    ->orderBy('name');

                break;


            default:

                $query
                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy(
                        'sequence_number'
                    )
                    ->orderBy(
                        'name'
                    );

                break;
        }


        $attributes =
            $query
            ->paginate(
                $perPage
            )
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Grupos para filtro
        |--------------------------------------------------------------------------
        */

        $groups =
            AttributeGroup::query()
            ->ownedBy(
                $request->user()
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


        return view(
            'attributes.index',
            compact(
                'attributes',
                'groups',
                'stats',
                'search',
                'dataType',
                'status',
                'scope',
                'multiple',
                'groupId',
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
        Request $request
    ): View {

        $this->authorize(
            'create',
            Attribute::class
        );


        $groups =
            AttributeGroup::query()
            ->ownedBy(
                $request->user()
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


        $nextSequence =
            $this->nextSequence(
                $request->user()->id
            );


        $previewCode =
            Attribute::formatCode(
                $nextSequence
            );


        return view(
            'attributes.create',
            compact(
                'groups',
                'nextSequence',
                'previewCode'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreAttributeRequest $request
    ): RedirectResponse {

        $data =
            $request->validated();


        $groupIds =
            $data['group_ids']
            ?? [];


        unset(
            $data['group_ids']
        );


        /*
        |--------------------------------------------------------------------------
        | Imagen
        |--------------------------------------------------------------------------
        */

        $imagePath = null;


        if ($request->hasFile('image')) {

            $imagePath =
                $request
                ->file('image')
                ->store(
                    'attributes',
                    'public'
                );


            $data['image'] =
                $imagePath;
        }


        try {

            $attribute =
                DB::transaction(
                    function () use (
                        $request,
                        $data,
                        $groupIds
                    ) {

                        /*
                         * Bloqueo por usuario para evitar dos ATR000001
                         * simultáneos.
                         */

                        /** @var User $user */
                        $user =
                            User::query()
                            ->whereKey(
                                $request
                                    ->user()
                                    ->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();


                        $sequence =
                            $this->nextSequence(
                                $user->id
                            );


                        $data['sequence_number'] =
                            $sequence;


                        $data['code'] =
                            Attribute::formatCode(
                                $sequence
                            );


                        /*
                         * Slug automático e independiente del código.
                         */

                        $data['slug'] =
                            $this->uniqueSlug(
                                $user->id,
                                $data['name']
                            );


                        /*
                         * Orden visual.
                         */

                        $lastSortOrder =
                            (int) Attribute
                                ::withTrashed()
                                ->where(
                                    'user_id',
                                    $user->id
                                )
                                ->max(
                                    'sort_order'
                                );


                        $data['sort_order'] =
                            $lastSortOrder
                            + 10;


                        /*
                         * Publicación.
                         */

                        $data['published_at'] =
                            $this->shouldBePublished(
                                $data
                            )
                            ? now()
                            : null;


                        $attribute =
                            $user
                            ->attributes()
                            ->create(
                                $data
                            );


                        $attribute
                            ->groups()
                            ->sync(
                                $groupIds
                            );


                        return $attribute;
                    }
                );
        } catch (Throwable $exception) {

            if ($imagePath) {

                Storage::disk(
                    'public'
                )->delete(
                    $imagePath
                );
            }


            throw $exception;
        }


        return redirect()
            ->route(
                'attributes.show',
                $attribute
            )
            ->with(
                'success',
                'Atributo creado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Attribute $attribute
    ): View {

        $this->authorize(
            'view',
            $attribute
        );


        $attribute
            ->load(
                'groups'
            )
            ->loadCount([
                'entityAttributes',
                'options',
            ]);


        /*
        |--------------------------------------------------------------------------
        | Configuración del catálogo
        |--------------------------------------------------------------------------
        */

        $catalogSearch = trim(
            (string) $request->input(
                'catalog_search'
            )
        );


        $catalogStatus =
            $request->input(
                'catalog_status'
            );


        $catalogSort =
            (string) $request->input(
                'catalog_sort',
                'manual'
            );


        $allowedCatalogSorts = [
            'manual',
            'newest',
            'oldest',
            'name_asc',
            'name_desc',
            'usage_desc',
            'usage_asc',
        ];


        if (
            ! in_array(
                $catalogSort,
                $allowedCatalogSorts,
                true
            )
        ) {
            $catalogSort = 'manual';
        }


        $catalogPerPage =
            (int) $request->input(
                'catalog_per_page',
                24
            );


        if (
            ! in_array(
                $catalogPerPage,
                [
                    12,
                    24,
                    48,
                ],
                true
            )
        ) {
            $catalogPerPage = 24;
        }


        $catalogOptions = null;

        $parentOptions =
            collect();


        if ($attribute->usesCatalog()) {

            $optionsQuery =
                AttributeOption::query()
                ->where(
                    'attribute_id',
                    $attribute->id
                )
                ->with(
                    'parent'
                )
                ->withCount([
                    'children',
                    'values',
                ])
                ->when(
                    $catalogSearch,
                    fn($query) =>
                    $query->where(
                        fn($subquery) =>
                        $subquery
                            ->where(
                                'name',
                                'like',
                                "%{$catalogSearch}%"
                            )
                            ->orWhere(
                                'code',
                                'like',
                                "%{$catalogSearch}%"
                            )
                            ->orWhere(
                                'description',
                                'like',
                                "%{$catalogSearch}%"
                            )
                    )
                )
                ->when(
                    $catalogStatus,
                    fn($query) =>
                    $query->where(
                        'status',
                        $catalogStatus
                    )
                );


            switch ($catalogSort) {

                case 'newest':

                    $optionsQuery
                        ->orderByDesc(
                            'created_at'
                        )
                        ->orderByDesc(
                            'id'
                        );

                    break;


                case 'oldest':

                    $optionsQuery
                        ->orderBy(
                            'created_at'
                        )
                        ->orderBy(
                            'id'
                        );

                    break;


                case 'name_asc':

                    $optionsQuery->orderBy(
                        'name'
                    );

                    break;


                case 'name_desc':

                    $optionsQuery->orderByDesc(
                        'name'
                    );

                    break;


                case 'usage_desc':

                    $optionsQuery
                        ->orderByDesc(
                            'values_count'
                        )
                        ->orderBy(
                            'name'
                        );

                    break;


                case 'usage_asc':

                    $optionsQuery
                        ->orderBy(
                            'values_count'
                        )
                        ->orderBy(
                            'name'
                        );

                    break;


                default:

                    $optionsQuery
                        ->orderBy(
                            'sort_order'
                        )
                        ->orderBy(
                            'name'
                        );

                    break;
            }


            $catalogOptions =
                $optionsQuery
                ->paginate(
                    $catalogPerPage,
                    ['*'],
                    'catalog_page'
                )
                ->withQueryString();


            $catalogOptions
                ->fragment(
                    'catalog'
                );


            $parentOptions =
                $attribute
                ->options()
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->get();
        }


        return view(
            'attributes.show',
            compact(
                'attribute',
                'catalogOptions',
                'parentOptions',
                'catalogSearch',
                'catalogStatus',
                'catalogSort',
                'catalogPerPage'
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
        Attribute $attribute
    ): View {

        $this->authorize(
            'update',
            $attribute
        );


        $groups =
            AttributeGroup::query()
            ->ownedBy(
                $request->user()
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


        $attribute
            ->load(
                'groups'
            )
            ->loadCount([
                'options',
                'entityAttributes',
            ]);


        /*
         * Si ya contiene datos, el tipo se bloquea.
         */

        $typeLocked =
            $attribute->options_count > 0
            ||
            $attribute
            ->entity_attributes_count
            > 0;


        return view(
            'attributes.edit',
            compact(
                'attribute',
                'groups',
                'typeLocked'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateAttributeRequest $request,
        Attribute $attribute
    ): RedirectResponse {

        $data =
            $request->validated();


        /*
        |--------------------------------------------------------------------------
        | Proteger tipo cuando ya existen datos
        |--------------------------------------------------------------------------
        */

        $typeLocked =
            $attribute
            ->options()
            ->exists()

            ||

            $attribute
            ->entityAttributes()
            ->exists();


        if (
            $typeLocked
            &&
            $data['data_type']
            !== $attribute->data_type
        ) {

            throw ValidationException::withMessages([
                'data_type' =>
                'El tipo de dato ya no puede cambiarse porque este atributo contiene elementos de catálogo o está siendo utilizado por entidades.',
            ]);
        }


        $groupIds =
            $data['group_ids']
            ?? [];


        unset(
            $data['group_ids']
        );


        /*
        |--------------------------------------------------------------------------
        | Publicación
        |--------------------------------------------------------------------------
        */

        if (
            $this->shouldBePublished(
                $data
            )
        ) {

            if (! $attribute->published_at) {

                $data['published_at'] =
                    now();
            }
        } else {

            $data['published_at'] =
                null;
        }


        /*
        |--------------------------------------------------------------------------
        | Imagen
        |--------------------------------------------------------------------------
        */

        $oldImage =
            $attribute->image;


        $newImagePath =
            null;


        if ($request->hasFile('image')) {

            $newImagePath =
                $request
                ->file('image')
                ->store(
                    'attributes',
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

            DB::transaction(
                function () use (
                    $attribute,
                    $data,
                    $groupIds
                ) {

                    /*
                     * code, slug, sequence_number y sort_order no vienen
                     * de validated(), por lo que permanecen inmutables.
                     */

                    $attribute->update(
                        $data
                    );


                    $attribute
                        ->groups()
                        ->sync(
                            $groupIds
                        );
                }
            );
        } catch (Throwable $exception) {

            if ($newImagePath) {

                Storage::disk(
                    'public'
                )->delete(
                    $newImagePath
                );
            }


            throw $exception;
        }


        /*
        |--------------------------------------------------------------------------
        | Eliminar imagen antigua únicamente después de guardar
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

            Storage::disk(
                'public'
            )->delete(
                $oldImage
            );
        }


        return redirect()
            ->route(
                'attributes.show',
                $attribute
            )
            ->with(
                'success',
                'Atributo actualizado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Attribute $attribute
    ): RedirectResponse {

        $this->authorize(
            'delete',
            $attribute
        );


        if (
            $attribute
            ->entityAttributes()
            ->exists()
        ) {

            return back()->with(
                'error',
                'Este atributo está siendo utilizado por entidades. Archívalo en lugar de eliminarlo.'
            );
        }


        if (
            $attribute
            ->options()
            ->exists()
        ) {

            return back()->with(
                'error',
                'Este atributo contiene elementos en su catálogo. Archívalo o elimina primero su catálogo.'
            );
        }


        if ($attribute->image) {

            Storage::disk(
                'public'
            )->delete(
                $attribute->image
            );
        }


        $attribute->delete();


        return redirect()
            ->route(
                'attributes.index'
            )
            ->with(
                'success',
                'Atributo eliminado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Siguiente número
    |--------------------------------------------------------------------------
    */

    private function nextSequence(
        int $userId
    ): int {

        $lastSequence =
            (int) Attribute
                ::withTrashed()
                ->where(
                    'user_id',
                    $userId
                )
                ->max(
                    'sequence_number'
                );


        return $lastSequence + 1;
    }


    /*
    |--------------------------------------------------------------------------
    | Slug automático
    |--------------------------------------------------------------------------
    */

    private function uniqueSlug(
        int $userId,
        string $name
    ): string {

        $baseSlug =
            Str::slug(
                $name
            );


        if ($baseSlug === '') {

            $baseSlug =
                'atributo';
        }


        $slug =
            $baseSlug;


        $counter = 2;


        while (
            Attribute::withTrashed()
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'slug',
                $slug
            )
            ->exists()
        ) {

            $slug =
                $baseSlug
                . '-'
                . $counter;


            $counter++;
        }


        return $slug;
    }


    /*
    |--------------------------------------------------------------------------
    | Publicación
    |--------------------------------------------------------------------------
    */

    private function shouldBePublished(
        array $data
    ): bool {

        return (
            $data['scope']
            ?? null
        ) === 'PUBLIC'

            &&

            (
                $data['status']
                ?? null
            ) === 'ACTIVE';
    }
}
