<?php

namespace App\Http\Requests\Tournaments;

use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;


class StoreTournamentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return
            $this->user()
            ?->can(
                'create',
                TournamentTemplate::class
            )
            ?? false;
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

            /*
             * Cómo se reconoce la plantilla.
             *
             * No es estructura -no cambia el recorrido ni el reparto- y por
             * eso vive aquí y no en el constructor del grafo: es lo que
             * permite distinguir cuarenta plantillas de un vistazo.
             */
            'icon' => [
                'nullable',
                'string',
                'max:8',
            ],

            'accent' => [
                'nullable',
                Rule::in(TournamentTemplate::ACCENTS),
            ],

            'summary' => [
                'nullable',
                'string',
                'max:140',
            ],

            'category' => [
                'nullable',
                Rule::in(array_keys(TournamentTemplate::CATEGORIES)),
            ],

            'tags' => [
                'nullable',
                'array',
                'max:6',
            ],

            'tags.*' => [
                'string',
                'max:24',
            ],
        ];
    }


    public function messages(): array
    {
        return [

            'name.required' =>
            'El nombre de la plantilla es obligatorio.',

            'name.max' =>
            'El nombre no puede superar los 150 caracteres.',


            'description.max' =>
            'La descripción no puede superar los 5000 caracteres.',


            'image.max' =>
            'La imagen no puede superar los 4 MB.',


            'min_participants.required' =>
            'Indica la cantidad mínima de participantes.',

            'min_participants.min' =>
            'Un torneo debe permitir al menos 2 participantes.',


            'max_participants.gte' =>
            'El máximo de participantes no puede ser menor que el mínimo.',


            'status.in' =>
            'El estado seleccionado no es válido.',


            'visibility.in' =>
            'La visibilidad seleccionada no es válida.',
        ];
    }
}
