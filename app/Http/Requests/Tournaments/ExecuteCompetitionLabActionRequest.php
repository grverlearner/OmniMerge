<?php

namespace App\Http\Requests\Tournaments;

use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExecuteCompetitionLabActionRequest
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
            'action' =>
            strtoupper(
                trim(
                    (string)
                    $this->input(
                        'action'
                    )
                )
            ),

            'state_token' =>
            trim(
                (string)
                $this->input(
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
                    'PREPARE_NODE',
                    'SUBMIT_MATCH_RESULT',
                    'SUBMIT_ENCOUNTER_RESULT',
                    'SIMULATE_MATCH',
                    'SIMULATE_ROUND',
                    'START_TOURNAMENT',
                    'STEP_RUNTIME',
                    'RUN_TOURNAMENT',
                ]),
            ],

            'state_token' => [
                'required',
                'string',
                'max:10485760',
            ],

            'node_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'participant_ids' => [
                'nullable',
                'array',
                'min:2',
                'max:512',
            ],

            'participant_ids.*' => [
                'string',
                'distinct',
                'max:100',
            ],

            'match_id' => [
                'nullable',
                'string',
                'max:100',
            ],

            'qualifier_ids' => [
                'nullable',
                'array',
                'max:64',
            ],

            'qualifier_ids.*' => [
                'string',
                'distinct',
                'max:100',
            ],

            'score_a' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
            ],

            'score_b' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
            ],

            'maximum_operations' => [
                'nullable',
                'integer',
                'min:1',
                'max:1000',
            ],
        ];
    }

    public function withValidator(
        Validator $validator
    ): void {
        $validator->sometimes(
            [
                'node_id',
                'participant_ids',
            ],
            'required',
            fn($input) =>
            $input->action
                ===
                'PREPARE_NODE'
        );

        $validator->sometimes(
            [
                'node_id',
                'match_id',
            ],
            'required',
            fn($input) =>
            in_array(
                $input->action,
                [
                    'SUBMIT_MATCH_RESULT',
                    'SUBMIT_ENCOUNTER_RESULT',
                    'SIMULATE_MATCH',
                ],
                true
            )
        );

        $validator->sometimes(
            'qualifier_ids',
            'required',
            fn($input) =>
            $input->action
                ===
                'SUBMIT_ENCOUNTER_RESULT'
        );

        $validator->sometimes(
            [
                'score_a',
                'score_b',
            ],
            'required',
            fn($input) =>
            $input->action
                ===
                'SUBMIT_MATCH_RESULT'
        );

        $validator->sometimes(
            'node_id',
            'required',
            fn($input) =>
            $input->action
                ===
                'SIMULATE_ROUND'
        );
    }
}
