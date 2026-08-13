<?php

namespace App\Http\Requests\Tournaments;

use App\Models\TournamentPhaseConnection;
use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTournamentPhaseConnectionRequest
extends FormRequest
{
    public function authorize(): bool
    {
        $template =
            $this->route(
                'tournamentTemplate'
            );

        $connection =
            $this->route(
                'connection'
            );

        return
            $template
            instanceof
            TournamentTemplate
            &&
            $connection
            instanceof
            TournamentPhaseConnection
            &&
            $connection
            ->tournament_template_id
            ===
            $template->id
            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $template
                )
                ??
                false
            );
    }


    protected function prepareForValidation(): void
    {
        $this->merge([
            'allocation_mode' =>
            strtoupper(
                (string)
                $this->input(
                    'allocation_mode',
                    'ALL'
                )
            ),

            'priority' =>
            $this->integer(
                'priority',
                10
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
        $requiresValue =
            in_array(
                $this->input(
                    'allocation_mode'
                ),
                [
                    'TAKE_N',
                    'PERCENTAGE',
                ],
                true
            );


        return [
            'label' => [
                'nullable',
                'string',
                'max:120',
            ],

            'allocation_mode' => [
                'required',

                Rule::in([
                    'ALL',
                    'TAKE_N',
                    'PERCENTAGE',
                    'REMAINDER',
                ]),
            ],

            'allocation_value' => [
                Rule::excludeIf(
                    ! $requiresValue
                ),

                Rule::requiredIf(
                    $requiresValue
                ),

                'nullable',
                'numeric',
                'gt:0',
                'max:512',
            ],

            'priority' => [
                'required',
                'integer',
                'min:0',
                'max:10000',
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
}
