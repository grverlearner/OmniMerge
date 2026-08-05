<?php

namespace App\Http\Requests\Attributes;

use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateAttributeOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attribute = $this->route('attribute');
        $option = $this->route('option');

        return $attribute instanceof Attribute
            && $option instanceof AttributeOption
            && $option->attribute_id === $attribute->id
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

            'remove_image' =>
                $this->boolean('remove_image'),
        ]);
    }

    public function rules(): array
    {
        /** @var Attribute $attribute */
        $attribute = $this->route('attribute');

        /** @var AttributeOption $option */
        $option = $this->route('option');

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

                Rule::unique(
                    'attribute_options',
                    'code'
                )
                    ->where(
                        fn ($query) => $query->where(
                            'attribute_id',
                            $attribute->id
                        )
                    )
                    ->ignore($option->id),
            ],

            'description' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'parent_option_id' => [
                'nullable',

                Rule::exists(
                    'attribute_options',
                    'id'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'attribute_id',
                            $attribute->id
                        )
                        ->where('id', '<>', $option->id)
                        ->whereNull('deleted_at')
                ),
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
                    ->max('3mb'),
            ],

            'remove_image' => [
                'boolean',
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