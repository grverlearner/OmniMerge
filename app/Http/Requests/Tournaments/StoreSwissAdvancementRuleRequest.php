<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSwissAdvancementRuleRequest extends FormRequest
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
            'rule_type' =>
            strtoupper(
                (string)
                $this->input(
                    'rule_type'
                )
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
                'rule_type'
            );

        $winThreshold =
            $type
            ===
            'WIN_THRESHOLD';

        $lossThreshold =
            $type
            ===
            'LOSS_THRESHOLD';

        $exactRecord =
            $type
            ===
            'EXACT_RECORD';

        $usesTake =
            in_array(
                $type,
                [
                    'FINAL_TOP_N',
                    'FINAL_BOTTOM_N',
                ],
                true
            );

        $usesRankFrom =
            in_array(
                $type,
                [
                    'FINAL_RANK_POSITION',
                    'FINAL_RANK_RANGE',
                ],
                true
            );

        $usesRankTo =
            $type
            ===
            'FINAL_RANK_RANGE';

        return [
            'phase_exit_id' => [
                'required',
                'integer',
                'exists:phase_exits,id',
            ],

            'rule_type' => [
                'required',

                Rule::in([
                    'WIN_THRESHOLD',
                    'LOSS_THRESHOLD',
                    'EXACT_RECORD',

                    'FINAL_TOP_N',
                    'FINAL_BOTTOM_N',

                    'FINAL_RANK_POSITION',
                    'FINAL_RANK_RANGE',

                    'REMAINING',
                ]),
            ],

            'threshold_wins' => [
                Rule::excludeIf(
                    ! $winThreshold
                ),

                Rule::requiredIf(
                    $winThreshold
                ),

                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'threshold_losses' => [
                Rule::excludeIf(
                    ! $lossThreshold
                ),

                Rule::requiredIf(
                    $lossThreshold
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

            'take' => [
                Rule::excludeIf(
                    ! $usesTake
                ),

                Rule::requiredIf(
                    $usesTake
                ),

                'nullable',
                'integer',
                'min:1',
                'max:512',
            ],

            'rank_from' => [
                Rule::excludeIf(
                    ! $usesRankFrom
                ),

                Rule::requiredIf(
                    $usesRankFrom
                ),

                'nullable',
                'integer',
                'min:1',
                'max:512',
            ],

            'rank_to' => [
                Rule::excludeIf(
                    ! $usesRankTo
                ),

                Rule::requiredIf(
                    $usesRankTo
                ),

                'nullable',
                'integer',
                'min:1',
                'max:512',
                'gte:rank_from',
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

                $exitId =
                    $this->integer(
                        'phase_exit_id'
                    );

                if (
                    $exitId
                    &&
                    ! $phaseTemplate
                        ->exits()
                        ->whereKey(
                            $exitId
                        )
                        ->exists()
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'phase_exit_id',
                            'La puerta seleccionada no pertenece a esta Fase.'
                        );
                }
            }
        );
    }
}
