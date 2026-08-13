<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseSwissTiebreaker;
use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSwissTiebreakerRequest extends FormRequest
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
        $this->merge([
            'criterion' =>
            strtoupper(
                (string)
                $this->input(
                    'criterion'
                )
            ),

            'direction' =>
            strtoupper(
                (string)
                $this->input(
                    'direction',
                    'AUTO'
                )
            ),
        ]);
    }

    public function rules(): array
    {
        $phaseTemplate =
            $this->route(
                'phaseTemplate'
            );

        $tiebreaker =
            $this->route(
                'tiebreaker'
            );

        $cutCriterion =
            $this->input(
                'criterion'
            )
            ===
            'OPPONENT_SCORE_CUT_LOWEST';

        return [
            'criterion' => [
                'required',

                Rule::in([
                    'WINS',
                    'FEWEST_LOSSES',

                    'OPPONENT_SCORE_SUM',
                    'OPPONENT_SCORE_CUT_LOWEST',

                    'SONNEBORN_BERGER',
                    'CUMULATIVE_SCORE',

                    'SCORE_DIFFERENCE',
                    'SCORE_FOR',

                    'GAME_DIFFERENCE',
                    'GAME_WINS',

                    'HEAD_TO_HEAD',
                    'SEED',
                ]),

                Rule::unique(
                    'phase_swiss_tiebreakers',
                    'criterion'
                )
                    ->where(
                        fn($query) =>
                        $query->where(
                            'phase_template_id',
                            $phaseTemplate?->id
                        )
                    )
                    ->ignore(
                        $tiebreaker
                            instanceof
                            PhaseSwissTiebreaker
                            ? $tiebreaker->id
                            : null
                    ),
            ],

            'parameter_int' => [
                Rule::excludeIf(
                    ! $cutCriterion
                ),

                Rule::requiredIf(
                    $cutCriterion
                ),

                'nullable',
                'integer',
                'min:1',
                'max:20',
            ],

            'direction' => [
                'required',

                Rule::in([
                    'AUTO',
                    'ASC',
                    'DESC',
                ]),
            ],
        ];
    }
}
