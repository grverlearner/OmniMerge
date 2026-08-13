<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseSingleEliminationRoundRule;
use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSingleEliminationRoundRuleRequest extends FormRequest
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
            'SINGLE_ELIMINATION'

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

    public function rules(): array
    {
        $phaseTemplate =
            $this->route(
                'phaseTemplate'
            );

        $roundRule =
            $this->route(
                'roundRule'
            );

        return [
            'participants_in_round' => [
                'required',

                Rule::in([
                    2,
                    4,
                    8,
                    16,
                    32,
                    64,
                    128,
                    256,
                    512,
                ]),

                Rule::unique(
                    'phase_single_elimination_round_rules',
                    'participants_in_round'
                )
                    ->where(
                        fn($query) =>
                        $query->where(
                            'phase_template_id',
                            $phaseTemplate?->id
                        )
                    )
                    ->ignore(
                        $roundRule
                            instanceof
                            PhaseSingleEliminationRoundRule
                            ? $roundRule->id
                            : null
                    ),
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
        ];
    }

    public function messages(): array
    {
        return [
            'participants_in_round.unique' =>
            'Ya existe una configuración especial para esa ronda.',

            'participants_in_round.in' =>
            'Selecciona un tamaño de ronda válido.',

            'best_of.in' =>
            'El Best of debe ser 1, 3, 5, 7 o 9.',
        ];
    }
}
