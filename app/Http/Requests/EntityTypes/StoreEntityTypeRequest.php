<?php

namespace App\Http\Requests\EntityTypes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEntityTypeRequest extends FormRequest
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
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('entity_types', 'code')
                    ->where(
                        fn ($query) => $query->where(
                            'user_id',
                            $this->user()->id
                        )
                    ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'icon' => [
                'nullable',
                'string',
                'max:100',
            ],

            'color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'status' => [
                'required',
                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'ARCHIVED',
                ]),
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
                'El nombre del tipo es obligatorio.',

            'code.required' =>
                'El código es obligatorio.',

            'code.regex' =>
                'El código solo admite letras mayúsculas, números y guiones bajos.',

            'code.unique' =>
                'Ya tienes un tipo de entidad con este código.',

            'color.regex' =>
                'Selecciona un color válido.',

            'status.required' =>
                'Selecciona un estado.',
        ];
    }
}