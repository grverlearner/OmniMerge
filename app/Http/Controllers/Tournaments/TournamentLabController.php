<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\ExecuteCompetitionLabActionRequest;
use App\Http\Requests\Tournaments\InitializeCompetitionLabRequest;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\CompetitionLab\CompetitionLabService;
use App\Services\Tournaments\Graph\Flow\TournamentGraphFlowAnalysisService;
use App\Services\Tournaments\Graph\Flow\TournamentGraphFlowValidationService;
use App\Services\Tournaments\Graph\TournamentGraphValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentLabController
extends Controller
{
    public function __construct(
        private readonly
        TournamentGraphValidationService $graphValidationService,

        private readonly
        TournamentGraphFlowAnalysisService $flowAnalysisService,

        private readonly
        TournamentGraphFlowValidationService $flowValidationService,

        private readonly
        CompetitionLabService $labService
    ) {}

    public function index(
        Request $request
    ): View {
        $this->authorize(
            'viewAny',
            TournamentTemplate::class
        );

        $templates =
            TournamentTemplate::query()
            ->ownedBy(
                $request->user()
            )
            ->where(
                'status',
                '!=',
                'ARCHIVED'
            )
            ->withCount([
                'graphNodes',
                'graphStarts',
                'graphTerminals',
            ])
            ->orderBy('name')
            ->get();

        return view(
            'tournaments.lab.index',
            compact('templates')
        );
    }

    public function show(
        TournamentTemplate $tournamentTemplate
    ): View {
        $this->authorize(
            'update',
            $tournamentTemplate
        );

        $this->loadGraph(
            $tournamentTemplate
        );

        $validation =
            $this->validation(
                $tournamentTemplate
            );

        return view(
            'tournaments.lab.workspace',
            [
                'tournamentTemplate' =>
                $tournamentTemplate,

                'validation' =>
                $validation,

                'canInitialize' =>
                $validation['valid'],

                'labPayload' =>
                null,
            ]
        );
    }

    public function initialize(
        InitializeCompetitionLabRequest $request,
        TournamentTemplate $tournamentTemplate
    ): View|RedirectResponse {
        $this->loadGraph(
            $tournamentTemplate
        );

        $validation =
            $this->validation(
                $tournamentTemplate
            );

        if (! $validation['valid']) {
            return redirect()
                ->route(
                    'tournaments.lab.show',
                    $tournamentTemplate
                )
                ->withErrors([
                    'graph' =>
                    'Corrige el Tournament Graph antes de iniciar el Lab.',
                ]);
        }

        $labPayload =
            $this->labService
            ->initialize(
                $tournamentTemplate,
                $request->user(),
                $request->validated()
            );

        return view(
            'tournaments.lab.workspace',
            [
                'tournamentTemplate' =>
                $tournamentTemplate,

                'validation' =>
                $validation,

                'canInitialize' =>
                true,

                'labPayload' =>
                $labPayload,
            ]
        );
    }

    public function action(
        ExecuteCompetitionLabActionRequest $request,
        TournamentTemplate $tournamentTemplate
    ): JsonResponse {
        $result =
            $this->labService
            ->execute(
                $tournamentTemplate,
                $request->user(),
                $request->string(
                    'state_token'
                )->toString(),
                $request->string(
                    'action'
                )->toString(),

                $request->safe()->except([
                    'action',
                    'state_token',
                ])
            );

        return response()->json([
            'ok' => true,
            ...$result,
        ]);
    }

    private function validation(
        TournamentTemplate $template
    ): array {
        $structural =
            $this->graphValidationService
            ->validate(
                $template
            );

        $analysis =
            $this->flowAnalysisService
            ->analyze(
                $template
            );

        $flow =
            $this->flowValidationService
            ->validate(
                $template,
                $analysis
            );

        return [
            'valid' =>
            $structural['valid']
                &&
                $flow['valid'],

            'errors' =>
            collect(
                $structural['errors']
            )
                ->merge(
                    $flow['errors']
                )
                ->unique(
                    fn($problem) =>
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
                $structural['warnings']
            )
                ->merge(
                    $flow['warnings']
                )
                ->unique(
                    fn($problem) =>
                    $problem['code']
                        .
                        ':'
                        .
                        $problem['message']
                )
                ->values()
                ->all(),
        ];
    }

    private function loadGraph(
        TournamentTemplate $template
    ): void {
        $template->load([
            'graphStarts.outgoingConnections',
            'graphNodes.phaseTemplate.exits',
            'graphNodes.entryPorts.incomingConnections',
            'graphTerminals.incomingConnections',
            'graphConnections.sourceStart',
            'graphConnections.sourceNode',
            'graphConnections.sourcePhaseExit',
            'graphConnections.targetEntryPort.node',
            'graphConnections.targetTerminal',
        ]);
    }
}
