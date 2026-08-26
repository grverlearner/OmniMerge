<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreGroupStageExitRequest;
use App\Models\PhaseExit;
use App\Models\PhaseInputGate;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\GroupStage\GroupStageDefinitionService;
use App\Services\Tournaments\GroupStage\GroupStagePreviewService;
use App\Services\Tournaments\GroupStage\GroupStageSettingsService;
use App\Services\Tournaments\Preview\PreviewCastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| GroupStageStructureController
|--------------------------------------------------------------------------
|
| Las dos pantallas que a Group Stage le faltaban y Single Elimination sí
| tenía: ESTRUCTURA (cómo quedan los grupos) y ENTRADAS Y SALIDAS (por
| dónde entran y por dónde se van).
|
| La estructura se dibuja con caras reales tomadas prestadas de las
| entidades del usuario. No se guarda nada: es una previsualización, y
| cada figurante viene marcado como prestado para que nadie lo confunda
| con un inscrito.
|
| Ver docs/md/31-Fase-14-Group-Stage.md
|
*/

class GroupStageStructureController extends Controller
{
    public function __construct(
        private readonly GroupStageSettingsService $settingsService,
        private readonly GroupStagePreviewService $previewService,
        private readonly GroupStageDefinitionService $definitionService,
        private readonly PreviewCastService $cast,
        private readonly \App\Services\Tournaments\GroupStage\GroupStageGateService $gates,
        private readonly \App\Services\Tournaments\GroupStage\GroupStageExitForecastService $exitForecast,
        private readonly \App\Services\Tournaments\PhaseExitService $exitService,
        private readonly \App\Services\Tournaments\GroupStage\GroupStageAdvancementRuleService $ruleService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Estructura
    |--------------------------------------------------------------------------
    */

    /*
     * La estructura de una fase de grupos.
     *
     * Esta pantalla ya no existe: su contenido vive dentro de la Super
     * Edicion, junto a la configuracion que lo produce y reaccionando a
     * ella. Tenerlo tambien aqui daba dos sitios para lo mismo y solo
     * uno ensenaba el efecto de cada cambio.
     *
     * La ruta se conserva y redirige, para que ningun enlace guardado
     * acabe en un 404.
     */
    public function structure(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);
        $this->ensureCorrectType($phaseTemplate);

        return redirect()->route(
            'tournaments.phase-templates.super.show',
            $phaseTemplate
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Entradas y salidas
    |--------------------------------------------------------------------------
    */

    /*
     * Las entradas y salidas de una fase de grupos.
     *
     * Esta pantalla ya no existe: su contenido vive dentro de la Super
     * Edicion, junto a la configuracion que lo produce y reaccionando a
     * ella. Tenerlo tambien aqui daba dos sitios para lo mismo y solo
     * uno ensenaba el efecto de cada cambio.
     *
     * La ruta se conserva y redirige, para que ningun enlace guardado
     * acabe en un 404.
     */
    public function io(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);
        $this->ensureCorrectType($phaseTemplate);

        return redirect()->route(
            'tournaments.phase-templates.super.show',
            $phaseTemplate
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Puertas de salida: alta y baja
    |--------------------------------------------------------------------------
    |
    | Una salida y el criterio que la cruza se crean juntos, porque son la
    | misma decisión. Una puerta sin criterio no la cruza nadie; un criterio
    | sin puerta no lleva a ningún sitio. Tenerlos en dos formularios
    | distintos solo servía para poder dejar la mitad hecha y descubrirlo
    | jugando.
    |
    | El selector de la puerta se fija en ENGINE_RULES a propósito. En una
    | fase de grupos quien decide es el criterio, y el motor entrega esa
    | lista tal cual: si la puerta además guardara su propio número, serían
    | dos verdades sobre lo mismo, y el grafo validaría una mientras el
    | torneo juega la otra.
    |
    */

    public function storeExit(
        StoreGroupStageExitRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);
        $this->ensureCorrectType($phaseTemplate);

        $data = $request->validated();

        DB::transaction(function () use ($phaseTemplate, $data) {

            $exit = $this->exitService->create($phaseTemplate, [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,

                'selector_type' => 'ENGINE_RULES',
                'exit_timing' => 'PHASE_END',

                'priority' => $data['priority'] ?? 10,
                'status' => $data['status'],
            ]);

            $this->ruleService->create($phaseTemplate, [
                'phase_exit_id' => $exit->id,
                'rule_type' => $data['rule_type'],

                'take' => $data['take'] ?? null,
                'position_from' => $data['position_from'] ?? null,
                'position_to' => $data['position_to'] ?? null,
                'phase_group_stage_group_id' => $data['phase_group_stage_group_id'] ?? null,

                'status' => $data['status'],
            ]);
        });

        return redirect()
            ->route('tournaments.group-stage.io', $phaseTemplate)
            ->with('success', 'Salida creada con su criterio.');
    }

    /*
     * Al borrar una salida se llevan sus criterios.
     *
     * La tabla de criterios guarda el id de la puerta sin llave foránea,
     * así que dejarlos atrás no da error: deja reglas apuntando a una
     * puerta que ya no existe, invisibles en pantalla y silenciosas hasta
     * que alguien mira el pronóstico y no le cuadran los números.
     */
    public function destroyExit(
        Request $request,
        PhaseTemplate $phaseTemplate,
        PhaseExit $phaseExit
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);
        $this->ensureCorrectType($phaseTemplate);

        abort_unless(
            (int) $phaseExit->phase_template_id === (int) $phaseTemplate->id,
            404
        );

        DB::transaction(function () use ($phaseTemplate, $phaseExit) {

            $phaseTemplate
                ->groupStageAdvancementRules()
                ->where('phase_exit_id', $phaseExit->id)
                ->delete();

            $this->exitService->delete($phaseExit);
        });

        return redirect()
            ->route('tournaments.group-stage.io', $phaseTemplate)
            ->with('success', 'Salida eliminada junto con sus criterios.');
    }


    /*
    |--------------------------------------------------------------------------
    | Puertas de entrada: alta, edición y baja
    |--------------------------------------------------------------------------
    |
    | Todo ocurre en la propia sección de entradas y salidas. No se manda
    | al usuario a otra pestaña para crear una puerta: esta pantalla es el
    | sitio donde se configura el contrato de la fase, entero.
    |
    */

    public function storeGate(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);
        $this->ensureCorrectType($phaseTemplate);

        $data = $this->validateGate($request, $phaseTemplate);

        $gate = $this->gates->create($phaseTemplate, $data);

        if ($data['target_group_code'] ?? null) {
            $this->gates->update($gate, $data);
        }

        return back()->with(
            'success',
            'Puerta de entrada «' . $gate->name . '» creada.'
        );
    }

