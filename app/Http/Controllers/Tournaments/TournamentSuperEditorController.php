<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\Graph\TournamentSuperEditorService;
use App\Services\Tournaments\TournamentTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| TournamentSuperEditorController
|--------------------------------------------------------------------------
|
| La Super Edicion de un torneo: una sola pantalla completa donde se ve y se
| edita el recorrido entero.
|
| Este controlador hace MUY poco a proposito. Todo el CRUD del grafo -crear
| una fase, un inicio, un terminal, una conexion- ya existe en sus propios
| controladores y todos ellos responden con `back()`, asi que sus formularios
| funcionan desde aqui sin tocar ni una ruta.
|
| Lo unico que se anade es lo que no tenia dueno:
|
|   show      la pantalla
|   preview   el mismo payload en JSON, para refrescar sin recargar
|   update    la identidad del torneo, que se edita en la cabecera
|
*/
class TournamentSuperEditorController extends Controller
{
    public function __construct(
        private readonly TournamentSuperEditorService $editor,
        private readonly TournamentTemplateService $templates
    ) {}

    public function show(
        Request $request,
        TournamentTemplate $tournamentTemplate
    ): View {

        $this->authorize('update', $tournamentTemplate);

        return view(
            'tournaments.super.editor',
            [
                'tournamentTemplate' => $tournamentTemplate,
                'payload' => $this->editor->payload($tournamentTemplate, $request->user()),
                'availablePhases' => $this->availablePhases($request, $tournamentTemplate),
            ]
        );
    }

    public function preview(
        Request $request,
        TournamentTemplate $tournamentTemplate
    ): JsonResponse {

        $this->authorize('update', $tournamentTemplate);

        return response()->json(
            $this->editor->payload($tournamentTemplate, $request->user())
        );
    }

    /*
     * La identidad del torneo.
     *
     * Reutiliza el servicio de plantillas para que la imagen se guarde y se
     * limpie igual que en la pantalla de edicion de siempre: dos formas de
     * subir la misma imagen acaban siendo dos formas de dejar basura en el
     * disco.
     */
    public function update(
        Request $request,
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {

        $this->authorize('update', $tournamentTemplate);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],

            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],

            'min_participants' => ['nullable', 'integer', 'min:2', 'max:1024'],
            'max_participants' => ['nullable', 'integer', 'min:2', 'max:1024', 'gte:min_participants'],

            'allow_byes' => ['boolean'],

            'status' => ['required', Rule::in(['DRAFT', 'ACTIVE', 'ARCHIVED'])],
            'visibility' => ['required', Rule::in(['PRIVATE', 'UNLISTED', 'PUBLIC'])],
        ]);

        $this->templates->update(
            $tournamentTemplate,
            $data,
            $request->file('image')
        );

        return redirect()
            ->route('tournaments.super.show', $tournamentTemplate)
            ->with('success', 'Torneo guardado.');
    }

    /*
     * Las fases que se pueden meter en el torneo.
     *
     * Solo las del usuario y solo las activas: meter una fase archivada
     * crearia un torneo que no se puede jugar sin decir por que.
     */
    private function availablePhases(Request $request, TournamentTemplate $tournament): array
    {
        return \App\Models\PhaseTemplate::query()
            ->ownedBy($request->user())
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn ($phase) => [
                'id' => $phase->id,
                'code' => $phase->code,
                'name' => $phase->name,
                'type' => $phase->phase_type,
                'type_label' => $phase->type_label,
                'contract' => $phase->participant_contract_label,
                'image_url' => $phase->image_url,
            ])
            ->all();
    }
}
