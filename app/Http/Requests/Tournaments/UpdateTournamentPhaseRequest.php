<?php

namespace App\Http\Requests\Tournaments;

use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateTournamentPhaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tournamentTemplate =
            $this->route(
                'tournamentTemplate'
            );


        return
            $tournamentTemplate
            instanceof
            TournamentTemplate

            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $tournamentTemplate
                )
                ?? false
            );
    }


    protected function prepareForValidation(): void
    {
        $this->merge([

            'name' =>
            trim(
                (string)
                $this->input(
                    'name'
                )
            ),

            'phase_type' =>
            strtoupper(
                (string)
                $this->input(
                    'phase_type',
                    'SINGLE_ELIMINATION'
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

            'allow_byes' =>
            $this->boolean(
                'allow_byes'
            ),
        ]);
    }


    public function rules(): array
    {
        return [

            'name' => [
                'required',
                'string',
                'max:150',
            ],


            'description' => [
                'nullable',
                'string',
                'max:3000',
            ],


            'phase_type' => [
                'required',

                Rule::in([
                    'SINGLE_ELIMINATION',
                ]),
            ],


            'input_participants' => [
                'nullable',
                'integer',
                'min:2',
                'max:512',
            ],


            'qualifiers_count' => [
                'nullable',
                'integer',
                'min:1',
                'max:512',
            ],


            'best_of' => [
                'required',

                Rule::in([
                    1,
                    3,
                    5,
                    7,
                    9,
                ]),
            ],


            'allow_byes' => [
                'boolean',
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
