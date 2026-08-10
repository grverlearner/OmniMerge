<?php

namespace App\Http\Requests\Entities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkEditEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }


    protected function prepareForValidation(): void
    {
        $this->merge([
            'entity_ids' =>
            collect(
                (array) $this->input(
                    'entity_ids',
                    []
                )
            )
                ->map(
                    fn($id) =>
                    (int) $id
                )
                ->filter()
                ->unique()
                ->values()
                ->all(),

            'collection_ids' =>
            collect(
                (array) $this->input(
                    'collection_ids',
                    []
                )
            )
                ->map(
                    fn($id) =>
                    (int) $id
                )
                ->filter()
                ->unique()
                ->values()
                ->all(),

            'attribute_order' =>
            collect(
                (array) $this->input(
                    'attribute_order',
                    []
                )
            )
                ->map(
                    fn($id) =>
                    (int) $id
                )
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);
    }


    public function rules(): array
    {
        $userId =
            $this->user()->id;


        return [

            /*
            |--------------------------------------------------------------------------
            | Selección
            |--------------------------------------------------------------------------
            */

            'entity_ids' => [
                'required',
                'array',
                'min:1',
                'max:500',
            ],

            'entity_ids.*' => [
                'integer',
                'distinct',

                Rule::exists(
                    'entities',
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
            | Operación
            |--------------------------------------------------------------------------
            */

            'operation' => [
                'required',

                Rule::in([
                    'set_property',

                    'set_attribute',
                    'append_attribute',
                    'remove_attribute_value',
                    'clear_attribute_value',
                    'remove_attribute',

                    'attribute_presentation',
                    'reorder_attributes',

                    'add_collection',
                    'remove_collection',
                    'set_collections',

                    'set_publication',

                    'matrix_update',

                    'archive',
                    'delete',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | Propiedad
            |--------------------------------------------------------------------------
            */

            'property' => [
                'nullable',

                Rule::in([
                    'entity_type_id',
                    'description',
                    'status',
                    'visibility',
                    'allow_cloning',
                ]),
            ],

            'property_value' => [
                'nullable',
            ],


            /*
            |--------------------------------------------------------------------------
            | Atributos
            |--------------------------------------------------------------------------
            */

            'attribute_id' => [
                'nullable',
                'integer',

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
             * Guardamos cualquier valor de Atributo como JSON
             * para soportar simples y multiselección.
             */
            'attribute_value_json' => [
                'nullable',
                'string',
                'max:100000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Presentación de EntityAttribute
            |--------------------------------------------------------------------------
            */

            'custom_label' => [
                'nullable',
                'string',
                'max:150',
            ],

            'presentation_visibility' => [
                'nullable',

                Rule::in([
                    '',
                    '1',
                    '0',
                ]),
            ],

            'presentation_featured' => [
                'nullable',

                Rule::in([
                    '',
                    '1',
                    '0',
                ]),
            ],

            'presentation_sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:1000000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:3000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Orden
            |--------------------------------------------------------------------------
            */

            'attribute_order' => [
                'nullable',
                'array',
                'max:100',
            ],

            'attribute_order.*' => [
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
                            ->whereNull(
                                'deleted_at'
                            )
                    ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Colecciones
            |--------------------------------------------------------------------------
            */

            'collection_id' => [
                'nullable',
                'integer',

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

            'collection_ids' => [
                'nullable',
                'array',
                'max:100',
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
            | Publicación
            |--------------------------------------------------------------------------
            */

            'publication_status' => [
                'nullable',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'ARCHIVED',
                ]),
            ],

            'publication_visibility' => [
                'nullable',

                Rule::in([
                    'PUBLIC',
                    'PRIVATE',
                    'UNLISTED',
                ]),
            ],

            'publication_allow_cloning' => [
                'nullable',

                Rule::in([
                    '',
                    '1',
                    '0',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | Matriz
            |--------------------------------------------------------------------------
            */

            'matrix_payload' => [
                'nullable',
                'string',
                'max:2000000',
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

                $operation =
                    $this->input(
                        'operation'
                    );


                /*
                |--------------------------------------------------------------------------
                | Propiedades
                |--------------------------------------------------------------------------
                */

                if (
                    $operation
                    === 'set_property'
                    &&
                    ! $this->filled(
                        'property'
                    )
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'property',
                            'Selecciona la propiedad que deseas modificar.'
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Operaciones con Atributos
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        $operation,
                        [
                            'set_attribute',
                            'append_attribute',
                            'remove_attribute_value',
                            'clear_attribute_value',
                            'remove_attribute',
                            'attribute_presentation',
                        ],
                        true
                    )
                    &&
                    ! $this->filled(
                        'attribute_id'
                    )
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'attribute_id',
                            'Selecciona un Atributo.'
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Orden
                |--------------------------------------------------------------------------
                */

                if (
                    $operation
                    === 'reorder_attributes'
                    &&
                    empty($this->input(
                            'attribute_order',
                            []
                        ))
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'attribute_order',
                            'Debes indicar al menos un Atributo para ordenar.'
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Colecciones
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        $operation,
                        [
                            'add_collection',
                            'remove_collection',
                        ],
                        true
                    )
                    &&
                    ! $this->filled(
                        'collection_id'
                    )
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'collection_id',
                            'Selecciona una Colección.'
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Matrix JSON
                |--------------------------------------------------------------------------
                */

                if (
                    $operation
                    === 'matrix_update'
                ) {

                    $payload =
                        $this->input(
                            'matrix_payload'
                        );


                    if (
                        ! is_string(
                            $payload
                        )
                        ||
                        $payload === ''
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'matrix_payload',
                                'No se recibieron cambios de la matriz.'
                            );

                        return;
                    }


                    json_decode(
                        $payload,
                        true
                    );


                    if (
                        json_last_error()
                        !== JSON_ERROR_NONE
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'matrix_payload',
                                'Los cambios de la matriz no tienen un formato válido.'
                            );
                    }
                }
            }
        );
    }


    public function messages(): array
    {
        return [

            'entity_ids.required' =>
            'Selecciona al menos una Entidad.',

            'entity_ids.max' =>
            'Puedes modificar como máximo 500 Entidades en una operación.',

            'entity_ids.*.exists' =>
            'Una de las Entidades seleccionadas no te pertenece o ya no existe.',

            'attribute_id.exists' =>
            'El Atributo seleccionado no es válido.',

            'collection_id.exists' =>
            'La Colección seleccionada no es válida.',
        ];
    }
}
