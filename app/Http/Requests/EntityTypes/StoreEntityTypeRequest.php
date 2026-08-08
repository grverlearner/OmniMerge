<?php

namespace App\Http\Requests\EntityTypes;

use App\Models\EntityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntityTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            EntityType::class
        ) ?? false;
    }


    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),

            'description' => trim(
                (string) $this->input('description')
            ),

            'icon' => trim(
                (string) $this->input('icon')
            ),
        ]);
    }


    public function rules(): array
    {
        return [

            'name' => [
                'required',
                'string',
                'max:100',
            ],


            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],


            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
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
                'El nombre del tipo es obligatorio.',


            'description.max' =>
                'La descripción no puede superar los 2000 caracteres.',


            'image.image' =>
                'El archivo seleccionado debe ser una imagen.',


            'image.mimes' =>
                'La imagen debe ser JPG, JPEG, PNG o WEBP.',


            'image.max' =>
                'La imagen no puede superar los 4 MB.',


            'color.regex' =>
                'Selecciona un color válido.',


            'status.required' =>
                'Selecciona un estado.',
        ];
    }
}