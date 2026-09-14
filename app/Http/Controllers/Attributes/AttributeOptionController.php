<?php

namespace App\Http\Controllers\Attributes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attributes\StoreAttributeOptionRequest;
use App\Http\Requests\Attributes\UpdateAttributeOptionRequest;
use App\Models\Attribute;
use App\Models\AttributeContextRuleCondition;
use App\Models\AttributeOption;
use App\Models\AttributeOptionRelationship;
use App\Models\Entity;
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


            /*
             * Un valor sin imagen es invisible en todas las pantallas que
             * eligen por la cara —el constructor de reglas, las dependencias de
             * catalogo, el selector de una entidad—. Merece su propia cifra.
             */
            'sin_imagen' => (clone $baseQuery)
                ->whereNull(
                    'image'
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


        /*
        |--------------------------------------------------------------------------
        | Cuantas entidades llevan cada valor
        |--------------------------------------------------------------------------
        |
        | La pagina ya trae el `values_count` de los valores que muestra, pero la
        | vista de catalogos y la de jerarquia hablan de TODOS, no solo de los de
        | esta pagina. Se saca de una vez con una consulta agrupada.
        |
        */

        $usoPorValor =
            DB::table(
                'entity_attribute_values'
            )
            ->join(
                'attribute_options',
                'attribute_options.id',
                '=',
                'entity_attribute_values.attribute_option_id'
            )
            ->where(
                'attribute_options.user_id',
                $request->user()->id
            )
            ->selectRaw(
                'attribute_options.id as opcion, COUNT(*) as total'
            )
            ->groupBy(
                'attribute_options.id'
            )
            ->pluck(
                'total',
                'opcion'
            );


        /*
        |--------------------------------------------------------------------------
        | Todos los valores, en version ligera
        |--------------------------------------------------------------------------
        |
        | Dos vistas nuevas necesitan la biblioteca entera y no la pagina:
        |
        |   · «Catalogos», que resume cada catalogo con las caras de los suyos.
        |   · «Jerarquia», porque un arbol partido por la paginacion no es un
        |     arbol: si el padre cae en la pagina 1 y dos hijos en la 3, lo que
        |     se dibuja miente.
        |
        | Por eso se piden solo las columnas que hacen falta para pintarlos.
        |
        */

        $todosLosValores =
            AttributeOption::query()
            ->ownedBy(
                $request->user()
            )
            ->orderBy(
                'attribute_id'
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->get([
                'id',
                'attribute_id',
                'parent_option_id',
                'name',
                'image',
                'icon',
                'color',
                'status',
                'sort_order',
            ]);


        $valoresPorCatalogo =
            $todosLosValores
            ->groupBy(
                'attribute_id'
            );


        /*
        |--------------------------------------------------------------------------
        | El resumen de cada catalogo
        |--------------------------------------------------------------------------
        |
        | Se calcula sobre la coleccion que ya esta en memoria, sin volver a la
        | base de datos.
        |
        */

        $resumenCatalogos =
            $attributes
            ->map(
                function ($atributo) use ($valoresPorCatalogo, $usoPorValor) {

                    $suyos =
                        $valoresPorCatalogo
                        ->get(
                            $atributo->id,
                            collect()
                        );

                    $usados =
                        $suyos
                        ->filter(
                            fn($valor) =>
                            ($usoPorValor[$valor->id] ?? 0) > 0
                        )
                        ->count();

                    return [
                        'atributo' => $atributo,

                        'total' => $suyos->count(),

                        'activos' => $suyos
                            ->where('status', 'ACTIVE')
                            ->count(),

                        'con_imagen' => $suyos
                            ->filter(fn($valor) => (bool) $valor->image)
                            ->count(),

                        'con_padre' => $suyos
                            ->filter(fn($valor) => $valor->parent_option_id !== null)
                            ->count(),

                        'usados' => $usados,

                        'caras' => $suyos
                            ->filter(fn($valor) => (bool) $valor->image)
                            ->take(8)
                            ->values(),
                    ];
                }
            )
            ->sortByDesc(
                'total'
            )
            ->values();


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
                'perPage',
                'usoPorValor',
                'valoresPorCatalogo',
                'resumenCatalogos'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Cambio rapido desde el indice
    |--------------------------------------------------------------------------
    |
    | Archivar o reactivar un valor sin abrir su formulario entero. En un
    | catalogo de cincuenta clanes, entrar y salir de la ficha para tocar un
    | desplegable es el trabajo que nadie hace, y por eso los catalogos se
    | quedan con valores muertos dentro.
    |
    */

    public function quickUpdate(
        Request $request,
        AttributeOption $attributeOption
    ): RedirectResponse {

        $this->authorize(
            'update',
            $attributeOption
        );


        $data =
            $request->validate(
                [
                    'status' => [
                        'required',
                        'in:ACTIVE,INACTIVE,ARCHIVED',
                    ],
                ],
                [
                    'status.required' =>
                    'Falta decir en qué estado se queda.',

                    'status.in' =>
                    'Ese estado no existe.',
                ]
            );


        $attributeOption->update(
            $data
        );


        $etiquetas = [
            'ACTIVE' => 'activo',
            'INACTIVE' => 'inactivo',
            'ARCHIVED' => 'archivado',
        ];


        return back()
            ->with(
                'success',
                "{$attributeOption->name} queda "
                    . $etiquetas[$data['status']]
                    . '.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Cambio en lote
    |--------------------------------------------------------------------------
    |
    | Lo mismo, pero para varios a la vez. Es la unica manera razonable de
    | ordenar un catalogo grande.
    |
    */

    public function bulkUpdate(
        Request $request
    ): RedirectResponse {

        $data =
            $request->validate(
                [
                    'ids' => [
                        'required',
                        'array',
                        'min:1',
                    ],

                    'ids.*' => [
                        'integer',
                    ],

                    'status' => [
                        'required',
                        'in:ACTIVE,INACTIVE,ARCHIVED',
                    ],
                ],
                [
                    'ids.required' =>
                    'No has seleccionado ningún valor.',

                    'status.in' =>
                    'Ese estado no existe.',
                ]
            );


        /*
         * Solo los suyos. Un id ajeno colado en el envio se queda fuera sin
         * hacer ruido, que es lo que tiene que pasar.
         */

        $afectados =
            AttributeOption::query()
            ->ownedBy(
                $request->user()
            )
            ->whereIn(
                'id',
                $data['ids']
            )
            ->update([
                'status' =>
                $data['status'],
            ]);


        $etiquetas = [
            'ACTIVE' => 'activos',
            'INACTIVE' => 'inactivos',
            'ARCHIVED' => 'archivados',
        ];


        return back()
            ->with(
                'success',
                $afectados === 0

                    ? 'No se cambió ninguno: los valores seleccionados no son tuyos.'

                    : $afectados
                    . ($afectados === 1 ? ' valor queda ' : ' valores quedan ')
                    . $etiquetas[$data['status']]
                    . '.'
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
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()

            : collect();


        /*
        |--------------------------------------------------------------------------
        | Lo que ya hay dentro
        |--------------------------------------------------------------------------
        |
        | Sirve para dos cosas que faltaban:
        |
        |   · Ensenar de que esta hecho el catalogo antes de anadirle nada, para
        |     no crear el mismo valor dos veces con otro nombre.
        |   · Avisar en cuanto se escribe un nombre que ya existe. Un catalogo
        |     con «Uzumaki» y «uzumaki» no da error en ningun sitio: simplemente
        |     queda mal para siempre.
        |
        | Entran los de cualquier estado, porque un valor archivado ocupa el
        | nombre igual.
        |
        */

        $existingOptions =
            $selectedAttribute

            ? $selectedAttribute
            ->options()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
                'image',
                'icon',
                'color',
                'status',
                'parent_option_id',
            ])

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
                'existingOptions',
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
        | Seguir añadiendo
        |--------------------------------------------------------------------------
        |
        | Rellenar un catalogo de cincuenta clanes es cincuenta veces el mismo
        | formulario. Si se marca la casilla, se vuelve a el con el catalogo —y
        | el padre, si lo habia— ya elegidos, en vez de aterrizar en la ficha del
        | que se acaba de crear.
        |
        */

        if (
            $request->boolean(
                'keep_adding'
            )
        ) {

            return redirect()
                ->route(
                    'attribute-options.create',
                    array_filter([
                        'attribute' => $attribute->id,

                        'parent' => $request->input(
                            'parent_option_id'
                        ),
                    ])
                )
                ->with(
                    'success',
                    "{$option->name} añadido. Puedes seguir con el siguiente."
                );
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


        /*
        |--------------------------------------------------------------------------
        | Quien lo lleva
        |--------------------------------------------------------------------------
        |
        | Era el agujero de esta pantalla: ensenaba «17 usos» como un numero y no
        | dejaba ver ni una de las diecisiete. Un valor de catalogo existe para
        | que alguien lo lleve; sin las caras, la ficha no dice nada.
        |
        */

        $entidades =
            Entity::query()
            ->whereHas(
                'entityAttributes.values',
                fn($query) =>
                $query->where(
                    'attribute_option_id',
                    $attributeOption->id
                )
            )
            ->with('entityType')
            ->orderBy('name')
            ->limit(24)
            ->get();


        $totalEntidades =
            Entity::query()
            ->whereHas(
                'entityAttributes.values',
                fn($query) =>
                $query->where(
                    'attribute_option_id',
                    $attributeOption->id
                )
            )
            ->count();


        /*
         * Y aparte, las versiones de entidad. Es otro sitio donde este mismo
         * valor puede estar puesto, y no contarlo hace parecer que no se usa.
         */

        $usoEnVersiones =
            DB::table(
                'entity_version_attribute_values'
            )
            ->where(
                'attribute_option_id',
                $attributeOption->id
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Sus hermanos de catalogo
        |--------------------------------------------------------------------------
        |
        | Sirven para dos cosas: saltar de uno a otro sin volver al indice, y ver
        | de un vistazo si este es el que sostiene el catalogo o el que no ha
        | tocado nadie.
        |
        */

        $hermanos =
            $attributeOption
            ->attribute
            ->options()
            ->withCount('values')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();


        $porUso =
            $hermanos
            ->sortByDesc('values_count')
            ->values();


        $posicion =
            $porUso
            ->search(
                fn($hermano) =>
                $hermano->id === $attributeOption->id
            );


        $posicion =
            $posicion === false
            ? null
            : $posicion + 1;


        /*
        |--------------------------------------------------------------------------
        | Quien depende de el
        |--------------------------------------------------------------------------
        |
        | Antes de borrar un valor conviene saber si hay una regla montada
        | encima. Esto no se veia en ninguna parte.
        |
        */

        $dependencias =
            AttributeOptionRelationship::query()
            ->where(
                fn($query) =>
                $query
                    ->where(
                        'source_option_id',
                        $attributeOption->id
                    )
                    ->orWhere(
                        'target_option_id',
                        $attributeOption->id
                    )
            )
            ->with([
                'sourceOption.attribute',
                'targetOption.attribute',
            ])
            ->get();


        $enReglas =
            AttributeContextRuleCondition::query()
            ->where(
                'source_option_id',
                $attributeOption->id
            )
            ->with([
                'rule.targetAttribute',
                'sourceAttribute',
            ])
            ->get();


        return view(
            'attribute-options.show',
            compact(
                'attributeOption',
                'ancestors',
                'entidades',
                'totalEntidades',
                'usoEnVersiones',
                'hermanos',
                'posicion',
                'dependencias',
                'enReglas'
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


        /*
         * Sus hermanos de catalogo, para avisar de un nombre repetido y para
         * ensenar de que esta hecho el catalogo. El propio elemento queda
         * fuera: repetirse consigo mismo no es repetirse.
         */

        $existingOptions =
            $attributeOption
            ->attribute
            ->options()
            ->whereKeyNot(
                $attributeOption->id
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
                'image',
                'icon',
                'color',
                'status',
                'parent_option_id',
            ]);


        return view(
            'attribute-options.edit',
            compact(
                'attributeOption',
                'parentOptions',
                'existingOptions'
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
