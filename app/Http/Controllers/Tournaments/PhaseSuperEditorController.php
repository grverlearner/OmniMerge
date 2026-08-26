<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\PhaseSuperEditorGateRequest;
use App\Http\Requests\Tournaments\PhaseSuperEditorExitRequest;
use App\Http\Requests\Tournaments\UpdatePhaseSuperEditorRequest;
use App\Models\PhaseExit;
use App\Models\PhaseGroupStageGroup;
use App\Models\PhaseInputGate;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\PhaseEditor\PhaseSuperEditorRegistry;
use App\Services\Tournaments\PhaseEditor\SupportsEditableGroups;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| PhaseSuperEditorController
|--------------------------------------------------------------------------
|
| La Super Edicion de una fase.
|
| El controlador no sabe nada de Round Robin: pregunta al registro quien
| edita este tipo de fase y le pide el payload y las vistas de sus paneles.
| Cuando exista el editor de Eliminacion Directa no habra que abrir este
| archivo.
|
| Tres acciones y ninguna mas:
|
|   show     la pantalla completa
|   preview  el mismo calculo en JSON, para que la pantalla reaccione
|   update   lo que de verdad pertenece a la fase
|
| El preview existe para no duplicar matematica en el navegador. Cambiar de
| 8 a 6 participantes o de una vuelta a dos recalcula el calendario con el
| MISMO calculador que usan el simulador y Group Stage; el cliente solo
| decide quien ocupa cada semilla, que es una permutacion y no necesita
| servidor.
|
*/
class PhaseSuperEditorController extends Controller
{
    public function __construct(
        private readonly PhaseSuperEditorRegistry $registry
    ) {}

    public function show(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): View {

        $this->authorize('update', $phaseTemplate);

        $editor = $this->registry->for($phaseTemplate);

        $payload = $editor->payload(
            $phaseTemplate,
            $request->user(),
            $this->overrides($request, $phaseTemplate)
        );

        return view(
            'tournaments.phase-templates.super.editor',
            [
                'phaseTemplate' => $phaseTemplate,
                'payload' => $payload,
                'configView' => $editor->configView(),
                'stageView' => $editor->stageView(),
                'gatesView' => $editor->gatesView(),
                'scheduleView' => $editor->scheduleView(),
                'saveFieldsView' => $editor->saveFieldsView(),
                'clientEngine' => $editor->clientEngine(),
            ]
        );
    }

    public function preview(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): JsonResponse {

        $this->authorize('update', $phaseTemplate);

        $editor = $this->registry->for($phaseTemplate);

        return response()->json(
            $editor->payload(
                $phaseTemplate,
                $request->user(),
                $this->overrides($request, $phaseTemplate)
            )
        );
    }

    public function update(
        UpdatePhaseSuperEditorRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);

        $this->registry
            ->for($phaseTemplate)
            ->persist(
                $phaseTemplate,
                $request->validated()
            );

