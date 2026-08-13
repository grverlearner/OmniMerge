<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StorePhaseEntryPortRequest;
use App\Http\Requests\Tournaments\UpdatePhaseEntryPortRequest;
use App\Models\PhaseEntryPort;
use App\Models\TournamentPhaseNode;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\Graph\TournamentGraphNodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PhaseEntryPortController
extends Controller
{
    public function __construct(
        private readonly
        TournamentGraphNodeService $service
    ) {}


    public function store(
        StorePhaseEntryPortRequest $request,
        TournamentTemplate $tournamentTemplate,
        TournamentPhaseNode $node
    ): JsonResponse|RedirectResponse {
        $this->ensureNodeBelongs(
            $tournamentTemplate,
            $node
        );


        $port =
            $this->service
            ->createEntryPort(
                $node,
                $request->validated()
            );


        if (
            $request->expectsJson()
        ) {
            return response()->json([
                'ok' =>
                true,

                'id' =>
                $port->id,
            ]);
        }


        return back()
            ->with(
                'success',
                'Puerta de entrada creada.'
            );
    }


    public function update(
        UpdatePhaseEntryPortRequest $request,
        TournamentTemplate $tournamentTemplate,
        TournamentPhaseNode $node,
        PhaseEntryPort $entryPort
    ): RedirectResponse {
        $this->ensureBelongs(
            $tournamentTemplate,
            $node,
            $entryPort
        );


        $entryPort->update(
            $request->validated()
        );


        return back()
            ->with(
                'success',
                'Puerta de entrada actualizada.'
            );
    }


    public function destroy(
        TournamentTemplate $tournamentTemplate,
        TournamentPhaseNode $node,
        PhaseEntryPort $entryPort
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $tournamentTemplate
        );


        $this->ensureBelongs(
            $tournamentTemplate,
            $node,
            $entryPort
        );


        if (
            $node
            ->entryPorts()
            ->count()
            <=
            1
        ) {
            abort(
                422,
                'Un Node debe conservar al menos una puerta de entrada.'
            );
        }


        $entryPort->delete();


        if (
            request()->expectsJson()
        ) {
            return response()->json([
                'ok' => true,
            ]);
        }


        return back();
    }


    private function ensureNodeBelongs(
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


    private function ensureBelongs(
        TournamentTemplate $template,
        TournamentPhaseNode $node,
        PhaseEntryPort $entryPort
    ): void {
        $this->ensureNodeBelongs(
            $template,
            $node
        );


        abort_unless(
            $entryPort
                ->tournament_phase_node_id
                ===
                $node->id,
            404
        );
    }
}
