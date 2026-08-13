<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePhaseExitRequest extends FormRequest
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
        $selectorType =
            strtoupper(
                (string)
                $this->input(
                    'selector_type'
                )
            );

        $exitTiming =
            strtoupper(
                (string)
                $this->input(
                    'exit_timing',
                    'PHASE_END'
                )
            );

        /*
         * Esta salida necesariamente ocurre
         * al producirse la eliminación.
         */
        if (
            $selectorType
            ===
            'ELIMINATED_IN_ROUND'
        ) {
            $exitTiming =
                'ON_ELIMINATION';
        }

        $this->merge([
            'name' =>
            trim(
                (string)
                $this->input(
                    'name'
                )
            ),

            'selector_type' =>
            $selectorType,

            'exit_timing' =>
            $exitTiming,

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

            /*
            |--------------------------------------------------------------------------
            | Selector
            |--------------------------------------------------------------------------
            */

            'selector_type' => [
                'required',

                Rule::in([
                    /*
                     * Single Elimination
                     */
                    'SURVIVORS',
                    'ELIMINATED',
                    'ELIMINATED_IN_ROUND',

                    /*
                     * Genéricos
                     */
                    'MATCH_WINNERS',
                    'MATCH_LOSERS',
                    'TOP_N',
                    'BOTTOM_N',
                    'RANK_POSITION',
                    'RANK_RANGE',
                    'ALL',
                    'REMAINING',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Timing
            |--------------------------------------------------------------------------
            */

            'exit_timing' => [
                'required',

                Rule::in([
                    'PHASE_END',
                    'ON_ELIMINATION',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Selectores genéricos
            |--------------------------------------------------------------------------
            */

            'selector_from' => [
                Rule::requiredIf(
                    fn() =>
                    in_array(
                        $this->input(
                            'selector_type'
                        ),
                        [
                            'TOP_N',
                            'BOTTOM_N',
                            'RANK_POSITION',
                            'RANK_RANGE',
                        ],
                        true
                    )
                ),

                'nullable',
                'integer',
                'min:1',
                'max:512',
            ],

            'selector_to' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input(
                        'selector_type'
                    )
                        ===
                        'RANK_RANGE'
                ),

                'nullable',
                'integer',
                'min:1',
                'max:512',

                'gte:selector_from',
            ],

            /*
            |--------------------------------------------------------------------------
            | Single Elimination — ronda
            |--------------------------------------------------------------------------
            */

            'selector_round_size' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input(
                        'selector_type'
                    )
                        ===
                        'ELIMINATED_IN_ROUND'
                ),

                'nullable',

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
            ],

            /*
            |--------------------------------------------------------------------------
            | Prioridad
            |--------------------------------------------------------------------------
            */

            'priority' => [
                'required',
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
        ];
    }

    public function messages(): array
    {
        return [
            'selector_round_size.required' =>
            'Selecciona la ronda cuya eliminación utilizará esta puerta.',

            'selector_round_size.in' =>
            'La ronda seleccionada no es válida.',
        ];
    }
}
