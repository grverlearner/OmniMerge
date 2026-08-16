<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSingleEliminationStructureElementRequest extends FormRequest
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
            'SINGLE_ELIMINATION'

            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $phaseTemplate
                )
                ??
                false
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_locked' =>
            $this->boolean(
                'is_locked'
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'nullable',
                'string',
                'max:120',
            ],

            'label' => [
                'nullable',
                'string',
                'max:160',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'status' => [
                'required',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                ]),
            ],

            'is_locked' => [
                'boolean',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' =>
            'nombre',

            'label' =>
            'etiqueta',

            'description' =>
            'descripción',

            'status' =>
            'estado',

            'is_locked' =>
            'protección',
        ];
    }
}
