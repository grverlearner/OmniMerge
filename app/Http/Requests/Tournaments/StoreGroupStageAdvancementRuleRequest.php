<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\GroupStage\GroupStageExitForecastService;
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

                $this->validateScale(
                    $validator,
                    $phaseTemplate
                );
            }
        );
    }

    /*
     * Una regla «de cada grupo» multiplica: la cantidad que se escribe no
     * es la que clasifica.
     *
     * Sin este control se puede pedir «los 8 primeros de cada grupo» en
     * una fase de 4 grupos de 4 y guardarlo sin una sola queja. La regla
     * es válida, se ejecuta, y clasifica a los 16 —todos— porque la
     * posición de todo el mundo es menor o igual que 8. El error no se ve
     * al configurar ni al empezar: se ve cuando la fase entera ya se jugó
     * y la siguiente puerta rechaza el doble de participantes de los que
     * admite, con el torneo bloqueado y sin vuelta atrás.
     *
     * El aviso llega aquí, en el campo donde se escribió el número, y trae
     * hecha la cuenta que hay que hacer.
     */
    private function validateScale(
        Validator $validator,
        PhaseTemplate $phaseTemplate
    ): void {

        $ruleType =
            (string)
            $this->input('rule_type');

        $usesPerGroupTake =
            in_array(
                $ruleType,
                [
                    'EACH_GROUP_TOP_N',
                    'EACH_GROUP_BOTTOM_N',
                ],
                true
            );

        $usesPerGroupPosition =
            in_array(
                $ruleType,
                [
                    'EACH_GROUP_POSITION',
                    'EACH_GROUP_RANGE',
                ],
                true
            );

        if (
            ! $usesPerGroupTake
            &&
            ! $usesPerGroupPosition
        ) {
            return;
        }

        $forecaster =
            app(
                GroupStageExitForecastService::class
            );

        $sizes =
            $forecaster->groupSizes(
                $phaseTemplate,
                $forecaster->referenceParticipants(
                    $phaseTemplate
                )
            );

        /*
         * Sin un reparto en grupos válido no hay nada que comparar, y el
         * motivo ya se está avisando en la pantalla de estructura. Un
         * segundo aviso derivado del primero solo sería ruido.
         */
        if ($sizes === []) {
            return;
        }

        $smallest =
            min($sizes);

        $groupCount =
            count($sizes);

        if ($usesPerGroupPosition) {

            $from =
                (int)
                $this->input('position_from');

            if ($from > $smallest) {
                $validator
                    ->errors()
                    ->add(
                        'position_from',
                        'El grupo más pequeño tiene '
                        . $smallest
                        . ' participantes, así que el puesto '
                        . $from
                        . ' no existe: esta regla no seleccionaría a nadie.'
                    );
            }

            return;
        }

        $take =
            (int)
            $this->input('take');

        if ($take < $smallest) {
            return;
        }

        $perGroup =
            intdiv(
                $take,
                $groupCount
            );

        $validator
            ->errors()
            ->add(
                'take',
                'Esta cantidad es POR GRUPO, no en total. Con '
                . $groupCount
                . ' grupos de '
                . $smallest
                . ', pedir '
                . $take
                . ' de cada uno clasifica a los '
                . ($smallest * $groupCount)
                . ' participantes de la fase: no eliminaría a nadie. '
                . (
                    $perGroup >= 1 && $perGroup < $smallest
                    ? 'Para que pasen ' . $take . ' en total, escribe ' . $perGroup . '.'
                    : 'Si la cantidad que quieres es el total, usa «Mejores N restantes».'
                )
            );
    }
}
