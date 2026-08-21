<?php

namespace App\Http\Requests\Universes;

use App\Models\Universe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUniverseTournamentRequest extends FormRequest
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

            /*
             * Solo plantillas del propietario del Universo.
             */
            'tournament_template_id' => [
                'required',
                'integer',

                Rule::exists('tournament_templates', 'id')
                    ->where(
                        'user_id',
                        $this->user()?->id
                    )
                    ->whereNull('deleted_at'),
            ],

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

            'tournament_template_id.required' =>
            'Selecciona la plantilla de torneo que usará este Universo.',

            'tournament_template_id.exists' =>
            'La plantilla seleccionada no es válida.',

            'name.required' =>
            'Dale un nombre a este torneo dentro del Universo.',

            'status.in' =>
            'El estado seleccionado no es válido.',
        ];
    }
}
