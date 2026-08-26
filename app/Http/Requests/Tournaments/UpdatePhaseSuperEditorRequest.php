<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\PhaseEditor\PhaseSuperEditorRegistry;
use Illuminate\Foundation\Http\FormRequest;

/*
|--------------------------------------------------------------------------
| UpdatePhaseSuperEditorRequest
|--------------------------------------------------------------------------
|
| Las reglas las pone el editor del tipo de fase, no este archivo.
|
| Round Robin valida vueltas y puntos; Eliminacion Directa validara otra
| cosa. Si las reglas vivieran aqui habria que venir a tocar un request
| compartido cada vez que se anade un motor, y acabaria siendo un cajon con
| las reglas de los cuatro mezcladas.
|
*/
class UpdatePhaseSuperEditorRequest extends FormRequest
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
            ->persistenceRules();
    }
}
