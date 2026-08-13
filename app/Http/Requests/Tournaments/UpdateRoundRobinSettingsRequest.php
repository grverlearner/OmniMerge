<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoundRobinSettingsRequest extends FormRequest
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
            'ROUND_ROBIN'

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
            'initial_order_mode' =>
            strtoupper(
                (string)
                $this->input(
                    'initial_order_mode',
                    'INPUT_ORDER'
                )
            ),

            'schedule_mode' =>
            strtoupper(
                (string)
                $this->input(
                    'schedule_mode',
                    'BALANCED'
                )
            ),

            'cutoff_tie_policy' =>
            strtoupper(
                (string)
                $this->input(
                    'cutoff_tie_policy',
                    'USE_TIEBREAKERS'
                )
            ),

            'allow_draws' =>
            $this->boolean(
                'allow_draws'
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'cycles' => [
                'required',
                'integer',
                'min:1',
                'max:10',
            ],

            'initial_order_mode' => [
                'required',

                Rule::in([
                    'INPUT_ORDER',
                    'RANDOM',
                    'RANKING',
                    'MANUAL',
                ]),
            ],

            'schedule_mode' => [
                'required',

                Rule::in([
                    'BALANCED',
                ]),
            ],

            'allow_draws' => [
                'boolean',
            ],

            'win_points' => [
                'required',
                'numeric',
                'between:-9999.99,9999.99',
            ],

            'draw_points' => [
                'required',
                'numeric',
                'between:-9999.99,9999.99',
            ],

            'loss_points' => [
                'required',
                'numeric',
                'between:-9999.99,9999.99',
            ],

            'default_best_of' => [
                'required',

                Rule::in([
                    1,
                    3,
                    5,
                    7,
                    9,
                ]),
            ],

            'cutoff_tie_policy' => [
                'required',

                Rule::in([
                    'USE_TIEBREAKERS',
                    'MANUAL_RESOLUTION',
                    'RANDOM_RESOLUTION',
                    'INCLUDE_ALL_TIED',
                    'REQUIRE_PLAYOFF',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cycles.min' =>
            'Debe existir al menos un ciclo.',

            'cycles.max' =>
            'Por ahora OmniMerge admite como máximo 10 ciclos.',

            'default_best_of.in' =>
            'El Best of debe ser 1, 3, 5, 7 o 9.',
        ];
    }
}
