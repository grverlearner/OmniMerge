<?php

namespace App\Http\Requests\Universes;

use App\Models\Universe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUniverseSeasonRequest extends FormRequest
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
                    'PLANNED'
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
                    'PLANNED',
                    'ACTIVE',
                    'COMPLETED',
                    'ARCHIVED',
                ]),
            ],

            'starts_at' => [
                'nullable',
                'date',
            ],

            'ends_at' => [
                'nullable',
                'date',
                'after_or_equal:starts_at',
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'name.required' =>
            'La temporada necesita un nombre.',

            'ends_at.after_or_equal' =>
            'La fecha de fin no puede ser anterior a la de inicio.',

            'status.in' =>
            'El estado seleccionado no es válido.',
        ];
    }
}
