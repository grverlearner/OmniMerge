<?php

namespace App\Http\Controllers\Attributes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attributes\StoreAttributeOptionRequest;
use App\Http\Requests\Attributes\UpdateAttributeOptionRequest;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class AttributeOptionController extends Controller
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
            AttributeOption::class
        );


        $search =
            trim(
                (string) $request->input(
                    'search'
                )
            );


        $attributeId =
            $request->filled('attribute')
            ? $request->integer(
                'attribute'
            )
            : null;


        $status =
            $request->input(
                'status'
            );


        $image =
            $request->input(
                'image'
            );


        $hierarchy =
            $request->input(
                'hierarchy'
            );


        $usage =
            $request->input(
                'usage'
            );


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

            'usage_desc',
            'usage_asc',

            'children_desc',
            'children_asc',
        ];


        if (
            ! in_array(
                $sort,
                $allowedSorts,
                true
            )
        ) {

            $sort =
                'manual';
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
                    96,
                ],
                true
            )
        ) {

            $perPage = 24;
        }


        /*
        |--------------------------------------------------------------------------
        | Base del usuario
        |--------------------------------------------------------------------------
        */

        $baseQuery =
            AttributeOption::query()
            ->ownedBy(
                $request->user()
            );


        /*
        |--------------------------------------------------------------------------
        | Estadísticas
        |--------------------------------------------------------------------------
        */

        $stats = [

            'total' => (clone $baseQuery)
                ->count(),


            'catalogs' => (clone $baseQuery)
                ->distinct()
                ->count(
                    'attribute_id'
                ),


            'active' => (clone $baseQuery)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),


            'used' => (clone $baseQuery)
                ->whereHas(
                    'values'
                )
                ->count(),


            'hierarchical' => (clone $baseQuery)
                ->whereNotNull(
                    'parent_option_id'
                )
                ->count(),


            'archived' => (clone $baseQuery)
                ->where(
                    'status',
                    'ARCHIVED'
                )
                ->count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | Consulta principal
        |--------------------------------------------------------------------------
        */

        $query =
            AttributeOption::query()
            ->ownedBy(
                $request->user()
            )
            ->with([
                'attribute',
                'parent',
            ])
            ->withCount([
                'children',
                'values',
            ])


            /*
                |--------------------------------------------------------------------------
                | Buscar
                |--------------------------------------------------------------------------
                */

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


            /*
                |--------------------------------------------------------------------------
                | Catálogo
                |--------------------------------------------------------------------------
                */

            ->when(
                $attributeId,
                fn($query) =>
                $query->where(
                    'attribute_id',
                    $attributeId
                )
            )


            /*
                |--------------------------------------------------------------------------
                | Estado
                |--------------------------------------------------------------------------
                */

            ->when(
                $status,
                fn($query) =>
                $query->where(
                    'status',
                    $status
                )
            )


            /*
                |--------------------------------------------------------------------------
                | Imagen
                |--------------------------------------------------------------------------
                */

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


            /*
                |--------------------------------------------------------------------------
                | Jerarquía
                |--------------------------------------------------------------------------
                */

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


            /*
                |--------------------------------------------------------------------------
                | Uso
                |--------------------------------------------------------------------------
                */

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


            case 'usage_desc':

                $query
                    ->orderByDesc(
                        'values_count'
                    )
                    ->orderBy(
                        'name'
                    );

                break;


            case 'usage_asc':

                $query
                    ->orderBy(
                        'values_count'
                    )
                    ->orderBy(
                        'name'
                    );

                break;


            case 'children_desc':

                $query
                    ->orderByDesc(
                        'children_count'
                    )
                    ->orderBy(
                        'name'
                    );

                break;


            case 'children_asc':

                $query
                    ->orderBy(
                        'children_count'
                    )
                    ->orderBy(
                        'name'
                    );

                break;


            default:

                $query
                    ->orderBy(
                        'attribute_id'
                    )
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


        $options =
            $query
            ->paginate(
                $perPage
            )
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Catálogos disponibles para filtros
        |--------------------------------------------------------------------------
        */

        $attributes =
            Attribute::query()
            ->ownedBy(
                $request->user()
            )
            ->active()
            ->where(
                fn($query) =>
                $query
                    ->where(
                        'data_type',
                        'OPTION'
                    )
                    ->orWhereIn(
                        'value_source',
                        [
                            'CATALOG',
                            'MIXED',
                        ]
                    )
            )
            ->withCount(
                'options'
            )
            ->orderBy(
                'name'
            )
            ->get();


        $selectedAttribute =
            $attributeId
            ? $attributes
            ->firstWhere(
                'id',
                $attributeId
            )
            : null;


        return view(
            'attribute-options.index',
            compact(
                'options',
                'attributes',
                'selectedAttribute',
                'stats',
                'search',
                'attributeId',
                'status',
                'image',
                'hierarchy',
                'usage',
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
            AttributeOption::class
        );


        $attributes =
            Attribute::query()
            ->ownedBy(
                $request->user()
            )
            ->active()
            ->where(
                fn($query) =>
                $query
                    ->where(
                        'data_type',
                        'OPTION'
                    )
                    ->orWhereIn(
                        'value_source',
                        [
                            'CATALOG',
                            'MIXED',
                        ]
                    )
            )
            ->withCount(
                'options'
            )
            ->orderBy(
                'name'
            )
            ->get();


        $selectedAttribute =
            null;


        if (
            $request->filled(
                'attribute'
            )
        ) {

            $selectedAttribute =
                $attributes
                ->firstWhere(
                    'id',
                    (int) $request->input(
                        'attribute'
                    )
                );
        }


        $parentOptions =
            $selectedAttribute

            ? $selectedAttribute
            ->options()
            ->where(
                'status',
                'ACTIVE'
            )
            ->get()

            : collect();


        $selectedParentId =
            $request->integer(
                'parent'
            );


        $nextSequence =
            $this->nextSequence(
                $request->user()->id
            );


        $previewCode =
            AttributeOption::formatCode(
                $nextSequence
            );


        return view(
            'attribute-options.create',
            compact(
                'attributes',
                'selectedAttribute',
                'parentOptions',
                'selectedParentId',
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
        StoreAttributeOptionRequest $request,
        Attribute $attribute
    ): RedirectResponse {

        abort_unless(
            $attribute->user_id
                === $request->user()->id,
            404
        );


        abort_unless(
            $attribute->isSelectable(),
            422,
            'Este atributo no admite elementos de Catálogo.'
        );


        $data =
            $request->validated();


        /*
        |--------------------------------------------------------------------------
        | Imagen
        |--------------------------------------------------------------------------
        */

        $imagePath =
            null;


        if (
            $request->hasFile(
                'image'
            )
        ) {

            $imagePath =
                $request
                ->file(
                    'image'
                )
                ->store(
                    'attribute-options',
                    'public'
                );


            $data['image'] =
                $imagePath;
        }


        try {

            $option =
                DB::transaction(
                    function () use (
                        $request,
                        $attribute,
                        $data
                    ) {

                        /*
                         * Bloqueamos al usuario para garantizar
                         * secuencia única.
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


                        /*
                         * Orden visual dentro de este Catálogo.
                         */

                        $lastSortOrder =
                            (int) AttributeOption
                                ::withTrashed()
                                ->where(
                                    'attribute_id',
                                    $attribute->id
                                )
                                ->max(
                                    'sort_order'
                                );


                        $data['user_id'] =
                            $user->id;


                        $data['sequence_number'] =
                            $sequence;


                        $data['code'] =
                            AttributeOption::formatCode(
                                $sequence
                            );


                        $data['sort_order'] =
                            $lastSortOrder
                            + 10;


                        return $attribute
                            ->options()
                            ->create(
                                $data
                            );
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


        /*
        |--------------------------------------------------------------------------
        | Creación rápida desde Attribute Show
        |--------------------------------------------------------------------------
        */

        if (
            $request->input(
                'context'
            ) === 'attribute_show'
        ) {

            return redirect()
                ->to(
                    route(
                        'attributes.show',
                        $attribute
                    )
                        . '#catalog'
                )
                ->with(
                    'success',
                    "{$option->name} fue agregado al Catálogo."
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Panel global
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'attribute-options.show',
                $option
            )
            ->with(
                'success',
                'Elemento creado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(
        AttributeOption $attributeOption
    ): View {

        $this->authorize(
            'view',
            $attributeOption
        );


        $attributeOption
            ->load([
                'attribute',
                'parent',
                'children' =>
                fn($query) =>
                $query
                    ->withCount([
                        'children',
                        'values',
                    ])
                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy(
                        'name'
                    ),
            ])
            ->loadCount([
                'children',
                'values',
            ]);


        $attributeOption
            ->attribute
            ->loadCount(
                'options'
            );


        $ancestors =
            $attributeOption
            ->ancestorChain();


        return view(
            'attribute-options.show',
            compact(
                'attributeOption',
                'ancestors'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        AttributeOption $attributeOption
    ): View {

        $this->authorize(
            'update',
            $attributeOption
        );


        $attributeOption
            ->load([
                'attribute',
                'parent',
            ])
            ->loadCount([
                'children',
                'values',
            ]);


        /*
        |--------------------------------------------------------------------------
        | Candidatos válidos para padre
        |--------------------------------------------------------------------------
        |
        | Excluimos:
        |
        | - el propio elemento
        | - sus descendientes
        | - inactivos/archivados
        |
        */

        $parentOptions =
            $attributeOption
            ->attribute
            ->options()
            ->whereKeyNot(
                $attributeOption->id
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->get()
            ->reject(
                fn(
                    AttributeOption $candidate
                ) =>
                $candidate
                    ->isDescendantOf(
                        $attributeOption
                    )
            )
            ->values();


        return view(
            'attribute-options.edit',
            compact(
                'attributeOption',
                'parentOptions'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateAttributeOptionRequest $request,
        Attribute $attribute,
        AttributeOption $option
    ): RedirectResponse {

        abort_unless(
            $option->attribute_id
                === $attribute->id,
            404
        );


        abort_unless(
            $option->user_id
                === $request->user()->id,
            404
        );


        $data =
            $request->validated();


        /*
        |--------------------------------------------------------------------------
        | Datos inmutables
        |--------------------------------------------------------------------------
        |
        | No vienen desde validated():
        |
        | user_id
        | attribute_id
        | sequence_number
        | code
        | sort_order
        |
        */


        $oldImage =
            $option->image;


        $newImagePath =
            null;


        /*
        |--------------------------------------------------------------------------
        | Nueva imagen
        |--------------------------------------------------------------------------
        */

        if (
            $request->hasFile(
                'image'
            )
        ) {

            $newImagePath =
                $request
                ->file(
                    'image'
                )
                ->store(
                    'attribute-options',
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

            $option->update(
                $data
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
        | Eliminar imagen antigua
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
                'attribute-options.show',
                $option
            )
            ->with(
                'success',
                'Elemento actualizado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        AttributeOption $attributeOption
    ): RedirectResponse {

        $this->authorize(
            'delete',
            $attributeOption
        );


        /*
        |--------------------------------------------------------------------------
        | Protegido por uso
        |--------------------------------------------------------------------------
        */

        if (
            $attributeOption
            ->values()
            ->exists()
        ) {

            return back()->with(
                'error',
                'Este elemento está siendo utilizado por entidades. Archívalo en lugar de eliminarlo.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Protegido por jerarquía
        |--------------------------------------------------------------------------
        */

        if (
            $attributeOption
            ->children()
            ->exists()
        ) {

            return back()->with(
                'error',
                'Este elemento tiene subelementos. Muévelos, elimínalos o archívalos antes de eliminar el elemento superior.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Imagen
        |--------------------------------------------------------------------------
        */

        if (
            $attributeOption->image
        ) {

            Storage::disk(
                'public'
            )->delete(
                $attributeOption->image
            );
        }


        $attributeOption->delete();


        return redirect()
            ->route(
                'attribute-options.index'
            )
            ->with(
                'success',
                'Elemento eliminado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Siguiente secuencia
    |--------------------------------------------------------------------------
    */

    private function nextSequence(
        int $userId
    ): int {

        $lastSequence =
            (int) AttributeOption
                ::withTrashed()
                ->where(
                    'user_id',
                    $userId
                )
                ->max(
                    'sequence_number'
                );


        return $lastSequence
            + 1;
    }
}
