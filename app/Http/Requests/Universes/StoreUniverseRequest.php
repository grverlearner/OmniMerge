<?php

namespace App\Http\Requests\Universes;

use App\Models\Universe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;


class StoreUniverseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return
            $this->user()
            ?->can(
                'create',
                Universe::class
            )
            ?? false;
    }


    protected function prepareForValidation(): void
    {
        $this->merge([

            'name' =>
            trim(
                (string)
                $this->input(
                    'name'
                )
            ),

            'status' =>
            strtoupper(
                (string)
                $this->input(
                    'status',
                    'DRAFT'
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

            'status' => [
                'required',

                Rule::in([
                    'DRAFT',
                    'ACTIVE',
                    'ARCHIVED',
                ]),
            ],
        ];
    }


    public function messages(): array
    {
        return [

            'name.required' =>
            'El nombre del Universo es obligatorio.',

            'name.max' =>
            'El nombre no puede superar los 150 caracteres.',

            'description.max' =>
            'La descripción no puede superar los 5000 caracteres.',

            'image.max' =>
            'La imagen no puede superar los 4 MB.',

            'status.in' =>
            'El estado seleccionado no es válido.',
        ];
    }
}
