<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGroupStageAdvancementRuleRequest extends FormRequest
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
            'GROUP_STAGE'
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
        $ruleType =
            $this->input(
                'rule_type'
            );

        $usesGroup =
            in_array(
                $ruleType,
                [
                    'SPECIFIC_GROUP_POSITION',
                    'SPECIFIC_GROUP_RANGE',
                ],
                true
            );

        $usesPositionFrom =
            in_array(
                $ruleType,
                [
                    'EACH_GROUP_POSITION',
                    'EACH_GROUP_RANGE',

                    'CROSS_GROUP_POSITION_TOP_N',
                    'CROSS_GROUP_POSITION_BOTTOM_N',

                    'SPECIFIC_GROUP_POSITION',
                    'SPECIFIC_GROUP_RANGE',
                ],
                true
            );

        $usesPositionTo =
            in_array(
                $ruleType,
                [
                    'EACH_GROUP_RANGE',
                    'SPECIFIC_GROUP_RANGE',
                ],
                true
            );

        $usesTake =
            in_array(
                $ruleType,
                [
                    'EACH_GROUP_TOP_N',
                    'EACH_GROUP_BOTTOM_N',

                    'CROSS_GROUP_POSITION_TOP_N',
                    'CROSS_GROUP_POSITION_BOTTOM_N',

                    'BEST_REMAINING',
                    'WORST_REMAINING',
                ],
                true
            );

        return [
            /*
        |--------------------------------------------------------------------------
        | Puerta
        |--------------------------------------------------------------------------
        */

            'phase_exit_id' => [
                'required',
                'integer',
                'exists:phase_exits,id',
            ],


            /*
        |--------------------------------------------------------------------------
        | Grupo específico
        |--------------------------------------------------------------------------
        */

            'phase_group_stage_group_id' => [
                Rule::excludeIf(
                    ! $usesGroup
                ),

                Rule::requiredIf(
                    $usesGroup
                ),

                'nullable',
                'integer',
                'exists:phase_group_stage_groups,id',
            ],


            /*
        |--------------------------------------------------------------------------
        | Rule Type
        |--------------------------------------------------------------------------
        */

            'rule_type' => [
                'required',

                Rule::in([
                    'EACH_GROUP_TOP_N',
                    'EACH_GROUP_BOTTOM_N',

                    'EACH_GROUP_POSITION',
                    'EACH_GROUP_RANGE',

                    'CROSS_GROUP_POSITION_TOP_N',
                    'CROSS_GROUP_POSITION_BOTTOM_N',

                    'BEST_REMAINING',
                    'WORST_REMAINING',

                    'SPECIFIC_GROUP_POSITION',
                    'SPECIFIC_GROUP_RANGE',

                    'REMAINING',
                ]),
            ],


            /*
        |--------------------------------------------------------------------------
        | Position From
        |--------------------------------------------------------------------------
        */

            'position_from' => [
                Rule::excludeIf(
                    ! $usesPositionFrom
                ),

                Rule::requiredIf(
                    $usesPositionFrom
                ),

                'nullable',
                'integer',
                'min:1',
                'max:512',
            ],


            /*
        |--------------------------------------------------------------------------
        | Position To
        |--------------------------------------------------------------------------
        */

            'position_to' => [
                Rule::excludeIf(
                    ! $usesPositionTo
                ),

                Rule::requiredIf(
                    $usesPositionTo
                ),

                'nullable',
                'integer',
                'min:1',
                'max:512',

                'gte:position_from',
            ],


            /*
        |--------------------------------------------------------------------------
        | Take
        |--------------------------------------------------------------------------
        */

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


            /*
        |--------------------------------------------------------------------------
        | Estado
        |--------------------------------------------------------------------------
        */

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

                $groupId =
                    $this->integer(
                        'phase_group_stage_group_id'
                    );

                if (
                    $groupId
                    &&
                    ! $phaseTemplate
                        ->groupStageGroups()
                        ->whereKey(
                            $groupId
                        )
                        ->exists()
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'phase_group_stage_group_id',
                            'El grupo seleccionado no pertenece a esta Fase.'
                        );
                }
            }
        );
    }
}
