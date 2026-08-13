<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseRoundRobinTiebreaker;
use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoundRobinTiebreakerRequest extends FormRequest
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
            'ROUND_ROBIN'

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

        return [
            'criterion' => [
                'required',

                Rule::in([
                    'WINS',
                    'FEWEST_LOSSES',
                    'HEAD_TO_HEAD',
                    'SCORE_DIFFERENCE',
                    'SCORE_FOR',
                    'GAME_DIFFERENCE',
                    'GAME_WINS',
                    'SEED',
                ]),

                Rule::unique(
                    'phase_round_robin_tiebreakers',
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
                            PhaseRoundRobinTiebreaker
                            ? $tiebreaker->id
                            : null
                    ),
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

    public function messages(): array
    {
        return [
            'criterion.unique' =>
            'Ese criterio ya forma parte de la cadena de desempate.',
        ];
    }
}
