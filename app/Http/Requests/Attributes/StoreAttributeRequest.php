<?php

namespace App\Http\Requests\Attributes;

use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this
            ->user()
            ?->can(
                'create',
                Attribute::class
            ) ?? false;
    }


    /*
    |--------------------------------------------------------------------------
    | Preparar datos
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation(): void
    {
        $dataType = strtoupper(
            (string) $this->input(
                'data_type',
                'OPTION'
            )
        );


        /*
         * Catálogo:
         * múltiple por defecto.
         *
         * Los demás:
         * valor único.
         */

        if ($dataType === 'OPTION') {

            $allowsMultiple =
                $this->has('allows_multiple')
                ? $this->boolean(
                    'allows_multiple'
                )
                : true;
        } else {

            $allowsMultiple = false;
        }


        $this->merge([

            /*
            |--------------------------------------------------------------------------
            | Texto
            |--------------------------------------------------------------------------
            */

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
            | Tipo
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


            /*
            |--------------------------------------------------------------------------
            | Comportamiento
            |--------------------------------------------------------------------------
            */

            'allows_multiple' =>
            $allowsMultiple,

            /*
             * En esta etapa no permitiremos catálogo + valor libre.
             */
            'allows_custom_values' =>
            false,


            'is_required' =>
            $this->boolean(
                'is_required'
            ),


            'is_filterable' =>
            $this->has('is_filterable')
                ? $this->boolean(
                    'is_filterable'
                )
                : true,


            'is_comparable' =>
            $this->has('is_comparable')
                ? $this->boolean(
                    'is_comparable'
                )
                : true,


            'is_searchable' =>
            $this->has('is_searchable')
                ? $this->boolean(
                    'is_searchable'
                )
                : true,


            'is_visible' =>
            $this->has('is_visible')
                ? $this->boolean(
                    'is_visible'
                )
                : true,


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
                    'PUBLIC'
                )
            ),

            'status' =>
            strtoupper(
                (string) $this->input(
                    'status',
                    'ACTIVE'
                )
            ),

            'allow_cloning' =>
            $this->has('allow_cloning')
                ? $this->boolean(
                    'allow_cloning'
                )
                : true,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Reglas
    |--------------------------------------------------------------------------
    */

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


            /*
            |--------------------------------------------------------------------------
            | Imagen
            |--------------------------------------------------------------------------
            */

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


            'icon' => [
                'nullable',
                'string',
                'max:100',
            ],


            'color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],


            /*
            |--------------------------------------------------------------------------
            | Tipo
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | Comportamiento
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | Restricciones
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | Grupos
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | Publicación
            |--------------------------------------------------------------------------
            */

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


    public function messages(): array
    {
        return [

            'name.required' =>
            'El nombre del atributo es obligatorio.',

            'image.image' =>
            'El archivo seleccionado debe ser una imagen.',

            'image.max' =>
            'La imagen no puede superar los 4 MB.',

            'color.regex' =>
            'Selecciona un color válido.',

            'data_type.required' =>
            'Selecciona un tipo de atributo.',

            'scope.required' =>
            'Selecciona una visibilidad.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

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
