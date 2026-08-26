<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\PhaseEditor\PhaseSuperEditorRegistry;
use Illuminate\Foundation\Http\FormRequest;

/*
|--------------------------------------------------------------------------
| PhaseSuperEditorExitRequest
|--------------------------------------------------------------------------
|
| Las reglas las pone el editor del tipo de fase, no este archivo: una
| puerta no significa lo mismo en una liga que en un cuadro eliminatorio.
|
*/
class PhaseSuperEditorExitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $phaseTemplate = $this->route('phaseTemplate');

        return $phaseTemplate instanceof PhaseTemplate
            && ($this->user()?->can('update', $phaseTemplate) ?? false);
    }

    public function rules(): array
    {
        $phaseTemplate = $this->route('phaseTemplate');

        if (! $phaseTemplate instanceof PhaseTemplate) {
            return [];
        }

        return app(PhaseSuperEditorRegistry::class)
            ->for($phaseTemplate)
            ->exitRules();
    }
}
