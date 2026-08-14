<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Models\PhaseTemplate;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\Graph\TournamentGraphLayoutService;
use App\Services\Tournaments\Graph\TournamentGraphValidationService;
use App\Services\Tournaments\Graph\Flow\TournamentGraphFlowAnalysisService;
use App\Services\Tournaments\Graph\Flow\TournamentGraphPayloadService;
use App\Services\Tournaments\Graph\Flow\TournamentGraphFlowValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentGraphController
extends Controller
{
    public function __construct(
        private readonly
        TournamentGraphValidationService $validationService,

        private readonly
        TournamentGraphLayoutService $layoutService,

        private readonly
        TournamentGraphFlowAnalysisService $flowAnalysisService,

        private readonly
        TournamentGraphPayloadService $payloadService,

        private readonly
        TournamentGraphFlowValidationService $flowValidationService
    ) {}

    public function show(
        Request $request,
        TournamentTemplate $tournamentTemplate
    ): View {
        $this->authorize(
            'update',
            $tournamentTemplate
        );

        $tournamentTemplate->load([
            'graphNodes.phaseTemplate.exits' =>
            fn($query) =>
            $query->where('status', 'ACTIVE'),

            'graphNodes.entryPorts.incomingConnections',

            'graphStarts.outgoingConnections',

            'graphTerminals.incomingConnections',

            'graphConnections.sourceStart',

            'graphConnections.sourceNode',

            'graphConnections.sourcePhaseExit',

            'graphConnections.targetEntryPort.node',

            'graphConnections.targetTerminal',
        ]);

        $availablePhaseTemplates =
            PhaseTemplate::query()
            ->ownedBy($request->user())
            ->active()
            ->with([
                'exits' =>
                fn($query) =>
                $query->where(
                    'status',
                    'ACTIVE'
                ),
            ])
            ->orderBy('name')
            ->get();

        $graphValidation =
            $this->validationService->validate(
                $tournamentTemplate
            );

        $flowAnalysis =
            $this->flowAnalysisService->analyze(
                $tournamentTemplate
            );

        $flowValidation =
            $this->flowValidationService->validate(
                $tournamentTemplate,
                $flowAnalysis
            );

        $graphValidation = [
            'valid' =>
            $graphValidation['valid']
                &&
                $flowValidation['valid'],

            'errors' =>
            collect(
                $graphValidation['errors']
            )
                ->merge(
                    $flowValidation['errors']
                )
                ->unique(
                    fn(array $problem) =>
                    $problem['code']
                        .
                        ':'
                        .
                        $problem['message']
                )
                ->values()
                ->all(),

            'warnings' =>
            collect(
                $graphValidation['warnings']
            )
                ->merge(
                    $flowValidation['warnings']
                )
                ->unique(
                    fn(array $problem) =>
                    $problem['code']
                        .
                        ':'
                        .
                        $problem['message']
                )
                ->values()
                ->all(),

            'information' =>
            $flowValidation['information'],

            'forecasts' =>
            $flowValidation['forecasts'],

            'stats' => [
                'starts' =>
                $graphValidation['stats']['starts'],

                'nodes' =>
                $graphValidation['stats']['nodes'],

                'connections' =>
                $graphValidation['stats']['connections'],

                'terminals' =>
                $graphValidation['stats']['terminals'],

                'errors' =>
                count(
                    collect(
                        $graphValidation['errors']
                    )
                        ->merge(
                            $flowValidation['errors']
                        )
                        ->unique(
                            fn(array $problem) =>
                            $problem['code']
                                .
                                ':'
                                .
                                $problem['message']
                        )
                ),

                'warnings' =>
                count(
                    collect(
                        $graphValidation['warnings']
                    )
                        ->merge(
                            $flowValidation['warnings']
                        )
                        ->unique(
                            fn(array $problem) =>
                            $problem['code']
                                .
                                ':'
                                .
                                $problem['message']
                        )
                ),

                'information' =>
                count(
                    $flowValidation['information']
                ),
            ],
        ];

        $graphPayload =
            $this->payloadService->build(
                $tournamentTemplate,
                $flowAnalysis,
                $graphValidation
            );

        return view(
            'tournaments.graph.builder',
            compact(
                'tournamentTemplate',
                'availablePhaseTemplates',
                'graphValidation',
                'flowAnalysis',
                'graphPayload'
            )
        );
    }
    public function validateGraph(
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {
        $this->authorize(
            'update',
            $tournamentTemplate
        );

        $structuralValidation =
            $this->validationService
            ->validate(
                $tournamentTemplate
            );

        $flowAnalysis =
            $this->flowAnalysisService
            ->analyze(
                $tournamentTemplate
            );

        $flowValidation =
            $this->flowValidationService
            ->validate(
                $tournamentTemplate,
                $flowAnalysis
            );

        $errors =
            collect(
                $structuralValidation['errors']
            )
            ->merge(
                $flowValidation['errors']
            )
            ->unique(
                fn(array $problem) =>
                $problem['code']
                    .
                    ':'
                    .
                    $problem['message']
            )
            ->count();

        $warnings =
            collect(
                $structuralValidation['warnings']
            )
            ->merge(
                $flowValidation['warnings']
            )
            ->unique(
                fn(array $problem) =>
                $problem['code']
                    .
                    ':'
                    .
                    $problem['message']
            )
            ->count();

        if ($errors === 0) {
            return back()
                ->with(
                    'success',
                    $warnings > 0
                        ? 'El flujo es ejecutable, pero conserva '
                        .
                        $warnings
                        .
                        ' advertencias.'
                        : 'El Tournament Flow es estructuralmente válido y sus capacidades son compatibles.'
                );
        }

        return back()
            ->with(
                'warning',
                'El Tournament Flow contiene '
                    .
                    $errors
                    .
                    ' problemas bloqueantes y '
                    .
                    $warnings
                    .
                    ' advertencias.'
            );
    }


    public function autoLayout(
        TournamentTemplate $tournamentTemplate
    ): RedirectResponse {
        $this->authorize(
            'update',
            $tournamentTemplate
        );


        $this
            ->layoutService
            ->layout(
                $tournamentTemplate
            );


        return back()
            ->with(
                'success',
                'Auto-layout aplicado.'
            );
    }
}
