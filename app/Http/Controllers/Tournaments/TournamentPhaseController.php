<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreTournamentPhaseRequest;
use App\Http\Requests\Tournaments\UpdateTournamentPhaseRequest;
use App\Models\TournamentPhase;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\TournamentPhaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;


class TournamentPhaseController extends Controller
{
    public function __construct(
        private readonly
        TournamentPhaseService $service
    ) {}


    public function index(
        TournamentTemplate $tournamentTemplate
    ): View {

        $this->authorize(
            'view',
            $tournamentTemplate
        );


        $tournamentTemplate
            ->load(
                'phases'
            );


        return view(
            'tournaments.phases.index',
            compact(
                'tournamentTemplate'
            )
        );
    }


    public function create(
        TournamentTemplate $tournamentTemplate
    ): View {

        $this->authorize(
            'update',
            $tournamentTemplate
        );


        return view(
            'tournaments.phases.create',
            compact(
                'tournamentTemplate'
            )
        );
    }


    public function store(
        StoreTournamentPhaseRequest $request,
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {

        $this->service
            ->create(
                $tournamentTemplate,
                $request->validated()
            );


        return redirect()
            ->route(
                'tournaments.phases.index',
                $tournamentTemplate
            )
            ->with(
                'success',
                'Fase creada correctamente.'
            );
    }


    public function edit(
        TournamentTemplate $tournamentTemplate,
        TournamentPhase $phase
    ): View {

        $this->ensureBelongsToTemplate(
            $tournamentTemplate,
            $phase
        );


        $this->authorize(
            'update',
            $tournamentTemplate
        );


        return view(
            'tournaments.phases.edit',
            compact(
                'tournamentTemplate',
                'phase'
            )
        );
    }


    public function update(
        UpdateTournamentPhaseRequest $request,
        TournamentTemplate $tournamentTemplate,
        TournamentPhase $phase
    ): RedirectResponse {

        $this->ensureBelongsToTemplate(
            $tournamentTemplate,
            $phase
        );


        $this->service
            ->update(
                $phase,
                $request->validated()
            );


        return redirect()
            ->route(
                'tournaments.phases.index',
                $tournamentTemplate
            )
            ->with(
                'success',
                'Fase actualizada correctamente.'
            );
    }


    public function destroy(
        TournamentTemplate $tournamentTemplate,
        TournamentPhase $phase
    ): RedirectResponse {

        $this->ensureBelongsToTemplate(
            $tournamentTemplate,
            $phase
        );


        $this->authorize(
            'update',
            $tournamentTemplate
        );


        $this->service
            ->delete(
                $phase
            );


        return redirect()
            ->route(
                'tournaments.phases.index',
                $tournamentTemplate
            )
            ->with(
                'success',
                'Fase eliminada correctamente.'
            );
    }


    private function ensureBelongsToTemplate(
        TournamentTemplate $template,
        TournamentPhase $phase
    ): void {

        abort_unless(
            $phase->tournament_template_id
                ===
                $template->id,
            404
        );
    }
}
