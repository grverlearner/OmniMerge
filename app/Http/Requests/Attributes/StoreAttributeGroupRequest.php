<?php

namespace App\Http\Requests\Attributes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreAttributeGroupRequest extends FormRequest
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

            'collapsible' =>
                $this->boolean('collapsible'),

            'default_expanded' =>
                $this->boolean('default_expanded'),
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

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_]+$/',

                Rule::unique('attribute_groups', 'code')
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

            'attribute_ids' => [
                'nullable',
                'array',
            ],

            'attribute_ids.*' => [
                Rule::exists('attributes', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'user_id',
                                $this->user()->id
                            )
                            ->whereNull('deleted_at')
                    ),
            ],
        ];
    }
}