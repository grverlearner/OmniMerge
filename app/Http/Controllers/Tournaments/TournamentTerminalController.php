<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreTournamentTerminalRequest;
use App\Http\Requests\Tournaments\UpdateTournamentGraphPositionRequest;
use App\Http\Requests\Tournaments\UpdateTournamentTerminalRequest;
use App\Models\TournamentTemplate;
use App\Models\TournamentTerminal;
use App\Services\Tournaments\Graph\TournamentGraphEndpointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TournamentTerminalController
extends Controller
{
    public function __construct(
        private readonly
        TournamentGraphEndpointService $service
    ) {}


    public function store(
        StoreTournamentTerminalRequest $request,
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {
        $this->service
            ->createTerminal(
                $tournamentTemplate,
                $request->validated()
            );


        return back()
            ->with(
                'success',
                'Terminal creado.'
            );
    }


    public function update(
        UpdateTournamentTerminalRequest $request,
        TournamentTemplate $tournamentTemplate,
        TournamentTerminal $terminal
    ): RedirectResponse {
        $this->ensureBelongs(
            $tournamentTemplate,
            $terminal
        );


        $this->service
            ->updateTerminal(
                $terminal,
                $request->validated()
            );


        return back();
    }


    public function position(
        UpdateTournamentGraphPositionRequest $request,
        TournamentTemplate $tournamentTemplate,
        TournamentTerminal $terminal
    ): JsonResponse {
        $this->ensureBelongs(
            $tournamentTemplate,
            $terminal
        );


        $this->service
            ->updatePosition(
                $terminal,

                $request->integer(
                    'x_position'
                ),

                $request->integer(
                    'y_position'
                )
            );


        return response()->json([
            'ok' => true,
        ]);
    }


    public function destroy(
        TournamentTemplate $tournamentTemplate,
        TournamentTerminal $terminal
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $tournamentTemplate
        );

        $this->ensureBelongs(
            $tournamentTemplate,
            $terminal
        );


        $this->service
            ->deleteTerminal(
                $terminal
            );


        if (
            request()->expectsJson()
        ) {
            return response()->json([
                'ok' => true,
            ]);
        }


        return back();
    }


    private function ensureBelongs(
        TournamentTemplate $template,
        TournamentTerminal $terminal
    ): void {
        abort_unless(
            $terminal
                ->tournament_template_id
                ===
                $template->id,
            404
        );
    }
}
