<?php

namespace App\Http\Requests\Attributes;

use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreAttributeOptionRequest extends FormRequest
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

            $attribute->isSelectable()

            &&

            $this
            ->user()
            ?->can(
                'update',
                $attribute
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Preparar
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation(): void
    {
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


            'icon' =>
            $this->nullableText(
                'icon'
            ),


            'status' =>
            strtoupper(
                (string) $this->input(
                    'status',
                    'ACTIVE'
                )
            ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Reglas
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        /** @var Attribute $attribute */
        $attribute =
            $this->route(
                'attribute'
            );


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


            /*
            |--------------------------------------------------------------------------
            | Jerarquía
            |--------------------------------------------------------------------------
            */

            'parent_option_id' => [
                'nullable',

                Rule::exists(
                    'attribute_options',
                    'id'
                )->where(
                    fn($query) =>
                    $query
                        ->where(
                            'attribute_id',
                            $attribute->id
                        )
                        ->where(
                            'user_id',
                            $this->user()->id
                        )
                        ->where(
                            'status',
                            'ACTIVE'
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                ),
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
            | Valor numérico opcional
            |--------------------------------------------------------------------------
            */

            'numeric_value' => [
                'nullable',
                'numeric',
            ],


            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            'status' => [
                'required',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'ARCHIVED',
                ]),
            ],
        ];
    }


    public function messages(): array
    {
        return [

            'name.required' =>
            'El nombre del elemento es obligatorio.',


            'parent_option_id.exists' =>
            'El elemento superior seleccionado no pertenece a este Catálogo.',


            'image.image' =>
            'El archivo seleccionado debe ser una imagen.',


            'image.max' =>
            'La imagen no puede superar los 4 MB.',


            'color.regex' =>
            'Selecciona un color válido.',


            'numeric_value.numeric' =>
            'El valor de referencia debe ser numérico.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Redirección al Catálogo del atributo
    |--------------------------------------------------------------------------
    */

    protected function getRedirectUrl(): string
    {
        $attribute =
            $this->route(
                'attribute'
            );


        if (
            $this->input(
                'context'
            ) === 'attribute_show'

            &&

            $attribute
            instanceof Attribute
        ) {

            return route(
                'attributes.show',
                $attribute
            )
                . '#catalog';
        }


        return parent::getRedirectUrl();
    }


    private function nullableText(
        string $field
    ): ?string {

        $value =
            trim(
                (string) $this->input(
                    $field
                )
            );


        return $value !== ''
            ? $value
            : null;
    }
}
