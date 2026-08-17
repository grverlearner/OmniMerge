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

    /**
     * Permite que Builder, validadores y Runtime consulten la misma verdad
     * sobre compatibilidad sin duplicar listas de motores en cada capa.
     */
    public function supports(
        string $phaseType
    ): bool {
        foreach (
            $this->engines()
            as
            $engine
        ) {
            if ($engine->supports($phaseType)) {
                return true;
            }
        }

        return false;
    }

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

    public function submitSelection(
        string $phaseType,
        array $runtime,
        string $matchId,
        array $qualifierIds
    ): array {
        if (
            $phaseType
            !==
            'SINGLE_ELIMINATION'
        ) {
            $this->failSelection();
        }

        return $this->singleElimination
            ->submitSelection(
                $runtime,
                $matchId,
                $qualifierIds
            );
    }

    public function simulateSelection(
        string $phaseType,
        array $runtime,
        string $matchId
    ): array {
        if (
            $phaseType
            !==
            'SINGLE_ELIMINATION'
        ) {
            $this->failSelection();
        }

        return $this->singleElimination
            ->simulateSelection(
                $runtime,
                $matchId
            );
    }
    
    /**
     * @return array<int, LabPhaseEngine>
     */
    private function engines(): array
    {
        return [
            $this->singleElimination,
            $this->roundRobin,
            $this->groupStage,
            $this->swiss,
        ];
    }

    private function engine(
        string $phaseType
    ): LabPhaseEngine {
        foreach (
            $this->engines()
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

    private function failSelection(): never
    {
        throw ValidationException::withMessages([
            'match_id' => [
                'Esta fase no admite selección manual de varios clasificados.',
            ],
        ]);
    }
}
