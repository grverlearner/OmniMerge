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
                ?? false
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'completion_mode' =>
            strtoupper(
                (string)
                $this->input(
                    'completion_mode',
                    'WINNER'
                )
            ),

            'seeding_mode' =>
            strtoupper(
                (string)
                $this->input(
                    'seeding_mode',
                    'INPUT_ORDER'
                )
            ),

            'pairing_mode' =>
            strtoupper(
                (string)
                $this->input(
                    'pairing_mode',
                    'STANDARD_SEEDED'
                )
            ),

            'bye_assignment' =>
            strtoupper(
                (string)
                $this->input(
                    'bye_assignment',
                    'TOP_SEEDS'
                )
            ),

            'reseed_each_round' =>
            $this->boolean(
                'reseed_each_round'
            ),
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

    public function rules(): array
    {
        return [
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

                $completionMode =
                    $this->input(
                        'completion_mode'
                    );

                $target =
                    $completionMode === 'WINNER'
                    ? 1
                    : (int)
                    $this->input(
                        'target_survivors'
                    );

                /*
                |--------------------------------------------------------------------------
                | Target debe ser una potencia de 2
                |--------------------------------------------------------------------------
                */

                if (
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
                            'El objetivo de supervivientes debe ser una potencia de 2: 1, 2, 4, 8, 16, 32...'
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Debe existir al menos una eliminación
                |--------------------------------------------------------------------------
                */

                $effectiveParticipants =
                    $phaseTemplate->exact_participants
                    ??
                    $phaseTemplate->min_participants;

                if (
                    $target
                    >=
                    $effectiveParticipants
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'target_survivors',
                            'El objetivo debe ser menor que la cantidad efectiva de entrada de la Fase: '
                                .
                                $effectiveParticipants
                                .
                                '.'
                        );
                }
            }
        );
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
