<?php

namespace App\Http\Requests\Entities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));

        $this->merge([
            'name' => $name,

            'code' => Str::upper(
                Str::slug(
                    $this->input('code') ?: $name,
                    '_'
                )
            ),

            'slug' => Str::slug(
                $this->input('slug') ?: $name
            ),

            'allow_cloning' =>
            $this->boolean('allow_cloning'),
        ]);
    }

    public function rules(): array
    {
        return [
            'entity_type_id' => [
                'nullable',
                Rule::exists('entity_types', 'id')
                    ->where(
                        fn($query) => $query
                            ->where('user_id', $this->user()->id)
                            ->whereNull('deleted_at')
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
                Rule::unique('entities', 'code')
                    ->where(
                        fn($query) => $query->where(
                            'user_id',
                            $this->user()->id
                        )
                    ),
            ],

            'slug' => [
                'required',
                'string',
                'max:180',
                Rule::unique('entities', 'slug')
                    ->where(
                        fn($query) => $query->where(
                            'user_id',
                            $this->user()->id
                        )
                    ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
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
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
            'El nombre de la entidad es obligatorio.',

            'entity_type_id.exists' =>
            'El tipo seleccionado no es válido.',

            'code.unique' =>
            'Ya tienes una entidad con este código.',

            'slug.unique' =>
            'Ya tienes una entidad con este identificador.',

            'image.image' =>
            'El archivo seleccionado debe ser una imagen.',

            'image.mimes' =>
            'La imagen debe ser JPG, PNG o WEBP.',

            'image.max' =>
            'La imagen no puede superar los 2 MB.',
        ];
    }
}
