<?php

namespace App\Http\Requests\Entities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkStoreEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }


    protected function prepareForValidation(): void
    {
        $rows = collect(
            (array) $this->input(
                'rows',
                []
            )
        )
            ->map(
                function ($row) {

                    $row =
                        (array) $row;

                    $row['name'] =
                        trim(
                            (string) (
                                $row['name']
                                ?? ''
                            )
                        );

                    $description =
                        trim(
                            (string) (
                                $row['description']
                                ?? ''
                            )
                        );

                    $row['description'] =
                        $description !== ''
                        ? $description
                        : null;

                    $row['entity_type_id'] =
                        ! empty($row['entity_type_id'])
                        ? (int) $row['entity_type_id']
                        : null;

                    $row['attributes'] =
                        (array) (
                            $row['attributes']
                            ?? []
                        );

                    return $row;
                }
            )
            ->all();


        $this->merge([
            'batch_name' =>
            trim(
                (string) $this->input(
                    'batch_name',
                    ''
                )
            ),

            'entity_type_id' =>
            $this->filled(
                'entity_type_id'
            )
                ? $this->integer(
                    'entity_type_id'
                )
                : null,

            'allow_cloning' =>
            $this->boolean(
                'allow_cloning'
            ),

            'collection_ids' =>
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            (array) $this->input(
                                'collection_ids',
                                []
                            )
                        )
                    )
                )
            ),

            'selected_attribute_ids' =>
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            (array) $this->input(
                                'selected_attribute_ids',
                                []
                            )
                        )
                    )
                )
            ),

            'common_attribute_ids' =>
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            (array) $this->input(
                                'common_attribute_ids',
                                []
                            )
                        )
                    )
                )
            ),

            'rows' =>
            $rows,
        ]);
    }


    public function rules(): array
    {
        $userId =
            $this->user()->id;


        return [

            /*
            |--------------------------------------------------------------------------
            | Lote
            |--------------------------------------------------------------------------
            */

            'batch_name' => [
                'nullable',
                'string',
                'max:150',
            ],


            /*
            |--------------------------------------------------------------------------
            | Configuración común
            |--------------------------------------------------------------------------
            */

            'entity_type_id' => [
                'nullable',

                Rule::exists(
                    'entity_types',
                    'id'
                )
                    ->where(
                        fn($query) =>
                        $query
                            ->where(
                                'user_id',
                                $userId
                            )
                            ->where(
                                'status',
                                'ACTIVE'
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                    ),
            ],

            'status' => [
                'required',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'ARCHIVED',
                ]),
            ],

            'visibility' => [
                'required',

                Rule::in([
                    'PRIVATE',
                    'PUBLIC',
                    'UNLISTED',
                ]),
            ],

            'allow_cloning' => [
                'boolean',
            ],

            'duplicate_strategy' => [
                'required',

                Rule::in([
                    'create',
                    'skip',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | Colecciones
            |--------------------------------------------------------------------------
            */

            'collection_ids' => [
                'nullable',
                'array',
            ],

            'collection_ids.*' => [
                'integer',
                'distinct',

                Rule::exists(
                    'collections',
                    'id'
                )
                    ->where(
                        fn($query) =>
                        $query
                            ->where(
                                'user_id',
                                $userId
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                    ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Atributos seleccionados
            |--------------------------------------------------------------------------
            */

            'selected_attribute_ids' => [
                'nullable',
                'array',
                'max:100',
            ],

            'selected_attribute_ids.*' => [
                'integer',
                'distinct',

                Rule::exists(
                    'attributes',
                    'id'
                )
                    ->where(
                        fn($query) =>
                        $query
                            ->where(
                                'user_id',
                                $userId
                            )
                            ->where(
                                'status',
                                'ACTIVE'
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                    ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Atributos comunes
            |--------------------------------------------------------------------------
            */

            'common_attribute_ids' => [
                'nullable',
                'array',
            ],

            'common_attribute_ids.*' => [
                'integer',
                'distinct',

                Rule::exists(
                    'attributes',
                    'id'
                )
                    ->where(
                        fn($query) =>
                        $query
                            ->where(
                                'user_id',
                                $userId
                            )
                            ->where(
                                'status',
                                'ACTIVE'
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                    ),
            ],

            'common_attributes' => [
                'nullable',
                'array',
            ],


            /*
            |--------------------------------------------------------------------------
            | Filas
            |--------------------------------------------------------------------------
            */

            'rows' => [
                'required',
                'array',
                'min:1',
                'max:200',
            ],

            'rows.*.name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'rows.*.description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'rows.*.entity_type_id' => [
                'nullable',

                Rule::exists(
                    'entity_types',
                    'id'
                )
                    ->where(
                        fn($query) =>
                        $query
                            ->where(
                                'user_id',
                                $userId
                            )
                            ->where(
                                'status',
                                'ACTIVE'
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                    ),
            ],

            'rows.*.attributes' => [
                'nullable',
                'array',
            ],


            /*
            |--------------------------------------------------------------------------
            | Imágenes por fila
            |--------------------------------------------------------------------------
            */

            'images' => [
                'nullable',
                'array',
            ],

            'images.*' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],


            /*
            |--------------------------------------------------------------------------
            | Imágenes masivas
            |--------------------------------------------------------------------------
            */

            'bulk_images' => [
                'nullable',
                'array',
                'max:200',
            ],

            'bulk_images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }


    public function withValidator(
        Validator $validator
    ): void {

        $validator->after(
            function (
                Validator $validator
            ) {

                /*
                |--------------------------------------------------------------------------
                | Debe existir al menos una fila con nombre
                |--------------------------------------------------------------------------
                */

                $namedRows =
                    collect(
                        (array) $this->input(
                            'rows',
                            []
                        )
                    )
                    ->filter(
                        fn($row) =>
                        trim(
                            (string) (
                                $row['name']
                                ?? ''
                            )
                        ) !== ''
                    );


                if ($namedRows->isEmpty()) {

                    $validator
                        ->errors()
                        ->add(
                            'rows',
                            'Debes ingresar al menos una entidad con nombre.'
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Los comunes deben formar parte de los seleccionados
                |--------------------------------------------------------------------------
                */

                $selected =
                    collect(
                        (array) $this->input(
                            'selected_attribute_ids',
                            []
                        )
                    )
                    ->map('intval');

                $common =
                    collect(
                        (array) $this->input(
                            'common_attribute_ids',
                            []
                        )
                    )
                    ->map('intval');


                $invalidCommon =
                    $common->diff(
                        $selected
                    );


                if (
                    $invalidCommon
                    ->isNotEmpty()
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'common_attribute_ids',
                            'Un atributo común debe estar seleccionado en el lote.'
                        );
                }
            }
        );
    }


    public function messages(): array
    {
        return [

            'rows.max' =>
            'Puedes crear como máximo 200 entidades por lote.',

            'rows.*.name.max' =>
            'El nombre de una entidad no puede superar 150 caracteres.',

            'rows.*.description.max' =>
            'La descripción no puede superar 5000 caracteres.',

            'images.*.image' =>
            'Uno de los archivos individuales no es una imagen válida.',

            'images.*.mimes' =>
            'Las imágenes deben ser JPG, PNG o WEBP.',

            'images.*.max' =>
            'Cada imagen puede pesar como máximo 2 MB.',

            'bulk_images.*.image' =>
            'Uno de los archivos de la carga masiva no es una imagen válida.',

            'bulk_images.*.max' =>
            'Cada imagen puede pesar como máximo 2 MB.',
        ];
    }
}