        return redirect()
            ->route('tournaments.phase-templates.super.show', $phaseTemplate)
            ->with('success', 'Configuración de la fase guardada.');
    }

    /*
    |--------------------------------------------------------------------------
    | Puertas
    |--------------------------------------------------------------------------
    |
    | Se crean, se editan y se borran sin salir del editor. Antes habia que
    | irse a otra pantalla, configurar sin ver la estructura y volver para
    | comprobar el efecto; en un editor cuya razon de ser es que todo
    | reacciona a la vez, mandar al usuario fuera era lo unico que no
    | reaccionaba.
    |
    | Que significa cada campo lo decide el editor del motor: en una liga
    | una puerta de entrada reparte puestos de la parrilla, y en otra fase
    | significara otra cosa.
    |
    */

    public function storeGate(
        PhaseSuperEditorGateRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);

        $this->registry
            ->for($phaseTemplate)
            ->persistGate($phaseTemplate, null, $request->validated());

        return $this->backToEditor($phaseTemplate, 'Puerta de entrada creada.');
    }

    public function updateGate(
        PhaseSuperEditorGateRequest $request,
        PhaseTemplate $phaseTemplate,
        PhaseInputGate $gate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);

        $this->ensureBelongs($phaseTemplate, $gate->phase_template_id);

        $this->registry
            ->for($phaseTemplate)
            ->persistGate($phaseTemplate, $gate, $request->validated());

        return $this->backToEditor($phaseTemplate, 'Puerta de entrada actualizada.');
    }

    public function destroyGate(
        PhaseTemplate $phaseTemplate,
        PhaseInputGate $gate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);

        $this->registry
            ->for($phaseTemplate)
            ->deleteGate($phaseTemplate, $gate);

        return $this->backToEditor($phaseTemplate, 'Puerta de entrada eliminada.');
    }

    public function storeExit(
        PhaseSuperEditorExitRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);

        $this->registry
            ->for($phaseTemplate)
            ->persistExit($phaseTemplate, null, $request->validated());

        return $this->backToEditor($phaseTemplate, 'Puerta de salida creada.');
    }

    public function updateExit(
        PhaseSuperEditorExitRequest $request,
        PhaseTemplate $phaseTemplate,
        PhaseExit $phaseExit
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);

        $this->ensureBelongs($phaseTemplate, $phaseExit->phase_template_id);

        $this->registry
            ->for($phaseTemplate)
            ->persistExit($phaseTemplate, $phaseExit, $request->validated());

        return $this->backToEditor($phaseTemplate, 'Puerta de salida actualizada.');
    }

    public function destroyExit(
        PhaseTemplate $phaseTemplate,
        PhaseExit $phaseExit
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);

        $this->registry
            ->for($phaseTemplate)
            ->deleteExit($phaseTemplate, $phaseExit);

        return $this->backToEditor($phaseTemplate, 'Puerta de salida eliminada.');
    }

    /*
    |--------------------------------------------------------------------------
    | Grupos
    |--------------------------------------------------------------------------
    |
    | Solo para los motores que reparten en grupos con nombre propio. Se
    | pregunta con instanceof en vez de meterlo en el contrato principal:
    | una liga no tiene grupos, y obligarla a escribir metodos vacios seria
    | prometer algo que no cumple.
    |
    */

    public function storeGroup(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);

        $editor = $this->groupEditor($phaseTemplate);

        $editor->persistGroup(
            $phaseTemplate,
            null,
            $request->validate($editor->groupRules())
        );

        return $this->backToEditor($phaseTemplate, 'Grupo creado.');
    }

    public function updateGroup(
        Request $request,
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageGroup $group
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);

        $this->ensureBelongs($phaseTemplate, $group->phase_template_id);

        $editor = $this->groupEditor($phaseTemplate);

        $editor->persistGroup(
            $phaseTemplate,
            $group,
            $request->validate($editor->groupRules())
        );

        return $this->backToEditor($phaseTemplate, 'Grupo actualizado.');
    }

    public function destroyGroup(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageGroup $group
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);

        $this->groupEditor($phaseTemplate)
            ->deleteGroup($phaseTemplate, $group);

        return $this->backToEditor($phaseTemplate, 'Grupo eliminado.');
    }

    public function adoptGroups(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);

        $data = $request->validate([
            'sizes' => ['required', 'array', 'min:1', 'max:64'],
            'sizes.*' => ['required', 'integer', 'min:1', 'max:512'],
        ]);

        $this->groupEditor($phaseTemplate)
            ->adoptGroupSizes($phaseTemplate, $data['sizes']);

        return $this->backToEditor(
            $phaseTemplate,
            'Grupos creados a partir del reparto anterior.'
        );
    }

    private function groupEditor(
        PhaseTemplate $phaseTemplate
    ): SupportsEditableGroups {

        $editor = $this->registry->for($phaseTemplate);

        abort_unless(
            $editor instanceof SupportsEditableGroups,
            404,
            'Este tipo de fase no reparte en grupos.'
        );

        return $editor;
    }

    private function ensureBelongs(
        PhaseTemplate $phaseTemplate,
        ?int $ownerId
    ): void {
        abort_unless((int) $ownerId === (int) $phaseTemplate->id, 404);
    }

    /*
     * Se vuelve al editor conservando lo que se estaba previsualizando: si
     * estabas mirando 12 participantes, crear una puerta no debe devolverte
     * a 8 y hacerte recolocar la pantalla.
     */
    private function backToEditor(
        PhaseTemplate $phaseTemplate,
        string $message
    ): RedirectResponse {

        return redirect()
            ->route(
                'tournaments.phase-templates.super.show',
                [$phaseTemplate, ...$this->overrides(request(), $phaseTemplate)]
            )
            ->with('success', $message);
    }

    /*
     * Lo que se esta tocando en pantalla pero todavia no se ha guardado.
     * Sin esto el preview contestaria con la configuracion antigua y la
     * pantalla parpadearia hacia atras en cada cambio.
     */
    private function overrides(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): array {

        $overrides = [];

        foreach (
            $this->registry->for($phaseTemplate)->previewOverrideKeys()
            as $key => $type
        ) {
            if (! $request->filled($key)) {
                continue;
            }

            $overrides[$key] = $type === 'int'
                ? (int) $request->integer($key)
                : (string) $request->string($key);
        }

        return $overrides;
    }
}
