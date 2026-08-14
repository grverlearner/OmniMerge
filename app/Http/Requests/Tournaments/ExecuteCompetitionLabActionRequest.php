<?php

namespace App\Http\Requests\Tournaments;

use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExecuteCompetitionLabActionRequest
extends FormRequest
{
    public function authorize(): bool
    {
        $template =
            $this->route('tournamentTemplate');

        return
            $template instanceof TournamentTemplate
            &&
            (
                $this->user()
                ?->can('update', $template)
                ??
                false
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'action' =>
            strtoupper(
                trim(
                    (string) $this->input(
                        'action'
                    )
                )
            ),

            'state_token' =>
            trim(
                (string) $this->input(
                    'state_token'
                )
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'action' => [
                'required',

                Rule::in([
                    'START',
                    'PAUSE',
                    'RESUME',
                    'RESET',
                ]),
            ],

            'state_token' => [
                'required',
                'string',
                'max:10485760',
            ],
        ];
    }
}
