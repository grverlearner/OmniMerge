<?php

namespace App\Http\Requests\Tournaments;

use App\Http\Requests\Tournaments\Concerns\ValidatesGroupStageRuleScale;
use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/*
|--------------------------------------------------------------------------
| StoreGroupStageExitRequest
|--------------------------------------------------------------------------
|
| Una salida de fase de grupos y el criterio que la cruza, en un solo paso.
|
| Antes eran dos formularios en dos secciones distintas: se creaba la puerta
| y después, más abajo, una «regla de clasificación» que había que acordarse
| de apuntar a esa puerta. Una puerta sin regla no la cruza nadie y una
| regla sin puerta no lleva a ningún sitio, así que separarlas solo servía
| para poder dejar la mitad hecha.
|
| Aquí se pide lo único que hay que decidir: cómo se llama la salida y quién
| sale por ella. Lo demás —el selector de la puerta y el momento en que se
| cruza— lo fija el motor, porque en una fase de grupos solo hay una
| respuesta posible: las reglas mandan, y no hay clasificación firme hasta
| que la fase termina.
|
*/
class StoreGroupStageExitRequest extends FormRequest
{
    use ValidatesGroupStageRuleScale;

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
                    'rule_type',
                    'EACH_GROUP_TOP_N'
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
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'priority' => [
                'nullable',
                'integer',
                'min:1',
                'max:999',
            ],

            'status' => [
                'required',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                ]),
            ],

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

                $this->validateRuleScale(
                    $validator,
                    $phaseTemplate,
                    (string) $this->input('rule_type'),
                    $this->input('take') === null ? null : (int) $this->input('take'),
                    $this->input('position_from') === null ? null : (int) $this->input('position_from')
                );
            }
        );
    }
}
