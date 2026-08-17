<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreTournamentTemplateRequest;
use App\Http\Requests\Tournaments\UpdateTournamentTemplateRequest;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\TournamentTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ];


        $query =
            TournamentTemplate::query()
            ->ownedBy(
                $user
            )
            ->withCount(
                'graphNodes'
            )


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
                'sort'
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
        TournamentTemplate $tournamentTemplate
    ): View {

        $this->authorize(
            'view',
            $tournamentTemplate
        );


        $tournamentTemplate
            ->load([
                'graphNodes',

                'graphStarts',

                'graphTerminals',
            ])
            ->loadCount([
                'graphNodes',

                'graphConnections',

                'graphStarts',

                'graphTerminals',
            ]);


        return view(
            'tournaments.templates.show',
            compact(
                'tournamentTemplate'
            )
        );
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
