<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseSingleEliminationRoundRule;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationRoundAvailabilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

            'encounter_profile' =>
            $this->filled(
                'encounter_profile'
            )
                ? strtoupper(
                    (string)
                    $this->input(
                        'encounter_profile'
                    )
                )
                : null,

            'entrants_per_match' =>
            $this->filled(
                'entrants_per_match'
            )
                ? $this->input(
                    'entrants_per_match'
                )
                : null,

            'qualifiers_per_match' =>
            $this->filled(
                'qualifiers_per_match'
            )
                ? $this->input(
                    'qualifiers_per_match'
                )
                : null,
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
                ??
                false
            );
    }

    public function rules(): array
    {
        $routePhaseTemplate =
            $this->route(
                'phaseTemplate'
            );

        $phaseTemplate =
            $routePhaseTemplate
            instanceof PhaseTemplate
            ? $routePhaseTemplate
            : null;

        $roundRule =
            $this->route(
                'roundRule'
            );

        $settings =
            $phaseTemplate
            ? $phaseTemplate->singleEliminationSetting
            : null;

        $roundRules =
            $phaseTemplate
            ? $phaseTemplate
            ->singleEliminationRoundRules()
            ->get()
            : collect();

        $possibleRoundSizes =
            $phaseTemplate
            &&
            $settings
            ? app(
                SingleEliminationRoundAvailabilityService::class
            )
            ->possibleRoundSizes(
                $phaseTemplate,
                $settings,
                $roundRules
            )
            : [];

        return [
            'participants_in_round' => [
                'required',
                'integer',

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

            'entrants_per_match' => [
                'nullable',
                'integer',
                'min:2',
                'max:64',
                'required_with:qualifiers_per_match,encounter_profile',
            ],

            'qualifiers_per_match' => [
                'nullable',
                'integer',
                'min:1',
                'max:63',
                'required_with:entrants_per_match,encounter_profile',
            ],

            'encounter_profile' => [
                'nullable',

                Rule::in([
                    'DUEL',
                    'MULTI_COMPETITOR',
                    'CUSTOM',
                ]),

                'required_with:entrants_per_match,qualifiers_per_match',
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

    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (
                Validator $validator
            ) {
                $phaseTemplate =
                    $this->route(
                        'phaseTemplate'
                    );

                $settings =
                    $phaseTemplate
                    ?->singleEliminationSetting;

                $hasAdvancedOverride =
                    $this->filled(
                        'entrants_per_match'
                    )
                    ||
                    $this->filled(
                        'qualifiers_per_match'
                    )
                    ||
                    $this->filled(
                        'encounter_profile'
                    );

                if (! $hasAdvancedOverride) {
                    return;
                }

                if (
                    $settings
                    ?->configuration_mode
                    !==
                    'ADVANCED'
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'entrants_per_match',
                            'Los overrides K → Q solo están disponibles en modo avanzado.'
                        );

                    return;
                }

                $entrants =
                    (int)
                    $this->input(
                        'entrants_per_match'
                    );

                $qualifiers =
                    (int)
                    $this->input(
                        'qualifiers_per_match'
                    );

                $profile =
                    $this->input(
                        'encounter_profile'
                    );

                if (
                    $qualifiers
                    >=
                    $entrants
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'qualifiers_per_match',
                            'Los clasificados deben ser menos que los participantes del encuentro.'
                        );
                }

                if (
                    $profile === 'DUEL'
                    &&
                    (
                        $entrants !== 2
                        ||
                        $qualifiers !== 1
                    )
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'encounter_profile',
                            'El perfil Duelo exige una relación 2 → 1.'
                        );
                }

                if (
                    $profile
                    ===
                    'MULTI_COMPETITOR'
                    &&
                    $entrants < 3
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'entrants_per_match',
                            'El perfil Multicompetidor exige al menos 3 participantes.'
                        );
                }
            }
        );
    }

    public function messages(): array
    {
        return [
            'participants_in_round.unique' =>
            'Ya existe una configuración especial para esa ronda.',

            'entrants_per_match.required_with' =>
            'Indica los participantes por encuentro del override.',

            'entrants_per_match.min' =>
            'El override necesita al menos 2 participantes.',

            'qualifiers_per_match.required_with' =>
            'Indica los clasificados por encuentro del override.',

            'qualifiers_per_match.min' =>
            'El override debe clasificar al menos 1 participante.',

            'encounter_profile.required_with' =>
            'Selecciona el perfil competitivo del override.',

            'encounter_profile.in' =>
            'Selecciona un perfil de encuentro válido.',

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
