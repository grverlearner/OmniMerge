<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\BulkStoreEntityRequest;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\Collection;
use App\Models\Entity;
use App\Models\EntityAttribute;
use App\Models\EntityAttributeValue;
use App\Models\EntityType;
use App\Services\Entities\EntityBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class BulkEntityController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | EDITOR MASIVO
    |--------------------------------------------------------------------------
    */

    public function create(
        Request $request
    ): View {

        $this->authorize(
            'create',
            Entity::class
        );


        $user =
            $request->user();


        /*
        |--------------------------------------------------------------------------
        | Tipos
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


        /*
        |--------------------------------------------------------------------------
        | Atributos + Catálogos
        |--------------------------------------------------------------------------
        */

        $attributes =
            Attribute::query()
                ->ownedBy(
                    $user
                )
                ->active()
                ->with([
                    'groups',

                    'options' =>
                        fn ($query) =>
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
                ->orderBy(
                    'sort_order'
                )
                ->orderBy(
                    'name'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Grupos
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Colecciones
        |--------------------------------------------------------------------------
        */

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
        | Entidades disponibles como plantilla
        |--------------------------------------------------------------------------
        */

        $templateEntities =
            Entity::query()
                ->ownedBy(
                    $user
                )
                ->with(
                    'entityType'
                )
                ->latest(
                    'updated_at'
                )
                ->limit(100)
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Entidad elegida como plantilla
        |--------------------------------------------------------------------------
        */

        $templatePayload =
            null;


        $templateEntityId =
            $request->filled(
                'template_entity'
            )
                ? $request->integer(
                    'template_entity'
                )
                : null;


        if ($templateEntityId) {

            $template =
                Entity::query()
                    ->ownedBy(
                        $user
                    )
                    ->with([
                        'collections',

                        'entityAttributes.attribute',

                        'entityAttributes.values.option',
                    ])
                    ->find(
                        $templateEntityId
                    );


            if ($template) {

                $values = [];


                foreach (
                    $template->entityAttributes
                    as $assignment
                ) {

                    $values[
                        (string) $assignment->attribute_id
                    ] =
                        $this->assignmentValue(
                            $assignment
                        );
                }


                $templatePayload = [

                    'id' =>
                        $template->id,

                    'name' =>
                        $template->name,

                    'entity_type_id' =>
                        $template->entity_type_id
                            ? (string) $template->entity_type_id
                            : '',

                    'collection_ids' =>
                        $template
                            ->collections
                            ->pluck(
                                'id'
                            )
                            ->map(
                                fn ($id) =>
                                    (string) $id
                            )
                            ->values()
                            ->all(),

                    'selected_attribute_ids' =>
                        $template
                            ->entityAttributes
                            ->pluck(
                                'attribute_id'
                            )
                            ->map(
                                fn ($id) =>
                                    (string) $id
                            )
                            ->values()
                            ->all(),

                    'values' =>
                        $values,
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Nombres existentes para advertencias visuales
        |--------------------------------------------------------------------------
        */

        $existingEntityNames =
            Entity::query()
                ->ownedBy(
                    $user
                )
                ->pluck(
                    'name'
                )
                ->values()
                ->all();


        return view(
            'entities.bulk.create',
            compact(
                'entityTypes',
                'attributes',
                'groups',
                'collections',
                'templateEntities',
                'templatePayload',
                'templateEntityId',
                'existingEntityNames'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GUARDAR LOTE
    |--------------------------------------------------------------------------
    */

    public function store(
        BulkStoreEntityRequest $request,
        EntityBuilderService $builder
    ): RedirectResponse {

        $this->authorize(
            'create',
            Entity::class
        );


        $data =
            $request->validated();


        /*
        |--------------------------------------------------------------------------
        | Ignorar filas sin nombre
        |--------------------------------------------------------------------------
        */

        $rows =
            collect(
                $data['rows']
            )
                ->filter(
                    fn ($row) =>
                        trim(
                            (string) (
                                $row['name']
                                ?? ''
                            )
                        ) !== ''
                )
                ->all();


        /*
        |--------------------------------------------------------------------------
        | Imágenes que realmente almacenamos
        |--------------------------------------------------------------------------
        */

        $storedPaths = [];


        try {

            /*
            |--------------------------------------------------------------------------
            | 1. Imágenes individuales
            |--------------------------------------------------------------------------
            */

            $individualImages =
                $request->file(
                    'images',
                    []
                );


            foreach (
                $individualImages
                as $rowKey => $file
            ) {

                if (
                    ! isset(
                        $rows[
                            $rowKey
                        ]
                    )
                ) {
                    continue;
                }


                $path =
                    $file->store(
                        'entities',
                        'public'
                    );


                $rows[
                    $rowKey
                ]['image'] =
                    $path;


                $storedPaths[] =
                    $path;
            }


            /*
            |--------------------------------------------------------------------------
            | 2. Carga masiva por nombre de archivo
            |--------------------------------------------------------------------------
            |
            | "Naruto Uzumaki"
            | se relaciona con:
            |
            | naruto-uzumaki.jpg
            | Naruto Uzumaki.png
            | NARUTO_UZUMAKI.webp
            |
            */

            $availableRows =
                [];


            foreach (
                $rows
                as $rowKey => $row
            ) {

                /*
                 * Imagen individual tiene prioridad.
                 */
                if (
                    ! empty(
                        $row['image']
                    )
                ) {
                    continue;
                }


                $normalized =
                    $this->normalizeImageName(
                        $row['name']
                    );


                if ($normalized === '') {
                    continue;
                }


                $availableRows[
                    $normalized
                ][] =
                    $rowKey;
            }


            foreach (
                $request->file(
                    'bulk_images',
                    []
                )
                as $file
            ) {

                $originalBaseName =
                    pathinfo(
                        $file->getClientOriginalName(),
                        PATHINFO_FILENAME
                    );


                $normalized =
                    $this->normalizeImageName(
                        $originalBaseName
                    );


                if (
                    empty(
                        $availableRows[
                            $normalized
                        ]
                    )
                ) {

                    /*
                     * Si no coincide con ninguna fila,
                     * simplemente no se almacena.
                     */
                    continue;
                }


                $rowKey =
                    array_shift(
                        $availableRows[
                            $normalized
                        ]
                    );


                $path =
                    $file->store(
                        'entities',
                        'public'
                    );


                $rows[
                    $rowKey
                ]['image'] =
                    $path;


                $storedPaths[] =
                    $path;
            }


            /*
            |--------------------------------------------------------------------------
            | Datos comunes
            |--------------------------------------------------------------------------
            */

            $commonData = [

                'entity_type_id' =>
                    $data[
                        'entity_type_id'
                    ]
                    ?? null,

                'status' =>
                    $data[
                        'status'
                    ],

                'visibility' =>
                    $data[
                        'visibility'
                    ],

                'allow_cloning' =>
                    $data[
                        'allow_cloning'
                    ]
                    ?? false,
            ];


            /*
            |--------------------------------------------------------------------------
            | Crear lote
            |--------------------------------------------------------------------------
            */

            $result =
                $builder->createMany(

                    $request->user(),

                    $commonData,

                    $rows,

                    $data[
                        'selected_attribute_ids'
                    ]
                    ?? [],

                    $data[
                        'common_attribute_ids'
                    ]
                    ?? [],

                    $data[
                        'common_attributes'
                    ]
                    ?? [],

                    $data[
                        'collection_ids'
                    ]
                    ?? [],

                    $data[
                        'duplicate_strategy'
                    ]
                );


            /*
            |--------------------------------------------------------------------------
            | Si se omitieron duplicados,
            | eliminar sus imágenes recién almacenadas
            |--------------------------------------------------------------------------
            */

            foreach (
                array_keys(
                    $result[
                        'skipped'
                    ]
                )
                as $rowKey
            ) {

                $path =
                    $rows[
                        $rowKey
                    ]['image']
                    ?? null;


                if ($path) {

                    Storage::disk(
                        'public'
                    )
                        ->delete(
                            $path
                        );
                }
            }


            $createdCount =
                count(
                    $result[
                        'created'
                    ]
                );


            $skippedCount =
                count(
                    $result[
                        'skipped'
                    ]
                );


            $message =
                "{$createdCount} entidades creadas correctamente.";


            if ($skippedCount > 0) {

                $message .=
                    " {$skippedCount} se omitieron por existir previamente.";
            }


            return redirect()
                ->route(
                    'entities.index',
                    [
                        'sort' =>
                            'newest',
                    ]
                )
                ->with(
                    'success',
                    $message
                );


        } catch (Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | Si falla todo el lote,
            | eliminar todas las imágenes almacenadas
            |--------------------------------------------------------------------------
            */

            foreach (
                $storedPaths
                as $path
            ) {

                Storage::disk(
                    'public'
                )
                    ->delete(
                        $path
                    );
            }


            throw $exception;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | OBTENER VALOR DE UNA ENTIDAD PLANTILLA
    |--------------------------------------------------------------------------
    */

    private function assignmentValue(
        EntityAttribute $assignment
    ): mixed {

        $attribute =
            $assignment->attribute;


        $values =
            $assignment
                ->values
                ->map(
                    fn (
                        EntityAttributeValue $value
                    ) =>
                        $this->rawValue(
                            $attribute,
                            $value
                        )
                )
                ->filter(
                    fn ($value) =>
                        $value !== null
                        &&
                        $value !== ''
                )
                ->values();


        if (
            $attribute->allows_multiple
        ) {

            return $values
                ->map(
                    fn ($value) =>
                        (string) $value
                )
                ->all();
        }


        $first =
            $values->first();


        return $first !== null
            ? (string) $first
            : '';
    }


    /*
    |--------------------------------------------------------------------------
    | VALOR CRUDO PARA EL FORMULARIO
    |--------------------------------------------------------------------------
    */

    private function rawValue(
        Attribute $attribute,
        EntityAttributeValue $value
    ): mixed {

        return match (
            $attribute->data_type
        ) {

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
                $value->date_value
                    ?->format(
                        'Y-m-d'
                    ),

            'COLOR' =>
                $value->color_value,

            default =>
                $value->text_value
                ?? $value->custom_value,
        };
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZAR NOMBRE DE ARCHIVO
    |--------------------------------------------------------------------------
    */

    private function normalizeImageName(
        string $value
    ): string {

        return Str::slug(
            $value
        );
    }
}