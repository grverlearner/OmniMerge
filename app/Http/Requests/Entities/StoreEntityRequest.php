<?php

namespace App\Http\Requests\Entities;

use App\Models\Entity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            Entity::class
        ) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),

            'description' => $this->nullableText(
                'description'
            ),

            'visibility' => strtoupper(
                (string) $this->input(
                    'visibility',
                    'PUBLIC'
                )
            ),

            'status' => strtoupper(
                (string) $this->input(
                    'status',
                    'ACTIVE'
                )
            ),

            'allow_cloning' => $this->has('allow_cloning')
                ? $this->boolean('allow_cloning')
                : true,
        ]);
    }

    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Información
            |--------------------------------------------------------------------------
            */

            'entity_type_id' => [
                'nullable',

                Rule::exists(
                    'entity_types',
                    'id'
                )->where(
                    fn($query) => $query
                        ->where(
                            'user_id',
                            $this->user()->id
                        )
                        ->whereNull('deleted_at')
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

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

            /*
            |--------------------------------------------------------------------------
            | Publicación
            |--------------------------------------------------------------------------
            */

            'visibility' => [
                'required',

                Rule::in([
                    'PUBLIC',
                    'PRIVATE',
                    'UNLISTED',
                ]),
            ],

            'status' => [
                'required',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'ARCHIVED',
                ]),
            ],

            'allow_cloning' => [
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Características seleccionadas
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
                )->where(
                    fn($query) => $query
                        ->where(
                            'user_id',
                            $this->user()->id
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
                )->where(
                    fn($query) => $query
                        ->where(
                            'user_id',
                            $this->user()->id
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
            'El nombre de la entidad es obligatorio.',

            'entity_type_id.exists' =>
            'El tipo seleccionado no es válido.',

            'image.image' =>
            'El archivo seleccionado debe ser una imagen.',

            'image.max' =>
            'La imagen no puede superar los 4 MB.',

            'selected_attribute_ids.*.exists' =>
            'Uno de los atributos seleccionados no es válido.',

            'collection_ids.*.exists' =>
            'Una de las colecciones seleccionadas no es válida.',
        ];
    }

    private function nullableText(
        string $field
    ): ?string {
        $value = trim(
            (string) $this->input($field)
        );

        return $value !== ''
            ? $value
            : null;
    }
}
