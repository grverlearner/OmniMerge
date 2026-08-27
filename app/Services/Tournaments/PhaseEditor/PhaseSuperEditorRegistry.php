<?php

namespace App\Services\Tournaments\PhaseEditor;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\GroupStage\GroupStageSuperEditor;
use App\Services\Tournaments\RoundRobin\RoundRobinSuperEditor;
use App\Services\Tournaments\SingleElimination\SingleEliminationSuperEditor;

/*
|--------------------------------------------------------------------------
| PhaseSuperEditorRegistry
|--------------------------------------------------------------------------
|
| Que editor sabe editar cada tipo de fase.
|
| Hoy solo hay uno. Existe igualmente porque el controlador y las rutas se
| escriben contra el registro y no contra Round Robin: cuando se implemente
| Single Elimination bastara con anadir una linea aqui, sin abrir el
| controlador ni duplicar rutas.
|
*/
class PhaseSuperEditorRegistry
{
    /**
     * @var array<string,class-string<PhaseSuperEditorContract>>
     */
    private const EDITORS = [
        'ROUND_ROBIN' => RoundRobinSuperEditor::class,
        'GROUP_STAGE' => GroupStageSuperEditor::class,
        'SINGLE_ELIMINATION' => SingleEliminationSuperEditor::class,
    ];

    public function supports(
        PhaseTemplate $phaseTemplate
    ): bool {
        return isset(
            self::EDITORS[$phaseTemplate->phase_type]
        );
    }

    public function for(
        PhaseTemplate $phaseTemplate
    ): PhaseSuperEditorContract {

        abort_unless(
            $this->supports($phaseTemplate),
            404,
            'Todavía no existe Super Edición para este tipo de fase.'
        );

        return app(
            self::EDITORS[$phaseTemplate->phase_type]
        );
    }

    /**
     * @return array<int,string>
     */
    public function supportedTypes(): array
    {
        return array_keys(
            self::EDITORS
        );
    }
}
