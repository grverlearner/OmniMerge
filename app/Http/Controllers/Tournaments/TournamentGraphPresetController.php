<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\ApplyTournamentGraphPresetRequest;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\Graph\Flow\TournamentGraphPresetService;
use Illuminate\Http\RedirectResponse;

class TournamentGraphPresetController
extends Controller
{
    public function __construct(
        private readonly
        TournamentGraphPresetService $presetService
    ) {}

    public function store(
        ApplyTournamentGraphPresetRequest $request,
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {
        $result =
            $this->presetService
            ->apply(
                $tournamentTemplate,
                $request->validated()
            );

        return redirect()
            ->route(
                'tournaments.graph.show',
                $tournamentTemplate
            )
            ->with(
                'success',
                'Estructura generada: '
                    .
                    $result['starts']
                    .
                    ' inicios, '
                    .
                    $result['nodes']
                    .
                    ' fases y '
                    .
                    $result['terminals']
                    .
                    ' destinos finales.'
            );
    }
}
