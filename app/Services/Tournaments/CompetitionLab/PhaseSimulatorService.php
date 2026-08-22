<?php

namespace App\Services\Tournaments\CompetitionLab;

use App\Models\PhaseTemplate;
use App\Models\User;
use App\Services\Tournaments\CompetitionLab\Engines\LabPhaseEngineManager;
use App\Services\Tournaments\CompetitionLab\Runtime\RuntimeOutcomeResolver;
use Illuminate\Validation\ValidationException;

/**
 * Orquestador delgado del Simulador de fase: reutiliza exactamente los
 * mismos motores de ejecución que el Competition Lab (LabPhaseEngineManager,
 * RuntimeOutcomeResolver), pero sin ningún TournamentTemplate ni Tournament
 * Graph de por medio. Opera siempre sobre UNA sola PhaseTemplate suelta con
 * participantes ficticios generados en memoria. No escribe nada en base de
 * datos: todo el estado viaja cifrado en el state_token (mismo mecanismo que
 * el Competition Lab).
 */
class PhaseSimulatorService
{
    public function __construct(
        private readonly PhaseSimulatorStateFactory $stateFactory,

        private readonly LabStateTokenService $tokenService,

        private readonly LabPhaseEngineManager $engineManager,

        private readonly RuntimeOutcomeResolver $outcomeResolver
    ) {}

    public function initialize(
        PhaseTemplate $phaseTemplate,
        User $user,
        array $configuration
    ): array {
        $state = $this->stateFactory->create(
            $phaseTemplate,
            $user,
            $configuration['participants'] ?? []
        );

        return $this->response($state);
    }

    public function execute(
        PhaseTemplate $phaseTemplate,
        User $user,
        string $token,
        string $action,
        array $payload = []
    ): array {
        $state = $this->tokenService->decode($token);

        $this->validateOwnership($state, $phaseTemplate, $user);

        $phaseTemplate->loadMissing(
            $this->eagerLoadRelationsFor($phaseTemplate->phase_type)
        );

        $state = match ($action) {
            'PREPARE_PHASE' => $this->preparePhase($state, $phaseTemplate),

            'SUBMIT_MATCH_RESULT' => $this->submitResult(
                $state,
                $phaseTemplate,
                (string) ($payload['match_id'] ?? ''),
                (int) ($payload['score_a'] ?? 0),
                (int) ($payload['score_b'] ?? 0)
            ),

            'SUBMIT_ENCOUNTER_RESULT' => $this->submitEncounterResult(
                $state,
                $phaseTemplate,
                (string) ($payload['match_id'] ?? ''),
                array_values($payload['qualifier_ids'] ?? [])
            ),

            'SIMULATE_MATCH' => $this->simulateMatch(
                $state,
                $phaseTemplate,
                (string) ($payload['match_id'] ?? '')
            ),

            /*
             * Sin payload simula la primera jornada pendiente, que es como
             * se comportaba. Con round_number/group_id simula la que el
             * usuario haya elegido.
             */
            'SIMULATE_ROUND' => $this->simulateRound(
                $state,
                $phaseTemplate,
                $payload
            ),

            'SIMULATE_GROUP' => $this->simulateScope(
                $state,
                $phaseTemplate,
                ['group_id' => $payload['group_id'] ?? null],
                'El grupo fue simulado completamente.'
            ),

            'SIMULATE_ALL' => $this->simulateScope(
                $state,
                $phaseTemplate,
                [],
                'La fase fue simulada completamente.'
            ),

            'RESOLVE_MANUAL_DECISION' => $this->resolveManualDecision($state, $phaseTemplate, $payload),

            'RESET' => $this->reset($state),

            default => $this->fail('La acción solicitada no está disponible.'),
        };

        return $this->response($state);
    }

