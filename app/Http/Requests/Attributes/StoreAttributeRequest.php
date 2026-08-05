<?php

namespace App\Http\Requests\Attributes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreAttributeRequest extends FormRequest
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
            'allows_multiple' =>
            $this->boolean('allows_multiple'),
            'allows_custom_values' =>
            $this->boolean('allows_custom_values'),
            'is_required' =>
            $this->boolean('is_required'),
            'is_filterable' =>
            $this->boolean('is_filterable'),
            'is_comparable' =>
            $this->boolean('is_comparable'),
            'is_searchable' =>
            $this->boolean('is_searchable'),
            'is_visible' =>
            $this->boolean('is_visible'),
            'is_featured' =>
            $this->boolean('is_featured'),

            'allow_cloning' =>
            $this->boolean('allow_cloning'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('attributes', 'code')
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
                Rule::unique('attributes', 'slug')
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
                'max:3000',
            ],

            'help_text' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'placeholder' => [
                'nullable',
                'string',
                'max:255',
            ],

            'data_type' => [
                'required',
                Rule::in([
                    'TEXT',
                    'LONG_TEXT',
                    'INTEGER',
                    'DECIMAL',
                    'BOOLEAN',
                    'DATE',
                    'COLOR',
                    'OPTION',
                ]),
            ],

            'value_source' => [
                'required',
                Rule::in([
                    'FREE',
                    'CATALOG',
                    'MIXED',
                ]),
            ],

            'display_style' => [
                'required',
                Rule::in([
                    'TEXTBOX',
                    'TEXTAREA',
                    'NUMBER',
                    'SELECT',
                    'MULTISELECT',
                    'RADIO',
                    'CHECKBOX',
                    'TAGS',
                    'SLIDER',
                    'COLOR_PICKER',
                    'DATE_PICKER',
                ]),
            ],

            'allows_multiple' => ['boolean'],
            'allows_custom_values' => ['boolean'],
            'is_required' => ['boolean'],
            'is_filterable' => ['boolean'],
            'is_comparable' => ['boolean'],
            'is_searchable' => ['boolean'],
            'is_visible' => ['boolean'],
            'is_featured' => ['boolean'],

            'min_numeric_value' => [
                'nullable',
                'numeric',
            ],

            'max_numeric_value' => [
                'nullable',
                'numeric',
                'gte:min_numeric_value',
            ],

            'min_length' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'max_length' => [
                'nullable',
                'integer',
                'gte:min_length',
            ],

            'unit' => [
                'nullable',
                'string',
                'max:30',
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
                    'ARCHIVED',
                ]),
            ],

            'group_ids' => [
                'nullable',
                'array',
            ],

            'group_ids.*' => [
                Rule::exists('attribute_groups', 'id')
                    ->where(
                        fn($query) => $query->where(
                            'user_id',
                            $this->user()->id
                        )
                    ),
            ],

            'scope' => [
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
}
