<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\RunTournamentFlowPreviewRequest;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\Graph\Flow\TournamentGraphFlowAnalysisService;
use App\Services\Tournaments\Graph\Flow\TournamentGraphFlowValidationService;
use App\Services\Tournaments\Graph\Preview\TournamentFlowPreviewService;
use App\Services\Tournaments\Graph\TournamentGraphValidationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentFlowPreviewController
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
        TournamentFlowPreviewService $previewService
    ) {}

    public function show(
        Request $request,
        TournamentTemplate $tournamentTemplate
    ): View {
        $this->authorize(
            'update',
            $tournamentTemplate
        );

        $this->loadGraph(
            $tournamentTemplate
        );

        $graphValidation =
            $this->graphValidationService
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

        $canPreview =
            $graphValidation['valid']
            &&
            $flowValidation['valid'];

        return view(
            'tournaments.graph.preview.show',
            [
                'tournamentTemplate' =>
                $tournamentTemplate,

                'graphValidation' =>
                $graphValidation,

                'flowValidation' =>
                $flowValidation,

                'flowAnalysis' =>
                $flowAnalysis,

                'canPreview' =>
                $canPreview,

                'preview' =>
                null,
            ]
        );
    }

    public function run(
        RunTournamentFlowPreviewRequest $request,
        TournamentTemplate $tournamentTemplate
    ): View {
        $this->loadGraph(
            $tournamentTemplate
        );

        $graphValidation =
            $this->graphValidationService
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

        $canPreview =
            $graphValidation['valid']
            &&
            $flowValidation['valid'];

        $preview =
            $this->previewService
            ->preview(
                $tournamentTemplate,
                $request->validated()
            );

        foreach (
            $preview['timeline']
            as
            $index => &$event
        ) {
            $event['step'] =
                $index + 1;
        }

        unset($event);

        return view(
            'tournaments.graph.preview.show',
            compact(
                'tournamentTemplate',
                'graphValidation',
                'flowValidation',
                'flowAnalysis',
                'canPreview',
                'preview'
            )
        );
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