    private function preparePhase(
        array $state,
        PhaseTemplate $phaseTemplate
    ): array {
        if ($state['runtime'] !== null) {
            $this->fail('Esta simulación ya fue preparada. Reinícala para volver a generarla.');
        }

        $participantIds = array_keys($state['participants']);

        $runtime = $this->engineManager->prepare(
            $phaseTemplate,
            $participantIds,
            $state['participants']
        );

        $state = $this->applyRuntimeState($state, $phaseTemplate, $runtime, $participantIds);

        $this->addEvent(
            $state,
            'PHASE_PREPARED',
            'SUCCESS',
            'La simulación fue generada con ' . count($participantIds) . ' participantes.'
        );

        return $state;
    }

    private function resolveManualDecision(
        array $state,
        PhaseTemplate $phaseTemplate,
        array $payload
    ): array {
        $runtime = $state['runtime'] ?? null;

        if (! $runtime || ($runtime['status'] ?? null) !== 'AWAITING_DECISION') {
            $this->fail('La simulación no está esperando una decisión manual.');
        }

        $runtime = $this->engineManager->resolveDecision(
            $phaseTemplate,
            $runtime,
            $state['participants'],
            $payload
        );

        $state = $this->applyRuntimeState(
            $state,
            $phaseTemplate,
            $runtime,
            array_keys($state['participants'])
        );

        $this->addEvent(
            $state,
            'MANUAL_DECISION_RESOLVED',
            'SUCCESS',
            'La decisión manual fue validada y la simulación puede continuar.'
        );

        return $state;
    }

    private function submitResult(
        array $state,
        PhaseTemplate $phaseTemplate,
        string $matchId,
        int $scoreA,
        int $scoreB,
        bool $registerEvent = true
    ): array {
        $runtime = $this->requireRuntime($state);

        $runtime = $this->engineManager->submit(
            $phaseTemplate->phase_type,
            $runtime,
            $matchId,
            $scoreA,
            $scoreB
        );

        $state = $this->applyRuntimeState(
            $state,
            $phaseTemplate,
            $runtime,
            array_keys($state['participants'])
        );

        if ($registerEvent) {
            $series = $runtime['series'][$matchId] ?? null;
            $completed = ($series['status'] ?? null) === 'COMPLETED';

            $this->addEvent(
                $state,
                $completed ? 'MATCH_COMPLETED' : 'SERIES_GAME_RECORDED',
                $completed ? 'SUCCESS' : 'INFO',
                $completed
                    ? "El encuentro {$matchId} fue completado."
                    : "Juego {$scoreA}-{$scoreB} registrado en {$matchId}."
            );
        }

        return $state;
    }

    private function submitEncounterResult(
        array $state,
        PhaseTemplate $phaseTemplate,
        string $matchId,
        array $qualifierIds,
        bool $registerEvent = true
    ): array {
        $runtime = $this->requireRuntime($state);

        $runtime = $this->engineManager->submitSelection(
            $phaseTemplate->phase_type,
            $runtime,
            $matchId,
            $qualifierIds
        );

        $state = $this->applyRuntimeState(
            $state,
            $phaseTemplate,
            $runtime,
            array_keys($state['participants'])
        );

        if ($registerEvent) {
            $this->addEvent(
                $state,
                'ENCOUNTER_COMPLETED',
                'SUCCESS',
                count($qualifierIds) . " clasificado(s) registrados en {$matchId}."
            );
        }

        return $state;
    }

    private function simulateMatch(
        array $state,
        PhaseTemplate $phaseTemplate,
        string $matchId,
        bool $registerEvent = true
    ): array {
        $runtime = $this->requireRuntime($state);

        if (($runtime['mode'] ?? null) === 'STRUCTURE_GRAPH') {
            $match = collect($runtime['rounds'] ?? [])
                ->flatMap(fn($round) => $round['matches'] ?? [])
                ->firstWhere('id', $matchId);

            if (
                $match
                && ($match['resolution_mode'] ?? null) === 'SCORE'
                && count($match['participant_ids'] ?? []) === 2
                && (int) ($match['qualifiers_count'] ?? 1) === 1
            ) {
                [$scoreA, $scoreB] = $this->randomScore($runtime);

                return $this->submitResult($state, $phaseTemplate, $matchId, $scoreA, $scoreB, $registerEvent);
            }

            return $this->simulateSelection($state, $phaseTemplate, $matchId, $registerEvent);
        }

        [$scoreA, $scoreB] = $this->randomScore($runtime);

        return $this->submitResult($state, $phaseTemplate, $matchId, $scoreA, $scoreB, $registerEvent);
    }

