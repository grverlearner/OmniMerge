<?php

namespace App\Http\Requests\Collections;

use App\Models\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $collection = $this->route(
            'collection'
        );

        return $collection
            instanceof Collection

            &&

            $this->user()?->can(
                'update',
                $collection
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),

            'remove_image' => $this->boolean(
                'remove_image'
            ),

            'visibility' => strtoupper(
                (string) $this->input(
                    'visibility',
                    'PUBLIC'
                )
            ),

            'status' => strtoupper(
                (string) $this->input(
                    'status',
                    'ACTIVE'
                )
            ),

            'allow_cloning' => $this->boolean(
                'allow_cloning'
            ),
        ]);
    }

    public function messages(): array
    {
        return [

            'name.required' =>
            'Ponle nombre a la colección.',

            'name.max' =>
            'El nombre no puede pasar de 150 caracteres.',

            'description.max' =>
            'La descripción no puede pasar de 5000 caracteres.',

            'image.max' =>
            'La portada no puede pasar de 4 MB.',

            'image.mimes' =>
            'La portada tiene que ser JPG, PNG o WEBP.',

            'color.regex' =>
            'El color tiene que ser un hexadecimal como #8b5cf6.',

            'visibility.required' =>
            'Falta decir quién puede verla.',

            'visibility.in' =>
            'Esa visibilidad no existe.',

            'status.required' =>
            'Falta el estado.',

            'status.in' =>
            'Ese estado no existe.',

            'entity_ids.*.exists' =>
            'Alguna de las entidades elegidas ya no existe o no es tuya.',
        ];
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
                'max:5000',
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

            'visibility' => [
                'required',

                Rule::in([
                    'PUBLIC',
                    'PRIVATE',
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

            'entity_ids' => [
                'nullable',
                'array',
            ],

            'entity_ids.*' => [
                'integer',

                Rule::exists(
                    'entities',
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
        ];
    }
}
