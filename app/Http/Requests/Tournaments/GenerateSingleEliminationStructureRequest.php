<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GenerateSingleEliminationStructureRequest extends FormRequest
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
        $this->merge([
            'replace_manual' =>
            $this->boolean(
                'replace_manual'
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'participants' => [
                'nullable',
                'integer',
                'min:2',
                'max:512',
            ],

            'replace_manual' => [
                'boolean',
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

                $participants =
                    (int) (
                        $this->input(
                            'participants'
                        )
                        ??
                        $phaseTemplate
                        ->exact_participants
                        ??
                        $phaseTemplate
                        ->min_participants
                    );

                if ($participants < 2) {
                    $validator
                        ->errors()
                        ->add(
                            'participants',
                            'La estructura necesita al menos dos participantes.'
                        );

                    return;
                }

                if (
                    $phaseTemplate
                    ->exact_participants
                    !==
                    null
                    &&
                    $participants
                    !==
                    $phaseTemplate
                    ->exact_participants
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'participants',
                            'Esta Fase exige exactamente '
                                .
                                $phaseTemplate
                                ->exact_participants
                                .
                                ' participantes.'
                        );
                }

                if (
                    $phaseTemplate
                    ->min_participants
                    !==
                    null
                    &&
                    $participants
                    <
                    $phaseTemplate
                    ->min_participants
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'participants',
                            'La cantidad es menor que el mínimo de la Fase.'
                        );
                }

                if (
                    $phaseTemplate
                    ->max_participants
                    !==
                    null
                    &&
                    $participants
                    >
                    $phaseTemplate
                    ->max_participants
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'participants',
                            'La cantidad supera el máximo de la Fase.'
                        );
                }

                if (
                    $phaseTemplate
                    ->participant_multiple
                    !==
                    null
                    &&
                    $phaseTemplate
                    ->participant_multiple
                    >
                    1
                    &&
                    $participants
                    %
                    $phaseTemplate
                    ->participant_multiple
                    !==
                    0
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'participants',
                            'La cantidad debe ser múltiplo de '
                                .
                                $phaseTemplate
                                ->participant_multiple
                                .
                                '.'
                        );
                }

                $settings =
                    $phaseTemplate
                    ->singleEliminationSetting;

                $target =
                    (int) (
                        $settings
                        ?->target_survivors
                        ??
                        1
                    );

                if ($participants <= $target) {
                    $validator
                        ->errors()
                        ->add(
                            'participants',
                            'Los participantes deben ser más que el objetivo de supervivientes.'
                        );
                }
            }
        );
    }
}
