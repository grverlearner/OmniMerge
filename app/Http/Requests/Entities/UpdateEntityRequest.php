<?php

namespace App\Http\Requests\Entities;

use App\Models\Entity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entity =
            $this->route(
                'entity'
            );


        return $entity instanceof Entity
            &&
            $this
            ->user()
            ?->can(
                'update',
                $entity
            );
    }


    protected function prepareForValidation(): void
    {
        $name =
            trim(
                (string) $this->input(
                    'name'
                )
            );


        $this->merge([
            'name' =>
            $name,

            'code' =>
            Str::upper(
                Str::slug(
                    $this->input(
                        'code'
                    )
                        ?: $name,
                    '_'
                )
            ),

            'slug' =>
            Str::slug(
                $this->input(
                    'slug'
                )
                    ?: $name
            ),

            'allow_cloning' =>
            $this->boolean(
                'allow_cloning'
            ),
        ]);
    }


    public function rules(): array
    {
        /** @var Entity $entity */
        $entity =
            $this->route(
                'entity'
            );


        return [
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
                                $this
                                    ->user()
                                    ->id
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                    ),
            ],


            'name' => [
                'required',
                'string',
                'max:150',
            ],


            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9_]+$/',

                Rule::unique(
                    'entities',
                    'code'
                )
                    ->where(
                        fn($query) =>
                        $query->where(
                            'user_id',
                            $this
                                ->user()
                                ->id
                        )
                    )
                    ->ignore(
                        $entity->id
                    ),
            ],


            'slug' => [
                'required',
                'string',
                'max:180',

                Rule::unique(
                    'entities',
                    'slug'
                )
                    ->where(
                        fn($query) =>
                        $query->where(
                            'user_id',
                            $this
                                ->user()
                                ->id
                        )
                    )
                    ->ignore(
                        $entity->id
                    ),
            ],


            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Imagen
            |--------------------------------------------------------------------------
            */

            'image' => [
                'nullable',

                File::image()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max('4mb'),
            ],


            'remove_image' => [
                'nullable',
                'boolean',
            ],


            /*
            |--------------------------------------------------------------------------
            | Publicación
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | Atributos
            |--------------------------------------------------------------------------
            */

            'selected_attribute_ids' => [
                'nullable',
                'array',
            ],


            'selected_attribute_ids.*' => [
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
                                $this
                                    ->user()
                                    ->id
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


            'attributes' => [
                'nullable',
                'array',
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

                Rule::exists(
                    'collections',
                    'id'
                )
                    ->where(
                        fn($query) =>
                        $query
                            ->where(
                                'user_id',
                                $this
                                    ->user()
                                    ->id
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                    ),
            ],
        ];
    }
}
