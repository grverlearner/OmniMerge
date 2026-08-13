<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreTournamentPhaseConnectionRequest;
use App\Http\Requests\Tournaments\UpdateTournamentPhaseConnectionRequest;
use App\Models\TournamentPhaseConnection;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\Graph\TournamentGraphConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TournamentPhaseConnectionController
extends Controller
{
    public function __construct(
        private readonly
        TournamentGraphConnectionService $service
    ) {}


    public function store(
        StoreTournamentPhaseConnectionRequest $request,
        TournamentTemplate $tournamentTemplate
    ): JsonResponse|RedirectResponse {
        $connection =
            $this->service
            ->create(
                $tournamentTemplate,
                $request->validated()
            );


        if (
            $request->expectsJson()
        ) {
            return response()->json([
                'ok' =>
                true,

                'id' =>
                $connection->id,
            ]);
        }


        return back()
            ->with(
                'success',
                'Conexión creada.'
            );
    }


    public function update(
        UpdateTournamentPhaseConnectionRequest $request,
        TournamentTemplate $tournamentTemplate,
        TournamentPhaseConnection $connection
    ): JsonResponse|RedirectResponse {
        $this->ensureBelongs(
            $tournamentTemplate,
            $connection
        );


        $connection =
            $this->service
            ->update(
                $connection,
                $request->validated()
            );


        if (
            $request->expectsJson()
        ) {
            return response()->json([
                'ok' =>
                true,

                'connection' => [
                    'id' =>
                    $connection->id,

                    'allocation_mode' =>
                    $connection
                        ->allocation_mode,

                    'allocation_value' =>
                    $connection
                        ->allocation_value,
                ],
            ]);
        }


        return back();
    }


    public function destroy(
        TournamentTemplate $tournamentTemplate,
        TournamentPhaseConnection $connection
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $tournamentTemplate
        );

        $this->ensureBelongs(
            $tournamentTemplate,
            $connection
        );


        $this->service
            ->delete(
                $connection
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
        TournamentPhaseConnection $connection
    ): void {
        abort_unless(
            $connection
                ->tournament_template_id
                ===
                $template->id,
            404
        );
    }
}
