<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePhaseExitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $phaseTemplate =
            $this->route('phaseTemplate');

        return $phaseTemplate
            instanceof PhaseTemplate
            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $phaseTemplate
                ) ?? false
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),

            'selector_type' => strtoupper(
                (string) $this->input(
                    'selector_type'
                )
            ),

            'status' => strtoupper(
                (string) $this->input(
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

            'selector_type' => [
                'required',
                Rule::in([
                    'MATCH_WINNERS',
                    'MATCH_LOSERS',
                    'TOP_N',
                    'BOTTOM_N',
                    'RANK_POSITION',
                    'RANK_RANGE',
                    'ALL',
                    'REMAINING',
                ]),
            ],

            'selector_from' => [
                Rule::requiredIf(
                    fn() =>
                    in_array(
                        $this->input('selector_type'),
                        [
                            'TOP_N',
                            'BOTTOM_N',
                            'RANK_POSITION',
                            'RANK_RANGE',
                        ],
                        true
                    )
                ),
                'nullable',
                'integer',
                'min:1',
                'max:512',
            ],

            'selector_to' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input('selector_type')
                        ===
                        'RANK_RANGE'
                ),
                'nullable',
                'integer',
                'min:1',
                'max:512',
                'gte:selector_from',
            ],

            'priority' => [
                'required',
                'integer',
                'min:1',
                'max:999',
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
