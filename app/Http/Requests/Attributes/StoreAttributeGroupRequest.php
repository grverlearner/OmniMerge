<?php

namespace App\Http\Requests\Attributes;

use App\Models\AttributeGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttributeGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            AttributeGroup::class
        ) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $collapsible = $this->boolean(
            'collapsible'
        );

        $this->merge([
            'name' => trim(
                (string) $this->input(
                    'name'
                )
            ),

            'description' =>
            $this->nullableText(
                'description'
            ),

            'icon' =>
            $this->nullableText(
                'icon'
            ),

            'layout_type' =>
            strtoupper(
                (string) $this->input(
                    'layout_type',
                    'LIST'
                )
            ),

            'collapsible' =>
            $collapsible,

            /*
             * Si NO puede contraerse,
             * siempre estará visualmente abierto.
             */

            'default_expanded' =>
            $collapsible
                ? $this->boolean(
                    'default_expanded'
                )
                : true,

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
        return [
            /*
            |--------------------------------------------------------------------------
            | Grupo
            |--------------------------------------------------------------------------
            */

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
                'max:3000',
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

            'layout_type' => [
                'required',

                Rule::in([
                    'LIST',
                    'GRID',
                    'CARDS',
                    'TABLE',
                    'COMPACT',
                ]),
            ],

            'collapsible' => [
                'boolean',
            ],

            'default_expanded' => [
                'boolean',
            ],

            'status' => [
                'required',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'ARCHIVED',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Atributos seleccionados
            |--------------------------------------------------------------------------
            */

            'attribute_ids' => [
                'nullable',
                'array',
            ],

            'attribute_ids.*' => [
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
                        ->whereNull(
                            'deleted_at'
                        )
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Configuración en el grupo
            |--------------------------------------------------------------------------
            |
            | Ejemplo:
            |
            | attribute_settings[5][custom_label]
            | attribute_settings[5][sort_order]
            | attribute_settings[5][is_featured]
            |
            */

            'attribute_settings' => [
                'nullable',
                'array',
            ],

            'attribute_settings.*' => [
                'nullable',
                'array',
            ],

            'attribute_settings.*.custom_label' => [
                'nullable',
                'string',
                'max:150',
            ],

            'attribute_settings.*.sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'attribute_settings.*.is_featured' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
            'El nombre del grupo es obligatorio.',

            'color.regex' =>
            'Selecciona un color válido.',

            'layout_type.in' =>
            'La presentación seleccionada no es válida.',

            'attribute_ids.*.exists' =>
            'Uno de los atributos seleccionados no es válido.',
        ];
    }

    private function nullableText(
        string $field
    ): ?string {
        $value = trim(
            (string) $this->input(
                $field
            )
        );

        return $value !== ''
            ? $value
            : null;
    }
}
