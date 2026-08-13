<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSwissSettingsRequest extends FormRequest
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
            'completion_mode' =>
            strtoupper(
                (string)
                $this->input(
                    'completion_mode',
                    'FIXED_ROUNDS'
                )
            ),

            'pairing_algorithm' =>
            strtoupper(
                (string)
                $this->input(
                    'pairing_algorithm',
                    'OMNIMERGE_SCORE_GROUP'
                )
            ),

            'pairing_basis' =>
            strtoupper(
                (string)
                $this->input(
                    'pairing_basis',
                    'MATCH_POINTS'
                )
            ),

            'first_round_mode' =>
            strtoupper(
                (string)
                $this->input(
                    'first_round_mode',
                    'SEEDED_HALVES'
                )
            ),

            'rematch_policy' =>
            strtoupper(
                (string)
                $this->input(
                    'rematch_policy',
                    'STRICT_NO_REMATCH'
                )
            ),

            'floater_policy' =>
            strtoupper(
                (string)
                $this->input(
                    'floater_policy',
                    'MINIMIZE_SCORE_GAP'
                )
            ),

            'side_balance_policy' =>
            strtoupper(
                (string)
                $this->input(
                    'side_balance_policy',
                    'PREFER_BALANCE'
                )
            ),

            'allow_draws' =>
            $this->boolean(
                'allow_draws'
            ),

            'bye_policy' =>
            strtoupper(
                (string)
                $this->input(
                    'bye_policy',
                    'LOWEST_STANDING_WITHOUT_BYE'
                )
            ),

            'initial_pairing_score_mode' =>
            strtoupper(
                (string)
                $this->input(
                    'initial_pairing_score_mode',
                    'ZERO'
                )
            ),

            'acceleration_mode' =>
            strtoupper(
                (string)
                $this->input(
                    'acceleration_mode',
                    'NONE'
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

            'fallback_policy' =>
            strtoupper(
                (string)
                $this->input(
                    'fallback_policy',
                    'FINAL_RANKING'
                )
            ),
        ]);
    }

    public function rules(): array
    {
        $fixed =
            $this->input(
                'completion_mode'
            )
            ===
            'FIXED_ROUNDS';

        $threshold =
            $this->input(
                'completion_mode'
            )
            ===
            'RECORD_THRESHOLDS';

        $accelerated =
            $this->input(
                'acceleration_mode'
            )
            ===
            'GENERIC_VIRTUAL_POINTS';

        return [
            'completion_mode' => [
                'required',

                Rule::in([
                    'FIXED_ROUNDS',
                    'RECORD_THRESHOLDS',
                ]),
            ],

            'fixed_rounds' => [
                Rule::excludeIf(
                    ! $fixed
                ),

                Rule::requiredIf(
                    $fixed
                ),

                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'qualification_wins' => [
                Rule::excludeIf(
                    ! $threshold
                ),

                Rule::requiredIf(
                    $threshold
                ),

                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'elimination_losses' => [
                Rule::excludeIf(
                    ! $threshold
                ),

                Rule::requiredIf(
                    $threshold
                ),

                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'max_rounds' => [
                Rule::excludeIf(
                    ! $threshold
                ),

                Rule::requiredIf(
                    $threshold
                ),

                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'pairing_algorithm' => [
                'required',

                Rule::in([
                    'OMNIMERGE_SCORE_GROUP',
                    'ADJACENT_STANDINGS',
                    'RANDOM_WITHIN_SCORE',
                ]),
            ],

            'pairing_basis' => [
                'required',

                Rule::in([
                    'MATCH_POINTS',
                    'WIN_LOSS_RECORD',
                    'PAIRING_SCORE',
                ]),
            ],

            'first_round_mode' => [
                'required',

                Rule::in([
                    'INPUT_ORDER',
                    'RANDOM',
                    'SEEDED_HALVES',
                    'TOP_VS_BOTTOM',
                ]),
            ],

            'rematch_policy' => [
                'required',

                Rule::in([
                    'STRICT_NO_REMATCH',
                    'AVOID_IF_POSSIBLE',
                    'ALLOW_REMATCH',
                ]),
            ],

            'floater_policy' => [
                'required',

                Rule::in([
                    'MINIMIZE_SCORE_GAP',
                    'LOWEST_SEED_FIRST',
                    'HIGHEST_SEED_FIRST',
                    'AVOID_REPEAT_FLOAT',
                ]),
            ],

            'side_balance_policy' => [
                'required',

                Rule::in([
                    'NONE',
                    'PREFER_BALANCE',
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

            'bye_policy' => [
                'required',

                Rule::in([
                    'DISABLED',
                    'LOWEST_STANDING_WITHOUT_BYE',
                    'LOWEST_SEED_WITHOUT_BYE',
                    'RANDOM_ELIGIBLE',
                    'MANUAL',
                ]),
            ],

            'bye_points' => [
                'required',
                'numeric',
                'between:-9999.99,9999.99',
            ],

            'max_byes_per_participant' => [
                'required',
                'integer',
                'min:0',
                'max:20',
            ],

            'initial_pairing_score_mode' => [
                'required',

                Rule::in([
                    'ZERO',
                    'EXTERNAL_SCORE',
                ]),
            ],

            'acceleration_mode' => [
                'required',

                Rule::in([
                    'NONE',
                    'GENERIC_VIRTUAL_POINTS',
                ]),
            ],

            'acceleration_rounds' => [
                Rule::excludeIf(
                    ! $accelerated
                ),

                Rule::requiredIf(
                    $accelerated
                ),

                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'acceleration_seed_count' => [
                Rule::excludeIf(
                    ! $accelerated
                ),

                Rule::requiredIf(
                    $accelerated
                ),

                'nullable',
                'integer',
                'min:1',
                'max:512',
            ],

            'acceleration_virtual_points' => [
                Rule::excludeIf(
                    ! $accelerated
                ),

                Rule::requiredIf(
                    $accelerated
                ),

                'nullable',
                'numeric',
                'between:-9999.99,9999.99',
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

            'fallback_policy' => [
                'required',

                Rule::in([
                    'FINAL_RANKING',
                    'MANUAL_RESOLUTION',
                    'REMAINING_EXIT',
                ]),
            ],
        ];
    }
}