    private function simulateSelection(
        array $state,
        PhaseTemplate $phaseTemplate,
        string $matchId,
        bool $registerEvent = true
    ): array {
        $runtime = $this->requireRuntime($state);

        $runtime = $this->engineManager->simulateSelection(
            $phaseTemplate->phase_type,
            $runtime,
            $matchId
        );

        $state = $this->applyRuntimeState(
            $state,
            $phaseTemplate,
            $runtime,
            array_keys($state['participants'])
        );

        if ($registerEvent) {
            $this->addEvent(
                $state,
                'ENCOUNTER_SIMULATED',
                'INFO',
                "El encuentro {$matchId} fue simulado."
            );
        }

        return $state;
    }

    private function simulateRound(
        array $state,
        PhaseTemplate $phaseTemplate,
        array $payload = []
    ): array {
        $runtime = $this->requireRuntime($state);

        if (($runtime['status'] ?? null) !== 'RUNNING') {
            $this->fail('La simulación ya está completada.');
        }

        $isExecutableMatch = $this->executableMatchFilter($runtime);

        $wanted = $payload['round_number'] ?? null;
        $wantedGroup = $payload['group_id'] ?? null;

        $round = collect($runtime['rounds'] ?? [])
            ->first(
                function ($round) use ($isExecutableMatch, $wanted, $wantedGroup) {

                    if (
                        $wanted !== null
                        && (int) ($round['number'] ?? 0) !== (int) $wanted
                    ) {
                        return false;
                    }

                    if (
                        $wantedGroup !== null
                        && (string) ($round['group_id'] ?? '') !== (string) $wantedGroup
                    ) {
                        return false;
                    }

                    return collect($round['matches'] ?? [])->contains($isExecutableMatch);
                }
            );

        if (! $round) {
            $this->fail('Esa jornada no tiene encuentros pendientes.');
        }

        $pendingMatchIds = collect($round['matches'])
            ->filter($isExecutableMatch)
            ->pluck('id')
            ->values()
            ->all();

        if ($pendingMatchIds === []) {
            $this->fail('La ronda no tiene encuentros ejecutables.');
        }

        foreach ($pendingMatchIds as $matchId) {
            $state = $this->simulateMatch($state, $phaseTemplate, $matchId, false);
        }

        $this->addEvent(
            $state,
            'ROUND_SIMULATED',
            'SUCCESS',
            'La ronda ' . ($round['label'] ?? '') . ' fue simulada completamente.'
        );

        return $state;
    }

    /**
     * Qué encuentros puede resolver el motor ahora mismo. Se comparte
     * entre simular una jornada, un grupo y la fase entera para que las
     * tres usen exactamente el mismo criterio.
     */
    private function executableMatchFilter(array $runtime): callable
    {
        $isStructureGraph = ($runtime['mode'] ?? null) === 'STRUCTURE_GRAPH';

        return fn(array $match): bool =>
            ($match['status'] ?? null) === 'PENDING'
            && (
                $isStructureGraph
                    ? count($match['participant_ids'] ?? []) >= (int) ($match['qualifiers_count'] ?? 1)
                    : ! empty($match['participant_a_id']) && ! empty($match['participant_b_id'])
            );
    }

