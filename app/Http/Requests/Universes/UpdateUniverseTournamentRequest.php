<?php

namespace App\Http\Requests\Universes;

use App\Models\Universe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/*
|--------------------------------------------------------------------------
| UpdateUniverseTournamentRequest
|--------------------------------------------------------------------------
|
| No permite cambiar la plantilla de origen: eso sería otro torneo.
| Para usar otra plantilla se adopta una nueva.
|
*/

class UpdateUniverseTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $universe =
            $this->route('universe');

        return
            $universe
            instanceof
            Universe

            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $universe
                )
                ?? false
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([

            'name' =>
            trim(
                (string)
                $this->input('name')
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
            'Dale un nombre a este torneo dentro del Universo.',

            'status.in' =>
            'El estado seleccionado no es válido.',
        ];
    }
}