    public function updateGate(
        Request $request,
        PhaseTemplate $phaseTemplate,
        PhaseInputGate $gate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);
        $this->ensureCorrectType($phaseTemplate);

        abort_unless($gate->phase_template_id === $phaseTemplate->id, 404);

        $this->gates->update(
            $gate,
            $this->validateGate($request, $phaseTemplate)
        );

        return back()->with('success', 'Puerta actualizada.');
    }

    public function destroyGate(
        PhaseTemplate $phaseTemplate,
        PhaseInputGate $gate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);
        $this->ensureCorrectType($phaseTemplate);

        abort_unless($gate->phase_template_id === $phaseTemplate->id, 404);

        $name = $gate->name;

        $this->gates->delete($gate);

        return back()->with(
            'success',
            'Puerta «' . $name . '» eliminada.'
        );
    }

    /**
     * Una puerta de fase de grupos: identidad, capacidad y a qué grupo
     * envía. Sin slots, porque aquí no hay cuadro interno.
     */
    private function validateGate(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): array {

        $codes =
            $phaseTemplate->groupStageGroups()->pluck('code')->all();

        return $request->validate([

            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],

            'input_type' => ['required', Rule::in(['STANDARD', 'SEEDED', 'QUALIFIER', 'WILDCARD'])],
            'merge_policy' => ['required', Rule::in(['APPEND', 'WAIT_ALL', 'FIRST_AVAILABLE', 'PRIORITY'])],
            'distribution_mode' => ['required', Rule::in(['SEQUENTIAL', 'BALANCED', 'SNAKE', 'RANDOM'])],
            'empty_behavior' => ['required', Rule::in(['ALLOW', 'BLOCK'])],

            'min_participants' => ['nullable', 'integer', 'min:0', 'max:512'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:512'],

            'is_required' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],

            'target_group_code' => ['nullable', Rule::in($codes)],
        ], [], [
            'name' => 'nombre',
            'target_group_code' => 'grupo destino',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Grupo destino de una puerta
    |--------------------------------------------------------------------------
    |
    | Se guarda dentro del JSON `settings` de la puerta, que ya existe.
    | Una columna nueva obligaría a migrar para algo que solo Group Stage
    | entiende.
    |
    */

    public function updateGateTarget(
        Request $request,
        PhaseTemplate $phaseTemplate,
        PhaseInputGate $gate
    ): RedirectResponse {

        $this->authorize('update', $phaseTemplate);
        $this->ensureCorrectType($phaseTemplate);

        abort_unless(
            $gate->phase_template_id === $phaseTemplate->id,
            404
        );

        $codes =
            $phaseTemplate->groupStageGroups()
            ->pluck('code')
            ->all();

        $data = $request->validate([
            'target_group_code' => [
                'nullable',
                Rule::in($codes),
            ],
        ], [], [
            'target_group_code' => 'grupo destino',
        ]);

        $gateSettings = $gate->settings ?? [];

        if (($data['target_group_code'] ?? null) === null) {
            unset($gateSettings['target_group_code']);
        } else {
            $gateSettings['target_group_code'] = $data['target_group_code'];
        }

        $gate->forceFill([
            'settings' => $gateSettings ?: null,
        ])->save();

        return back()->with(
            'success',

            ($data['target_group_code'] ?? null)
                ? 'Esta puerta enviará a sus participantes al grupo '
                    . $data['target_group_code'] . '.'
                : 'Esta puerta vuelve al reparto automático.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Interno
    |--------------------------------------------------------------------------
    */

    private function participantCount(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): int {

        $requested = (int) $request->integer('participants');

        if ($requested > 0) {
            return min(256, $requested);
        }

        return (int) (
            $phaseTemplate->exact_participants
            ?? $phaseTemplate->min_participants
            ?? 8
        );
    }

    private function ensureCorrectType(PhaseTemplate $phaseTemplate): void
    {
        abort_unless($phaseTemplate->phase_type === 'GROUP_STAGE', 404);
    }
}
