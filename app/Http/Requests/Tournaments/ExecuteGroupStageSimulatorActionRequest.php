<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExecuteGroupStageSimulatorActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $phaseTemplate = $this->route('phaseTemplate');

        return
            $phaseTemplate instanceof PhaseTemplate
            && $phaseTemplate->phase_type === 'GROUP_STAGE'
            && ($this->user()?->can('update', $phaseTemplate) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'action' => strtoupper(trim((string) $this->input('action'))),
            'state_token' => trim((string) $this->input('state_token')),
        ]);
    }

    public function rules(): array
    {
        return [
            'action' => [
                'required',
                Rule::in([
                    'PREPARE_PHASE',
                    'SUBMIT_MATCH_RESULT',
                    'SIMULATE_MATCH',
                    'SIMULATE_ROUND',

                    /* Simulacion en bloque */
                    'SIMULATE_GROUP',
                    'SIMULATE_ALL',

                    'RESOLVE_MANUAL_DECISION',
                    'RESET',
                ]),
            ],

            'state_token' => [
                'required',
                'string',
                'max:1048576',
            ],

            'match_id' => [
                'nullable',
                'string',
                'max:100',
            ],

            /* Jornada y grupo concretos, para simular en bloque */
            'round_number' => [
                'nullable',
                'integer',
                'min:1',
                'max:512',
            ],

            'group_id' => [
                'nullable',
                'string',
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

            'decision_id' => [
                'nullable',
                'string',
                'max:100',
            ],

            'ordered_participant_ids' => [
                'nullable',
                'array',
                'max:256',
            ],

            'ordered_participant_ids.*' => [
                'string',
                'distinct',
                'max:100',
            ],

            'selected_participant_ids' => [
                'nullable',
                'array',
                'max:256',
            ],

            'selected_participant_ids.*' => [
                'string',
                'distinct',
                'max:100',
            ],

            'group_assignments' => [
                'nullable',
                'array',
                'max:256',
            ],

            'group_assignments.*' => [
                'string',
                'max:100',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes(
            ['match_id'],
            'required',
            fn($input) => in_array($input->action, [
                'SUBMIT_MATCH_RESULT',
                'SIMULATE_MATCH',
            ], true)
        );

        $validator->sometimes(
            ['score_a', 'score_b'],
            'required',
            fn($input) => $input->action === 'SUBMIT_MATCH_RESULT'
        );

        $validator->sometimes(
            'decision_id',
            'required',
            fn($input) => $input->action === 'RESOLVE_MANUAL_DECISION'
        );
    }
}
