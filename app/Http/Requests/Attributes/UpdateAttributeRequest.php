<?php

namespace App\Http\Requests\Attributes;

use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attribute =
            $this->route(
                'attribute'
            );


        return $attribute
            instanceof Attribute

            &&

            $this
            ->user()
            ?->can(
                'update',
                $attribute
            );
    }


    protected function prepareForValidation(): void
    {
        /** @var Attribute|null $attribute */
        $attribute =
            $this->route(
                'attribute'
            );


        $dataType = strtoupper(
            (string) $this->input(
                'data_type',
                $attribute?->data_type
                    ?? 'OPTION'
            )
        );


        if ($dataType === 'OPTION') {

            $allowsMultiple =
                $this->has('allows_multiple')
                ? $this->boolean(
                    'allows_multiple'
                )
                : (bool) (
                    $attribute
                    ?->allows_multiple
                    ?? true
                );
        } else {

            $allowsMultiple = false;
        }


        $this->merge([

            'name' =>
            trim(
                (string) $this->input(
                    'name'
                )
            ),

            'description' =>
            $this->nullableText(
                'description'
            ),

            'help_text' =>
            $this->nullableText(
                'help_text'
            ),

            'placeholder' =>
            $this->nullableText(
                'placeholder'
            ),

            'icon' =>
            $this->nullableText(
                'icon'
            ),

            'unit' =>
            $this->nullableText(
                'unit'
            ),


            /*
            |--------------------------------------------------------------------------
            | Tipo automático
            |--------------------------------------------------------------------------
            */

            'data_type' =>
            $dataType,

            'value_source' =>
            $this->valueSourceFor(
                $dataType
            ),

            'display_style' =>
            $this->displayStyleFor(
                $dataType,
                $allowsMultiple
            ),


            'allows_multiple' =>
            $allowsMultiple,

            'allows_custom_values' =>
            false,


            /*
            |--------------------------------------------------------------------------
            | Flags
            |--------------------------------------------------------------------------
            */

            'is_required' =>
            $this->boolean(
                'is_required'
            ),

            'is_filterable' =>
            $this->boolean(
                'is_filterable'
            ),

            'is_comparable' =>
            $this->boolean(
                'is_comparable'
            ),

            'is_searchable' =>
            $this->boolean(
                'is_searchable'
            ),

            'is_visible' =>
            $this->boolean(
                'is_visible'
            ),

            'is_featured' =>
            $this->boolean(
                'is_featured'
            ),


            /*
            |--------------------------------------------------------------------------
            | Publicación
            |--------------------------------------------------------------------------
            */

            'scope' =>
            strtoupper(
                (string) $this->input(
                    'scope',
                    $attribute?->scope
                        ?? 'PUBLIC'
                )
            ),

            'status' =>
            strtoupper(
                (string) $this->input(
                    'status',
                    $attribute?->status
                        ?? 'ACTIVE'
                )
            ),

            'allow_cloning' =>
            $this->boolean(
                'allow_cloning'
            ),

            'remove_image' =>
            $this->boolean(
                'remove_image'
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


            'data_type' => [
                'required',

                Rule::in([
                    'OPTION',
                    'BOOLEAN',
                    'TEXT',
                    'LONG_TEXT',
                    'INTEGER',
                    'DECIMAL',
                    'DATE',
                    'COLOR',
                ]),
            ],


            'value_source' => [
                'required',

                Rule::in([
                    'FREE',
                    'CATALOG',
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
                    'COLOR_PICKER',
                    'DATE_PICKER',
                ]),
            ],


            'allows_multiple' => [
                'boolean',
            ],

            'allows_custom_values' => [
                'boolean',
            ],

            'is_required' => [
                'boolean',
            ],

            'is_filterable' => [
                'boolean',
            ],

            'is_comparable' => [
                'boolean',
            ],

            'is_searchable' => [
                'boolean',
            ],

            'is_visible' => [
                'boolean',
            ],

            'is_featured' => [
                'boolean',
            ],


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


            'group_ids' => [
                'nullable',
                'array',
            ],


            'group_ids.*' => [

                Rule::exists(
                    'attribute_groups',
                    'id'
                )->where(
                    fn($query) =>
                    $query->where(
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


    private function valueSourceFor(
        string $dataType
    ): string {

        return $dataType === 'OPTION'
            ? 'CATALOG'
            : 'FREE';
    }


    private function displayStyleFor(
        string $dataType,
        bool $multiple
    ): string {

        return match ($dataType) {

            'OPTION' =>
            $multiple
                ? 'MULTISELECT'
                : 'SELECT',

            'BOOLEAN' =>
            'RADIO',

            'LONG_TEXT' =>
            'TEXTAREA',

            'INTEGER',
            'DECIMAL' =>
            'NUMBER',

            'DATE' =>
            'DATE_PICKER',

            'COLOR' =>
            'COLOR_PICKER',

            default =>
            'TEXTBOX',
        };
    }
}
