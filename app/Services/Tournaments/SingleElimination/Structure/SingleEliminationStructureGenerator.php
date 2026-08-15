<?php

namespace App\Services\Tournaments\SingleElimination\Structure;

use App\Models\PhaseExit;
use App\Models\PhaseInputGate;
use App\Models\PhaseSingleEliminationConnection;
use App\Models\PhaseSingleEliminationEncounter;
use App\Models\PhaseSingleEliminationResult;
use App\Models\PhaseSingleEliminationRound;
use App\Models\PhaseSingleEliminationSlot;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationBracketCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SingleEliminationStructureGenerator
{
    public function __construct(
        private readonly
        SingleEliminationBracketCalculator $calculator,

        private readonly
        SingleEliminationEntryPortSynchronizer $entryPortSynchronizer
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Generar estructura
    |--------------------------------------------------------------------------
    */

    public function generate(
        PhaseTemplate $phaseTemplate,
        int $participants,
        bool $replaceManual = false
    ): array {
        $this->ensureCorrectType(
            $phaseTemplate
        );

        return DB::transaction(
            function () use (
                $phaseTemplate,
                $participants,
                $replaceManual
            ) {
                $lockedPhase =
                    PhaseTemplate::query()
                    ->whereKey(
                        $phaseTemplate->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $settings =
                    $lockedPhase
                    ->singleEliminationSetting()
                    ->firstOrFail();

                $roundRules =
                    $lockedPhase
                    ->singleEliminationRoundRules()
                    ->get();

                $preview =
                    $this->calculator
                    ->calculate(
                        $lockedPhase,
                        $settings,
                        $participants,
                        $roundRules
                    );

                $this->ensurePreviewCanGenerate(
                    $preview
                );

                $this->ensureReplacementAllowed(
                    $lockedPhase,
                    $replaceManual
                );

                /*
                |--------------------------------------------------------------------------
                | Reinicio transaccional
                |--------------------------------------------------------------------------
                */

                $lockedPhase
                    ->singleEliminationConnections()
                    ->delete();

                $lockedPhase
                    ->singleEliminationRounds()
                    ->delete();

                $lockedPhase
                    ->inputGates()
                    ->delete();

                /*
                |--------------------------------------------------------------------------
                | Salidas externas
                |--------------------------------------------------------------------------
                */

                $targetSurvivors =
                    (int)
                    $preview['target_survivors'];

                $survivorExit =
                    $this->ensureExit(
                        $lockedPhase,
                        'SURVIVORS',
                        $targetSurvivors,
                        $targetSurvivors === 1
                            ? 'Ganador'
                            : 'Supervivientes',
                        10
                    );

                $eliminatedQuantity =
                    max(
                        0,
                        $participants
                            -
                            $targetSurvivors
                    );

                $eliminatedExit =
                    $this->ensureExit(
                        $lockedPhase,
                        'ELIMINATED',
                        $eliminatedQuantity,
                        'Eliminados',
                        20
                    );

                /*
                |--------------------------------------------------------------------------
                | Entradas iniciales
                |--------------------------------------------------------------------------
                */

                $feeders =
                    $this->createInputFeeders(
                        $lockedPhase,
                        $settings->input_mode,
                        $settings->seeding_mode,
                        $participants
                    );

                $roundSequence =
                    0;

                $encounterSequence =
                    0;

                $connectionSequence =
                    0;

                /*
                |--------------------------------------------------------------------------
                | Construcción de las rondas
                |--------------------------------------------------------------------------
                */

                foreach (
                    $preview['rounds']
                    as
                    $roundData
                ) {
                    $roundSequence++;

                    $round =
                        $this->createRound(
                            $lockedPhase,
                            $roundData,
                            $roundSequence
                        );

                    $distribution =
                        $this->roundDistribution(
                            $roundData
                        );

                    $nextFeeders = [];

                    foreach (
                        $distribution
                        as
                        $encounterIndex =>
                        $actualEntrants
                    ) {
                        $encounterSequence++;

                        $configuredEntrants =
                            max(
                                2,
                                (int) (
                                    $roundData['entrants_per_match']
                                    ??
                                    2
                                )
                            );

                        $qualifiers =
                            max(
                                1,
                                (int) (
                                    $roundData['qualifiers_per_match']
                                    ??
                                    1
                                )
                            );

                        $incomplete =
                            $actualEntrants
                            <
                            $configuredEntrants;

                        $encounterEntrants =
                            $incomplete
                            ? $configuredEntrants
                            : $actualEntrants;

                        $encounter =
                            $this->createEncounter(
                                $lockedPhase,
                                $round,
                                $roundData,
                                $encounterSequence,
                                $encounterIndex + 1,
                                $encounterEntrants,
                                $actualEntrants,
                                $qualifiers,
                                $incomplete
                            );

                        $slots =
                            $this->createSlots(
                                $encounter,
                                $encounterEntrants,
                                $actualEntrants,
                                $settings->pairing_mode
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Alimentar slots reales
                        |--------------------------------------------------------------------------
                        */

                        for (
                            $position = 1;
                            $position <= $actualEntrants;
                            $position++
                        ) {
                            $source =
                                array_shift(
                                    $feeders
                                );

                            if (! $source) {
                                continue;
                            }

                            $slot =
                                $slots->firstWhere(
                                    'position',
                                    $position
                                );

                            if (! $slot) {
                                continue;
                            }

                            $connectionSequence++;

                            $this->createConnection(
                                $lockedPhase,
                                $connectionSequence,
                                $source,
                                'SLOT',
                                $slot->id,
                                null,
                                $source['label']
                                    .
                                    ' → '
                                    .
                                    $encounter->name
                                    .
                                    ' · Slot '
                                    .
                                    $slot->position
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Resultados clasificatorios
                        |--------------------------------------------------------------------------
                        */

                        for (
                            $position = 1;
                            $position <= $qualifiers;
                            $position++
                        ) {
                            $result =
                                $this->createQualifierResult(
                                    $encounter,
                                    $position,
                                    $actualEntrants,
                                    $qualifiers
                                );

                            $nextFeeders[] = [
                                'type' =>
                                'RESULT',

                                'input_gate_id' =>
                                null,

                                'result_id' =>
                                $result->id,

                                'position' =>
                                null,

                                'label' =>
                                $encounter->name
                                    .
                                    ' · '
                                    .
                                    $result->name,
                            ];
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Resultado de eliminados
                        |--------------------------------------------------------------------------
                        */

                        $eliminated =
                            $actualEntrants
                            -
                            $qualifiers;

                        if ($eliminated > 0) {
                            $result =
                                $this->createEliminatedResult(
                                    $encounter,
                                    $qualifiers + 1,
                                    $eliminated,
                                    $actualEntrants
                                );

                            $connectionSequence++;

                            $this->createConnection(
                                $lockedPhase,
                                $connectionSequence,
                                [
                                    'type' =>
                                    'RESULT',

                                    'input_gate_id' =>
                                    null,

                                    'result_id' =>
                                    $result->id,

                                    'position' =>
                                    null,

                                    'label' =>
                                    $encounter->name
                                        .
                                        ' · '
                                        .
                                        $result->name,
                                ],
                                'PHASE_EXIT',
                                null,
                                $eliminatedExit->id,
                                $encounter->name
                                    .
                                    ' · Eliminados → '
                                    .
                                    $eliminatedExit->name
                            );
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | BYEs o participantes que no disputan esta ronda
                    |--------------------------------------------------------------------------
                    |
                    | Los feeders restantes avanzan directamente.
                    | No se crea un participante falso para representar el BYE.
                    |
                    */

                    foreach (
                        $feeders
                        as
                        $bypassFeeder
                    ) {
                        $bypassFeeder['label'] =
                            $bypassFeeder['label']
                            .
                            ' · BYE';

                        $nextFeeders[] =
                            $bypassFeeder;
                    }

                    $feeders =
                        $nextFeeders;
                }

                /*
                |--------------------------------------------------------------------------
                | Supervivientes finales
                |--------------------------------------------------------------------------
                */

                foreach (
                    $feeders
                    as
                    $finalFeeder
                ) {
                    $connectionSequence++;

                    $this->createConnection(
                        $lockedPhase,
                        $connectionSequence,
                        $finalFeeder,
                        'PHASE_EXIT',
                        null,
                        $survivorExit->id,
                        $finalFeeder['label']
                            .
                            ' → '
                            .
                            $survivorExit->name
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Estado y huella de la estructura
                |--------------------------------------------------------------------------
                */

                $fingerprint =
                    $this->fingerprint(
                        $lockedPhase,
                        $settings,
                        $roundRules,
                        $participants
                    );

                $settings->update([
                    'structure_status' =>
                    'GENERATED',

                    'structure_version' => (
                        (int)
                        $settings->structure_version
                    )
                        +
                        1,

                    'structure_fingerprint' =>
                    $fingerprint,

                    'structure_generated_at' =>
                    now(),

                    'structure_validated_at' =>
                    null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Proyección hacia Tournament Graph
                |--------------------------------------------------------------------------
                */

                $this
                    ->entryPortSynchronizer
                    ->syncPhase(
                        $lockedPhase
                    );

                return [
                    'preview' =>
                    $preview,

                    'fingerprint' =>
                    $fingerprint,

                    'rounds' =>
                    $lockedPhase
                        ->singleEliminationRounds()
                        ->count(),

                    'encounters' =>
                    $lockedPhase
                        ->singleEliminationEncounters()
                        ->count(),

                    'connections' =>
                    $lockedPhase
                        ->singleEliminationConnections()
                        ->count(),

                    'input_gates' =>
                    $lockedPhase
                        ->inputGates()
                        ->count(),

                    'survivor_exit_id' =>
                    $survivorExit->id,

                    'eliminated_exit_id' =>
                    $eliminatedExit->id,
                ];
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Crear entradas y feeders
    |--------------------------------------------------------------------------
    */

    private function createInputFeeders(
        PhaseTemplate $phaseTemplate,
        string $inputMode,
        string $distributionMode,
        int $participants
    ): array {
        $feeders = [];

        if ($inputMode === 'PER_SEED') {
            for (
                $seed = 1;
                $seed <= $participants;
                $seed++
            ) {
                $gate =
                    $phaseTemplate
                    ->inputGates()
                    ->create([
                        'sequence_number' =>
                        $seed,

                        'code' =>
                        PhaseInputGate::formatCode(
                            $seed
                        ),

                        'name' =>
                        'Seed '
                            .
                            $seed,

                        'description' =>
                        'Entrada exacta reservada para el seed '
                            .
                            $seed
                            .
                            '.',

                        'input_type' =>
                        'PER_SEED',

                        'merge_policy' =>
                        'FIRST_AVAILABLE',

                        'distribution_mode' =>
                        'INPUT_ORDER',

                        'empty_behavior' =>
                        'ERROR',

                        'min_participants' =>
                        1,

                        'max_participants' =>
                        1,

                        'exact_participants' =>
                        1,

                        'is_required' =>
                        true,

                        'accepts_batch' =>
                        false,

                        'accepts_multiple_connections' =>
                        false,

                        'priority' =>
                        $seed,

                        'sort_order' =>
                        $seed * 10,

                        'status' =>
                        'ACTIVE',

                        'generation_source' =>
                        'GENERATED',

                        'is_locked' =>
                        false,

                        'settings' => [
                            'seed' =>
                            $seed,
                        ],
                    ]);

                $feeders[] = [
                    'type' =>
                    'INPUT_GATE',

                    'input_gate_id' =>
                    $gate->id,

                    'result_id' =>
                    null,

                    'position' =>
                    1,

                    'label' =>
                    $gate->name,
                ];
            }

            return $feeders;
        }

        $gate =
            $phaseTemplate
            ->inputGates()
            ->create([
                'sequence_number' =>
                1,

                'code' =>
                PhaseInputGate::formatCode(
                    1
                ),

                'name' =>
                $this->defaultGateName(
                    $inputMode
                ),

                'description' =>
                'Puerta principal generada desde la configuración de Eliminación Simple.',

                'input_type' =>
                $inputMode,

                'merge_policy' =>
                'APPEND',

                'distribution_mode' =>
                $distributionMode,

                'empty_behavior' =>
                'ERROR',

                'min_participants' =>
                $participants,

                'max_participants' =>
                $participants,

                'exact_participants' =>
                $participants,

                'is_required' =>
                true,

                'accepts_batch' =>
                true,

                'accepts_multiple_connections' =>
                true,

                'priority' =>
                10,

                'sort_order' =>
                10,

                'status' =>
                'ACTIVE',

                'generation_source' =>
                'GENERATED',

                'is_locked' =>
                false,

                'settings' => [
                    'generated_placeholder' =>
                    in_array(
                        $inputMode,
                        [
                            'GROUPED',
                            'HYBRID',
                            'CUSTOM',
                        ],
                        true
                    ),
                ],
            ]);

        for (
            $position = 1;
            $position <= $participants;
            $position++
        ) {
            $feeders[] = [
                'type' =>
                'INPUT_GATE',

                'input_gate_id' =>
                $gate->id,

                'result_id' =>
                null,

                'position' =>
                $position,

                'label' =>
                $gate->name
                    .
                    ' · Posición '
                    .
                    $position,
            ];
        }

        return $feeders;
    }

    private function defaultGateName(
        string $inputMode
    ): string {
        return match ($inputMode) {
            'GROUPED' =>
            'Grupo principal',

            'HYBRID' =>
            'Bolsa general híbrida',

            'CUSTOM' =>
            'Entrada personalizada',

            default =>
            'Entrada general',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Crear ronda
    |--------------------------------------------------------------------------
    */

    private function createRound(
        PhaseTemplate $phaseTemplate,
        array $roundData,
        int $sequence
    ): PhaseSingleEliminationRound {
        return $phaseTemplate
            ->singleEliminationRounds()
            ->create([
                'sequence_number' =>
                $sequence,

                'code' =>
                PhaseSingleEliminationRound::formatCode(
                    $sequence
                ),

                'name' =>
                $roundData['label']
                    ??
                    (
                        'Ronda '
                        .
                        $sequence
                    ),

                'description' =>
                'Ronda estructural generada automáticamente.',

                'stage_number' =>
                $sequence,

                'branch_code' =>
                'MAIN',

                'round_type' =>
                ! empty($roundData['preliminary'])
                    ? 'PRELIMINARY'
                    : 'MAIN',

                'participants_expected' =>
                (int) (
                    $roundData['participants']
                    ??
                    $roundData['slots']
                    ??
                    0
                ),

                'qualifiers_expected' =>
                (int) (
                    $roundData['survivors']
                    ??
                    0
                ),

                'sort_order' =>
                $sequence * 10,

                'status' =>
                'ACTIVE',

                'generation_source' =>
                'GENERATED',

                'is_locked' =>
                false,

                'settings' => [
                    'preview_key' =>
                    $roundData['key']
                        ??
                        null,

                    'has_override' =>
                    (bool) (
                        $roundData['has_override']
                        ??
                        false
                    ),

                    'byes' =>
                    (int) (
                        $roundData['byes']
                        ??
                        0
                    ),

                    'remainder_policy' =>
                    $roundData['remainder_policy']
                        ??
                        null,
                ],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Distribución real de encuentros
    |--------------------------------------------------------------------------
    */

    private function roundDistribution(
        array $roundData
    ): array {
        if (
            isset($roundData['distribution'])
            &&
            is_array(
                $roundData['distribution']
            )
        ) {
            return array_values(
                array_map(
                    fn($value) =>
                    max(
                        2,
                        (int)
                        $value
                    ),
                    $roundData['distribution']
                )
            );
        }

        $series =
            max(
                0,
                (int) (
                    $roundData['series']
                    ??
                    0
                )
            );

        $entrants =
            max(
                2,
                (int) (
                    $roundData['entrants_per_match']
                    ??
                    2
                )
            );

        return array_fill(
            0,
            $series,
            $entrants
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Crear encuentro
    |--------------------------------------------------------------------------
    */

    private function createEncounter(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationRound $round,
        array $roundData,
        int $sequence,
        int $position,
        int $configuredEntrants,
        int $actualEntrants,
        int $qualifiers,
        bool $incomplete
    ): PhaseSingleEliminationEncounter {
        $seriesFormat =
            $roundData['series_format']
            ??
            'BEST_OF';

        return $round
            ->encounters()
            ->create([
                'phase_template_id' =>
                $phaseTemplate->id,

                'sequence_number' =>
                $sequence,

                'code' =>
                PhaseSingleEliminationEncounter::formatCode(
                    $sequence
                ),

                'name' =>
                $round->name
                    .
                    ' · Encuentro '
                    .
                    $position,

                'description' =>
                $incomplete
                    ? 'Encuentro generado con slots opcionales.'
                    : 'Encuentro generado automáticamente.',

                'position' =>
                $position,

                'entrants_count' =>
                $configuredEntrants,

                'qualifiers_count' =>
                $qualifiers,

                'min_entrants_to_start' =>
                $incomplete
                    ? $actualEntrants
                    : $configuredEntrants,

                'encounter_profile' =>
                $roundData['encounter_profile']
                    ??
                    (
                        $configuredEntrants === 2
                        &&
                        $qualifiers === 1
                        ? 'DUEL'
                        : 'MULTI_COMPETITOR'
                    ),

                'activation_policy' =>
                $incomplete
                    ? 'MINIMUM_REACHED'
                    : 'ALL_REQUIRED',

                'allows_incomplete' =>
                $incomplete,

                'series_format' =>
                $seriesFormat,

                'best_of' =>
                $seriesFormat === 'BEST_OF'
                    ? (
                        (int) (
                            $roundData['best_of']
                            ??
                            1
                        )
                    )
                    : null,

                'fixed_games' =>
                $seriesFormat
                    ===
                    'FIXED_GAMES'
                    ? (
                        (int) (
                            $roundData['fixed_games']
                            ??
                            1
                        )
                    )
                    : null,

                'sort_order' =>
                $position * 10,

                'status' =>
                'ACTIVE',

                'generation_source' =>
                'GENERATED',

                'is_locked' =>
                false,

                'settings' => [
                    'actual_entrants' =>
                    $actualEntrants,
                ],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Crear slots
    |--------------------------------------------------------------------------
    */

    private function createSlots(
        PhaseSingleEliminationEncounter $encounter,
        int $slotCount,
        int $actualEntrants,
        string $pairingMode
    ): Collection {
        $slots = collect();

        for (
            $position = 1;
            $position <= $slotCount;
            $position++
        ) {
            $optional =
                $position
                >
                $actualEntrants;

            $slots->push(
                $encounter
                    ->slots()
                    ->create([
                        'code' =>
                        PhaseSingleEliminationSlot::formatCode(
                            $position
                        ),

                        'position' =>
                        $position,

                        'slot_type' =>
                        $optional
                            ? 'OPTIONAL'
                            : 'PARTICIPANT',

                        'capacity' =>
                        1,

                        'is_required' =>
                        ! $optional,

                        'source_policy' =>
                        'SINGLE',

                        'empty_behavior' =>
                        $optional
                            ? 'ALLOW_EMPTY'
                            : 'WAIT',

                        'assignment_rule' =>
                        $this->slotAssignmentRule(
                            $pairingMode
                        ),

                        'sort_order' =>
                        $position * 10,

                        'status' =>
                        'ACTIVE',

                        'generation_source' =>
                        'GENERATED',

                        'is_locked' =>
                        false,
                    ])
            );
        }

        return $slots;
    }

    private function slotAssignmentRule(
        string $pairingMode
    ): string {
        return match ($pairingMode) {
            'STANDARD_SEEDED' =>
            'SEEDED',

            'RANDOM' =>
            'RANDOM',

            default =>
            'POSITIONAL',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Resultados clasificatorios
    |--------------------------------------------------------------------------
    */

    private function createQualifierResult(
        PhaseSingleEliminationEncounter $encounter,
        int $position,
        int $actualEntrants,
        int $qualifiers
    ): PhaseSingleEliminationResult {
        $winner =
            $actualEntrants === 2
            &&
            $qualifiers === 1
            &&
            $position === 1;

        return $encounter
            ->results()
            ->create([
                'sequence_number' =>
                $position,

                'code' =>
                PhaseSingleEliminationResult::formatCode(
                    $position
                ),

                'name' =>
                $winner
                    ? 'Ganador'
                    : (
                        'Posición '
                        .
                        $position
                    ),

                'description' =>
                'Resultado clasificatorio generado automáticamente.',

                'result_type' =>
                $winner
                    ? 'WINNER'
                    : 'POSITION',

                'position_from' =>
                $position,

                'position_to' =>
                $position,

                'quantity' =>
                1,

                'flow_mode' =>
                'CONSUME',

                'participant_status' =>
                'ACTIVE',

                'is_required' =>
                true,

                'is_splittable' =>
                false,

                'accepts_multiple_connections' =>
                false,

                'priority' =>
                $position * 10,

                'sort_order' =>
                $position * 10,

                'status' =>
                'ACTIVE',

                'generation_source' =>
                'GENERATED',

                'is_locked' =>
                false,
            ]);
    }

    private function createEliminatedResult(
        PhaseSingleEliminationEncounter $encounter,
        int $sequence,
        int $quantity,
        int $actualEntrants
    ): PhaseSingleEliminationResult {
        return $encounter
            ->results()
            ->create([
                'sequence_number' =>
                $sequence,

                'code' =>
                PhaseSingleEliminationResult::formatCode(
                    $sequence
                ),

                'name' =>
                $quantity === 1
                    ? 'Eliminado'
                    : 'Eliminados',

                'description' =>
                'Participantes que no continúan en la estructura generada.',

                'result_type' =>
                'ELIMINATED',

                'position_from' =>
                $actualEntrants
                    -
                    $quantity
                    +
                    1,

                'position_to' =>
                $actualEntrants,

                'quantity' =>
                $quantity,

                'flow_mode' =>
                'CONSUME',

                'participant_status' =>
                'ELIMINATED',

                'is_required' =>
                true,

                'is_splittable' =>
                true,

                'accepts_multiple_connections' =>
                true,

                'priority' =>
                100,

                'sort_order' =>
                100,

                'status' =>
                'ACTIVE',

                'generation_source' =>
                'GENERATED',

                'is_locked' =>
                false,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Crear conexiones
    |--------------------------------------------------------------------------
    */

    private function createConnection(
        PhaseTemplate $phaseTemplate,
        int $sequence,
        array $source,
        string $targetType,
        ?int $targetSlotId,
        ?int $targetPhaseExitId,
        string $label
    ): PhaseSingleEliminationConnection {
        $fromInputGate =
            $source['type']
            ===
            'INPUT_GATE';

        return $phaseTemplate
            ->singleEliminationConnections()
            ->create([
                'sequence_number' =>
                $sequence,

                'code' =>
                PhaseSingleEliminationConnection::formatCode(
                    $sequence
                ),

                'label' =>
                $label,

                'source_type' =>
                $source['type'],

                'source_input_gate_id' =>
                $fromInputGate
                    ? $source['input_gate_id']
                    : null,

                'source_result_id' =>
                $fromInputGate
                    ? null
                    : $source['result_id'],

                'target_type' =>
                $targetType,

                'target_slot_id' =>
                $targetType === 'SLOT'
                    ? $targetSlotId
                    : null,

                'target_phase_exit_id' =>
                $targetType === 'PHASE_EXIT'
                    ? $targetPhaseExitId
                    : null,

                'allocation_mode' =>
                $fromInputGate
                    ? 'POSITION'
                    : 'ALL',

                'allocation_value' =>
                $fromInputGate
                    ? $source['position']
                    : null,

                'priority' =>
                $sequence * 10,

                'condition_type' =>
                'ALWAYS',

                'condition' =>
                null,

                'status' =>
                'ACTIVE',

                'generation_source' =>
                'GENERATED',

                'is_locked' =>
                false,

                'settings' => [
                    'generated_by' =>
                    'SINGLE_ELIMINATION_STRUCTURE_GENERATOR',

                    'source_position' =>
                    $source['position'],
                ],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Salidas
    |--------------------------------------------------------------------------
    */

    private function ensureExit(
        PhaseTemplate $phaseTemplate,
        string $selectorType,
        int $quantity,
        string $name,
        int $priority
    ): PhaseExit {
        $exit =
            PhaseExit::withTrashed()
            ->where(
                'phase_template_id',
                $phaseTemplate->id
            )
            ->where(
                'selector_type',
                $selectorType
            )
            ->orderBy('sequence_number')
            ->first();

        if ($exit?->trashed()) {
            $exit->restore();
        }

        if (! $exit) {
            $sequence =
                (
                    (int)
                    PhaseExit::withTrashed()
                        ->where(
                            'phase_template_id',
                            $phaseTemplate->id
                        )
                        ->max(
                            'sequence_number'
                        )
                )
                +
                1;

            $exit =
                $phaseTemplate
                ->exits()
                ->create([
                    'sequence_number' =>
                    $sequence,

                    'code' =>
                    PhaseExit::formatCode(
                        $sequence
                    ),

                    'name' =>
                    $name,

                    'description' =>
                    'Salida administrada por el grafo interno de Eliminación Simple.',

                    'selector_type' =>
                    $selectorType,

                    'resolution_mode' =>
                    'INTERNAL_GRAPH',

                    'exit_timing' =>
                    'PHASE_END',

                    'priority' =>
                    $priority,

                    'sort_order' =>
                    $priority,

                    'status' =>
                    'ACTIVE',
                ]);
        }

        $exit->update([
            'name' =>
            $name,

            'resolution_mode' =>
            'INTERNAL_GRAPH',

            'min_participants' =>
            $quantity,

            'max_participants' =>
            $quantity,

            'exact_participants' =>
            $quantity,

            'priority' =>
            $priority,

            'status' =>
            'ACTIVE',

            'settings' =>
            array_merge(
                $exit->settings
                    ??
                    [],
                [
                    'managed_by_internal_graph' =>
                    true,
                ]
            ),
        ]);

        return $exit->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Protección de personalizaciones
    |--------------------------------------------------------------------------
    */

    private function ensureReplacementAllowed(
        PhaseTemplate $phaseTemplate,
        bool $replaceManual
    ): void {
        if ($replaceManual) {
            return;
        }

        $protected =
            $phaseTemplate
            ->inputGates()
            ->where(
                function ($query) {
                    $query
                        ->where(
                            'generation_source',
                            'MANUAL'
                        )
                        ->orWhere(
                            'is_locked',
                            true
                        );
                }
            )
            ->exists()

            ||

            $phaseTemplate
            ->singleEliminationRounds()
            ->where(
                function ($query) {
                    $query
                        ->where(
                            'generation_source',
                            'MANUAL'
                        )
                        ->orWhere(
                            'is_locked',
                            true
                        );
                }
            )
            ->exists()

            ||

            $phaseTemplate
            ->singleEliminationEncounters()
            ->where(
                function ($query) {
                    $query
                        ->where(
                            'generation_source',
                            'MANUAL'
                        )
                        ->orWhere(
                            'is_locked',
                            true
                        );
                }
            )
            ->exists()

            ||

            $phaseTemplate
            ->singleEliminationConnections()
            ->where(
                function ($query) {
                    $query
                        ->where(
                            'generation_source',
                            'MANUAL'
                        )
                        ->orWhere(
                            'is_locked',
                            true
                        );
                }
            )
            ->exists();

        if ($protected) {
            throw ValidationException::withMessages([
                'structure' =>
                'La estructura contiene elementos manuales o bloqueados. Confirma el reemplazo manual antes de regenerarla.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validación previa
    |--------------------------------------------------------------------------
    */

    private function ensurePreviewCanGenerate(
        array $preview
    ): void {
        $errors =
            $preview['errors']
            ??
            [];

        if (! ($preview['valid'] ?? false)) {
            throw ValidationException::withMessages([
                'structure' =>
                $errors !== []
                    ? $errors
                    : [
                        'La configuración matemática no es válida.',
                    ],
            ]);
        }

        if (
            array_key_exists(
                'complete',
                $preview
            )
            &&
            ! $preview['complete']
        ) {
            throw ValidationException::withMessages([
                'structure' => [
                    'La configuración todavía necesita decisiones manuales y no puede generar una estructura completa.',
                ],
            ]);
        }

        if (
            empty($preview['rounds'])
        ) {
            throw ValidationException::withMessages([
                'structure' => [
                    'La configuración no produjo rondas internas.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Huella
    |--------------------------------------------------------------------------
    */

    private function fingerprint(
        PhaseTemplate $phaseTemplate,
        $settings,
        Collection $roundRules,
        int $participants
    ): string {
        $payload = [
            'phase' => [
                'id' =>
                $phaseTemplate->id,

                'participants' =>
                $participants,

                'min_participants' =>
                $phaseTemplate->min_participants,

                'max_participants' =>
                $phaseTemplate->max_participants,

                'exact_participants' =>
                $phaseTemplate->exact_participants,

                'allow_byes' =>
                $phaseTemplate->allow_byes,
            ],

            'settings' =>
            $settings->only([
                'configuration_mode',
                'input_mode',
                'routing_mode',
                'entrants_per_match',
                'qualifiers_per_match',
                'encounter_profile',
                'remainder_policy',
                'completion_mode',
                'target_survivors',
                'seeding_mode',
                'pairing_mode',
                'bye_assignment',
                'reseed_each_round',
                'series_format',
                'default_best_of',
                'fixed_games',
            ]),

            'round_rules' =>
            $roundRules
                ->map(
                    fn($rule) =>
                    $rule->only([
                        'participants_in_round',
                        'entrants_per_match',
                        'qualifiers_per_match',
                        'encounter_profile',
                        'series_format',
                        'best_of',
                        'fixed_games',
                        'sort_order',
                    ])
                )
                ->values()
                ->all(),
        ];

        return hash(
            'sha256',
            json_encode(
                $payload,
                JSON_THROW_ON_ERROR
            )
        );
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
