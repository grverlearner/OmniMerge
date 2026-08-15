<?php

namespace App\Services\Tournaments\SingleElimination\Structure;

use App\Models\PhaseSingleEliminationConnection;
use App\Models\PhaseSingleEliminationEncounter;
use App\Models\PhaseTemplate;
use Illuminate\Support\Collection;

class SingleEliminationStructureValidator
{
    public function validate(
        PhaseTemplate $phaseTemplate
    ): array {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        $phaseTemplate->load([
            'singleEliminationSetting',

            'inputGates',

            'singleEliminationRounds.encounters.slots',
            'singleEliminationRounds.encounters.results',

            'exits',
        ]);

        $connections =
            $phaseTemplate
            ->singleEliminationConnections()
            ->with([
                'sourceInputGate',

                'sourceResult.encounter.round',

                'targetSlot.encounter.round',

                'targetPhaseExit',
            ])
            ->get();

        $rounds =
            $phaseTemplate
            ->singleEliminationRounds;

        $encounters =
            $rounds
            ->flatMap(
                fn($round) =>
                $round->encounters
            )
            ->values();

        $errors = [];
        $warnings = [];
        $recommendations = [];

        if ($phaseTemplate->inputGates->isEmpty()) {
            $this->addIssue(
                $errors,
                'NO_INPUT_GATES',
                'La estructura no tiene puertas de entrada.',
                'PHASE_TEMPLATE',
                $phaseTemplate->id,
                $phaseTemplate->name
            );
        }

        if ($rounds->isEmpty()) {
            $this->addIssue(
                $errors,
                'NO_ROUNDS',
                'La estructura no contiene rondas internas.',
                'PHASE_TEMPLATE',
                $phaseTemplate->id,
                $phaseTemplate->name
            );
        }

        if ($encounters->isEmpty()) {
            $this->addIssue(
                $errors,
                'NO_ENCOUNTERS',
                'La estructura no contiene encuentros internos.',
                'PHASE_TEMPLATE',
                $phaseTemplate->id,
                $phaseTemplate->name
            );
        }

        $this->validateInputGates(
            $phaseTemplate,
            $connections,
            $errors,
            $warnings,
            $recommendations
        );

        $this->validateEncounters(
            $encounters,
            $connections,
            $errors,
            $warnings
        );

        $this->validateConnections(
            $phaseTemplate,
            $connections,
            $errors,
            $warnings
        );

        $this->validateTopology(
            $phaseTemplate,
            $encounters,
            $connections,
            $errors,
            $warnings
        );

        $this->validateExits(
            $phaseTemplate,
            $connections,
            $errors,
            $warnings
        );

        $errors =
            $this->uniqueIssues(
                $errors
            );

        $warnings =
            $this->uniqueIssues(
                $warnings
            );

        $recommendations =
            $this->uniqueIssues(
                $recommendations
            );

        $manualCodes = [
            'MANUAL_INPUT_DISTRIBUTION',
            'MANUAL_RESULT',
            'MANUAL_CONNECTION',
            'CUSTOM_ENCOUNTER',
            'CUSTOM_INPUT',
        ];

        $requiresManualResolution =
            collect($warnings)
            ->contains(
                fn(array $issue) =>
                in_array(
                    $issue['code'],
                    $manualCodes,
                    true
                )
            );

        return [
            'valid' =>
            $errors === [],

            'executable' =>
            $errors === []
                &&
                ! $requiresManualResolution,

            'errors' =>
            $errors,

            'warnings' =>
            $warnings,

            'recommendations' =>
            $recommendations,

            'counts' => [
                'errors' =>
                count($errors),

                'warnings' =>
                count($warnings),

                'recommendations' =>
                count($recommendations),
            ],

            'stats' => [
                'input_gates' =>
                $phaseTemplate
                    ->inputGates
                    ->count(),

                'rounds' =>
                $rounds->count(),

                'encounters' =>
                $encounters->count(),

                'slots' =>
                $encounters->sum(
                    fn($encounter) =>
                    $encounter
                        ->slots
                        ->count()
                ),

                'results' =>
                $encounters->sum(
                    fn($encounter) =>
                    $encounter
                        ->results
                        ->count()
                ),

                'connections' =>
                $connections->count(),

                'exits' =>
                $phaseTemplate
                    ->exits
                    ->count(),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Puertas de entrada
    |--------------------------------------------------------------------------
    */

    private function validateInputGates(
        PhaseTemplate $phaseTemplate,
        Collection $connections,
        array &$errors,
        array &$warnings,
        array &$recommendations
    ): void {
        foreach (
            $phaseTemplate->inputGates
            as
            $gate
        ) {
            if ($gate->status !== 'ACTIVE') {
                continue;
            }

            if (
                $gate->exact_participants !== null
                &&
                $gate->min_participants !== null
                &&
                $gate->exact_participants
                <
                $gate->min_participants
            ) {
                $this->addIssue(
                    $errors,
                    'INPUT_EXACT_BELOW_MINIMUM',
                    'La cantidad exacta de la puerta es menor que su mínimo.',
                    'INPUT_GATE',
                    $gate->id,
                    $gate->name
                );
            }

            if (
                $gate->exact_participants !== null
                &&
                $gate->max_participants !== null
                &&
                $gate->exact_participants
                >
                $gate->max_participants
            ) {
                $this->addIssue(
                    $errors,
                    'INPUT_EXACT_ABOVE_MAXIMUM',
                    'La cantidad exacta de la puerta supera su máximo.',
                    'INPUT_GATE',
                    $gate->id,
                    $gate->name
                );
            }

            if (
                $gate->min_participants !== null
                &&
                $gate->max_participants !== null
                &&
                $gate->min_participants
                >
                $gate->max_participants
            ) {
                $this->addIssue(
                    $errors,
                    'INPUT_MINIMUM_ABOVE_MAXIMUM',
                    'La capacidad mínima de la puerta supera su capacidad máxima.',
                    'INPUT_GATE',
                    $gate->id,
                    $gate->name
                );
            }

            $outgoing =
                $connections
                ->where(
                    'source_type',
                    'INPUT_GATE'
                )
                ->where(
                    'source_input_gate_id',
                    $gate->id
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->values();

            if (
                $gate->is_required
                &&
                $outgoing->isEmpty()
            ) {
                $this->addIssue(
                    $errors,
                    'REQUIRED_INPUT_WITHOUT_DESTINATION',
                    'La puerta obligatoria no alimenta ningún slot ni salida.',
                    'INPUT_GATE',
                    $gate->id,
                    $gate->name
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Una posición de entrada no puede consumirse dos veces
            |--------------------------------------------------------------------------
            */

            $positionConnections =
                $outgoing
                ->where(
                    'allocation_mode',
                    'POSITION'
                )
                ->groupBy(
                    fn($connection) =>
                    (string)
                    (int)
                    $connection->allocation_value
                );

            foreach (
                $positionConnections
                as
                $position =>
                $positionRoutes
            ) {
                if ($positionRoutes->count() <= 1) {
                    continue;
                }

                $this->addIssue(
                    $errors,
                    'INPUT_POSITION_DUPLICATED',
                    'La posición '
                        .
                        $position
                        .
                        ' de la puerta está conectada a más de un destino competitivo.',
                    'INPUT_GATE',
                    $gate->id,
                    $gate->name
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Todas las posiciones exactas deben tener destino
            |--------------------------------------------------------------------------
            */

            if (
                $gate->exact_participants !== null
                &&
                $outgoing
                ->where(
                    'allocation_mode',
                    'POSITION'
                )
                ->isNotEmpty()
            ) {
                for (
                    $position = 1;
                    $position
                        <=
                        $gate->exact_participants;
                    $position++
                ) {
                    if (
                        $positionConnections
                        ->has(
                            (string)
                            $position
                        )
                    ) {
                        continue;
                    }

                    $this->addIssue(
                        $errors,
                        'INPUT_POSITION_WITHOUT_DESTINATION',
                        'La posición '
                            .
                            $position
                            .
                            ' de la puerta no tiene destino.',
                        'INPUT_GATE',
                        $gate->id,
                        $gate->name
                    );
                }
            }

            if (
                in_array(
                    $gate->distribution_mode,
                    [
                        'MANUAL',
                        'CUSTOM',
                    ],
                    true
                )
            ) {
                $this->addIssue(
                    $warnings,
                    $gate->distribution_mode
                        ===
                        'MANUAL'
                        ? 'MANUAL_INPUT_DISTRIBUTION'
                        : 'CUSTOM_INPUT',
                    'La distribución de esta puerta necesita resolución manual o personalizada.',
                    'INPUT_GATE',
                    $gate->id,
                    $gate->name
                );
            }

            if (
                (
                    $gate->settings['generated_placeholder']
                    ??
                    false
                )
            ) {
                $this->addIssue(
                    $warnings,
                    'INPUT_PLACEHOLDER_REQUIRES_REVIEW',
                    'La puerta fue generada como base temporal y debe revisarse para el modo '
                        .
                        $gate->input_type
                        .
                        '.',
                    'INPUT_GATE',
                    $gate->id,
                    $gate->name
                );
            }
        }

        if (
            $phaseTemplate
            ->inputGates
            ->count()
            ===
            1
            &&
            $phaseTemplate
            ->inputGates
            ->first()
            ?->input_type
            ===
            'PER_SEED'
        ) {
            $this->addIssue(
                $recommendations,
                'PER_SEED_SINGLE_GATE',
                'La entrada por seed normalmente necesita una puerta exacta por posición.',
                'PHASE_TEMPLATE',
                $phaseTemplate->id,
                $phaseTemplate->name
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Encuentros, slots y resultados
    |--------------------------------------------------------------------------
    */

    private function validateEncounters(
        Collection $encounters,
        Collection $connections,
        array &$errors,
        array &$warnings
    ): void {
        foreach (
            $encounters
            as
            $encounter
        ) {
            if ($encounter->status !== 'ACTIVE') {
                continue;
            }

            if ($encounter->entrants_count < 2) {
                $this->encounterError(
                    $errors,
                    $encounter,
                    'ENCOUNTER_TOO_SMALL',
                    'El encuentro debe tener al menos dos slots.'
                );
            }

            if (
                $encounter->qualifiers_count < 1
                ||
                $encounter->qualifiers_count
                >=
                $encounter->entrants_count
            ) {
                $this->encounterError(
                    $errors,
                    $encounter,
                    'INVALID_ENCOUNTER_QUALIFIERS',
                    'Los clasificados deben ser al menos uno y menores que los participantes.'
                );
            }

            if (
                $encounter->min_entrants_to_start < 1
                ||
                $encounter->min_entrants_to_start
                >
                $encounter->entrants_count
            ) {
                $this->encounterError(
                    $errors,
                    $encounter,
                    'INVALID_MINIMUM_TO_START',
                    'El mínimo para iniciar no es compatible con la capacidad del encuentro.'
                );
            }

            if (
                $encounter->encounter_profile
                ===
                'DUEL'
                &&
                (
                    $encounter->entrants_count !== 2
                    ||
                    $encounter->qualifiers_count !== 1
                )
            ) {
                $this->encounterError(
                    $errors,
                    $encounter,
                    'INVALID_DUEL_SHAPE',
                    'Un encuentro con perfil Duelo debe utilizar una relación 2 → 1.'
                );
            }

            if (
                $encounter->encounter_profile
                ===
                'CUSTOM'
            ) {
                $this->addIssue(
                    $warnings,
                    'CUSTOM_ENCOUNTER',
                    'El encuentro personalizado necesitará un tipo de batalla compatible antes de ejecutarse.',
                    'ENCOUNTER',
                    $encounter->id,
                    $encounter->name
                );
            }

            $slots =
                $encounter
                ->slots
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->values();

            if (
                $slots->count()
                !==
                $encounter->entrants_count
            ) {
                $this->encounterError(
                    $errors,
                    $encounter,
                    'ENCOUNTER_SLOT_COUNT_MISMATCH',
                    'La cantidad de slots no coincide con la capacidad K del encuentro.'
                );
            }

            foreach (
                $slots
                as
                $slot
            ) {
                $incoming =
                    $connections
                    ->where(
                        'target_type',
                        'SLOT'
                    )
                    ->where(
                        'target_slot_id',
                        $slot->id
                    )
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->values();

                if (
                    $slot->is_required
                    &&
                    $incoming->isEmpty()
                ) {
                    $this->addIssue(
                        $errors,
                        'REQUIRED_SLOT_WITHOUT_SOURCE',
                        'El slot obligatorio no tiene una fuente.',
                        'SLOT',
                        $slot->id,
                        $encounter->name
                            .
                            ' · Slot '
                            .
                            $slot->position
                    );
                }

                if (
                    $slot->source_policy
                    ===
                    'SINGLE'
                    &&
                    $incoming->count() > 1
                ) {
                    $this->addIssue(
                        $errors,
                        'SINGLE_SLOT_WITH_MULTIPLE_SOURCES',
                        'El slot admite una sola fuente, pero tiene varias conexiones.',
                        'SLOT',
                        $slot->id,
                        $encounter->name
                            .
                            ' · Slot '
                            .
                            $slot->position
                    );
                }

                $knownIncoming =
                    $incoming
                    ->map(
                        fn($connection) =>
                        $this->connectionQuantity(
                            $connection
                        )
                    )
                    ->filter(
                        fn($quantity) =>
                        $quantity !== null
                    );

                if (
                    $knownIncoming->count()
                    ===
                    $incoming->count()
                    &&
                    $knownIncoming->sum()
                    >
                    $slot->capacity
                ) {
                    $this->addIssue(
                        $errors,
                        'SLOT_CAPACITY_EXCEEDED',
                        'Las conexiones pueden enviar más participantes que la capacidad del slot.',
                        'SLOT',
                        $slot->id,
                        $encounter->name
                            .
                            ' · Slot '
                            .
                            $slot->position
                    );
                }
            }

            $this->validateEncounterResults(
                $encounter,
                $connections,
                $errors,
                $warnings
            );
        }
    }

    private function validateEncounterResults(
        PhaseSingleEliminationEncounter $encounter,
        Collection $connections,
        array &$errors,
        array &$warnings
    ): void {
        $results =
            $encounter
            ->results
            ->where(
                'status',
                'ACTIVE'
            )
            ->values();

        if ($results->isEmpty()) {
            $this->encounterError(
                $errors,
                $encounter,
                'ENCOUNTER_WITHOUT_RESULTS',
                'El encuentro no define resultados internos.'
            );

            return;
        }

        $actualEntrants =
            (int) (
                $encounter->settings['actual_entrants']
                ??
                $encounter->entrants_count
            );

        $consumedQuantity =
            $results
            ->where(
                'flow_mode',
                'CONSUME'
            )
            ->sum(
                'quantity'
            );

        if (
            $consumedQuantity
            !==
            $actualEntrants
        ) {
            $this->encounterError(
                $errors,
                $encounter,
                'RESULT_PARTITION_MISMATCH',
                'Los resultados competitivos producen '
                    .
                    $consumedQuantity
                    .
                    ' participantes, pero el encuentro resuelve '
                    .
                    $actualEntrants
                    .
                    '.'
            );
        }

        $activeQuantity =
            $results
            ->where(
                'flow_mode',
                'CONSUME'
            )
            ->where(
                'participant_status',
                'ACTIVE'
            )
            ->sum(
                'quantity'
            );

        if (
            $activeQuantity
            !==
            $encounter->qualifiers_count
        ) {
            $this->encounterError(
                $errors,
                $encounter,
                'QUALIFIER_RESULT_MISMATCH',
                'Los resultados activos no coinciden con la cantidad Q de clasificados.'
            );
        }

        foreach (
            $results
            as
            $result
        ) {
            if ($result->quantity < 1) {
                $this->addIssue(
                    $errors,
                    'RESULT_WITHOUT_QUANTITY',
                    'El resultado debe producir al menos un participante.',
                    'RESULT',
                    $result->id,
                    $encounter->name
                        .
                        ' · '
                        .
                        $result->name
                );
            }

            if (
                $result->position_from !== null
                &&
                (
                    $result->position_from < 1
                    ||
                    $result->position_from
                    >
                    $actualEntrants
                )
            ) {
                $this->addIssue(
                    $errors,
                    'RESULT_POSITION_OUT_OF_RANGE',
                    'La posición inicial del resultado está fuera del encuentro.',
                    'RESULT',
                    $result->id,
                    $encounter->name
                        .
                        ' · '
                        .
                        $result->name
                );
            }

            if (
                $result->position_from !== null
                &&
                $result->position_to !== null
                &&
                $result->position_to
                <
                $result->position_from
            ) {
                $this->addIssue(
                    $errors,
                    'RESULT_POSITION_RANGE_INVALID',
                    'El rango de posiciones del resultado es inválido.',
                    'RESULT',
                    $result->id,
                    $encounter->name
                        .
                        ' · '
                        .
                        $result->name
                );
            }

            $outgoing =
                $connections
                ->where(
                    'source_type',
                    'RESULT'
                )
                ->where(
                    'source_result_id',
                    $result->id
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->values();

            if (
                $result->is_required
                &&
                $result->flow_mode
                ===
                'CONSUME'
                &&
                $outgoing->isEmpty()
            ) {
                $this->addIssue(
                    $errors,
                    'REQUIRED_RESULT_WITHOUT_DESTINATION',
                    'El resultado obligatorio no tiene destino.',
                    'RESULT',
                    $result->id,
                    $encounter->name
                        .
                        ' · '
                        .
                        $result->name
                );
            }

            if (
                ! $result
                    ->accepts_multiple_connections
                &&
                $outgoing->count() > 1
            ) {
                $this->addIssue(
                    $errors,
                    'RESULT_WITH_MULTIPLE_DESTINATIONS',
                    'El resultado no permite varias conexiones.',
                    'RESULT',
                    $result->id,
                    $encounter->name
                        .
                        ' · '
                        .
                        $result->name
                );
            }

            if (
                $outgoing->count() > 1
                &&
                ! $result->is_splittable
            ) {
                $this->addIssue(
                    $errors,
                    'RESULT_SPLIT_NOT_ALLOWED',
                    'El resultado se divide entre varios destinos, pero no está marcado como divisible.',
                    'RESULT',
                    $result->id,
                    $encounter->name
                        .
                        ' · '
                        .
                        $result->name
                );
            }

            $quantities =
                $outgoing
                ->map(
                    fn($connection) =>
                    $this->connectionQuantity(
                        $connection
                    )
                );

            if (
                $outgoing->isNotEmpty()
                &&
                ! $quantities->contains(
                    null
                )
            ) {
                $allocated =
                    (int)
                    $quantities->sum();

                if ($allocated > $result->quantity) {
                    $this->addIssue(
                        $errors,
                        'RESULT_OVERALLOCATED',
                        'Las conexiones intentan distribuir más participantes que los producidos.',
                        'RESULT',
                        $result->id,
                        $encounter->name
                            .
                            ' · '
                            .
                            $result->name
                    );
                }

                if (
                    $result->flow_mode
                    ===
                    'CONSUME'
                    &&
                    $allocated
                    <
                    $result->quantity
                ) {
                    $this->addIssue(
                        $errors,
                        'RESULT_PARTIALLY_UNROUTED',
                        'Parte del resultado competitivo queda sin destino.',
                        'RESULT',
                        $result->id,
                        $encounter->name
                            .
                            ' · '
                            .
                            $result->name
                    );
                }
            }

            if (
                $result->flow_mode
                ===
                'INFORMATIONAL'
                &&
                $outgoing
                ->where(
                    'target_type',
                    'SLOT'
                )
                ->isNotEmpty()
            ) {
                $this->addIssue(
                    $errors,
                    'INFORMATIONAL_RESULT_FEEDS_SLOT',
                    'Un resultado informativo no puede duplicar participantes hacia otro encuentro.',
                    'RESULT',
                    $result->id,
                    $encounter->name
                        .
                        ' · '
                        .
                        $result->name
                );
            }

            if (
                in_array(
                    $result->result_type,
                    [
                        'MANUAL',
                        'CUSTOM',
                    ],
                    true
                )
            ) {
                $this->addIssue(
                    $warnings,
                    $result->result_type
                        ===
                        'MANUAL'
                        ? 'MANUAL_RESULT'
                        : 'CUSTOM_ENCOUNTER',
                    'El resultado necesita resolución manual o un tipo de batalla personalizado.',
                    'RESULT',
                    $result->id,
                    $encounter->name
                        .
                        ' · '
                        .
                        $result->name
                );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Integridad de conexiones
    |--------------------------------------------------------------------------
    */

    private function validateConnections(
        PhaseTemplate $phaseTemplate,
        Collection $connections,
        array &$errors,
        array &$warnings
    ): void {
        $seen = [];

        foreach (
            $connections
            as
            $connection
        ) {
            if ($connection->status !== 'ACTIVE') {
                continue;
            }

            $sourceCount =
                (
                    $connection
                    ->source_input_gate_id
                    !==
                    null
                    ? 1
                    : 0
                )
                +
                (
                    $connection
                    ->source_result_id
                    !==
                    null
                    ? 1
                    : 0
                );

            $targetCount =
                (
                    $connection
                    ->target_slot_id
                    !==
                    null
                    ? 1
                    : 0
                )
                +
                (
                    $connection
                    ->target_phase_exit_id
                    !==
                    null
                    ? 1
                    : 0
                );

            if ($sourceCount !== 1) {
                $this->connectionError(
                    $errors,
                    $connection,
                    'INVALID_CONNECTION_SOURCE',
                    'La conexión debe tener exactamente un origen.'
                );
            }

            if ($targetCount !== 1) {
                $this->connectionError(
                    $errors,
                    $connection,
                    'INVALID_CONNECTION_TARGET',
                    'La conexión debe tener exactamente un destino.'
                );
            }

            if (
                $connection->source_type
                ===
                'INPUT_GATE'
                &&
                (
                    ! $connection->sourceInputGate
                    ||
                    $connection
                    ->sourceInputGate
                    ->phase_template_id
                    !==
                    $phaseTemplate->id
                )
            ) {
                $this->connectionError(
                    $errors,
                    $connection,
                    'FOREIGN_INPUT_GATE',
                    'La puerta de origen no pertenece a esta plantilla.'
                );
            }

            if (
                $connection->source_type
                ===
                'RESULT'
                &&
                (
                    ! $connection->sourceResult
                    ||
                    $connection
                    ->sourceResult
                    ->encounter
                    ?->phase_template_id
                    !==
                    $phaseTemplate->id
                )
            ) {
                $this->connectionError(
                    $errors,
                    $connection,
                    'FOREIGN_RESULT',
                    'El resultado de origen no pertenece a esta plantilla.'
                );
            }

            if (
                $connection->target_type
                ===
                'SLOT'
                &&
                (
                    ! $connection->targetSlot
                    ||
                    $connection
                    ->targetSlot
                    ->encounter
                    ?->phase_template_id
                    !==
                    $phaseTemplate->id
                )
            ) {
                $this->connectionError(
                    $errors,
                    $connection,
                    'FOREIGN_SLOT',
                    'El slot de destino no pertenece a esta plantilla.'
                );
            }

            if (
                $connection->target_type
                ===
                'PHASE_EXIT'
                &&
                (
                    ! $connection->targetPhaseExit
                    ||
                    $connection
                    ->targetPhaseExit
                    ->phase_template_id
                    !==
                    $phaseTemplate->id
                )
            ) {
                $this->connectionError(
                    $errors,
                    $connection,
                    'FOREIGN_PHASE_EXIT',
                    'La salida de destino no pertenece a esta plantilla.'
                );
            }

            if (
                $connection->allocation_mode
                ===
                'TAKE_N'
                &&
                (
                    $connection->allocation_value
                    ===
                    null
                    ||
                    $connection->allocation_value
                    <
                    1
                )
            ) {
                $this->connectionError(
                    $errors,
                    $connection,
                    'INVALID_TAKE_N',
                    'La asignación TAKE_N necesita una cantidad mayor que cero.'
                );
            }

            if (
                $connection->allocation_mode
                ===
                'POSITION'
                &&
                (
                    $connection->allocation_value
                    ===
                    null
                    ||
                    $connection->allocation_value
                    <
                    1
                )
            ) {
                $this->connectionError(
                    $errors,
                    $connection,
                    'INVALID_SOURCE_POSITION',
                    'La conexión por posición necesita una posición válida.'
                );
            }

            $sourceKey =
                $connection->source_type
                .
                ':'
                .
                (
                    $connection->source_input_gate_id
                    ??
                    $connection->source_result_id
                );

            $targetKey =
                $connection->target_type
                .
                ':'
                .
                (
                    $connection->target_slot_id
                    ??
                    $connection->target_phase_exit_id
                );

            $allocationKey =
                $connection->allocation_mode
                .
                ':'
                .
                (
                    $connection->allocation_value
                    ??
                    'NULL'
                );

            $duplicateKey =
                $sourceKey
                .
                '>'
                .
                $targetKey
                .
                '>'
                .
                $allocationKey;

            if (isset($seen[$duplicateKey])) {
                $this->connectionError(
                    $errors,
                    $connection,
                    'DUPLICATED_CONNECTION',
                    'Existe otra conexión idéntica entre el mismo origen y destino.'
                );
            }

            $seen[$duplicateKey] =
                true;

            if (
                in_array(
                    $connection->condition_type,
                    [
                        'MANUAL',
                        'CUSTOM',
                    ],
                    true
                )
            ) {
                $this->addIssue(
                    $warnings,
                    'MANUAL_CONNECTION',
                    'La conexión necesita resolución manual o personalizada.',
                    'CONNECTION',
                    $connection->id,
                    $connection->label
                        ??
                        $connection->code
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Retroceso topológico evidente
            |--------------------------------------------------------------------------
            */

            if (
                $connection->source_type
                ===
                'RESULT'
                &&
                $connection->target_type
                ===
                'SLOT'
                &&
                $connection
                ->sourceResult
                ?->encounter
                ?->round
                &&
                $connection
                ->targetSlot
                ?->encounter
                ?->round
            ) {
                $sourceStage =
                    $connection
                    ->sourceResult
                    ->encounter
                    ->round
                    ->stage_number;

                $targetStage =
                    $connection
                    ->targetSlot
                    ->encounter
                    ->round
                    ->stage_number;

                if ($targetStage < $sourceStage) {
                    $this->connectionError(
                        $errors,
                        $connection,
                        'BACKWARD_CONNECTION',
                        'La conexión regresa hacia una etapa anterior.'
                    );
                }

                if (
                    $targetStage === $sourceStage
                    &&
                    $connection
                    ->sourceResult
                    ->encounter_id
                    !==
                    $connection
                    ->targetSlot
                    ->encounter_id
                ) {
                    $this->connectionError(
                        $warnings,
                        $connection,
                        'SAME_STAGE_CONNECTION',
                        'La conexión ocurre dentro de la misma etapa; revisa su orden y activación.'
                    );
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Topología y alcanzabilidad
    |--------------------------------------------------------------------------
    */

    private function validateTopology(
        PhaseTemplate $phaseTemplate,
        Collection $encounters,
        Collection $connections,
        array &$errors,
        array &$warnings
    ): void {
        $adjacency = [];

        foreach (
            $encounters
            as
            $encounter
        ) {
            $adjacency[$encounter->id] =
                [];
        }

        foreach (
            $connections
            as
            $connection
        ) {
            if (
                $connection->status !== 'ACTIVE'
                ||
                $connection->source_type
                !==
                'RESULT'
                ||
                $connection->target_type
                !==
                'SLOT'
                ||
                ! $connection->sourceResult
                ||
                ! $connection->targetSlot
            ) {
                continue;
            }

            $sourceEncounterId =
                $connection
                ->sourceResult
                ->encounter_id;

            $targetEncounterId =
                $connection
                ->targetSlot
                ->encounter_id;

            $adjacency[$sourceEncounterId][] =
                $targetEncounterId;
        }

        if (
            $this->hasCycle(
                $adjacency
            )
        ) {
            $this->addIssue(
                $errors,
                'INTERNAL_GRAPH_CYCLE',
                'El grafo interno contiene una ruta circular.',
                'PHASE_TEMPLATE',
                $phaseTemplate->id,
                $phaseTemplate->name
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Encuentros alcanzables desde una puerta
        |--------------------------------------------------------------------------
        */

        $reachable = [];
        $queue = [];

        foreach (
            $connections
            as
            $connection
        ) {
            if (
                $connection->status !== 'ACTIVE'
                ||
                $connection->source_type
                !==
                'INPUT_GATE'
                ||
                $connection->target_type
                !==
                'SLOT'
                ||
                ! $connection->targetSlot
            ) {
                continue;
            }

            $queue[] =
                $connection
                ->targetSlot
                ->encounter_id;
        }

        while ($queue !== []) {
            $encounterId =
                array_shift(
                    $queue
                );

            if (isset($reachable[$encounterId])) {
                continue;
            }

            $reachable[$encounterId] =
                true;

            foreach (
                $adjacency[$encounterId]
                    ??
                    []
                as
                $nextEncounterId
            ) {
                $queue[] =
                    $nextEncounterId;
            }
        }

        foreach (
            $encounters
            as
            $encounter
        ) {
            if (
                $encounter->status !== 'ACTIVE'
                ||
                isset(
                    $reachable[$encounter->id]
                )
            ) {
                continue;
            }

            $this->addIssue(
                $errors,
                'UNREACHABLE_ENCOUNTER',
                'El encuentro no puede alcanzarse desde ninguna puerta de entrada.',
                'ENCOUNTER',
                $encounter->id,
                $encounter->name
            );
        }

        $exitConnections =
            $connections
            ->where(
                'target_type',
                'PHASE_EXIT'
            )
            ->where(
                'status',
                'ACTIVE'
            );

        if (
            $encounters->isNotEmpty()
            &&
            $exitConnections->isEmpty()
        ) {
            $this->addIssue(
                $errors,
                'GRAPH_WITHOUT_EXIT_ROUTE',
                'Ningún resultado del grafo llega a una puerta de salida.',
                'PHASE_TEMPLATE',
                $phaseTemplate->id,
                $phaseTemplate->name
            );
        }
    }

    private function hasCycle(
        array $adjacency
    ): bool {
        $state = [];

        $visit =
            function (
                int $node
            ) use (
                &$visit,
                &$state,
                $adjacency
            ): bool {
                if (
                    ($state[$node] ?? 0)
                    ===
                    1
                ) {
                    return true;
                }

                if (
                    ($state[$node] ?? 0)
                    ===
                    2
                ) {
                    return false;
                }

                $state[$node] =
                    1;

                foreach (
                    $adjacency[$node]
                        ??
                        []
                    as
                    $next
                ) {
                    if ($visit($next)) {
                        return true;
                    }
                }

                $state[$node] =
                    2;

                return false;
            };

        foreach (
            array_keys(
                $adjacency
            )
            as
            $node
        ) {
            if (
                $visit(
                    (int)
                    $node
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Salidas
    |--------------------------------------------------------------------------
    */

    private function validateExits(
        PhaseTemplate $phaseTemplate,
        Collection $connections,
        array &$errors,
        array &$warnings
    ): void {
        foreach (
            $phaseTemplate->exits
            as
            $exit
        ) {
            if (
                $exit->status !== 'ACTIVE'
                ||
                $exit->resolution_mode
                !==
                'INTERNAL_GRAPH'
            ) {
                continue;
            }

            $incoming =
                $connections
                ->where(
                    'target_type',
                    'PHASE_EXIT'
                )
                ->where(
                    'target_phase_exit_id',
                    $exit->id
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->values();

            if ($incoming->isEmpty()) {
                $this->addIssue(
                    $warnings,
                    'UNUSED_INTERNAL_EXIT',
                    'La salida está configurada para el grafo interno, pero no recibe resultados.',
                    'PHASE_EXIT',
                    $exit->id,
                    $exit->name
                );

                continue;
            }

            $quantities =
                $incoming
                ->map(
                    fn($connection) =>
                    $this->connectionQuantity(
                        $connection
                    )
                );

            if ($quantities->contains(null)) {
                continue;
            }

            $produced =
                (int)
                $quantities->sum();

            if (
                $exit->exact_participants !== null
                &&
                $produced
                !==
                $exit->exact_participants
            ) {
                $this->addIssue(
                    $errors,
                    'EXIT_EXACT_CAPACITY_MISMATCH',
                    'La salida promete '
                        .
                        $exit->exact_participants
                        .
                        ' participantes, pero las rutas producen '
                        .
                        $produced
                        .
                        '.',
                    'PHASE_EXIT',
                    $exit->id,
                    $exit->name
                );
            }

            if (
                $exit->max_participants !== null
                &&
                $produced
                >
                $exit->max_participants
            ) {
                $this->addIssue(
                    $errors,
                    'EXIT_MAXIMUM_EXCEEDED',
                    'Las rutas producen más participantes que el máximo permitido por la salida.',
                    'PHASE_EXIT',
                    $exit->id,
                    $exit->name
                );
            }

            if (
                $exit->min_participants !== null
                &&
                $produced
                <
                $exit->min_participants
            ) {
                $this->addIssue(
                    $errors,
                    'EXIT_MINIMUM_NOT_REACHED',
                    'Las rutas no alcanzan el mínimo requerido por la salida.',
                    'PHASE_EXIT',
                    $exit->id,
                    $exit->name
                );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Cantidad transportada
    |--------------------------------------------------------------------------
    */

    private function connectionQuantity(
        PhaseSingleEliminationConnection $connection
    ): ?int {
        if (
            $connection->allocation_mode
            ===
            'POSITION'
        ) {
            return 1;
        }

        if (
            $connection->allocation_mode
            ===
            'TAKE_N'
        ) {
            return
                (int)
                $connection->allocation_value;
        }

        if (
            $connection->allocation_mode
            ===
            'ALL'
        ) {
            if (
                $connection->source_type
                ===
                'RESULT'
            ) {
                return
                    $connection
                    ->sourceResult
                    ?->quantity;
            }

            if (
                $connection->source_type
                ===
                'INPUT_GATE'
            ) {
                return
                    $connection
                    ->sourceInputGate
                    ?->exact_participants;
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Issues
    |--------------------------------------------------------------------------
    */

    private function encounterError(
        array &$issues,
        PhaseSingleEliminationEncounter $encounter,
        string $code,
        string $message
    ): void {
        $this->addIssue(
            $issues,
            $code,
            $message,
            'ENCOUNTER',
            $encounter->id,
            $encounter->name
        );
    }

    private function connectionError(
        array &$issues,
        PhaseSingleEliminationConnection $connection,
        string $code,
        string $message
    ): void {
        $this->addIssue(
            $issues,
            $code,
            $message,
            'CONNECTION',
            $connection->id,
            $connection->label
                ??
                $connection->code
        );
    }

    private function addIssue(
        array &$issues,
        string $code,
        string $message,
        string $entityType,
        ?int $entityId,
        string $entityLabel
    ): void {
        $issues[] = [
            'code' =>
            $code,

            'message' =>
            $message,

            'entity_type' =>
            $entityType,

            'entity_id' =>
            $entityId,

            'entity_label' =>
            $entityLabel,
        ];
    }

    private function uniqueIssues(
        array $issues
    ): array {
        return collect($issues)
            ->unique(
                fn(array $issue) =>
                $issue['code']
                    .
                    ':'
                    .
                    $issue['entity_type']
                    .
                    ':'
                    .
                    (
                        $issue['entity_id']
                        ??
                        'NULL'
                    )
            )
            ->values()
            ->all();
    }

    private function ensureCorrectType(
        PhaseTemplate $phaseTemplate
    ): void {
        abort_unless(
            $phaseTemplate->phase_type
                ===
                'SINGLE_ELIMINATION',
            404
        );
    }
}
