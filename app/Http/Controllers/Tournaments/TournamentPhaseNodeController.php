<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreTournamentPhaseNodeRequest;
use App\Http\Requests\Tournaments\UpdateTournamentGraphPositionRequest;
use App\Http\Requests\Tournaments\UpdateTournamentPhaseNodeRequest;
use App\Models\PhaseTemplate;
use App\Models\TournamentPhaseNode;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\Graph\TournamentGraphNodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TournamentPhaseNodeController
extends Controller
{
    public function __construct(
        private readonly
        TournamentGraphNodeService $service
    ) {}


    public function store(
        StoreTournamentPhaseNodeRequest $request,
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {
        $phaseTemplate =
            PhaseTemplate::query()
            ->findOrFail(
                $request->integer(
                    'phase_template_id'
                )
            );


        $this->service
            ->create(
                $tournamentTemplate,
                $phaseTemplate,
                $request->validated()
            );


        return back()
            ->with(
                'success',
                'Fase colocada en el Tournament Graph.'
            );
    }


    public function update(
        UpdateTournamentPhaseNodeRequest $request,
        TournamentTemplate $tournamentTemplate,
        TournamentPhaseNode $node
    ): RedirectResponse {
        $this->ensureBelongs(
            $tournamentTemplate,
            $node
        );


        $this->service
            ->update(
                $node,
                $request->validated()
            );


        return back()
            ->with(
                'success',
                'Node actualizado.'
            );
    }


    public function position(
        UpdateTournamentGraphPositionRequest $request,
        TournamentTemplate $tournamentTemplate,
        TournamentPhaseNode $node
    ): JsonResponse {
        $this->ensureBelongs(
            $tournamentTemplate,
            $node
        );


        $node =
            $this->service
            ->updatePosition(
                $node,

                $request->integer(
                    'x_position'
                ),

                $request->integer(
                    'y_position'
                )
            );


        return response()->json([
            'ok' =>
            true,

            'x' =>
            $node->x_position,

            'y' =>
            $node->y_position,
        ]);
    }


    public function duplicate(
        TournamentTemplate $tournamentTemplate,
        TournamentPhaseNode $node
    ): RedirectResponse {
        $this->authorize(
            'update',
            $tournamentTemplate
        );

        $this->ensureBelongs(
            $tournamentTemplate,
            $node
        );


        $this->service
            ->duplicate(
                $node
            );


        return back()
            ->with(
                'success',
                'Node duplicado.'
            );
    }


    public function destroy(
        TournamentTemplate $tournamentTemplate,
        TournamentPhaseNode $node
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $tournamentTemplate
        );

        $this->ensureBelongs(
            $tournamentTemplate,
            $node
        );


        $this->service
            ->delete(
                $node
            );


        if (
            request()->expectsJson()
        ) {
            return response()->json([
                'ok' => true,
            ]);
        }


        return back()
            ->with(
                'success',
                'Node eliminado del grafo.'
            );
    }


    private function ensureBelongs(
        TournamentTemplate $template,
        TournamentPhaseNode $node
    ): void {
        abort_unless(
            $node
                ->tournament_template_id
                ===
                $template->id,
            404
        );
    }
}
