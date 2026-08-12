<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StorePhaseTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            ?->can(
                'create',
                PhaseTemplate::class
            ) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),

            'phase_type' => strtoupper(
                (string) $this->input(
                    'phase_type',
                    'SINGLE_ELIMINATION'
                )
            ),

            'participant_mode' => strtoupper(
                (string) $this->input(
                    'participant_mode',
                    'INDIVIDUAL'
                )
            ),

            'status' => strtoupper(
                (string) $this->input(
                    'status',
                    'DRAFT'
                )
            ),

            'visibility' => strtoupper(
                (string) $this->input(
                    'visibility',
                    'PRIVATE'
                )
            ),

            'allow_byes' =>
            $this->boolean('allow_byes'),

            'allow_cloning' =>
            $this->boolean('allow_cloning'),
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

            'phase_type' => [
                'required',
                Rule::in([
                    'SINGLE_ELIMINATION',
                    'ROUND_ROBIN',
                    'GROUP_STAGE',
                    'LEAGUE',
                    'SWISS',
                    'CUSTOM',
                ]),
            ],

            'participant_mode' => [
                'required',
                Rule::in([
                    'INDIVIDUAL',
                    'TEAM',
                    'FLEXIBLE',
                ]),
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

            'exact_participants' => [
                'nullable',
                'integer',
                'min:2',
                'max:512',
            ],

            'participant_multiple' => [
                'nullable',
                'integer',
                'min:2',
                'max:512',
            ],

            'allow_byes' => [
                'boolean',
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
            'El nombre de la Fase es obligatorio.',

            'phase_type.in' =>
            'El tipo de Fase seleccionado no es válido.',

            'min_participants.min' =>
            'Una Fase debe admitir al menos 2 participantes.',

            'max_participants.gte' =>
            'El máximo no puede ser menor que el mínimo.',

            'best_of.in' =>
            'Selecciona un Best of válido.',
        ];
    }
}
