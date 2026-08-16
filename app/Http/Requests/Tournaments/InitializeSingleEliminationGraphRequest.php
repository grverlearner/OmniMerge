<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;

class InitializeSingleEliminationGraphRequest extends FormRequest
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
            'replace_structure' => $this->boolean('replace_structure'),
        ]);
    }

    public function rules(): array
    {
        return [
            'participants' => ['required', 'integer', 'min:2', 'max:512'],
            'replace_structure' => ['boolean'],
        ];
    }
}
