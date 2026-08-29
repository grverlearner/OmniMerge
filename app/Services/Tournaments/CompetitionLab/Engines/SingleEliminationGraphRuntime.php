<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\CompetitionLab\Runtime\PlacementPlanner;
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
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
        SingleEliminationStructureFingerprint $fingerprint,

        private readonly
        SingleEliminationSettingsService $settingsService,

        private readonly
        PlacementPlanner $placement
    ) {}

    public function prepare(PhaseTemplate $phase, array $participantIds): array
    {
        $phase->loadMissing(
            'singleEliminationSetting'
        );

        /*
         * Ver nota equivalente en RoundRobinLabEngine::prepare(): si
         * loadMissing() ya trajo la fila, se usa tal cual; ensure() solo
         * entra en juego cuando realmente no existe (fase nunca visitada en
         * su pestaña "Reglas", ej. colocada directamente como Node del
         * Tournament Graph). El estado de la estructura (siguiente chequeo)
         * sigue siendo una decisión real que no se auto-genera.
         */
        $settings =
            $phase->singleEliminationSetting
            ?? $this->settingsService->ensure($phase);

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

                /*
                 * Que puesto pide esta salida.
                 *
                 * Una salida RANK_POSITION 3 o RANK_RANGE 5-8 dice «por
                 * aqui salen los que acaben en ese puesto». Sin guardar el
                 * rango no habia forma de servirla, y de hecho no se
                 * servia: nadie la alimentaba nunca.
                 */
                'selector_from' => $exit->selector_from === null
                    ? null
                    : (int) $exit->selector_from,

                'selector_to' => $exit->selector_to === null
                    ? null
                    : (int) $exit->selector_to,
            ];
            $runtime['exit_participants'][$exit->id] = [];
        }

        /*
         * Que puestos pide esta fase, dicho ANTES de jugar nada.
         *
         * Una salida «#3 lugar» no se sirve con el cuadro normal: hay que
         * disputarla. Anunciarlo desde el minuto cero evita la sensacion
         * de que la configuracion no se aplico -que es exactamente lo que
         * parecia cuando el cuadro arrancaba igual que siempre-.
         */
        $runtime['placement_wanted'] =
            $this->placement
            ->wantedFromExits(
                $runtime['exit_definitions']
            );

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

        /*
         * Un desempate de puestos no elimina a nadie: los que lo juegan ya
         * estaban eliminados del cuadro, y solo estan decidiendo en que
         * orden quedan. Pasarlos por recordEliminations() haria saltar el
         * control de doble eliminacion -con razon-, y enrutarlos por los
         * resultados de la estructura tampoco tiene sentido: un encuentro
         * de clasificacion no existe en la estructura, lo crea el motor.
         */
        if (isset($runtime['encounters'][$encounterId]['placement'])) {

            $runtime['matches_completed']++;

            return $this->refresh($runtime);
        }

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

            /*
             * El cuadro se acabo. Antes de dar la fase por terminada hay
             * que mirar si quedan puestos por disputar: si la fase pide un
             * «#3 lugar», el cuadro no lo ha decidido -dos perdieron en
             * semifinales y nadie jugo para separarlos- y hace falta una
             * batalla mas.
             */
            $runtime = $this->advancePlacement($runtime);

            $sigueCompleta =
                collect($runtime['encounters'])
                ->every(
                    fn($encounter) =>
                    $encounter['status'] === 'COMPLETED'
                );

            /* Se acaban de crear desempates: hay que volver a repartirlos */
            if (! $sigueCompleta) {
                return $this->refresh($runtime);
            }

            $runtime['status'] = 'COMPLETED';
            $runtime['standings'] =
                $this->placementStandings(
                    $runtime
                );

            /*
             * Y ahora las salidas por puesto.
             *
             * Van al final a proposito: hasta que la fase no termina no hay
             * clasificacion, y sin clasificacion no se puede decir quien
             * acabo tercero.
             */
            $runtime = $this->routeRankExits($runtime);
        } elseif (! $pending && $waiting) {
            $this->fail(
                'El grafo interno quedó bloqueado: existen encuentros en espera y ninguna transición puede producir nuevos participantes.'
            );
        }

        return $runtime;
    }

    /*
    |--------------------------------------------------------------------------
    | Las salidas por puesto
    |--------------------------------------------------------------------------
    |
    | Una fase puede tener una salida «#3 lugar» o «puestos 5-8». Hasta
    | ahora se podian crear y no servian para nada: solo el motor de liga
    | las resolvia, y en eliminacion directa nadie las alimentaba jamas.
    |
    | Aqui se sirven contra la clasificacion de la propia fase.
    |
    | ---------------------------------------------------------------
    |
    | La regla que gobierna esto: solo se sirve lo que se DISPUTO.
    |
    | En un cuadro puro, cuatro pierden en la misma ronda y nadie jugo para
    | separarlos: su plaza es un RANGO -«5.o-8.o»-, no un numero. Una salida
    | que pide «5-8» se sirve entera, porque pide exactamente ese rango. Una
    | que pide «el 5.o» NO se sirve: elegir uno de los cuatro seria inventar
    | un resultado que nadie jugo.
    |
    | Cuando pasa eso se anota en `unresolved_exits` para que la pantalla
    | pueda decir por que esa salida quedo vacia, en vez de dejar al usuario
    | pensando que se rompio algo.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Disputar los puestos
    |--------------------------------------------------------------------------
    |
    | Un cuadro de 16 reparte plazas asi: 1.o, 2.o, dos empatados en 3.o-4.o,
    | cuatro en 5.o-8.o y ocho en 9.o-16.o. Esas bandas no son un defecto: es
    | que nadie jugo para separarlas.
    |
    | Cuando la fase pide un «#3 lugar», un «#7» o un «#13», lo que falta es
    | precisamente eso -jugarlo-. Aqui se generan esas batallas.
    |
    | El metodo es el mismo que usa cualquier torneo real para el tercer
    | puesto, aplicado tantas veces como haga falta: se emparejan los
    | empatados, los que ganan se quedan con la mitad alta de la banda y los
    | que pierden con la baja. Repitiendo, la banda se parte hasta que el
    | puesto pedido cae en un borde.
    |
    | Solo se parten las bandas que HACEN FALTA. Pedir el #13 en una banda de
    | 9.o-16.o obliga a separar 9-12 de 13-16, luego 13-14 de 15-16, y luego
    | el 13 del 14: siete batallas. La banda 9.o-12.o no se toca, porque
    | nadie ha pedido distinguir dentro de ella y jugar esas batallas seria
    | hacer trabajar a los competidores para nada.
    |
    */
    private function advancePlacement(array $runtime): array
    {
        $pedidos =
            $runtime['placement_wanted']
            ?? $this->placement->wantedFromExits(
                $runtime['exit_definitions'] ?? []
            );

        $cortes = $this->placement->cuts($pedidos);

        if (! isset($runtime['placement'])) {

            $runtime['placement'] = [
                'cuts' => $cortes,
                'bands' => $this->placement->bands(
                    $this->standings($runtime)
                ),
                'splits' => [],
                'sequence' => 0,
                'done' => $cortes === [],
            ];
        }

        if ($cortes === []) {
            $runtime['placement']['done'] = true;

            return $runtime;
        }

        /*
         * Primero se cierran los desempates ya jugados: su banda se parte
         * en dos -arriba los que ganaron, abajo los que perdieron-.
         */
        foreach ($runtime['placement']['splits'] as $indice => $split) {

            if (($split['status'] ?? null) === 'CLOSED') {
                continue;
            }

            $encuentros = [];
            $todosJugados = true;

            foreach ($split['encounter_ids'] as $encounterId) {

                $encuentro = $runtime['encounters'][$encounterId] ?? null;

                if (($encuentro['status'] ?? null) !== 'COMPLETED') {
                    $todosJugados = false;
                    break;
                }

                $encuentros[] = $encuentro;
            }

            if (! $todosJugados) {
                continue;
            }

            $arriba = $split['byes'];
            $abajo = [];

            foreach ($encuentros as $encuentro) {
                $arriba = [...$arriba, ...$encuentro['qualifier_ids']];
                $abajo = [...$abajo, ...$encuentro['eliminated_ids']];
            }

            $runtime['placement']['bands'] =
                $this->placement->replaceBand(
                    $runtime['placement']['bands'],
                    (int) $split['from'],
                    (int) $split['to'],
                    $arriba,
                    $abajo
                );

            $runtime['placement']['splits'][$indice]['status'] = 'CLOSED';
        }

        /* Y despues se abren los que ahora hacen falta */
        $abiertos = [];

        foreach ($runtime['placement']['splits'] as $split) {
            if (($split['status'] ?? null) !== 'CLOSED') {
                $abiertos[] = (int) $split['from'];
            }
        }

        foreach ($runtime['placement']['bands'] as $band) {

            if (in_array((int) $band['from'], $abiertos, true)) {
                continue;
            }

            if (! $this->placement->needsSplit($band, $cortes)) {
                continue;
            }

            $runtime = $this->openPlacementSplit($runtime, $band);
        }

        $runtime['placement']['done'] =
            collect($runtime['placement']['splits'])
            ->every(
                fn($split) =>
                ($split['status'] ?? null) === 'CLOSED'
            );

        return $runtime;
    }

    /*
     * Los puntos donde una banda NO puede seguir siendo una banda.
     *
     * Una salida «#3 lugar» necesita que 3 empiece banda y que 4 empiece
     * otra; una «puestos 5-8», que 5 empiece banda y 9 empiece la siguiente.
     *
     * @return array<int,int>
     */

    /*
     * Las bandas de la clasificacion del cuadro: grupos de empatados.
     *
     * @return array<int,array<string,mixed>>
     */

    /*
     * Una banda hay que partirla cuando algun corte cae DENTRO de ella.
     */

    /*
     * @return array<int,array<string,mixed>>
     */

    /*
     * Abre el desempate de una banda: empareja a los empatados y crea su
     * ronda propia.
     *
     * Con un numero impar de empatados, uno pasa a la mitad alta sin jugar.
     * Es el mismo BYE que usa cualquier cuadro cuando los participantes no
     * son potencia de dos, y es preferible a inventarle un rival.
     */
    private function openPlacementSplit(array $runtime, array $band): array
    {
        ['pairs' => $parejas, 'byes' => $byes] =
            $this->placement->pairings($band);

        $formato = $this->placementFormat($runtime);

        $runtime['placement']['sequence']++;
        $secuencia = (int) $runtime['placement']['sequence'];

        $roundId = 900000 + $secuencia;
        $roundNumber = $this->nextRoundNumber($runtime);
        $etiqueta = $this->placement->label((int) $band['from'], (int) $band['to']);

        $runtime['rounds'][] = [
            'id' => $roundId,
            'number' => $roundNumber,
            'participants_in_round' => count($band['ids']),
            'label' => $etiqueta,

            /*
             * El proyector guarda esto como `group_label`, y es lo que
             * permite a la pantalla del cuadro separar los desempates de
             * las rondas normales sin adivinar por el numero de ronda.
             */
            'group_name' => $etiqueta,
            'status' => 'WAITING',
            'matches' => [],
            'placement' => ['from' => (int) $band['from'], 'to' => (int) $band['to']],
        ];

        $encounterIds = [];

        foreach ($parejas as $i => $pareja) {

            $encounterId = 900000000 + $secuencia * 1000 + $i;

            $runtime['encounters'][$encounterId] = [
                'id' => $encounterId,
                'round_id' => $roundId,
                'round_number' => $roundNumber,
                'round_participants' => count($band['ids']),
                'match_id' => 'SE-G-' . $encounterId,
                'code' => 'PL' . $secuencia . '-' . ($i + 1),
                'name' => $etiqueta . ' · ' . ($i + 1),
                'entrants_count' => 2,
                'qualifiers_count' => 1,
                'min_entrants_to_start' => 2,
                'allows_incomplete' => false,
                'activation_policy' => 'AUTOMATIC',
                'profile' => 'DUEL',
                'resolution_mode' => 'SCORE',
                'qualifier_ordering' => 'ORDERED',
                'series_format' => $formato['series_format'],
                'best_of' => $formato['best_of'],
                'fixed_games' => $formato['fixed_games'],
                'status' => 'WAITING',
                'participant_ids' => [],
                'qualifier_ids' => [],
                'eliminated_ids' => [],
                'score_a' => null,
                'score_b' => null,

                /* La marca que distingue un desempate de una batalla del cuadro */
                'placement' => [
                    'from' => (int) $band['from'],
                    'to' => (int) $band['to'],
                    'split' => $secuencia,
                ],
            ];

            foreach ([1, 2] as $posicion) {

                $slotId = 800000000 + $secuencia * 1000 + $i * 2 + $posicion;

                $runtime['slots'][$slotId] = [
                    'id' => $slotId,
                    'encounter_id' => $encounterId,
                    'position' => $posicion,
                    'required' => true,
                    'empty_behavior' => 'BLOCK',
                    'participant_id' => $pareja[$posicion - 1],
                ];
            }

            $encounterIds[] = $encounterId;
        }

        $runtime['placement']['splits'][] = [
            'id' => $secuencia,
            'from' => (int) $band['from'],
            'to' => (int) $band['to'],
            'label' => $etiqueta,
            'round_id' => $roundId,
            'round_number' => $roundNumber,
            'encounter_ids' => $encounterIds,
            'byes' => $byes,
            'status' => 'OPEN',
        ];

        return $runtime;
    }

    /*
     * Los desempates se juegan como se juega la fase: si el cuadro va a
     * BO3, el desempate por el tercer puesto tambien.
     *
     * @return array<string,mixed>
     */
    private function placementFormat(array $runtime): array
    {
        $referencia = null;

        foreach ($runtime['encounters'] as $encuentro) {

            if (isset($encuentro['placement'])) {
                continue;
            }

            if (
                $referencia === null
                ||
                (int) $encuentro['round_number'] > (int) $referencia['round_number']
            ) {
                $referencia = $encuentro;
            }
        }

        return [
            'series_format' => $referencia['series_format'] ?? 'SINGLE',
            'best_of' => $referencia['best_of'] ?? null,
            'fixed_games' => $referencia['fixed_games'] ?? null,
        ];
    }

    private function nextRoundNumber(array $runtime): int
    {
        $maximo = 0;

        foreach ($runtime['rounds'] as $ronda) {
            $maximo = max($maximo, (int) ($ronda['number'] ?? 0));
        }

        return $maximo + 1;
    }


    /*
     * La clasificacion final, con los desempates ya aplicados.
     *
     * El cuadro sigue siendo quien reparte las bandas; esto solo cambia el
     * puesto de quien jugo para salir de una.
     *
     * @return array<int,array<string,mixed>>
     */
    private function placementStandings(array $runtime): array
    {
        return $this->placement->standings(
            $this->standings($runtime),
            $runtime['placement']['bands'] ?? []
        );
    }


    private function routeRankExits(array $runtime): array
    {
        $runtime['unresolved_exits'] = [];

        foreach (($runtime['exit_definitions'] ?? []) as $exitId => $definition) {

            $selector = $definition['selector_type'] ?? null;

            if (! in_array($selector, ['RANK_POSITION', 'RANK_RANGE'], true)) {
                continue;
            }

            $from = (int) ($definition['selector_from'] ?? 0);

            /* RANK_POSITION es un rango de uno */
            $to = $selector === 'RANK_POSITION'
                ? $from
                : (int) ($definition['selector_to'] ?? $from);

            if ($from <= 0) {
                continue;
            }

            if ($to < $from) {
                [$from, $to] = [$to, $from];
            }

            $servidos = [];
            $sinDecidir = [];

            foreach (($runtime['standings'] ?? []) as $fila) {

                $desde = (int) ($fila['position_from'] ?? 0);
                $hasta = (int) ($fila['position_to'] ?? $desde);

                /* Fuera del rango pedido: ni se mira */
                if ($hasta < $from || $desde > $to) {
                    continue;
                }

                /*
                 * Su plaza cabe ENTERA en lo que la salida pide. Si su
                 * banda es 5-8 y la salida pide 5-8, entra; si pide solo
                 * el 5, no, porque el 5 no se disputo.
                 */
                if ($desde >= $from && $hasta <= $to) {
                    $servidos[] = $fila['participant_id'];
                    continue;
                }

                $sinDecidir[] = [
                    'participant_id' => $fila['participant_id'],
                    'band_from' => $desde,
                    'band_to' => $hasta,
                ];
            }

            if ($servidos !== []) {

                $runtime['exit_participants'][$exitId] = array_values(array_unique([
                    ...($runtime['exit_participants'][$exitId] ?? []),
                    ...$servidos,
                ]));

                $runtime['outcomes'][] = [
                    'exit_id' => (int) $exitId,
                    'exit_name' => $definition['name'] ?? 'Salida',
                    'participant_ids' => $runtime['exit_participants'][$exitId],
                ];
            }

            if ($sinDecidir !== []) {

                $runtime['unresolved_exits'][] = [
                    'exit_id' => (int) $exitId,
                    'exit_name' => $definition['name'] ?? 'Salida',
                    'wanted_from' => $from,
                    'wanted_to' => $to,
                    'candidates' => $sinDecidir,

                    'reason' => 'Ese puesto no se disputó: varios competidores '
                        . 'cayeron en la misma ronda y nadie jugó para separarlos.',
                ];
            }
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
