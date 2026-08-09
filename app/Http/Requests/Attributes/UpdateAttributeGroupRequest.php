<?php

namespace App\Http\Requests\Attributes;

use App\Models\AttributeGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttributeGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->route(
            'attribute_group'
        );

        return $group instanceof AttributeGroup
            &&
            $this->user()?->can(
                'update',
                $group
            );
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
                    fn ($query) => $query
                        ->where(
                            'user_id',
                            $this->user()->id
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                ),
            ],

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