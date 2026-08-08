<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ProfileUpdateRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Preparar datos
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation(): void
    {
        $website = trim(
            (string) $this->input('website')
        );

        /*
         * Permitimos que el usuario escriba:
         *
         * github.com/usuario
         *
         * y automáticamente se convierta en:
         *
         * https://github.com/usuario
         */

        if (
            $website !== ''
            &&
            ! Str::startsWith(
                $website,
                [
                    'http://',
                    'https://',
                ]
            )
        ) {
            $website =
                'https://' . $website;
        }


        $this->merge([
            'name' =>
            trim(
                (string) $this->input('name')
            ),

            'username' =>
            trim(
                (string) $this->input('username')
            ),

            'email' =>
            Str::lower(
                trim(
                    (string) $this->input('email')
                )
            ),

            'headline' =>
            trim(
                (string) $this->input('headline')
            ),

            'bio' =>
            trim(
                (string) $this->input('bio')
            ),

            'location' =>
            trim(
                (string) $this->input('location')
            ),

            'website' =>
            $website !== ''
                ? $website
                : null,

            'remove_avatar' =>
            $this->boolean('remove_avatar'),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Reglas
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        $userId =
            $this->user()->id;

        return [

            'name' => [
                'required',
                'string',
                'max:100',
            ],


            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',

                Rule::unique(
                    'users',
                    'username'
                )->ignore($userId),
            ],


            'email' => [
                'required',
                'string',
                'email',
                'max:150',

                Rule::unique(
                    'users',
                    'email'
                )->ignore($userId),
            ],


            'headline' => [
                'nullable',
                'string',
                'max:120',
            ],


            'bio' => [
                'nullable',
                'string',
                'max:500',
            ],


            'location' => [
                'nullable',
                'string',
                'max:100',
            ],


            'website' => [
                'nullable',
                'url',
                'max:255',
            ],


            'profile_visibility' => [
                'required',

                Rule::in([
                    'PUBLIC',
                    'PRIVATE',
                ]),
            ],


            'avatar' => [
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


            'remove_avatar' => [
                'boolean',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Mensajes personalizados
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [

            'name.required' =>
            'El nombre es obligatorio.',

            'username.required' =>
            'El nombre de usuario es obligatorio.',

            'username.alpha_dash' =>
            'El username solo puede contener letras, números, guiones y guiones bajos.',

            'username.unique' =>
            'Ese nombre de usuario ya está siendo utilizado.',

            'email.required' =>
            'El correo electrónico es obligatorio.',

            'email.email' =>
            'Ingresa un correo electrónico válido.',

            'email.unique' =>
            'Ese correo electrónico ya está registrado.',

            'headline.max' =>
            'La presentación corta no puede superar los 120 caracteres.',

            'bio.max' =>
            'La biografía no puede superar los 500 caracteres.',

            'website.url' =>
            'Ingresa una dirección web válida.',

            'avatar.max' =>
            'La foto de perfil no puede superar los 3 MB.',
        ];
    }
}
