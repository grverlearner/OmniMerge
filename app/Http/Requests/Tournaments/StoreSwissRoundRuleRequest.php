<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSwissRoundRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $phaseTemplate =
            $this->route(
                'phaseTemplate'
            );

        return
            $phaseTemplate
            instanceof PhaseTemplate
            &&
            $phaseTemplate->phase_type
            ===
            'SWISS'
            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $phaseTemplate
                )
                ?? false
            );
    }

    protected function prepareForValidation(): void
    {
        $drawValue =
            $this->input(
                'allow_draws_override'
            );

        $this->merge([
            'trigger_type' =>
            strtoupper(
                (string)
                $this->input(
                    'trigger_type'
                )
            ),

            'allow_draws_override' =>
            $drawValue === ''
                ||
                $drawValue === null
                ? null
                : filter_var(
                    $drawValue,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
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
        $type =
            $this->input(
                'trigger_type'
            );

        $roundNumber =
            $type
            ===
            'ROUND_NUMBER';

        $exactRecord =
            $type
            ===
            'EXACT_RECORD';

        return [
            'trigger_type' => [
                'required',

                Rule::in([
                    'ROUND_NUMBER',
                    'QUALIFICATION_MATCH',
                    'ELIMINATION_MATCH',
                    'QUALIFICATION_OR_ELIMINATION',
                    'EXACT_RECORD',
                ]),
            ],

            'round_number' => [
                Rule::excludeIf(
                    ! $roundNumber
                ),

                Rule::requiredIf(
                    $roundNumber
                ),

                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'record_wins' => [
                Rule::excludeIf(
                    ! $exactRecord
                ),

                Rule::requiredIf(
                    $exactRecord
                ),

                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],

            'record_draws' => [
                Rule::excludeIf(
                    ! $exactRecord
                ),

                Rule::requiredIf(
                    $exactRecord
                ),

                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],

            'record_losses' => [
                Rule::excludeIf(
                    ! $exactRecord
                ),

                Rule::requiredIf(
                    $exactRecord
                ),

                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],

            'best_of' => [
                'required',

                Rule::in([
                    1,
                    3,
                    5,
                    7,
                    9,
                ]),
            ],

            'allow_draws_override' => [
                'nullable',
                'boolean',
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
