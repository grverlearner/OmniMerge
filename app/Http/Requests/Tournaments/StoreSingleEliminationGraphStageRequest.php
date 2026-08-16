<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSingleEliminationGraphStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $phase = $this->route('phaseTemplate');

        return $phase instanceof PhaseTemplate
            && $phase->phase_type === 'SINGLE_ELIMINATION'
            && ($this->user()?->can('update', $phase) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'stage_number' => [
                'required',
                'integer',
                'min:1',
                'max:100',
                Rule::unique('phase_single_elimination_rounds', 'stage_number')
                    ->where('phase_template_id', $this->route('phaseTemplate')->id),
            ],
            'branch_code' => ['required', Rule::in(['MAIN', 'SECONDARY', 'REPECHAGE', 'CUSTOM'])],
        ];
    }
}
