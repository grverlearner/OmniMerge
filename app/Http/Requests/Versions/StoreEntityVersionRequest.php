<?php

namespace App\Http\Requests\Versions;

use App\Models\AttributeOption;
use App\Models\EntityVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEntityVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            &&
            $this->user()->isActive();
    }


    protected function prepareForValidation(): void
    {
        $this->merge([
            'definition_mode' =>
            strtoupper(
                (string) $this->input(
                    'definition_mode',
                    'EXISTING'
                )
            ),

            'image_source' =>
            strtoupper(
                (string) $this->input(
                    'image_source',
                    'UPLOAD'
                )
            ),

            'definition_image_mode' =>
            strtoupper(
                (string) $this->input(
                    'definition_image_mode',
                    'SAME'
                )
            ),

            'new_version_kind' =>
            strtoupper(
                (string) $this->input(
                    'new_version_kind',
                    'OTHER'
                )
            ),

            'new_version_activation_mode' =>
            strtoupper(
                (string) $this->input(
                    'new_version_activation_mode',
                    'BOTH'
                )
            ),

            'new_relation_type' =>
            strtoupper(
                (string) $this->input(
                    'new_relation_type',
                    'ACTIVATES'
                )
            ),

            'name' =>
            trim(
                (string) $this->input(
                    'name',
                    ''
                )
            ),

            'new_version_name' =>
            trim(
                (string) $this->input(
                    'new_version_name',
                    ''
                )
            ),

            'auto_parent' =>
            $this->boolean(
                'auto_parent',
                true
            ),

            'inherit_base_attributes' =>
            $this->boolean(
                'inherit_base_attributes',
                true
            ),

            'is_default' =>
            $this->boolean(
                'is_default'
            ),

            'priority' =>
            (int) $this->input(
                'priority',
                0
            ),

            'sort_order' =>
            (int) $this->input(
                'sort_order',
                0
            ),

            'status' =>
            strtoupper(
                (string) $this->input(
                    'status',
                    'ACTIVE'
                )
            ),
        ]);
    }


    public function rules(): array
    {
        $userId =
            $this->user()->id;


        $creatingDefinition =
            in_array(
                $this->input(
                    'definition_mode'
                ),
                [
                    'NEW_SHARED',
                    'NEW_EXCLUSIVE',
                ],
                true
            );


        return [

            /*
            |--------------------------------------------------------------------------
            | Modo
            |--------------------------------------------------------------------------
            */

            'definition_mode' => [
                'required',

                Rule::in([
                    'EXISTING',
                    'NEW_SHARED',
                    'NEW_EXCLUSIVE',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | Version existente
            |--------------------------------------------------------------------------
            */

            'version_id' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input(
                        'definition_mode'
                    )
                        ===
                        'EXISTING'
                ),

                'nullable',
                'integer',

                Rule::exists(
                    'versions',
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
            | Nueva definición
            |--------------------------------------------------------------------------
            */

            'new_version_name' => [
                Rule::requiredIf(
                    $creatingDefinition
                ),

                'nullable',
                'string',
                'max:150',
            ],


            'new_version_description' => [
                'nullable',
                'string',
                'max:5000',
            ],


            'new_version_kind' => [
                Rule::requiredIf(
                    $creatingDefinition
                ),

                Rule::in([
                    'ERA',
                    'AGE',
                    'FORM',
                    'TRANSFORMATION',
                    'OUTFIT',
                    'TIMELINE',
                    'OTHER',
                ]),
            ],


            'new_version_activation_mode' => [
                Rule::requiredIf(
                    $creatingDefinition
                ),

                Rule::in([
                    'AUTO',
                    'MANUAL',
                    'BOTH',
                ]),
            ],


            'new_version_parent_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'versions',
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
            | Contexto opcional
            |--------------------------------------------------------------------------
            */

            'new_catalog_attribute_id' => [
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
                                'data_type',
                                'OPTION'
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


            'new_catalog_attribute_option_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'attribute_options',
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


            'new_relation_type' => [
                Rule::in([
                    'ACTIVATES',
                    'CONTEXT',
                    'RELATED',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | Imagen concreta
            |--------------------------------------------------------------------------
            */

            'image_source' => [
                'required',

                Rule::in([
                    'UPLOAD',
                    'ENTITY',
                    'VERSION',
                ]),
            ],


            'image' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input(
                        'image_source'
                    )
                        ===
                        'UPLOAD'
                ),

                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],


            'source_entity_version_id' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input(
                        'image_source'
                    )
                        ===
                        'VERSION'
                ),

                'nullable',
                'integer',

                Rule::exists(
                    'entity_versions',
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
            | Imagen de definición
            |--------------------------------------------------------------------------
            */

            'definition_image_mode' => [
                Rule::requiredIf(
                    $creatingDefinition
                ),

                Rule::in([
                    'SAME',
                    'UPLOAD',
                ]),
            ],


            'new_version_image' => [
                Rule::requiredIf(
                    fn() =>
                    $creatingDefinition
                        &&
                        $this->input(
                            'definition_image_mode'
                        )
                        ===
                        'UPLOAD'
                ),

                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],


            /*
            |--------------------------------------------------------------------------
            | EntityVersion
            |--------------------------------------------------------------------------
            */

            'name' => [
                'nullable',
                'string',
                'max:150',
            ],


            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],


            'auto_parent' => [
                'boolean',
            ],


            'parent_entity_version_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'entity_versions',
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


            'inherit_base_attributes' => [
                'boolean',
            ],


            'is_default' => [
                'boolean',
            ],


            'priority' => [
                'integer',
                'min:-100000',
                'max:100000',
            ],


            'sort_order' => [
                'integer',
                'min:0',
                'max:1000000',
            ],


            'status' => [
                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'ARCHIVED',
                ]),
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

                $entity =
                    $this->route(
                        'entity'
                    );


                /*
                |--------------------------------------------------------------------------
                | Imagen de Entidad base
                |--------------------------------------------------------------------------
                */

                if (
                    $this->input(
                        'image_source'
                    )
                    ===
                    'ENTITY'
                    &&
                    ! $entity?->image
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'image_source',
                            'La Entidad base no tiene una imagen para reutilizar.'
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Imagen de otra EntityVersion
                |--------------------------------------------------------------------------
                */

                if (
                    $this->input(
                        'image_source'
                    )
                    ===
                    'VERSION'
                    &&
                    $this->filled(
                        'source_entity_version_id'
                    )
                ) {

                    $source =
                        EntityVersion::query()
                        ->find(
                            $this->input(
                                'source_entity_version_id'
                            )
                        );


                    if (
                        $source
                        &&
                        $source->entity_id
                        !==
                        $entity?->id
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'source_entity_version_id',
                                'Solo puedes reutilizar imágenes de Versiones de esta misma Entidad.'
                            );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Padre concreto
                |--------------------------------------------------------------------------
                */

                if (
                    $this->filled(
                        'parent_entity_version_id'
                    )
                ) {

                    $parent =
                        EntityVersion::query()
                        ->find(
                            $this->input(
                                'parent_entity_version_id'
                            )
                        );


                    if (
                        $parent
                        &&
                        $parent->entity_id
                        !==
                        $entity?->id
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'parent_entity_version_id',
                                'La Versión concreta padre debe pertenecer a la misma Entidad.'
                            );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Elemento de Catálogo
                |--------------------------------------------------------------------------
                */

                if (
                    $this->filled(
                        'new_catalog_attribute_id'
                    )
                    &&
                    $this->filled(
                        'new_catalog_attribute_option_id'
                    )
                ) {

                    $option =
                        AttributeOption::query()
                        ->find(
                            $this->input(
                                'new_catalog_attribute_option_id'
                            )
                        );


                    if (
                        ! $option
                        ||
                        $option->attribute_id
                        !==
                        (int) $this->input(
                            'new_catalog_attribute_id'
                        )
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                'new_catalog_attribute_option_id',
                                'El elemento seleccionado no pertenece al Catálogo indicado.'
                            );
                    }
                }
            }
        );
    }
}
