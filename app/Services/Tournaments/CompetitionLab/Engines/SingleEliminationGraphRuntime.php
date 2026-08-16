<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureValidator;
use Illuminate\Validation\ValidationException;

class SingleEliminationGraphRuntime
{
    public function __construct(
        private readonly SingleEliminationStructureValidator $validator
    ) {}

    public function prepare(PhaseTemplate $phase, array $participantIds): array
    {
        $validation = $this->validator->validate($phase);

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

        $phase->load([
            'singleEliminationSetting',
            'inputGates.outgoingConnections',
            'singleEliminationRounds.encounters.slots',
            'singleEliminationRounds.encounters.results.outgoingConnections',
            'singleEliminationConnections',
            'exits',
        ]);

        $participantIds = array_values(array_unique($participantIds));
        $expected = (int) data_get(
            $phase->singleEliminationSetting?->settings,
            'custom_graph_participants',
            0
        );

        if ($expected > 0 && count($participantIds) !== $expected) {
            $this->fail("El grafo personalizado necesita exactamente {$expected} participantes.");
        }

        $runtime = [
            'engine' => 'SINGLE_ELIMINATION',
            'mode' => 'STRUCTURE_GRAPH',
            'status' => 'RUNNING',
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
            'matches_total' => 0,
            'matches_completed' => 0,
            'current_round' => 1,
        ];

        foreach ($phase->exits as $exit) {
            $runtime['exit_definitions'][$exit->id] = [
                'id' => (int) $exit->id,
                'name' => $exit->name,
                'selector_type' => $exit->selector_type,
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
                    'match_id' => 'SE-G-' . $encounter->id,
                    'code' => $encounter->code,
                    'name' => $encounter->name,
                    'entrants_count' => (int) $encounter->entrants_count,
                    'qualifiers_count' => (int) $encounter->qualifiers_count,
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
                        'participant_status' => $result->participant_status,
                    ];
                }
            }
        }

        $remaining = $participantIds;
        foreach ($phase->inputGates->where('status', 'ACTIVE') as $gate) {
            $take = $gate->exact_participants ?? $gate->max_participants ?? count($remaining);
            $gateParticipants = array_splice($remaining, 0, min((int) $take, count($remaining)));

            if ($gate->exact_participants !== null && count($gateParticipants) !== (int) $gate->exact_participants) {
                $this->fail("La puerta {$gate->name} no recibió su cantidad exacta de participantes.");
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

        $qualifierIds = array_values(array_unique($qualifierIds));
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

        foreach ($runtime['results'] as $result) {
            if ((int) $result['encounter_id'] !== $encounterId) {
                continue;
            }

            if ($result['participant_status'] === 'ACTIVE') {
                $position = max(1, (int) $result['position_from']);
                $ids = isset($qualifierIds[$position - 1]) ? [$qualifierIds[$position - 1]] : [];
            } elseif ($result['participant_status'] === 'ELIMINATED') {
                $ids = $eliminatedIds;
            } else {
                $ids = [];
            }

            $runtime = $this->routeSource($runtime, 'RESULT', (int) $result['id'], $ids);
        }

        $runtime['eliminated_ids'] = array_values(array_unique([
            ...$runtime['eliminated_ids'],
            ...$eliminatedIds,
        ]));
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
            ->sortBy('priority')
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

            $participantIds = collect($runtime['slots'])
                ->where('encounter_id', (int) $encounterId)
                ->sortBy('position')
                ->pluck('participant_id')
                ->filter(fn($id) => $id !== null)
                ->values()
                ->all();
            $encounter['participant_ids'] = $participantIds;
            $encounter['status'] = count($participantIds) === (int) $encounter['entrants_count']
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
            $position = 1;
            $runtime['standings'] = [];
            foreach ($runtime['survivor_ids'] as $participantId) {
                $runtime['standings'][] = [
                    'position' => $position++,
                    'participant_id' => $participantId,
                    'status' => 'SURVIVOR',
                ];
            }
            foreach (array_reverse($runtime['eliminated_ids']) as $participantId) {
                if (in_array($participantId, $runtime['survivor_ids'], true)) {
                    continue;
                }
                $runtime['standings'][] = [
                    'position' => $position++,
                    'participant_id' => $participantId,
                    'status' => 'ELIMINATED',
                ];
            }
        } elseif (! $pending && $waiting) {
            $runtime['warnings'] = ['El grafo está esperando participantes de encuentros anteriores.'];
        }

        return $runtime;
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
