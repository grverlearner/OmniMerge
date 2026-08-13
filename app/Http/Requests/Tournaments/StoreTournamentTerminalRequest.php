<?php

namespace App\Http\Requests\Tournaments;

use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTournamentTerminalRequest
extends FormRequest
{
    public function authorize(): bool
    {
        $template =
            $this->route(
                'tournamentTemplate'
            );

        return
            $template
            instanceof
            TournamentTemplate
            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $template
                )
                ??
                false
            );
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

            'terminal_type' =>
            strtoupper(
                (string)
                $this->input(
                    'terminal_type',
                    'ELIMINATED'
                )
            ),

            'status' =>
            strtoupper(
                (string)
                $this->input(
                    'status',
                    'ACTIVE'
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
                'max:120',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'terminal_type' => [
                'required',

                Rule::in([
                    'CHAMPION',
                    'QUALIFIED',
                    'ELIMINATED',
                    'SECONDARY',
                    'PLACEMENT',
                    'CUSTOM',
                ]),
            ],

            'expected_participants' => [
                'nullable',
                'integer',
                'min:1',
                'max:512',
            ],

            'status' => [
                'required',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                ]),
            ],
        ];
    }
}