    /**
     * Simula en bloque todo lo pendiente dentro de un ámbito: un grupo, o
     * la fase entera si no se acota.
     *
     * Va por pasadas porque resolver una jornada puede abrir la siguiente:
     * una sola barrida dejaría a medias las fases que generan calendario
     * sobre la marcha.
     */
    private function simulateScope(
        array $state,
        PhaseTemplate $phaseTemplate,
        array $scope,
        string $message
    ): array {

        $group = $scope['group_id'] ?? null;

        $simulated = 0;

        for ($pass = 0; $pass < 60; $pass++) {

            $runtime = $this->requireRuntime($state);

            if (($runtime['status'] ?? null) !== 'RUNNING') {
                break;
            }

            $isExecutableMatch = $this->executableMatchFilter($runtime);

            $pending = collect($runtime['rounds'] ?? [])
                ->filter(
                    fn($round) =>
                    $group === null
                        || (string) ($round['group_id'] ?? '') === (string) $group
                )
                ->flatMap(fn($round) => $round['matches'] ?? [])
                ->filter($isExecutableMatch)
                ->pluck('id')
                ->values()
                ->all();

            if ($pending === []) {
                break;
            }

            foreach ($pending as $matchId) {
                $state = $this->simulateMatch($state, $phaseTemplate, $matchId, false);
                $simulated++;
            }
        }

        if ($simulated === 0) {
            $this->fail('No quedaban encuentros pendientes que simular.');
        }

        $this->addEvent(
            $state,
            'BULK_SIMULATED',
            'SUCCESS',
            $message . ' (' . $simulated . ' encuentros)'
        );

        return $state;
    }

    private function reset(array $state): array
    {
        $state['runtime'] = null;
        $state['exits_summary'] = null;
        $state['status'] = 'READY';

        foreach ($state['participants'] as $labId => $participant) {
            $state['participants'][$labId]['status'] = 'WAITING';
            $state['participants'][$labId]['manual_bye'] = false;

            $state['participants'][$labId]['current_location'] = [
                'type' => 'SIMULATOR',
                'name' => 'Participantes de la simulación',
            ];
        }

        $this->addEvent(
            $state,
            'SIMULATION_RESET',
            'INFO',
            'La simulación volvió a su estado inicial con los mismos participantes.'
        );

        return $state;
    }

    private function applyRuntimeState(
        array $state,
        PhaseTemplate $phaseTemplate,
        array $runtime,
        array $participantIds
    ): array {
        $state['runtime'] = $runtime;
        $state['status'] = $runtime['status'];

        foreach ($participantIds as $participantId) {
            if (! isset($state['participants'][$participantId])) {
                continue;
            }

            $state['participants'][$participantId]['status'] = match (true) {
                $runtime['status'] === 'COMPLETED'
                    && in_array($participantId, $runtime['survivor_ids'] ?? [], true) => 'QUALIFIED',

                $runtime['status'] === 'COMPLETED'
                    && in_array($participantId, $runtime['eliminated_ids'] ?? [], true) => 'ELIMINATED',

                default => 'COMPETING',
            };
        }

        if ($runtime['status'] === 'COMPLETED') {
            $state['exits_summary'] = $this->resolveExitsSummary(
                $phaseTemplate,
                $runtime,
                $participantIds
            );
        }

        return $state;
    }

