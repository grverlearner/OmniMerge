<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;

class StoreGroupStageGroupRequest extends FormRequest
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
            'name' =>
            trim(
                (string)
                $this->input(
                    'name'
                )
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'capacity' => [
                'nullable',
                'integer',
                'min:2',
                'max:512',
            ],
        ];
    }
}
