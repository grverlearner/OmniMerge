<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSingleEliminationGraphRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $phase = $this->route('phaseTemplate');

        return $phase instanceof PhaseTemplate
            && $phase->phase_type === 'SINGLE_ELIMINATION'
            && ($this->user()?->can('update', $phase) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'target_type' => strtoupper((string) $this->input('target_type')),
        ]);
    }

    public function rules(): array
    {
        return [
            'source_encounter_id' => ['required', 'integer', 'exists:phase_single_elimination_encounters,id'],
            'source_position_from' => ['required', 'integer', 'min:1', 'max:64'],
            'quantity' => ['required', 'integer', 'min:1', 'max:64'],
            'target_type' => ['required', Rule::in(['ENCOUNTER', 'PHASE_EXIT'])],
            'target_encounter_id' => [
                'nullable',
                'required_if:target_type,ENCOUNTER',
                'integer',
                'exists:phase_single_elimination_encounters,id',
            ],
            'target_slot_from' => [
                'nullable',
                'required_if:target_type,ENCOUNTER',
                'integer',
                'min:1',
                'max:64',
            ],
            'target_phase_exit_id' => [
                'nullable',
                'required_if:target_type,PHASE_EXIT',
                'integer',
                'exists:phase_exits,id',
            ],
        ];
    }
}
