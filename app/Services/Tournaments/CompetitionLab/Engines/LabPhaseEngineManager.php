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
        RoundRobinLabEngine $roundRobin
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
            ]
            as $engine
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
                'T9.2 solo puede ejecutar fases Single Elimination y Round Robin.',
            ],
        ]);
    }
}
