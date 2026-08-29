<?php

namespace App\Http\Requests\Universes;

use App\Models\Universe;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Universes\Concerns\ValidatesTournamentConfiguration;
use Illuminate\Validation\Rule;

class StoreUniverseTournamentRequest extends FormRequest
{
    use ValidatesTournamentConfiguration;

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

            'recurrence_mode' =>
            strtoupper(
                (string)
                $this->input(
                    'recurrence_mode',
                    'ONCE'
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

            /*
             * Ambientacion propia del Universo.
             */
            'context' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
             * Juego con el que se resolveran sus batallas (Fase 11).
             * Nulo = el que el Universo tenga por defecto.
             */
            'game_key' => [
                'nullable',
                'string',

                Rule::in(
                    app(\App\Services\Games\GameRegistry::class)->keys()
                ),
            ],

            'image' => [
                'nullable',

                \Illuminate\Validation\Rules\File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max('4mb'),
            ],

            /*
             * Recurrencia: cada cuanto vuelve a jugarse este torneo.
             */
            'recurrence_mode' => [
                'required',

                Rule::in([
                    'ONCE',
                    'EVERY_SEASON',
                    'EVERY_N_SEASONS',
                    'MANUAL',
                ]),
            ],

            'recurrence_interval' => [
                'nullable',
                'integer',
                'min:1',
                'max:50',
                'required_if:recurrence_mode,EVERY_N_SEASONS',
            ],

            'first_season_number' => [
                'nullable',
                'integer',
                'min:1',
                'max:9999',
            ],

            /*
             * El juego, la batalla y quien puede competir. Viven en un
             * trait compartido con el alta y la edicion para que no
             * diverjan.
             */
            ...$this->configurationRules(),
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

            'recurrence_interval.required_if' =>
            'Indica cada cuántas temporadas se repite.',
        ];
    }
}