    /**
     * Algunos motores (hoy: Round Robin y Group Stage) ya resuelven sus
     * propias Phase Exits internamente -con su propia lógica de
     * desempate/corte- y dejan el resultado en $runtime['outcomes']. Volver
     * a invocar RuntimeOutcomeResolver sobre ese mismo runtime produciría
     * una segunda resolución redundante y potencialmente distinta a la que
     * el motor ya decidió. Por eso: si el motor ya resolvió, se normaliza y
     * reutiliza ese resultado; solo se invoca el resolver genérico para
     * motores que no resuelven internamente (hoy, Single Elimination).
     */
    private function resolveExitsSummary(
        PhaseTemplate $phaseTemplate,
        array $runtime,
        array $participantIds
    ): array {
        if ($this->engineResolvesOwnOutcomes($phaseTemplate->phase_type)) {
            $exitsById = $phaseTemplate->exits->keyBy('id');

            $outcomes = collect($runtime['outcomes'] ?? [])
                ->map(fn(array $outcome) => [
                    'exit_id' => (int) $outcome['exit_id'],
                    'exit_name' => $outcome['exit_name'],
                    'selector_type' => $exitsById->get((int) $outcome['exit_id'])?->selector_type ?? 'ENGINE_RULES',
                    'participant_ids' => array_values($outcome['participant_ids']),
                ])
                ->values();

            $selectedIds = $outcomes
                ->flatMap(fn(array $outcome) => $outcome['participant_ids'])
                ->unique()
                ->values()
                ->all();

            return [
                'outcomes' => $outcomes->all(),
                'selected_ids' => $selectedIds,
                'unassigned_ids' => array_values(array_diff($participantIds, $selectedIds)),
            ];
        }

        return $this->outcomeResolver->resolve(
            $phaseTemplate->exits,
            $runtime,
            $participantIds
        );
    }

    private function engineResolvesOwnOutcomes(string $phaseType): bool
    {
        return in_array($phaseType, ['ROUND_ROBIN', 'GROUP_STAGE'], true);
    }

    /**
     * Cada motor ya hace su propio loadMissing() dentro de prepare(); esto
     * solo evita una carga innecesaria de relaciones de otro motor (ej.
     * cargar la estructura interna de Single Elimination para una fase
     * Round Robin, que nunca la tiene).
     */
    private function eagerLoadRelationsFor(string $phaseType): array
    {
        return match ($phaseType) {
            'SINGLE_ELIMINATION' => [
                'singleEliminationSetting',
                'singleEliminationRoundRules',
                'inputGates.outgoingConnections',
                'singleEliminationRounds.encounters.slots',
                'singleEliminationRounds.encounters.results.outgoingConnections',
                'singleEliminationConnections',
                'exits',
            ],

            'ROUND_ROBIN' => [
                'roundRobinSetting',
                'roundRobinTiebreakers',
                'exits',
            ],

            'GROUP_STAGE' => [
                'groupStageSetting',
                'groupStageGroups',
                'groupStageTiebreakers',
                'groupStageAdvancementRules.phaseExit',
                'groupStageAdvancementRules.group',
                'exits',
            ],

            default => ['exits'],
        };
    }

    private function randomScore(array $runtime): array
    {
        $scoreA = random_int(0, 5);
        $scoreB = random_int(0, 5);

        while ($scoreA === $scoreB) {
            $scoreB = random_int(0, 5);
        }

        return [$scoreA, $scoreB];
    }

    private function requireRuntime(array $state): array
    {
        $runtime = $state['runtime'] ?? null;

        if (! $runtime) {
            $this->fail('Primero debes generar la simulación.');
        }

        return $runtime;
    }

    private function validateOwnership(
        array $state,
        PhaseTemplate $phaseTemplate,
        User $user
    ): void {
        if (($state['schema_version'] ?? null) !== 1) {
            $this->fail('La versión del estado temporal no es compatible.');
        }

        if ((int) ($state['user_id'] ?? 0) !== (int) $user->id) {
            $this->fail('El estado temporal pertenece a otro usuario.');
        }

        if ((int) ($state['phase_template_id'] ?? 0) !== (int) $phaseTemplate->id) {
            $this->fail('El estado temporal pertenece a otra fase.');
        }
    }

    private function addEvent(
        array &$state,
        string $type,
        string $level,
        string $message
    ): void {
        $state['timeline'][] = [
            'step' => count($state['timeline']) + 1,
            'type' => $type,
            'level' => $level,
            'message' => $message,
            'created_at' => now()->toIso8601String(),
        ];

        $state['updated_at'] = now()->toIso8601String();
    }

    private function response(array $state): array
    {
        return [
            'state' => $state,
            'state_token' => $this->tokenService->encode($state),
        ];
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'simulator' => [$message],
        ]);
    }
}
