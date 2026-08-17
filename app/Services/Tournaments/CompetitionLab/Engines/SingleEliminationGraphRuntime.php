<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureExecutionPolicy;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureFingerprint;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureValidator;
use Illuminate\Validation\ValidationException;

class SingleEliminationGraphRuntime
{
    public function __construct(
        private readonly
        SingleEliminationStructureValidator $validator,

        private readonly
        SingleEliminationStructureExecutionPolicy $executionPolicy,

        private readonly
        SingleEliminationStructureFingerprint $fingerprint
    ) {}

    public function prepare(PhaseTemplate $phase, array $participantIds): array
    {
        $phase->loadMissing(
            'singleEliminationSetting'
        );

        $settings =
            $phase->singleEliminationSetting;

        if (! $settings) {
            $this->fail(
                'La fase no tiene configuración Single Elimination.'
            );
        }

        if ($settings->structure_status !== 'VALID') {
            $this->fail(
                'La estructura avanzada no está lista para ejecutarse. Estado actual: '
                . $settings->structure_status_label
                . '.'
            );
        }

        $validation =
            $this->executionPolicy
            ->apply(
                $this->validator
                ->validate(
                    $phase
                )
            );

        if (! $validation['valid'] || ! $validation['executable']) {
            $messages = collect([
                ...$validation['errors'],
                ...$validation['warnings'],
            ])->pluck('message')->unique()->values()->all();

            $this->fail($messages === []
                ? 'El grafo interno no está listo para ejecutarse.'
                : implode(' ', $messages)
            );
        }

        $currentFingerprint =
            $this->fingerprint
            ->forPhase(
                $phase
            );

        $storedFingerprint =
            (string) (
                $settings->structure_fingerprint
                ??
                ''
            );

        if (
            $storedFingerprint === ''
            ||
            ! hash_equals(
                $storedFingerprint,
                $currentFingerprint
            )
        ) {
            $this->fail(
                'La estructura avanzada cambió después de su última validación. '
                . 'Vuelve a validarla antes de ejecutarla.'
            );
        }

        $phase->load([
            'singleEliminationSetting',
            'inputGates.outgoingConnections',
            'singleEliminationRounds.encounters.slots',
            'singleEliminationRounds.encounters.results.outgoingConnections',
            'singleEliminationConnections',
            'exits',
        ]);

        $participantIds =
            array_values(
                $participantIds
            );

        if (
            count($participantIds)
            !==
            count(array_unique($participantIds))
        ) {
            $this->fail(
                'La entrada del grafo interno contiene participantes duplicados.'
            );
        }

        $expected = (int) data_get(
            $phase->singleEliminationSetting?->settings,
            'custom_graph_participants',
            $phase->exact_participants ?? 0
        );

        if ($expected > 0 && count($participantIds) !== $expected) {
            $this->fail("El grafo personalizado necesita exactamente {$expected} participantes.");
        }

        $runtime = [
            'engine' => 'SINGLE_ELIMINATION',
            'mode' => 'STRUCTURE_GRAPH',
            'status' => 'RUNNING',
            'structure_fingerprint' => $currentFingerprint,
            'initial_participant_count' => count($participantIds),
            'rounds' => [],
            'round_order' => [],
            'encounters' => [],
            'slots' => [],
            'results' => [],
            'connections' => [],
            'exit_definitions' => [],
            'exit_participants' => [],
            'outcomes' => [],
            'standings' => [],
            'survivor_ids' => [],
            'eliminated_ids' => [],
            'eliminations' => [],
            'matches_total' => 0,
            'matches_completed' => 0,
            'current_round' => 1,
        ];

        foreach ($phase->exits as $exit) {
            $runtime['exit_definitions'][$exit->id] = [
                'id' => (int) $exit->id,
                'name' => $exit->name,
                'selector_type' => $exit->selector_type,
                'exit_timing' => $exit->exit_timing,
                'selector_round_size' => $exit->selector_round_size === null
                    ? null
                    : (int) $exit->selector_round_size,
            ];
            $runtime['exit_participants'][$exit->id] = [];
        }

        foreach ($phase->singleEliminationConnections as $connection) {
            if ($connection->status !== 'ACTIVE') {
                continue;
            }

            $runtime['connections'][$connection->id] = [
                'id' => (int) $connection->id,
                'source_type' => $connection->source_type,
                'source_id' => (int) ($connection->source_input_gate_id ?? $connection->source_result_id),
                'target_type' => $connection->target_type,
                'target_id' => (int) ($connection->target_slot_id ?? $connection->target_phase_exit_id),
                'allocation_mode' => $connection->allocation_mode,
                'allocation_value' => $connection->allocation_value === null
                    ? null
                    : (int) $connection->allocation_value,
                'priority' => (int) $connection->priority,
            ];
        }

        foreach ($phase->singleEliminationRounds as $round) {
            $runtime['round_order'][] = (int) $round->id;
            $runtime['rounds'][] = [
                'id' => (int) $round->id,
                'number' => (int) $round->stage_number,
                'participants_in_round' => (int) (
                    $round->participants_expected
                    ?: $round->encounters->where('status', 'ACTIVE')->sum('entrants_count')
                ),
                'label' => $round->name,
                'status' => 'WAITING',
                'matches' => [],
            ];

            foreach ($round->encounters as $encounter) {
                if ($encounter->status !== 'ACTIVE') {
                    continue;
                }

                $runtime['encounters'][$encounter->id] = [
                    'id' => (int) $encounter->id,
                    'round_id' => (int) $round->id,
                    'round_number' => (int) $round->stage_number,
                    'round_participants' => (int) (
                        $round->participants_expected
                        ?: $round->encounters->where('status', 'ACTIVE')->sum('entrants_count')
                    ),
                    'match_id' => 'SE-G-' . $encounter->id,
                    'code' => $encounter->code,
                    'name' => $encounter->name,
                    'entrants_count' => (int) $encounter->entrants_count,
                    'qualifiers_count' => (int) $encounter->qualifiers_count,
                    'min_entrants_to_start' => max(
                        1,
                        (int) $encounter->min_entrants_to_start
                    ),
                    'allows_incomplete' => (bool) $encounter->allows_incomplete,
                    'activation_policy' => $encounter->activation_policy,
                    'profile' => $encounter->encounter_profile,
                    'resolution_mode' => data_get($encounter->settings, 'resolution_mode',
                        $encounter->encounter_profile === 'DUEL' ? 'SCORE' : 'RANKING'
                    ),
                    'qualifier_ordering' => data_get($encounter->settings, 'qualifier_ordering', 'ORDERED'),
                    'series_format' => $encounter->series_format,
                    'best_of' => $encounter->best_of,
                    'fixed_games' => $encounter->fixed_games,
                    'status' => 'WAITING',
                    'participant_ids' => [],
                    'qualifier_ids' => [],
                    'eliminated_ids' => [],
                    'score_a' => null,
                    'score_b' => null,
                ];

                foreach ($encounter->slots as $slot) {
                    if ($slot->status !== 'ACTIVE') {
                        continue;
                    }

                    $runtime['slots'][$slot->id] = [
                        'id' => (int) $slot->id,
                        'encounter_id' => (int) $encounter->id,
                        'position' => (int) $slot->position,
                        'required' => (bool) $slot->is_required,
                        'empty_behavior' => $slot->empty_behavior,
                        'participant_id' => null,
                    ];
                }

                foreach ($encounter->results as $result) {
                    if ($result->status !== 'ACTIVE') {
                        continue;
                    }

                    $runtime['results'][$result->id] = [
                        'id' => (int) $result->id,
                        'encounter_id' => (int) $encounter->id,
                        'name' => $result->name,
                        'position_from' => $result->position_from === null ? null : (int) $result->position_from,
                        'position_to' => $result->position_to === null ? null : (int) $result->position_to,
                        'quantity' => (int) $result->quantity,
                        'flow_mode' => $result->flow_mode,
                        'result_type' => $result->result_type,
                        'participant_status' => $result->participant_status,
                    ];
                }
            }
        }

        $remaining = $participantIds;
        $inputGates = $phase->inputGates
            ->where('status', 'ACTIVE')
            ->sortBy(
                fn($gate) => sprintf(
                    '%010d:%010d:%010d:%010d',
                    (int) $gate->priority,
                    (int) $gate->sort_order,
                    (int) $gate->sequence_number,
                    (int) $gate->id
                )
            )
            ->values();

        foreach ($inputGates as $gate) {
            $take = $gate->exact_participants ?? $gate->max_participants ?? count($remaining);
            $gateParticipants = array_splice($remaining, 0, min((int) $take, count($remaining)));
            $received = count($gateParticipants);

            if ($gate->exact_participants !== null && $received !== (int) $gate->exact_participants) {
                $this->fail("La puerta {$gate->name} no recibió su cantidad exacta de participantes.");
            }

            if ($gate->min_participants !== null && $received < (int) $gate->min_participants) {
                $this->fail("La puerta {$gate->name} no alcanzó su mínimo de participantes.");
            }

            if ($gate->max_participants !== null && $received > (int) $gate->max_participants) {
                $this->fail("La puerta {$gate->name} superó su máximo de participantes.");
            }

            $runtime = $this->routeSource($runtime, 'INPUT_GATE', (int) $gate->id, $gateParticipants);
        }

        if ($remaining !== []) {
            $this->fail('Quedaron participantes sin una puerta de entrada interna.');
        }

        return $this->refresh($runtime);
    }

