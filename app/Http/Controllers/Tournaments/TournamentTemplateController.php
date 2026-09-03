<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreTournamentTemplateRequest;
use App\Http\Requests\Tournaments\UpdateTournamentTemplateRequest;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\Graph\Preview\TournamentFlowPreviewService;
use App\Services\Tournaments\Graph\TournamentSuperEditorService;
use App\Services\Tournaments\TournamentTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;


class TournamentTemplateController extends Controller
{
    public function __construct(
        private readonly
        TournamentTemplateService $service
    ) {}


    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */


    public function index(
        Request $request
    ): View {

        $this->authorize(
            'viewAny',
            TournamentTemplate::class
        );


        $user =
            $request->user();


        $search =
            trim(
                (string)
                $request->input(
                    'search'
                )
            );


        $status =
            (string)
            $request->input(
                'status',
                ''
            );


        $visibility =
            (string)
            $request->input(
                'visibility',
                ''
            );


        $sort =
            (string)
            $request->input(
                'sort',
                'newest'
            );


        /*
         * Dos filtros mas.
         *
         * La categoria organiza la biblioteca por lo que la plantilla ES
         * -una copa, una liga, un clasificatorio-, y vive en `settings`,
         * asi que se filtra por JSON. El de uso separa lo que ya sostiene
         * torneos de lo que todavia no ha salido del taller, que es la
         * pregunta que uno se hace antes de tocar nada.
         */
        $category =
            (string)
            $request->input(
                'category',
                ''
            );


        $use =
            (string)
            $request->input(
                'use',
                ''
            );


        $base =
            TournamentTemplate::query()
            ->ownedBy(
                $user
            );


        $stats = [

            'total' => (clone $base)
                ->count(),

            'active' => (clone $base)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'draft' => (clone $base)
                ->where(
                    'status',
                    'DRAFT'
                )
                ->count(),

            'public' => (clone $base)
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->count(),

            /* Cuantas ya sostienen algun torneo de algun universo */
            'in_use' => (clone $base)
                ->has('universeTournaments')
                ->count(),
        ];


        $query =
            TournamentTemplate::query()
            ->ownedBy(
                $user
            )
            /*
             * Lo que la ficha cuenta sin abrir la plantilla: cuantas fases
             * tiene, por donde entra la gente, cuantos finales hay y si
             * algun torneo la esta usando. Se pide por adelantado para que
             * dieciocho fichas no sean setenta consultas.
             */
            ->withCount([
                'graphNodes',
                'graphConnections',
                'graphStarts',
                'graphTerminals',
                'universeTournaments',
            ])

            ->with([
                'graphNodes:id,tournament_template_id,phase_template_id,name,sequence_number',
                'graphNodes.phaseTemplate:id,name,phase_type,settings',
                'graphStarts:id,tournament_template_id,name,source_type,expected_participants,sequence_number',
                'graphTerminals:id,tournament_template_id,name,terminal_type,expected_participants,sequence_number',
            ])


            ->when(
                $search,

                fn($query) =>
                $query->where(
                    function ($subquery) use (
                        $search
                    ) {

                        $subquery
                            ->where(
                                'name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'code',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'description',
                                'like',
                                "%{$search}%"
                            );
                    }
                )
            )


            ->when(
                $status,

                fn($query) =>
                $query->where(
                    'status',
                    $status
                )
            )


            ->when(
                $visibility,

                fn($query) =>
                $query->where(
                    'visibility',
                    $visibility
                )
            )


            ->when(
                $category,

                fn($query) =>
                $query->where(
                    'settings->category',
                    $category
                )
            )


            ->when(
                $use === 'used',

                fn($query) =>
                $query->has('universeTournaments')
            )


            ->when(
                $use === 'unused',

                fn($query) =>
                $query->doesntHave('universeTournaments')
            );


        match ($sort) {

            'oldest' =>
            $query->orderBy(
                'created_at'
            ),

            'name_asc' =>
            $query->orderBy(
                'name'
            ),

            'name_desc' =>
            $query->orderByDesc(
                'name'
            ),

            // Conservamos el valor del query-string por compatibilidad con
            // enlaces existentes, pero el conteo vigente es el del Graph.
            'phases_desc' =>
            $query->orderByDesc(
                'graph_nodes_count'
            ),

            'phases_asc' =>
            $query->orderBy(
                'graph_nodes_count'
            ),

            'used_desc' =>
            $query->orderByDesc(
                'universe_tournaments_count'
            ),

            'participants_desc' =>
            $query->orderByDesc(
                'min_participants'
            ),

            default =>
            $query->orderByDesc(
                'created_at'
            ),
        };


        $templates =
            $query
            ->paginate(18)
            ->withQueryString();


        return view(
            'tournaments.templates.index',
            compact(
                'templates',
                'stats',
                'search',
                'status',
                'visibility',
                'sort',
                'category',
                'use'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */


    public function create(
        Request $request
    ): View {

        $this->authorize(
            'create',
            TournamentTemplate::class
        );


        $previewCode =
            $this->service
            ->previewCode(
                $request->user()
            );


        return view(
            'tournaments.templates.create',
            compact(
                'previewCode'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */


    public function store(
        StoreTournamentTemplateRequest $request
    ): RedirectResponse {

        $template =
            $this->service
            ->create(

                $request->user(),

                $request->validated(),

                $request->file(
                    'image'
                )
            );


        return redirect()
            ->route(
                'tournaments.templates.show',
                $template
            )
            ->with(
                'success',
                'Plantilla de torneo creada correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */


    public function show(
        Request $request,
        TournamentTemplate $tournamentTemplate,
        TournamentSuperEditorService $editor
    ): View {

        $this->authorize(
            'view',
            $tournamentTemplate
        );

        $tournamentTemplate->loadCount([
            'graphNodes',
            'graphConnections',
            'graphStarts',
            'graphTerminals',
        ]);

        /*
         * La ficha ensena el torneo COMO SE RECORRE, y para eso necesita el
         * mismo grafo que la Super Edicion: sus niveles, sus rutas, sus
         * vecinos y la silueta de cada fase.
         *
         * Se reutiliza el payload entero en vez de escribir una version "de
         * solo lectura": lo unico que sobra son los botones, y esos no
         * estan en el payload sino en la vista.
         */
        return view(
            'tournaments.templates.dossier',
            [
                'tournamentTemplate' => $tournamentTemplate,
                'payload' => $editor->payload($tournamentTemplate, $request->user()),
            ]
        );
    }

    /*
     * Simular el torneo entero.
     *
     * No hay motor nuevo: lo ejecuta TournamentFlowPreviewService, que ya
     * existia y recorre el grafo repartiendo participantes sinteticos por
     * las rutas hasta que llegan a un terminal o se pierden por el camino.
     *
     * Devuelve JSON porque la ficha lo pinta sin recargar: una simulacion
     * que te cambia de pagina te obliga a volver a buscar donde estabas.
     *
     * Los problemas del grafo NO se ocultan. Si el recorrido tiene errores
     * bloqueantes el servicio se niega a ejecutar, y eso es exactamente lo
     * que hay que ensenar: simular un torneo roto daria un resultado
     * inventado.
     */
    public function simulate(
        Request $request,
        TournamentTemplate $tournamentTemplate,
        TournamentFlowPreviewService $preview
    ): JsonResponse {

        $this->authorize('view', $tournamentTemplate);

        $data = $request->validate([
            'participants' => ['nullable', 'integer', 'min:2', 'max:512'],
            'seed' => ['nullable', 'integer', 'min:1', 'max:999999'],
        ]);

        $tournamentTemplate->load([
            'graphNodes.phaseTemplate.exits',
            'graphNodes.entryPorts',
            'graphStarts',
            'graphTerminals',
            'graphConnections',
        ]);

        $count = (int) ($data['participants']
            ?? $tournamentTemplate->max_participants
            ?? $tournamentTemplate->min_participants
            ?? 16);

        try {
            return response()->json([
                'ok' => true,
                'result' => $preview->preview($tournamentTemplate, [
                    'participant_mode' => 'SYNTHETIC',
                    'resolution_strategy' => 'RANDOM',
                    'seed' => (int) ($data['seed'] ?? random_int(1, 999999)),
                    'starts' => $tournamentTemplate->graphStarts
                        ->map(fn ($start) => [
                            'start_id' => $start->id,
                            'count' => $count,
                            'prefix' => 'P',
                        ])
                        ->all(),
                ]),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'ok' => false,
                'messages' => collect($e->errors())->flatten()->all(),
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */


    public function edit(
        TournamentTemplate $tournamentTemplate
    ): View {

        $this->authorize(
            'update',
            $tournamentTemplate
        );


        return view(
            'tournaments.templates.edit',
            compact(
                'tournamentTemplate'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */


    public function update(
        UpdateTournamentTemplateRequest $request,
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {

        $this->service
            ->update(

                $tournamentTemplate,

                $request->validated(),

                $request->file(
                    'image'
                )
            );


        return redirect()
            ->route(
                'tournaments.templates.show',
                $tournamentTemplate
            )
            ->with(
                'success',
                'Plantilla actualizada correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Duplicate
    |--------------------------------------------------------------------------
    */


    public function duplicate(
        Request $request,
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {

        $this->authorize(
            'duplicate',
            $tournamentTemplate
        );


        $copy =
            $this->service
            ->duplicate(
                $request->user(),
                $tournamentTemplate
            );


        return redirect()
            ->route(
                'tournaments.templates.edit',
                $copy
            )
            ->with(
                'success',
                'Plantilla duplicada. La copia empieza como borrador privado.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Archive
    |--------------------------------------------------------------------------
    */


    public function archive(
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {

        $this->authorize(
            'update',
            $tournamentTemplate
        );


        $this->service
            ->archive(
                $tournamentTemplate
            );


        return redirect()
            ->route(
                'tournaments.templates.show',
                $tournamentTemplate
            )
            ->with(
                'success',
                'Plantilla archivada correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */


    public function destroy(
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {

        $this->authorize(
            'delete',
            $tournamentTemplate
        );


        $this->service
            ->delete(
                $tournamentTemplate
            );


        return redirect()
            ->route(
                'tournaments.templates.index'
            )
            ->with(
                'success',
                'Plantilla eliminada correctamente.'
            );
    }
}
