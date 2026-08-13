<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseGroupStageTiebreaker;
use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupStageTiebreakerRequest extends FormRequest
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
            'criterion' =>
            strtoupper(
                (string)
                $this->input(
                    'criterion'
                )
            ),

            'normalization' =>
            strtoupper(
                (string)
                $this->input(
                    'normalization',
                    'DEFAULT'
                )
            ),

            'direction' =>
            strtoupper(
                (string)
                $this->input(
                    'direction',
                    'AUTO'
                )
            ),
        ]);
    }

    public function rules(): array
    {
        $phaseTemplate =
            $this->route(
                'phaseTemplate'
            );

        $tiebreaker =
            $this->route(
                'tiebreaker'
            );

        return [
            'criterion' => [
                'required',

                Rule::in([
                    'POINTS',
                    'WINS',
                    'SCORE_DIFFERENCE',
                    'SCORE_FOR',
                    'GAME_DIFFERENCE',
                    'GAME_WINS',
                    'SEED',
                ]),

                Rule::unique(
                    'phase_group_stage_tiebreakers',
                    'criterion'
                )
                    ->where(
                        fn($query) =>
                        $query
                            ->where(
                                'phase_template_id',
                                $phaseTemplate?->id
                            )
                            ->where(
                                'normalization',
                                $this->input(
                                    'normalization'
                                )
                            )
                    )
                    ->ignore(
                        $tiebreaker
                            instanceof
                            PhaseGroupStageTiebreaker
                            ? $tiebreaker->id
                            : null
                    ),
            ],

            'normalization' => [
                'required',

                Rule::in([
                    'DEFAULT',
                    'RAW',
                    'PER_MATCH',
                ]),
            ],

            'direction' => [
                'required',

                Rule::in([
                    'AUTO',
                    'ASC',
                    'DESC',
                ]),
            ],
        ];
    }
}
