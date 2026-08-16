<?php

namespace App\Services\Tournaments;

use App\Models\PhaseInputGate;
use App\Models\PhaseSingleEliminationConnection;
use App\Models\PhaseSingleEliminationSlot;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationEntryPortSynchronizer;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PhaseInputGateService
{
    public function __construct(
        private readonly
        SingleEliminationEntryPortSynchronizer $entryPortSynchronizer,

        private readonly
        SingleEliminationStructureService $structureService
    ) {}

    public function create(
        PhaseTemplate $phaseTemplate,
        array $data
    ): array {
        $gateId = DB::transaction(
            function () use (
                $phaseTemplate,
                $data
            ) {
                $lockedPhase = PhaseTemplate::query()
                    ->whereKey($phaseTemplate->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $targetSlotIds = $this->extractTargetSlotIds(
                    $data
                );

                $sequence = $this->nextGateSequence(
                    $lockedPhase
                );

                $sortOrder = (
                    (int) $lockedPhase
                        ->inputGates()
                        ->max('sort_order')
                ) + 10;

                $gate = $lockedPhase
                    ->inputGates()
                    ->create([
                        ...$this->gatePayload($data),

                        'sequence_number' =>
                        $sequence,

                        'code' =>
                        PhaseInputGate::formatCode(
                            $sequence
                        ),

                        'sort_order' =>
                        $sortOrder,

                        'generation_source' =>
                        'MANUAL',

                        'settings' => [
                            'created_from' =>
                            'STRUCTURE_IO_MANAGER',
                        ],
                    ]);

                $this->syncDestinations(
                    $lockedPhase,
                    $gate,
                    $targetSlotIds
                );

                $this->markStructureChanged(
                    $lockedPhase
                );

                $this->entryPortSynchronizer
                    ->syncPhase(
                        $lockedPhase
                    );

                return $gate->id;
            }
        );

        return $this->result(
            $phaseTemplate,
            $gateId
        );
    }

    public function update(
        PhaseTemplate $phaseTemplate,
        PhaseInputGate $gate,
        array $data
    ): array {
        $gateId = DB::transaction(
            function () use (
                $phaseTemplate,
                $gate,
                $data
            ) {
                $lockedPhase = PhaseTemplate::query()
                    ->whereKey($phaseTemplate->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedGate = PhaseInputGate::query()
                    ->whereKey($gate->id)
                    ->where(
                        'phase_template_id',
                        $lockedPhase->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $targetSlotIds = $this->extractTargetSlotIds(
                    $data
                );

                $lockedGate->update([
                    ...$this->gatePayload($data),

                    'generation_source' =>
                    'MANUAL',

                    'settings' => array_merge(
                        $lockedGate->settings ?? [],
                        [
                            'updated_from' =>
                            'STRUCTURE_IO_MANAGER',
                        ]
                    ),
                ]);

                $this->syncDestinations(
                    $lockedPhase,
                    $lockedGate,
                    $targetSlotIds
                );

                $this->markStructureChanged(
                    $lockedPhase
                );

                $this->entryPortSynchronizer
                    ->syncPhase(
                        $lockedPhase
                    );

                return $lockedGate->id;
            }
        );

        return $this->result(
            $phaseTemplate,
            $gateId
        );
    }

    public function duplicate(
        PhaseTemplate $phaseTemplate,
        PhaseInputGate $gate
    ): array {
        return $this->create(
            $phaseTemplate,
            [
                'name' => Str::limit(
                    'Copia de ' . $gate->name,
                    120,
                    ''
                ),

                'description' =>
                $gate->description,

                'input_type' =>
                $gate->input_type,

                'merge_policy' =>
                $gate->merge_policy,

                'distribution_mode' =>
                $gate->distribution_mode,

                'empty_behavior' =>
                $gate->empty_behavior,

                'min_participants' =>
                $gate->min_participants,

                'max_participants' =>
                $gate->max_participants,

                'exact_participants' =>
                $gate->exact_participants,

                'is_required' =>
                $gate->is_required,

                'accepts_batch' =>
                $gate->accepts_batch,

                'accepts_multiple_connections' =>
                $gate->accepts_multiple_connections,

                'priority' => min(
                    999,
                    $gate->priority + 1
                ),

                'status' =>
                $gate->status,

                'is_locked' =>
                true,

                /*
                 * No se duplican destinos porque normalmente
                 * los slots aceptan una sola fuente.
                 */
                'target_slot_ids' =>
                [],
            ]
        );
    }

    public function delete(
        PhaseTemplate $phaseTemplate,
        PhaseInputGate $gate
    ): array {
        DB::transaction(
            function () use (
                $phaseTemplate,
                $gate
            ) {
                $lockedPhase = PhaseTemplate::query()
                    ->whereKey($phaseTemplate->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedGate = PhaseInputGate::query()
                    ->whereKey($gate->id)
                    ->where(
                        'phase_template_id',
                        $lockedPhase->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $lockedGate
                    ->contextualEntryPorts()
                    ->whereHas(
                        'incomingConnections'
                    )
                    ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'input_gate' =>
                        'No puedes eliminar esta puerta porque ya recibe conexiones desde el Tournament Graph. Desconecta primero esas rutas externas.',
                    ]);
                }

                $lockedGate
                    ->outgoingConnections()
                    ->delete();

                /*
                 * Estos puertos no tienen conexiones externas
                 * porque se validó justo arriba.
                 */
                $lockedGate
                    ->contextualEntryPorts()
                    ->delete();

                $lockedGate->delete();

                $this->markStructureChanged(
                    $lockedPhase
                );

                $lockedPhase->unsetRelation(
                    'inputGates'
                );

                $this->entryPortSynchronizer
                    ->syncPhase(
                        $lockedPhase
                    );
            }
        );

        return [
            'validation' =>
            $this->structureService
                ->validateAndPersist(
                    $phaseTemplate->fresh()
                ),
        ];
    }

    private function syncDestinations(
        PhaseTemplate $phaseTemplate,
        PhaseInputGate $gate,
        array $targetSlotIds
    ): void {
        $slots = PhaseSingleEliminationSlot::query()
            ->with(
                'encounter.round'
            )
            ->whereIn(
                'id',
                $targetSlotIds
            )
            ->whereHas(
                'encounter',
                fn($query) =>
                $query->where(
                    'phase_template_id',
                    $phaseTemplate->id
                )
            )
            ->get()
            ->keyBy('id');

        if (
            $slots->count()
            !==
            count($targetSlotIds)
        ) {
            throw ValidationException::withMessages([
                'target_slot_ids' =>
                'Todos los slots seleccionados deben pertenecer a esta misma fase.',
            ]);
        }

        if (
            $gate->exact_participants !== null
            &&
            count($targetSlotIds)
            >
            $gate->exact_participants
        ) {
            throw ValidationException::withMessages([
                'target_slot_ids' =>
                'La puerta no puede alimentar más slots que su capacidad exacta.',
            ]);
        }

        /*
         * Detectar slots SINGLE que ya tengan
         * otra fuente activa.
         */
        $conflictingSlotIds =
            PhaseSingleEliminationConnection::query()
            ->where(
                'phase_template_id',
                $phaseTemplate->id
            )
            ->where(
                'target_type',
                'SLOT'
            )
            ->whereIn(
                'target_slot_id',
                $targetSlotIds
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->where(
                fn($query) =>
                $query
                    ->where(
                        'source_type',
                        '!=',
                        'INPUT_GATE'
                    )
                    ->orWhere(
                        'source_input_gate_id',
                        '!=',
                        $gate->id
                    )
                    ->orWhereNull(
                        'source_input_gate_id'
                    )
            )
            ->pluck(
                'target_slot_id'
            )
            ->unique()
            ->filter(
                fn($slotId) =>
                $slots->get($slotId)?->source_policy
                    ===
                    'SINGLE'
            )
            ->values();

        if ($conflictingSlotIds->isNotEmpty()) {
            $labels = $conflictingSlotIds
                ->map(
                    function ($slotId) use ($slots) {
                        $slot = $slots->get(
                            $slotId
                        );

                        return ($slot?->encounter?->name ?? 'Encuentro')
                            .
                            ' · Slot '
                            .
                            ($slot?->position ?? '?');
                    }
                )
                ->implode(', ');

            throw ValidationException::withMessages([
                'target_slot_ids' =>
                'Estos slots aceptan una sola fuente y ya están conectados: '
                    .
                    $labels
                    .
                    '.',
            ]);
        }

        /*
         * Reemplazar únicamente las rutas
         * salientes de esta puerta.
         */
        $gate
            ->outgoingConnections()
            ->delete();

        $connectionSequence = (
            (int) $phaseTemplate
                ->singleEliminationConnections()
                ->max('sequence_number')
        ) + 1;

        foreach (
            $targetSlotIds
            as
            $index => $slotId
        ) {
            $slot = $slots->get(
                $slotId
            );

            $sequence =
                $connectionSequence
                +
                $index;

            $phaseTemplate
                ->singleEliminationConnections()
                ->create([
                    'sequence_number' =>
                    $sequence,

                    'code' =>
                    PhaseSingleEliminationConnection::formatCode(
                        $sequence
                    ),

                    'label' =>
                    $gate->name
                        .
                        ' · Posición '
                        .
                        ($index + 1)
                        .
                        ' → '
                        .
                        $slot->encounter->name
                        .
                        ' · Slot '
                        .
                        $slot->position,

                    'source_type' =>
                    'INPUT_GATE',

                    'source_input_gate_id' =>
                    $gate->id,

                    'source_result_id' =>
                    null,

                    'target_type' =>
                    'SLOT',

                    'target_slot_id' =>
                    $slot->id,

                    'target_phase_exit_id' =>
                    null,

                    'allocation_mode' =>
                    'POSITION',

                    'allocation_value' =>
                    $index + 1,

                    'priority' =>
                    $sequence * 10,

                    'condition_type' =>
                    'ALWAYS',

                    'condition' =>
                    null,

                    'status' =>
                    'ACTIVE',

                    'generation_source' =>
                    'MANUAL',

                    'is_locked' =>
                    true,

                    'settings' => [
                        'created_from' =>
                        'STRUCTURE_IO_MANAGER',

                        'source_position' =>
                        $index + 1,
                    ],
                ]);
        }
    }

    private function result(
        PhaseTemplate $phaseTemplate,
        int $gateId
    ): array {
        $validation = $this->structureService
            ->validateAndPersist(
                $phaseTemplate->fresh()
            );

        return [
            'gate' =>
            PhaseInputGate::query()
                ->findOrFail(
                    $gateId
                ),

            'validation' =>
            $validation,
        ];
    }

    private function extractTargetSlotIds(
        array &$data
    ): array {
        $targetSlotIds = collect(
            $data['target_slot_ids']
                ?? []
        )
            ->map(
                fn($slotId) =>
                (int) $slotId
            )
            ->unique()
            ->values()
            ->all();

        unset(
            $data['target_slot_ids'],
            $data['capacity_mode']
        );

        return $targetSlotIds;
    }

    private function gatePayload(
        array $data
    ): array {
        return collect($data)
            ->only([
                'name',
                'description',
                'input_type',
                'merge_policy',
                'distribution_mode',
                'empty_behavior',
                'min_participants',
                'max_participants',
                'exact_participants',
                'is_required',
                'accepts_batch',
                'accepts_multiple_connections',
                'priority',
                'status',
                'is_locked',
            ])
            ->all();
    }

    private function nextGateSequence(
        PhaseTemplate $phaseTemplate
    ): int {
        return (
            (int) $phaseTemplate
                ->inputGates()
                ->max('sequence_number')
        ) + 1;
    }

    private function markStructureChanged(
        PhaseTemplate $phaseTemplate
    ): void {
        $settings = $phaseTemplate
            ->singleEliminationSetting()
            ->lockForUpdate()
            ->first();

        if (! $settings) {
            return;
        }

        $settings->update([
            'structure_status' =>
            'STALE',

            'structure_version' => ((int) $settings->structure_version) + 1,

            'structure_validated_at' =>
            null,
        ]);
    }
}
