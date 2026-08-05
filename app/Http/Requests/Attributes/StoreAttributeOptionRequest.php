<?php

namespace App\Http\Requests\Attributes;

use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreAttributeOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attribute = $this->route('attribute');

        return $attribute instanceof Attribute
            && $this->user()?->can(
                'update',
                $attribute
            );
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
        /** @var Attribute $attribute */
        $attribute = $this->route('attribute');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique(
                    'attribute_options',
                    'code'
                )->where(
                    fn ($query) => $query->where(
                        'attribute_id',
                        $attribute->id
                    )
                ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'parent_option_id' => [
                'nullable',
                Rule::exists(
                    'attribute_options',
                    'id'
                )->where(
                    fn ($query) => $query->where(
                        'attribute_id',
                        $attribute->id
                    )
                ),
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

            'numeric_value' => [
                'nullable',
                'numeric',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'status' => [
                'required',
                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                ]),
            ],
        ];
    }
}