    public function submitScore(
        array $runtime,
        string $matchId,
        int $scoreA,
        int $scoreB
    ): array {
        $match = $this->findMatch($runtime, $matchId);

        if (count($match['participant_ids']) !== 2) {
            $this->fail('Este encuentro necesita seleccionar sus clasificados, no un marcador A/B.');
        }

        if ($scoreA < 0 || $scoreB < 0 || $scoreA === $scoreB) {
            $this->fail('El marcador debe ser válido y no puede terminar empatado.');
        }

        $qualifiers = $scoreA > $scoreB
            ? [$match['participant_ids'][0]]
            : [$match['participant_ids'][1]];
        $runtime = $this->submitSelection($runtime, $matchId, $qualifiers);

        foreach ($runtime['rounds'] as &$round) {
            foreach ($round['matches'] as &$current) {
                if ($current['id'] === $matchId) {
                    $current['score_a'] = $scoreA;
                    $current['score_b'] = $scoreB;
                }
            }
        }
        unset($round, $current);

        $encounterId = (int) str_replace('SE-G-', '', $matchId);
        $runtime['encounters'][$encounterId]['score_a'] = $scoreA;
        $runtime['encounters'][$encounterId]['score_b'] = $scoreB;

        return $runtime;
    }

    public function submitSelection(
        array $runtime,
        string $matchId,
        array $qualifierIds
    ): array {
        if (($runtime['status'] ?? null) !== 'RUNNING') {
            $this->fail('La fase ya está completada.');
        }

        $match = $this->findMatch($runtime, $matchId);
        if ($match['status'] !== 'PENDING') {
            $this->fail('El encuentro no está listo o ya fue completado.');
        }

        $qualifierIds =
            array_values(
                $qualifierIds
            );

        if (
            count($qualifierIds)
            !==
            count(array_unique($qualifierIds))
        ) {
            $this->fail(
                'La selección contiene participantes clasificados duplicados.'
            );
        }

        if (count($qualifierIds) !== (int) $match['qualifiers_count']) {
            $this->fail("Debes seleccionar exactamente {$match['qualifiers_count']} clasificados.");
        }

        foreach ($qualifierIds as $participantId) {
            if (! in_array($participantId, $match['participant_ids'], true)) {
                $this->fail('Uno de los clasificados no pertenece al encuentro.');
            }
        }

        $eliminatedIds = array_values(array_filter(
            $match['participant_ids'],
            fn($participantId) => ! in_array($participantId, $qualifierIds, true)
        ));
        $encounterId = (int) str_replace('SE-G-', '', $matchId);
        $runtime['encounters'][$encounterId]['status'] = 'COMPLETED';
        $runtime['encounters'][$encounterId]['qualifier_ids'] = $qualifierIds;
        $runtime['encounters'][$encounterId]['eliminated_ids'] = $eliminatedIds;

        $this->recordEliminations(
            $runtime,
            $eliminatedIds,
            $match,
            $encounterId
        );

        foreach ($runtime['results'] as $result) {
            if ((int) $result['encounter_id'] !== $encounterId) {
                continue;
            }

            $ids =
                $this->participantsForResult(
                    $result,
                    $qualifierIds,
                    $eliminatedIds
                );

            if (
                ($result['flow_mode'] ?? null) === 'CONSUME'
                &&
                in_array(
                    $result['participant_status'] ?? null,
                    [
                        'ACTIVE',
                        'ELIMINATED',
                    ],
                    true
                )
                &&
                count($ids) !== (int) $result['quantity']
            ) {
                $this->fail(
                    'Un resultado competitivo no produjo la cantidad de participantes definida por la estructura.'
                );
            }

            $runtime = $this->routeSource($runtime, 'RESULT', (int) $result['id'], $ids);
        }

        $runtime['matches_completed']++;

        return $this->refresh($runtime);
    }

