<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\CompetitionLab\Runtime\MatchSeriesRuntime;
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
        SwissLabEngine $swiss,

        private readonly
        LabManualDecisionManager $manualDecisions,

        private readonly
        MatchSeriesRuntime $seriesRuntime
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

    /**
     * @param  array<int,array<int,string>>  $byPort  quién llegó por cada
     *         puerta de entrada, en el orden de las puertas. Lo usa una fase
     *         de grupos manual para no volver a preguntar un reparto que el
     *         recorrido ya trazó. Ver LabManualDecisionManager::orderingFromPorts().
     */
    public function prepare(
        PhaseTemplate $phase,
        array $participantIds,
        array $participants,
        array $byPort = []
    ): array {

        $porPuertas = $this->manualDecisions
            ->orderingFromPorts($phase, $participantIds, $byPort);

        if ($porPuertas === null) {

            $pending = $this->manualDecisions
                ->preparationDecision($phase, $participantIds);

            if ($pending !== null) {
                return $pending;
            }
        } else {

            /*
             * El motor reparte en grupos siguiendo el orden de la lista, que
             * es exactamente como se resuelve un reparto manual. Ordenarla
             * por puertas es, por tanto, repartir por puertas.
             */
            $participantIds = $porPuertas['order'];
        }

        $runtime = $this
            ->engine(
                $phase->phase_type
            )
            ->prepare(
                $phase,
                $participantIds,
                $participants
            );

        /* Y se deja dicho de dónde salió el reparto, para poder enseñarlo */
        if ($porPuertas !== null) {
            $runtime['group_source'] = $porPuertas['map'];
        }

        return $runtime;
    }

    public function resolveDecision(
        PhaseTemplate $phase,
        array $runtime,
        array $participants,
        array $payload
    ): array {
        $engine = $this->engine($phase->phase_type);
        $decision = $runtime['manual_decision'] ?? null;

        if (! is_array($decision)) {
            throw ValidationException::withMessages([
                'manual_decision' => [
                    'Esta fase no tiene una decisión manual pendiente.',
                ],
            ]);
        }

        if (($decision['scope'] ?? null) === 'PREPARATION') {
            return $this->manualDecisions->resolvePreparation(
                $phase,
                $runtime,
                $participants,
                $payload,
                fn(array $ids, array $prepared): array =>
                    $engine->prepare($phase, $ids, $prepared)
            );
        }

        if (! $engine instanceof SupportsManualDecision) {
            throw ValidationException::withMessages([
                'manual_decision' => [
                    'El motor de esta fase no admite la decisión pendiente.',
                ],
            ]);
        }

        return $engine->resolveManualDecision($runtime, $payload);
    }

    /**
     * @param  float $pointsA  puntos reales del enfrentamiento, cuando el
     * @param  float $pointsB  juego los registra. Deciden una serie de
     *                         cantidad fija empatada en enfrentamientos.
     */
    public function submit(
        string $phaseType,
        array $runtime,
        string $matchId,
        int $scoreA,
        int $scoreB,
        float $pointsA = 0.0,
        float $pointsB = 0.0
    ): array {
        $series = $this->seriesRuntime->submitGame(
            $runtime,
            $matchId,
            $scoreA,
            $scoreB,
            $phaseType === 'SINGLE_ELIMINATION',
            $pointsA,
            $pointsB
        );

        if (! $series['completed']) {
            return $series['runtime'];
        }

        return $this
            ->engine(
                $phaseType
            )
            ->submit(
                $series['runtime'],
                $matchId,
                $series['engine_score_a'],
                $series['engine_score_b']
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
