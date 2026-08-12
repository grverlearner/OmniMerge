<?php

namespace App\Http\Requests\Tournaments;

use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;


class UpdateTournamentTemplateRequest extends FormRequest
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

            'status' =>
            strtoupper(
                (string)
                $this->input(
                    'status',
                    'DRAFT'
                )
            ),

            'visibility' =>
            strtoupper(
                (string)
                $this->input(
                    'visibility',
                    'PRIVATE'
                )
            ),

            'allow_byes' =>
            $this->boolean(
                'allow_byes'
            ),

            'allow_cloning' =>
            $this->boolean(
                'allow_cloning'
            ),

            'remove_image' =>
            $this->boolean(
                'remove_image'
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
                'max:5000',
            ],


            'image' => [
                'nullable',

                File::image()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max('4mb'),
            ],


            'remove_image' => [
                'boolean',
            ],


            'min_participants' => [
                'required',
                'integer',
                'min:2',
                'max:512',
            ],


            'max_participants' => [
                'nullable',
                'integer',
                'min:2',
                'max:512',
                'gte:min_participants',
            ],


            'allow_byes' => [
                'boolean',
            ],


            'status' => [
                'required',

                Rule::in([
                    'DRAFT',
                    'ACTIVE',
                    'ARCHIVED',
                ]),
            ],


            'visibility' => [
                'required',

                Rule::in([
                    'PRIVATE',
                    'PUBLIC',
                    'UNLISTED',
                ]),
            ],


            'allow_cloning' => [
                'boolean',
            ],
        ];
    }


    public function messages(): array
    {
        return [

            'name.required' =>
            'El nombre de la plantilla es obligatorio.',

            'max_participants.gte' =>
            'El máximo de participantes no puede ser menor que el mínimo.',

            'image.max' =>
            'La imagen no puede superar los 4 MB.',
        ];
    }
}