    public function simulate(array $runtime, string $matchId): array
    {
        $match = $this->findMatch($runtime, $matchId);

        if (
            ($match['resolution_mode'] ?? null) === 'SCORE'
            && count($match['participant_ids']) === 2
            && (int) $match['qualifiers_count'] === 1
        ) {
            $scoreA = random_int(0, 5);
            $scoreB = random_int(0, 5);

            while ($scoreA === $scoreB) {
                $scoreB = random_int(0, 5);
            }

            return $this->submitScore($runtime, $matchId, $scoreA, $scoreB);
        }

        $participantIds = $match['participant_ids'];
        shuffle($participantIds);

        return $this->submitSelection(
            $runtime,
            $matchId,
            array_slice($participantIds, 0, (int) $match['qualifiers_count'])
        );
    }

    private function routeSource(
        array $runtime,
        string $sourceType,
        int $sourceId,
        array $participantIds
    ): array {
        $connections = collect($runtime['connections'])
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->sortBy(
                fn($connection) => sprintf(
                    '%010d:%010d',
                    (int) ($connection['priority'] ?? 0),
                    (int) ($connection['id'] ?? 0)
                )
            )
            ->values();
        $remaining = array_values($participantIds);

        foreach ($connections as $connection) {
            $selected = match ($connection['allocation_mode']) {
                'POSITION' => isset($participantIds[$connection['allocation_value'] - 1])
                    ? [$participantIds[$connection['allocation_value'] - 1]]
                    : [],
                'TAKE_N' => array_splice($remaining, 0, (int) $connection['allocation_value']),
                'REMAINDER' => array_splice($remaining, 0),
                default => array_splice($remaining, 0),
            };

            if ($connection['target_type'] === 'SLOT') {
                if (count($selected) > 1) {
                    $this->fail('Una conexión intenta colocar varios participantes en un slot individual.');
                }

                $participantId = $selected[0] ?? null;
                if ($participantId === null) {
                    continue;
                }

                $slotId = (int) $connection['target_id'];
                if (($runtime['slots'][$slotId]['participant_id'] ?? null) !== null) {
                    $this->fail('Un slot recibió más de un participante durante la ejecución.');
                }
                $runtime['slots'][$slotId]['participant_id'] = $participantId;
            } else {
                $exitId = (int) $connection['target_id'];
                $runtime['exit_participants'][$exitId] = array_values(array_unique([
                    ...($runtime['exit_participants'][$exitId] ?? []),
                    ...$selected,
                ]));
            }
        }

        return $runtime;
    }

