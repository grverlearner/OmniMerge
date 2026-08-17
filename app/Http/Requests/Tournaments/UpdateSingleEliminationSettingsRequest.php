<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSingleEliminationSettingsRequest extends FormRequest
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
                ??
                false
            );
    }

    protected function prepareForValidation(): void
    {
        $uppercaseFields = [
            'configuration_mode' =>
            'BASIC',

            'input_mode' =>
            'POOL',

            'routing_mode' =>
            'AUTOMATIC',

            'encounter_profile' =>
            'DUEL',

            'remainder_policy' =>
            'REJECT',

            'completion_mode' =>
            'WINNER',

            'seeding_mode' =>
            'INPUT_ORDER',

            'pairing_mode' =>
            'STANDARD_SEEDED',

            'bye_assignment' =>
            'TOP_SEEDS',

            'series_format' =>
            'BEST_OF',
        ];

        $normalized = [];

        foreach (
            $uppercaseFields
            as
            $field => $default
        ) {
            $normalized[$field] =
                strtoupper(
                    (string)
                    $this->input(
                        $field,
                        $default
                    )
                );
        }

        $normalized['reseed_each_round'] =
            $this->boolean(
                'reseed_each_round'
            );

        $this->merge(
            $normalized
        );
    }

    public function rules(): array
    {
        $advanced =
            fn() =>
            $this->input(
                'configuration_mode'
            )
                ===
                'ADVANCED';

        return [
            'configuration_mode' => [
                'required',

                Rule::in([
                    'BASIC',
                    'ADVANCED',
                ]),
            ],

            'input_mode' => [
                'required',

                Rule::in([
                    'POOL',
                    'PER_SEED',
                    'GROUPED',
                    'HYBRID',
                    'CUSTOM',
                ]),
            ],

            'routing_mode' => [
                'required',

                Rule::in([
                    'AUTOMATIC',
                    'POSITIONAL',
                    'MANUAL',
                    'CUSTOM',
                ]),
            ],

            'entrants_per_match' => [
                Rule::requiredIf(
                    $advanced
                ),

                'nullable',
                'integer',
                'min:2',
                'max:64',
            ],

            'qualifiers_per_match' => [
                Rule::requiredIf(
                    $advanced
                ),

                'nullable',
                'integer',
                'min:1',
                'max:63',
            ],

            'encounter_profile' => [
                'required',

                Rule::in([
                    'DUEL',
                    'MULTI_COMPETITOR',
                    'CUSTOM',
                ]),
            ],

            'remainder_policy' => [
                'required',

                Rule::in([
                    'BYE',
                    'PRELIMINARY',
                    'BALANCED',
                    'INCOMPLETE_MATCH',
                    'MANUAL',
                    'REJECT',
                ]),
            ],

            'completion_mode' => [
                'required',

                Rule::in([
                    'WINNER',
                    'SURVIVORS',
                ]),
            ],

            'target_survivors' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input(
                        'completion_mode'
                    )
                        ===
                        'SURVIVORS'
                ),

                'nullable',
                'integer',
                'min:1',
                'max:256',
            ],

            'seeding_mode' => [
                'required',

                Rule::in([
                    'INPUT_ORDER',
                    'RANDOM',
                    'RANKING',
                    'MANUAL',
                ]),
            ],

            'pairing_mode' => [
                'required',

                Rule::in([
                    'STANDARD_SEEDED',
                    'SEQUENTIAL',
                    'RANDOM',
                ]),
            ],

            'bye_assignment' => [
                'required',

                Rule::in([
                    'TOP_SEEDS',
                    'RANDOM',
                    'MANUAL',
                ]),
            ],

            'reseed_each_round' => [
                'boolean',
            ],

            'series_format' => [
                'required',

                Rule::in([
                    'BEST_OF',
                    'FIXED_GAMES',
                ]),
            ],

            'default_best_of' => [
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

                if (
                    ! $phaseTemplate
                        instanceof PhaseTemplate
                ) {
                    return;
                }

                $advanced =
                    $this->input(
                        'configuration_mode'
                    )
                    ===
                    'ADVANCED';

                $target =
                    $this->input(
                        'completion_mode'
                    )
                    ===
                    'WINNER'
                    ? 1
                    : (int)
                    $this->input(
                        'target_survivors'
                    );

                $effectiveParticipants =
                    $phaseTemplate->exact_participants
                    ??
                    $phaseTemplate->min_participants;

                /*
                |--------------------------------------------------------------------------
                | En modo básico el objetivo conserva la restricción tradicional
                |--------------------------------------------------------------------------
                */

                if (
                    ! $advanced
                    &&
                    $target > 0
                    &&
                    ! $this->isPowerOfTwo(
                        $target
                    )
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'target_survivors',
                            'En modo básico el objetivo debe ser una potencia de 2: 1, 2, 4, 8, 16, 32...'
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Siempre debe existir al menos una eliminación
                |--------------------------------------------------------------------------
                */

                if (
                    $target
                    >=
                    $effectiveParticipants
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'target_survivors',
                            'El objetivo debe ser menor que la entrada efectiva de la Fase: '
                                .
                                $effectiveParticipants
                                .
                                '.'
                        );
                }

                if (! $advanced) {
                    if ($this->input('input_mode') !== 'POOL') {
                        $validator->errors()->add(
                            'input_mode',
                            'En modo básico la entrada debe usar Bolsa común.'
                        );
                    }

                    if ($this->input('routing_mode') !== 'AUTOMATIC') {
                        $validator->errors()->add(
                            'routing_mode',
                            'En modo básico el enrutamiento debe ser Automático.'
                        );
                    }

                    if ($this->input('encounter_profile') !== 'DUEL') {
                        $validator->errors()->add(
                            'encounter_profile',
                            'En modo básico el perfil de encuentro debe ser Duelo 2 → 1.'
                        );
                    }

                    if (! in_array(
                        $this->input('remainder_policy'),
                        ['BYE', 'REJECT'],
                        true
                    )) {
                        $validator->errors()->add(
                            'remainder_policy',
                            'En modo básico los sobrantes solo admiten BYE o Rechazar.'
                        );
                    }

                    if (
                        $this->input('remainder_policy') === 'BYE'
                        && ! $phaseTemplate->allow_byes
                    ) {
                        $validator->errors()->add(
                            'remainder_policy',
                            'No puedes usar BYE porque el contrato de la Fase no los permite.'
                        );
                    }

                    if (
                        $this->input('remainder_policy') === 'REJECT'
                        && $phaseTemplate->exact_participants !== null
                        && ! $this->isPowerOfTwo(
                            (int) $phaseTemplate->exact_participants
                        )
                    ) {
                        $validator->errors()->add(
                            'remainder_policy',
                            'Rechazar sobrantes necesita una cantidad exacta potencia de 2 en modo básico.'
                        );
                    }

                    if (
                        $this->boolean('reseed_each_round')
                        && $this->input('pairing_mode') !== 'STANDARD_SEEDED'
                    ) {
                        $validator->errors()->add(
                            'reseed_each_round',
                            'El reseeding del modo básico solo es compatible con Pairing Seeded estándar.'
                        );
                    }

                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | Validaciones avanzadas K → Q
                |--------------------------------------------------------------------------
                */

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
                            'El perfil Multicompetidor exige al menos 3 participantes por encuentro.'
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Política BYE
                |--------------------------------------------------------------------------
                */

                if (
                    $this->input(
                        'remainder_policy'
                    )
                    ===
                    'BYE'
                    &&
                    ! $phaseTemplate->allow_byes
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'remainder_policy',
                            'No puedes usar BYE porque el contrato de la Fase no los permite.'
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Entrada por seed
                |--------------------------------------------------------------------------
                */

                if (
                    $this->input(
                        'input_mode'
                    )
                    ===
                    'PER_SEED'
                    &&
                    $phaseTemplate->exact_participants
                    ===
                    null
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'input_mode',
                            'La entrada Por seed necesita una cantidad exacta de participantes.'
                        );
                }
            }
        );
    }

    public function messages(): array
    {
        return [
            'configuration_mode.in' =>
            'Selecciona modo Básico o Avanzado.',

            'input_mode.in' =>
            'Selecciona una forma de entrada válida.',

            'routing_mode.in' =>
            'Selecciona un modo de enrutamiento válido.',

            'entrants_per_match.required' =>
            'Indica cuántos participantes entran en cada encuentro.',

            'entrants_per_match.min' =>
            'Cada encuentro debe recibir al menos 2 participantes.',

            'entrants_per_match.max' =>
            'Un encuentro no puede recibir más de 64 participantes.',

            'qualifiers_per_match.required' =>
            'Indica cuántos participantes clasifican por encuentro.',

            'qualifiers_per_match.min' =>
            'Cada encuentro debe clasificar al menos 1 participante.',

            'qualifiers_per_match.max' =>
            'No puedes clasificar más de 63 participantes por encuentro.',

            'encounter_profile.in' =>
            'Selecciona un perfil de encuentro válido.',

            'remainder_policy.in' =>
            'Selecciona una política de participantes sobrantes válida.',

            'default_best_of.in' =>
            'El Best of debe ser 1, 3, 5, 7 o 9.',

            'fixed_games.min' =>
            'Debe disputarse al menos un enfrentamiento.',

            'fixed_games.max' =>
            'La cantidad fija no puede superar 99 enfrentamientos.',
        ];
    }

    private function isPowerOfTwo(
        int $value
    ): bool {
        return
            $value > 0
            &&
            (
                $value
                &
                ($value - 1)
            )
            ===
            0;
    }
}
