<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;

class PreviewGroupStageRequest extends FormRequest
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

    public function rules(): array
    {
        return [
            'participants' => [
                'nullable',
                'integer',
                'min:4',
                'max:512',
            ],
        ];
    }
}
