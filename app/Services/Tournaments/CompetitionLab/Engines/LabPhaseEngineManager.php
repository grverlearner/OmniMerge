<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseTemplate;
use Illuminate\Validation\ValidationException;

class LabPhaseEngineManager
{
    public function __construct(
        private readonly
        SingleEliminationLabEngine $singleElimination,

        private readonly
        RoundRobinLabEngine $roundRobin,

        private readonly
        GroupStageLabEngine $groupStage,

        private readonly
        SwissLabEngine $swiss
    ) {}

    public function prepare(
        PhaseTemplate $phase,
        array $participantIds,
        array $participants
    ): array {
        return $this
            ->engine(
                $phase->phase_type
            )
            ->prepare(
                $phase,
                $participantIds,
                $participants
            );
    }

    public function submit(
        string $phaseType,
        array $runtime,
        string $matchId,
        int $scoreA,
        int $scoreB
    ): array {
        return $this
            ->engine(
                $phaseType
            )
            ->submit(
                $runtime,
                $matchId,
                $scoreA,
                $scoreB
            );
    }

    private function engine(
        string $phaseType
    ): LabPhaseEngine {
        foreach (
            [
                $this->singleElimination,
                $this->roundRobin,
                $this->groupStage,
                $this->swiss,
            ]
            as
            $engine
        ) {
            if (
                $engine->supports(
                    $phaseType
                )
            ) {
                return $engine;
            }
        }

        throw ValidationException::withMessages([
            'node_id' => [
                'El Competition Lab no tiene un motor compatible con esta fase.',
            ],
        ]);
    }
}