    private function refresh(array $runtime): array
    {
        foreach ($runtime['encounters'] as $encounterId => &$encounter) {
            if ($encounter['status'] === 'COMPLETED') {
                continue;
            }

            $encounterSlots =
                collect($runtime['slots'])
                ->where(
                    'encounter_id',
                    (int) $encounterId
                )
                ->sortBy('position')
                ->values();

            $participantIds =
                $encounterSlots
                ->pluck('participant_id')
                ->filter(
                    fn($id) =>
                    $id !== null
                )
                ->values()
                ->all();

            if (
                count($participantIds)
                >
                (int) $encounter['entrants_count']
            ) {
                $this->fail(
                    'Un encuentro recibió más participantes que su capacidad K.'
                );
            }

            $requiredSlotsFilled =
                $encounterSlots
                ->filter(
                    fn($slot) =>
                    (bool) ($slot['required'] ?? false)
                )
                ->every(
                    fn($slot) =>
                    ($slot['participant_id'] ?? null)
                    !==
                    null
                );

            $minimum =
                max(
                    1,
                    (int) (
                        $encounter['min_entrants_to_start']
                        ??
                        $encounter['entrants_count']
                    )
                );

            $allowsIncomplete =
                (bool) (
                    $encounter['allows_incomplete']
                    ??
                    false
                );

            $hasEnoughParticipants =
                count($participantIds)
                >=
                $minimum;

            $hasFullCapacity =
                count($participantIds)
                ===
                (int) $encounter['entrants_count'];

            $ready =
                $requiredSlotsFilled
                &&
                $hasEnoughParticipants
                &&
                (
                    $allowsIncomplete
                    ||
                    $hasFullCapacity
                );

            if (
                $ready
                &&
                count($participantIds)
                <=
                (int) $encounter['qualifiers_count']
            ) {
                $this->fail(
                    'Un encuentro ejecutable debe tener más participantes que clasificados.'
                );
            }

            $encounter['participant_ids'] =
                $participantIds;

            $encounter['status'] =
                $ready
                ? 'PENDING'
                : 'WAITING';
        }
        unset($encounter);

        foreach ($runtime['rounds'] as &$round) {
            $round['matches'] = [];
            foreach ($runtime['encounters'] as $encounter) {
                if ((int) $encounter['round_id'] !== (int) $round['id']) {
                    continue;
                }

                $round['matches'][] = [
                    'id' => $encounter['match_id'],
                    'number' => count($round['matches']) + 1,
                    'round_number' => $encounter['round_number'],
                    'round_participants' => $encounter['round_participants'],
                    'label' => $encounter['name'],
                    'participant_ids' => $encounter['participant_ids'],
                    'participant_a_id' => $encounter['participant_ids'][0] ?? null,
                    'participant_b_id' => $encounter['participant_ids'][1] ?? null,
                    'qualifiers_count' => $encounter['qualifiers_count'],
                    'qualifier_ids' => $encounter['qualifier_ids'],
                    'eliminated_ids' => $encounter['eliminated_ids'],
                    'winner_id' => $encounter['qualifier_ids'][0] ?? null,
                    'loser_id' => count($encounter['eliminated_ids']) === 1
                        ? $encounter['eliminated_ids'][0]
                        : null,
                    'score_a' => $encounter['score_a'],
                    'score_b' => $encounter['score_b'],
                    'resolution_mode' => $encounter['resolution_mode'],
                    'qualifier_ordering' => $encounter['qualifier_ordering'],
                    'series_format' => $encounter['series_format'],
                    'best_of' => $encounter['best_of'],
                    'fixed_games' => $encounter['fixed_games'],
                    'series_label' => $encounter['series_format'] === 'BEST_OF'
                        ? 'BO' . ($encounter['best_of'] ?? 1)
                        : ($encounter['series_format'] === 'FIXED_GAMES'
                            ? ($encounter['fixed_games'] ?? 1) . ' juegos fijos'
                            : 'Encuentro único'),
                    'status' => $encounter['status'],
                ];
            }

            $statuses = collect($round['matches'])->pluck('status');
            $round['status'] = $statuses->isNotEmpty() && $statuses->every(fn($status) => $status === 'COMPLETED')
                ? 'COMPLETED'
                : ($statuses->contains('PENDING') ? 'RUNNING' : 'WAITING');
        }
        unset($round);

        $runtime['matches_total'] = count($runtime['encounters']);
        $pending = collect($runtime['encounters'])->contains(fn($encounter) => $encounter['status'] === 'PENDING');
        $waiting = collect($runtime['encounters'])->contains(fn($encounter) => $encounter['status'] === 'WAITING');
        $completed = collect($runtime['encounters'])->every(fn($encounter) => $encounter['status'] === 'COMPLETED');

        $currentRound = collect($runtime['rounds'])
            ->first(fn($round) => in_array($round['status'], ['RUNNING', 'WAITING'], true));
        $runtime['current_round'] = $currentRound['number'] ?? count($runtime['rounds']);

        $runtime['outcomes'] = [];
        foreach ($runtime['exit_participants'] as $exitId => $ids) {
            if ($ids === []) {
                continue;
            }
            $definition = $runtime['exit_definitions'][$exitId] ?? null;
            $runtime['outcomes'][] = [
                'exit_id' => (int) $exitId,
                'exit_name' => $definition['name'] ?? 'Salida',
                'participant_ids' => array_values(array_unique($ids)),
            ];
        }

        $runtime['survivor_ids'] = collect($runtime['exit_participants'])
            ->flatMap(function ($ids, $exitId) use ($runtime) {
                $selector = $runtime['exit_definitions'][$exitId]['selector_type'] ?? null;
                return $selector === 'SURVIVORS' ? $ids : [];
            })->unique()->values()->all();

        if ($completed) {
            $runtime['status'] = 'COMPLETED';
            $runtime['standings'] =
                $this->standings(
                    $runtime
                );
        } elseif (! $pending && $waiting) {
            $this->fail(
                'El grafo interno quedó bloqueado: existen encuentros en espera y ninguna transición puede producir nuevos participantes.'
            );
        }

        return $runtime;
    }

