<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreTournamentStartRequest;
use App\Http\Requests\Tournaments\UpdateTournamentGraphPositionRequest;
use App\Http\Requests\Tournaments\UpdateTournamentStartRequest;
use App\Models\TournamentStart;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\Graph\TournamentGraphEndpointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TournamentStartController
extends Controller
{
    public function __construct(
        private readonly
        TournamentGraphEndpointService $service
    ) {}


    public function store(
        StoreTournamentStartRequest $request,
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {
        $this->service
            ->createStart(
                $tournamentTemplate,
                $request->validated()
            );


        return back()
            ->with(
                'success',
                'Punto de inicio creado.'
            );
    }


    public function update(
        UpdateTournamentStartRequest $request,
        TournamentTemplate $tournamentTemplate,
        TournamentStart $start
    ): RedirectResponse {
        $this->ensureBelongs(
            $tournamentTemplate,
            $start
        );


        $this->service
            ->updateStart(
                $start,
                $request->validated()
            );


        return back();
    }


    public function position(
        UpdateTournamentGraphPositionRequest $request,
        TournamentTemplate $tournamentTemplate,
        TournamentStart $start
    ): JsonResponse {
        $this->ensureBelongs(
            $tournamentTemplate,
            $start
        );


        $this->service
            ->updatePosition(
                $start,

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
        TournamentStart $start
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $tournamentTemplate
        );

        $this->ensureBelongs(
            $tournamentTemplate,
            $start
        );


        $this->service
            ->deleteStart(
                $start
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
        TournamentStart $start
    ): void {
        abort_unless(
            $start
                ->tournament_template_id
                ===
                $template->id,
            404
        );
    }
}
