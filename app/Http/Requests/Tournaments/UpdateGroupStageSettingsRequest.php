<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupStageSettingsRequest extends FormRequest
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
        /*
    |--------------------------------------------------------------------------
    | Group Count Mode
    |--------------------------------------------------------------------------
    */

        $groupCountMode =
            strtoupper(
                (string)
                $this->input(
                    'group_count_mode',
                    'FIXED_GROUP_COUNT'
                )
            );


        /*
    |--------------------------------------------------------------------------
    | Remainder Policy
    |--------------------------------------------------------------------------
    |
    | CUSTOM_GROUPS siempre administra la capacidad
    | directamente desde cada definición de grupo.
    |
    | En cualquier otro modo respetamos exactamente
    | la política elegida por el usuario.
    |
    */

        $remainderPolicy =
            $groupCountMode === 'CUSTOM_GROUPS'
            ? 'MANUAL'
            : strtoupper(
                (string)
                $this->input(
                    'remainder_policy',
                    'BALANCED'
                )
            );


        /*
    |--------------------------------------------------------------------------
    | Merge normalizado
    |--------------------------------------------------------------------------
    */

        $this->merge([
            'group_count_mode' =>
            $groupCountMode,

            'remainder_policy' =>
            $remainderPolicy,

            'distribution_mode' =>
            strtoupper(
                (string)
                $this->input(
                    'distribution_mode',
                    'SNAKE_SEEDED'
                )
            ),

            'internal_allow_draws' =>
            $this->boolean(
                'internal_allow_draws'
            ),

            'cross_group_normalization' =>
            strtoupper(
                (string)
                $this->input(
                    'cross_group_normalization',
                    'RAW'
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
        ]);
    }

    public function rules(): array
    {
        return [
            'group_count_mode' => [
                'required',

                Rule::in([
                    'FIXED_GROUP_COUNT',
                    'TARGET_GROUP_SIZE',
                    'CUSTOM_GROUPS',
                ]),
            ],

            'group_count' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input(
                        'group_count_mode'
                    )
                        ===
                        'FIXED_GROUP_COUNT'
                ),

                'nullable',
                'integer',
                'min:2',
                'max:256',
            ],

            'target_group_size' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input(
                        'group_count_mode'
                    )
                        ===
                        'TARGET_GROUP_SIZE'
                ),

                'nullable',
                'integer',
                'min:2',
                'max:256',
            ],

            'min_group_size' => [
                'required',
                'integer',
                'min:2',
                'max:256',
            ],

            'max_group_size' => [
                'required',
                'integer',
                'min:2',
                'max:512',
                'gte:min_group_size',
            ],

            'remainder_policy' => [
                'required',

                Rule::in([
                    'BALANCED',
                    'FIRST_GROUPS',
                    'LAST_GROUPS',
                    'MANUAL',
                ]),
            ],

            'distribution_mode' => [
                'required',

                Rule::in([
                    'INPUT_ORDER',
                    'RANDOM',
                    'SNAKE_SEEDED',
                    'POT_DRAW',
                    'MANUAL',
                ]),
            ],

            'pot_count' => [
                'nullable',
                'integer',
                'min:1',
                'max:256',
            ],

            'internal_cycles' => [
                'required',
                'integer',
                'min:1',
                'max:10',
            ],

            'internal_allow_draws' => [
                'boolean',
            ],

            'internal_win_points' => [
                'required',
                'numeric',
                'between:-9999.99,9999.99',
            ],

            'internal_draw_points' => [
                'required',
                'numeric',
                'between:-9999.99,9999.99',
            ],

            'internal_loss_points' => [
                'required',
                'numeric',
                'between:-9999.99,9999.99',
            ],

            'internal_series_format' => [
                'required',
                Rule::in(['BEST_OF', 'FIXED_GAMES']),
            ],

            'internal_fixed_games' => [
                Rule::requiredIf(
                    fn() => $this->input('internal_series_format') === 'FIXED_GAMES'
                ),
                'nullable',
                'integer',
                'min:1',
                'max:20',
            ],

            'internal_best_of' => [
                'required',

                Rule::in([
                    1,
                    3,
                    5,
                    7,
                    9,
                ]),
            ],

            'cross_group_normalization' => [
                'required',

                Rule::in([
                    'RAW',
                    'PER_MATCH',
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
}
