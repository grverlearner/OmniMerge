<?php

namespace App\Http\Requests\Attributes;

use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class UpdateAttributeOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attribute =
            $this->route(
                'attribute'
            );


        $option =
            $this->route(
                'option'
            );


        return $attribute
            instanceof Attribute

            &&

            $option
            instanceof AttributeOption

            &&

            $option->attribute_id
            === $attribute->id

            &&

            $option->user_id
            === $this->user()?->id

            &&

            $this
            ->user()
            ?->can(
                'update',
                $option
            );
    }


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


            'remove_image' =>
            $this->boolean(
                'remove_image'
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


    public function rules(): array
    {
        /** @var Attribute $attribute */
        $attribute =
            $this->route(
                'attribute'
            );


        /** @var AttributeOption $option */
        $option =
            $this->route(
                'option'
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
                            'id',
                            '<>',
                            $option->id
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


            'numeric_value' => [
                'nullable',
                'numeric',
            ],


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


    /*
    |--------------------------------------------------------------------------
    | Proteger de ciclos
    |--------------------------------------------------------------------------
    */

    public function withValidator(
        Validator $validator
    ): void {

        $validator->after(
            function (
                Validator $validator
            ) {

                /** @var AttributeOption|null $option */
                $option =
                    $this->route(
                        'option'
                    );


                if (! $option) {
                    return;
                }


                $parentId =
                    $this->input(
                        'parent_option_id'
                    );


                if (! $parentId) {
                    return;
                }


                $parent =
                    AttributeOption::query()
                    ->whereKey(
                        $parentId
                    )
                    ->where(
                        'attribute_id',
                        $option->attribute_id
                    )
                    ->where(
                        'user_id',
                        $option->user_id
                    )
                    ->first();


                if (! $parent) {
                    return;
                }


                /*
                 * Si el futuro padre ya es descendiente
                 * del elemento actual, crearíamos un ciclo.
                 */

                if (
                    $parent
                    ->isDescendantOf(
                        $option
                    )
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'parent_option_id',
                            'No puedes utilizar un subelemento como elemento superior porque produciría una jerarquía circular.'
                        );
                }
            }
        );
    }


    public function messages(): array
    {
        return [

            'name.required' =>
            'El nombre del elemento es obligatorio.',


            'parent_option_id.exists' =>
            'El elemento superior seleccionado no es válido.',


            'image.image' =>
            'El archivo seleccionado debe ser una imagen.',


            'image.max' =>
            'La imagen no puede superar los 4 MB.',
        ];
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