    private function recordEliminations(
        array &$runtime,
        array $participantIds,
        array $match,
        int $encounterId
    ): void {
        foreach ($participantIds as $participantId) {
            $duplicate = collect(
                $runtime['eliminations'] ?? []
            )->contains(
                fn($event) =>
                ($event['participant_id'] ?? null)
                ===
                $participantId
            );

            if ($duplicate) {
                $this->fail(
                    "El participante {$participantId} ya había sido eliminado en esta fase."
                );
            }

            $eventId =
                'ELIMINATION:'
                . $match['id']
                . ':'
                . $participantId;

            $runtime['eliminations'][] = [
                'id' => $eventId,
                'participant_id' => $participantId,
                'round_number' => (int) ($match['round_number'] ?? 0),
                'round_participants' => (int) ($match['round_participants'] ?? 0),
                'match_id' => $match['id'],
                'encounter_id' => $encounterId,
                'source' => 'MATCH_RESULT',
            ];

            $runtime['eliminated_ids'][] =
                $participantId;
        }

        $runtime['eliminated_ids'] =
            array_values(
                array_unique(
                    $runtime['eliminated_ids']
                )
            );
    }

    private function standings(
        array $runtime
    ): array {
        $standings = [];
        $survivors =
            array_values(
                array_unique(
                    $runtime['survivor_ids'] ?? []
                )
            );

        $survivorCount =
            count($survivors);

        foreach ($survivors as $participantId) {
            $standings[] = [
                'position' => 1,
                'position_from' => 1,
                'position_to' => max(1, $survivorCount),
                'participant_id' => $participantId,
                'status' => 'SURVIVOR',
                'placement_status' => $survivorCount === 1
                    ? 'RANKED'
                    : 'UNRANKED_SURVIVOR',
            ];
        }

        $eventsByRound = [];

        foreach ($runtime['eliminations'] ?? [] as $event) {
            $eventsByRound[(int) ($event['round_number'] ?? 0)][] =
                $event;
        }

        krsort(
            $eventsByRound,
            SORT_NUMERIC
        );

        $positionFrom =
            $survivorCount + 1;

        foreach ($eventsByRound as $roundNumber => $events) {
            usort(
                $events,
                fn($left, $right) =>
                    strcmp(
                        (string) ($left['participant_id'] ?? ''),
                        (string) ($right['participant_id'] ?? '')
                    )
            );

            $positionTo =
                $positionFrom
                + count($events)
                - 1;

            foreach ($events as $event) {
                $standings[] = [
                    'position' => $positionFrom,
                    'position_from' => $positionFrom,
                    'position_to' => $positionTo,
                    'participant_id' => $event['participant_id'],
                    'status' => 'ELIMINATED',
                    'placement_status' => $positionFrom === $positionTo
                        ? 'RANKED'
                        : 'TIED_BAND',
                    'eliminated_round' => $roundNumber,
                    'match_id' => $event['match_id'],
                ];
            }

            $positionFrom =
                $positionTo + 1;
        }

        return $standings;
    }

    private function participantsForResult(
        array $result,
        array $qualifierIds,
        array $eliminatedIds
    ): array {
        $quantity =
            max(
                0,
                (int) ($result['quantity'] ?? 0)
            );

        if ($quantity === 0) {
            return [];
        }

        if (($result['participant_status'] ?? null) === 'ACTIVE') {
            $position =
                max(
                    1,
                    (int) ($result['position_from'] ?? 1)
                );

            return array_values(
                array_slice(
                    $qualifierIds,
                    $position - 1,
                    $quantity
                )
            );
        }

        if (($result['participant_status'] ?? null) === 'ELIMINATED') {
            return array_values(
                array_slice(
                    $eliminatedIds,
                    0,
                    $quantity
                )
            );
        }

        return [];
    }

    private function findMatch(array $runtime, string $matchId): array
    {
        $match = collect($runtime['rounds'])
            ->flatMap(fn($round) => $round['matches'] ?? [])
            ->firstWhere('id', $matchId);

        if (! $match) {
            $this->fail('El encuentro solicitado no existe en este grafo.');
        }

        return $match;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'engine' => [$message],
        ]);
    }
}
