<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseSingleEliminationRoundRule;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationRoundAvailabilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSingleEliminationRoundRuleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'series_format' =>
            strtoupper(
                (string)
                $this->input(
                    'series_format',
                    'BEST_OF'
                )
            ),
        ]);
    }
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

        $settings =
            $phaseTemplate
            ?->singleEliminationSetting;

        $possibleRoundSizes =
            $phaseTemplate
            &&
            $settings
            ? app(
                SingleEliminationRoundAvailabilityService::class
            )
            ->possibleRoundSizes(
                $phaseTemplate,
                $settings
            )
            : [];

        return [
            'participants_in_round' => [
                'required',

                function (
                    string $attribute,
                    mixed $value,
                    \Closure $fail
                ) use (
                    $phaseTemplate,
                    $possibleRoundSizes
                ) {
                    $roundSize =
                        (int)
                        $value;

                    if (
                        in_array(
                            $roundSize,
                            $possibleRoundSizes,
                            true
                        )
                    ) {
                        return;
                    }

                    $contract =
                        $phaseTemplate
                        ?->exact_participants
                        !==
                        null
                        ? 'exactamente '
                        .
                        $phaseTemplate->exact_participants
                        .
                        ' participantes'
                        : 'el contrato actual de participantes';

                    $fail(
                        'La ronda de '
                            .
                            $roundSize
                            .
                            ' participantes no puede existir con '
                            .
                            $contract
                            .
                            ' y el objetivo configurado.'
                    );
                },

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

            'series_format' => [
                'required',

                Rule::in([
                    'BEST_OF',
                    'FIXED_GAMES',
                ]),
            ],

            'best_of' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input(
                        'series_format'
                    )
                        ===
                        'BEST_OF'
                ),

                'nullable',

                Rule::in([
                    1,
                    3,
                    5,
                    7,
                    9,
                ]),
            ],

            'fixed_games' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input(
                        'series_format'
                    )
                        ===
                        'FIXED_GAMES'
                ),

                'nullable',
                'integer',
                'min:1',
                'max:99',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'participants_in_round.unique' =>
            'Ya existe una configuración especial para esa ronda.',

            'best_of.in' =>
            'El Best of debe ser 1, 3, 5, 7 o 9.',

            'series_format.in' =>
            'Selecciona Best of o Cantidad fija.',

            'fixed_games.required' =>
            'Indica cuántos enfrentamientos deben disputarse.',

            'fixed_games.min' =>
            'Debe disputarse al menos un enfrentamiento.',

            'fixed_games.max' =>
            'La cantidad fija no puede superar 99 enfrentamientos.',
        ];
    }
}
