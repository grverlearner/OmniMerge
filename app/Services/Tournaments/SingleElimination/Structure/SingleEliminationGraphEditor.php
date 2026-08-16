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
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SingleEliminationGraphEditor
{
    public function __construct(
        private readonly SingleEliminationSettingsService $settingsService,
        private readonly SingleEliminationEntryPortSynchronizer $entryPortSynchronizer
    ) {}

    public function initialize(
        PhaseTemplate $phaseTemplate,
        int $participants,
        bool $replaceStructure = false
    ): void {
        $this->ensureCorrectType($phaseTemplate);

        DB::transaction(function () use ($phaseTemplate, $participants, $replaceStructure) {
            $phase = PhaseTemplate::query()
                ->whereKey($phaseTemplate->id)
                ->lockForUpdate()
                ->firstOrFail();

            $hasStructure = $phase->singleEliminationRounds()->exists()
                || $phase->inputGates()->exists()
                || $phase->singleEliminationConnections()->exists();

            if ($hasStructure && ! $replaceStructure) {
                $this->fail(
                    'La fase ya contiene una estructura. Confirma el reemplazo para iniciar un grafo personalizado nuevo.'
                );
            }

            if ($hasStructure) {
                $phase->singleEliminationConnections()->delete();
                $phase->singleEliminationRounds()->delete();
                $phase->inputGates()->delete();
            }

            $settings = $this->settingsService->ensure($phase);
            $metadata = is_array($settings->settings) ? $settings->settings : [];
            $metadata['working_participants'] = $participants;
            $metadata['custom_graph_participants'] = $participants;

            $settings->update([
                'configuration_mode' => 'ADVANCED',
                'input_mode' => 'POOL',
                'routing_mode' => 'MANUAL',
                'remainder_policy' => 'MANUAL',
                'structure_mode' => 'MANUAL',
                'structure_status' => 'GENERATED',
                'structure_version' => (int) $settings->structure_version + 1,
                'structure_fingerprint' => null,
                'structure_generated_at' => now(),
                'structure_validated_at' => null,
                'settings' => $metadata,
            ]);

            $phase->inputGates()->create([
                'sequence_number' => 1,
                'code' => PhaseInputGate::formatCode(1),
                'name' => 'Entrada general',
                'description' => 'Participantes que alimentan el grafo interno personalizado.',
                'input_type' => 'POOL',
                'merge_policy' => 'APPEND',
                'distribution_mode' => 'INPUT_ORDER',
                'empty_behavior' => 'ERROR',
                'min_participants' => $participants,
                'max_participants' => $participants,
                'exact_participants' => $participants,
                'is_required' => true,
                'accepts_batch' => true,
                'accepts_multiple_connections' => true,
                'priority' => 10,
                'sort_order' => 10,
                'status' => 'ACTIVE',
                'generation_source' => 'MANUAL',
                'is_locked' => true,
                'settings' => [
                    'custom_graph_root' => true,
                ],
            ]);

            $this->ensureExit($phase, 'SURVIVORS', 'Campeón', 1, 10);
            $this->ensureExit($phase, 'ELIMINATED', 'Eliminados', max(0, $participants - 1), 20);
            $this->entryPortSynchronizer->syncPhase($phase);
        });
    }

    public function createStage(PhaseTemplate $phaseTemplate, array $data): PhaseSingleEliminationRound
    {
        $this->ensureCorrectType($phaseTemplate);

        return DB::transaction(function () use ($phaseTemplate, $data) {
            $phase = $this->lockPhase($phaseTemplate);
            $sequence = (int) $phase->singleEliminationRounds()->max('sequence_number') + 1;

            $round = $phase->singleEliminationRounds()->create([
                'sequence_number' => $sequence,
                'code' => PhaseSingleEliminationRound::formatCode($sequence),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'stage_number' => (int) $data['stage_number'],
                'branch_code' => $data['branch_code'] ?? 'MAIN',
                'round_type' => 'CUSTOM',
                'participants_expected' => 0,
                'qualifiers_expected' => 0,
                'sort_order' => (int) $data['stage_number'] * 10,
                'status' => 'ACTIVE',
                'generation_source' => 'MANUAL',
                'is_locked' => true,
                'settings' => [
                    'custom_graph_stage' => true,
                ],
            ]);

            $this->touchStructure($phase);

            return $round;
        });
    }

    public function deleteStage(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationRound $round
    ): void {
        $this->ensureRoundBelongsTo($phaseTemplate, $round);

        DB::transaction(function () use ($phaseTemplate, $round) {
            $phase = $this->lockPhase($phaseTemplate);
            $lockedRound = PhaseSingleEliminationRound::query()
                ->where('phase_template_id', $phase->id)
                ->whereKey($round->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRound->encounters()->exists()) {
                $this->fail('Elimina primero los encuentros pertenecientes a esta etapa.');
            }

            $lockedRound->delete();
            $this->touchStructure($phase);
        });
    }

    public function createEncounter(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseSingleEliminationEncounter {
        $this->ensureCorrectType($phaseTemplate);

        return DB::transaction(function () use ($phaseTemplate, $data) {
            $phase = $this->lockPhase($phaseTemplate);
            $round = PhaseSingleEliminationRound::query()
                ->where('phase_template_id', $phase->id)
                ->findOrFail($data['round_id']);

            $sequence = (int) $phase->singleEliminationEncounters()->max('sequence_number') + 1;
            $position = (int) $round->encounters()->max('position') + 1;
            $encounter = $round->encounters()->create([
                'phase_template_id' => $phase->id,
                'sequence_number' => $sequence,
                'code' => PhaseSingleEliminationEncounter::formatCode($sequence),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'position' => $position,
                'entrants_count' => (int) $data['entrants_count'],
                'qualifiers_count' => (int) $data['qualifiers_count'],
                'min_entrants_to_start' => (int) $data['entrants_count'],
                'encounter_profile' => $data['encounter_profile'],
                'activation_policy' => 'ALL_REQUIRED',
                'allows_incomplete' => false,
                'series_format' => $data['series_format'],
                'best_of' => $data['series_format'] === 'BEST_OF'
                    ? (int) ($data['best_of'] ?? 1)
                    : null,
                'fixed_games' => $data['series_format'] === 'FIXED_GAMES'
                    ? (int) ($data['fixed_games'] ?? 1)
                    : null,
                'sort_order' => $position * 10,
                'status' => 'ACTIVE',
                'generation_source' => 'MANUAL',
                'is_locked' => true,
                'settings' => [
                    'actual_entrants' => (int) $data['entrants_count'],
                    'resolution_mode' => $data['resolution_mode'],
                    'qualifier_ordering' => $data['qualifier_ordering'],
                    'custom_graph_encounter' => true,
                ],
            ]);

            $this->buildEncounterShape($phase, $encounter);
            $this->refreshStageTotals($round);
            $this->refreshExitTotals($phase);
            $this->touchStructure($phase);

            return $encounter->fresh(['round', 'slots', 'results']);
        });
    }

    public function updateEncounter(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationEncounter $encounter,
        array $data
    ): PhaseSingleEliminationEncounter {
        $this->ensureEncounterBelongsTo($phaseTemplate, $encounter);

        return DB::transaction(function () use ($phaseTemplate, $encounter, $data) {
            $phase = $this->lockPhase($phaseTemplate);
            $lockedEncounter = PhaseSingleEliminationEncounter::query()
                ->where('phase_template_id', $phase->id)
                ->whereKey($encounter->id)
                ->lockForUpdate()
                ->firstOrFail();

            $shapeChanged = (int) $lockedEncounter->entrants_count !== (int) $data['entrants_count']
                || (int) $lockedEncounter->qualifiers_count !== (int) $data['qualifiers_count'];

            if ($shapeChanged) {
                $hasCompetitiveRoutes = PhaseSingleEliminationConnection::query()
                    ->where(function ($query) use ($lockedEncounter) {
                        $query->whereIn(
                            'source_result_id',
                            $lockedEncounter->results()->pluck('id')
                        )->orWhereIn(
                            'target_slot_id',
                            $lockedEncounter->slots()->pluck('id')
                        );
                    })
                    ->where('status', 'ACTIVE')
                    ->where(function ($query) {
                        $query->where('target_type', 'SLOT')
                            ->orWhereHas('sourceResult', fn($resultQuery) =>
                                $resultQuery->where('participant_status', 'ACTIVE')
                            );
                    })
                    ->exists();

                if ($hasCompetitiveRoutes) {
                    $this->fail(
                        'El encuentro ya tiene rutas competitivas. Elimínalas antes de cambiar su relación de participantes y clasificados.'
                    );
                }
            }

            $settings = is_array($lockedEncounter->settings) ? $lockedEncounter->settings : [];
            $settings['actual_entrants'] = (int) $data['entrants_count'];
            $settings['resolution_mode'] = $data['resolution_mode'];
            $settings['qualifier_ordering'] = $data['qualifier_ordering'];
            $settings['custom_graph_encounter'] = true;

            $lockedEncounter->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'entrants_count' => (int) $data['entrants_count'],
                'qualifiers_count' => (int) $data['qualifiers_count'],
                'min_entrants_to_start' => (int) $data['entrants_count'],
                'encounter_profile' => $data['encounter_profile'],
                'series_format' => $data['series_format'],
                'best_of' => $data['series_format'] === 'BEST_OF'
                    ? (int) ($data['best_of'] ?? 1)
                    : null,
                'fixed_games' => $data['series_format'] === 'FIXED_GAMES'
                    ? (int) ($data['fixed_games'] ?? 1)
                    : null,
                'generation_source' => 'MANUAL',
                'is_locked' => true,
                'settings' => $settings,
            ]);

            if ($shapeChanged) {
                $lockedEncounter->slots()->delete();
                $lockedEncounter->results()->delete();
                $this->buildEncounterShape($phase, $lockedEncounter->fresh());
            }

            $this->refreshStageTotals($lockedEncounter->round);
            $this->refreshExitTotals($phase);
            $this->touchStructure($phase);

            return $lockedEncounter->fresh(['round', 'slots', 'results']);
        });
    }

    public function deleteEncounter(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationEncounter $encounter
    ): void {
        $this->ensureEncounterBelongsTo($phaseTemplate, $encounter);

        DB::transaction(function () use ($phaseTemplate, $encounter) {
            $phase = $this->lockPhase($phaseTemplate);
            $lockedEncounter = PhaseSingleEliminationEncounter::query()
                ->where('phase_template_id', $phase->id)
                ->whereKey($encounter->id)
                ->lockForUpdate()
                ->firstOrFail();
            $round = $lockedEncounter->round;

            $lockedEncounter->delete();
            $this->refreshStageTotals($round->fresh());
            $this->refreshExitTotals($phase);
            $this->touchStructure($phase);
        });
    }

    public function createRoute(PhaseTemplate $phaseTemplate, array $data): string
    {
        $this->ensureCorrectType($phaseTemplate);

        return DB::transaction(function () use ($phaseTemplate, $data) {
            $phase = $this->lockPhase($phaseTemplate);
            $source = PhaseSingleEliminationEncounter::query()
                ->with(['round', 'results.outgoingConnections'])
                ->where('phase_template_id', $phase->id)
                ->findOrFail($data['source_encounter_id']);

            $from = (int) $data['source_position_from'];
            $quantity = (int) $data['quantity'];
            $sourceResults = $source->results
                ->where('status', 'ACTIVE')
                ->where('participant_status', 'ACTIVE')
                ->filter(fn($result) =>
                    (int) $result->position_from >= $from
                    && (int) $result->position_from < $from + $quantity
                )
                ->sortBy('position_from')
                ->values();

            if ($sourceResults->count() !== $quantity) {
                $this->fail('El origen no contiene todas las posiciones clasificadas solicitadas.');
            }

            foreach ($sourceResults as $result) {
                if ($result->outgoingConnections->where('status', 'ACTIVE')->isNotEmpty()) {
                    $this->fail("{$result->name} de {$source->name} ya tiene un destino.");
                }
            }

            $targetType = $data['target_type'];
            $targetSlots = collect();
            $targetExit = null;

            if ($targetType === 'ENCOUNTER') {
                $target = PhaseSingleEliminationEncounter::query()
                    ->with(['round', 'slots.incomingConnections'])
                    ->where('phase_template_id', $phase->id)
                    ->findOrFail($data['target_encounter_id']);

                if ((int) $target->round->stage_number <= (int) $source->round->stage_number) {
                    $this->fail('El encuentro de destino debe pertenecer a una etapa posterior.');
                }

                $slotFrom = (int) $data['target_slot_from'];
                $targetSlots = $target->slots
                    ->filter(fn($slot) =>
                        (int) $slot->position >= $slotFrom
                        && (int) $slot->position < $slotFrom + $quantity
                    )
                    ->sortBy('position')
                    ->values();

                if ($targetSlots->count() !== $quantity) {
                    $this->fail('El destino no contiene todos los slots solicitados.');
                }

                foreach ($targetSlots as $slot) {
                    if ($slot->incomingConnections->where('status', 'ACTIVE')->isNotEmpty()) {
                        $this->fail("El slot {$slot->position} de {$target->name} ya está ocupado.");
                    }
                }
            } else {
                $targetExit = PhaseExit::query()
                    ->where('phase_template_id', $phase->id)
                    ->where('resolution_mode', 'INTERNAL_GRAPH')
                    ->findOrFail($data['target_phase_exit_id']);
            }

            $group = (string) Str::uuid();
            $sequence = (int) $phase->singleEliminationConnections()->max('sequence_number');

            foreach ($sourceResults as $index => $result) {
                $sequence++;
                $slot = $targetType === 'ENCOUNTER' ? $targetSlots[$index] : null;
                $label = $source->name . ' · ' . $result->name . ' → '
                    . ($slot ? $slot->encounter->name . ' · Slot ' . $slot->position : $targetExit->name);

                $phase->singleEliminationConnections()->create([
                    'sequence_number' => $sequence,
                    'code' => PhaseSingleEliminationConnection::formatCode($sequence),
                    'label' => $label,
                    'source_type' => 'RESULT',
                    'source_input_gate_id' => null,
                    'source_result_id' => $result->id,
                    'target_type' => $slot ? 'SLOT' : 'PHASE_EXIT',
                    'target_slot_id' => $slot?->id,
                    'target_phase_exit_id' => $targetExit?->id,
                    'allocation_mode' => 'ALL',
                    'allocation_value' => null,
                    'priority' => $sequence * 10,
                    'condition_type' => 'ALWAYS',
                    'condition' => null,
                    'status' => 'ACTIVE',
                    'generation_source' => 'MANUAL',
                    'is_locked' => true,
                    'settings' => [
                        'route_group' => $group,
                        'route_quantity' => $quantity,
                        'source_position_from' => $from,
                        'target_slot_from' => $slot ? (int) $data['target_slot_from'] : null,
                    ],
                ]);
            }

            $this->touchStructure($phase);

            return $group;
        });
    }

    public function deleteRoute(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationConnection $connection
    ): void {
        abort_unless((int) $connection->phase_template_id === (int) $phaseTemplate->id, 404);

        DB::transaction(function () use ($phaseTemplate, $connection) {
            $phase = $this->lockPhase($phaseTemplate);
            $lockedConnection = PhaseSingleEliminationConnection::query()
                ->where('phase_template_id', $phase->id)
                ->whereKey($connection->id)
                ->lockForUpdate()
                ->firstOrFail();
            $group = data_get($lockedConnection->settings, 'route_group');

            if ($group) {
                PhaseSingleEliminationConnection::query()
                    ->where('phase_template_id', $phase->id)
                    ->where('settings->route_group', $group)
                    ->delete();
            } else {
                $lockedConnection->delete();
            }

            $this->touchStructure($phase);
        });
    }

    private function buildEncounterShape(
        PhaseTemplate $phase,
        PhaseSingleEliminationEncounter $encounter
    ): void {
        for ($position = 1; $position <= $encounter->entrants_count; $position++) {
            $encounter->slots()->create([
                'code' => PhaseSingleEliminationSlot::formatCode($position),
                'position' => $position,
                'slot_type' => 'PARTICIPANT',
                'capacity' => 1,
                'is_required' => true,
                'source_policy' => 'SINGLE',
                'empty_behavior' => 'WAIT',
                'assignment_rule' => 'POSITIONAL',
                'sort_order' => $position * 10,
                'status' => 'ACTIVE',
                'generation_source' => 'MANUAL',
                'is_locked' => true,
            ]);
        }

        for ($position = 1; $position <= $encounter->qualifiers_count; $position++) {
            $winner = $encounter->entrants_count === 2
                && $encounter->qualifiers_count === 1;

            $encounter->results()->create([
                'sequence_number' => $position,
                'code' => PhaseSingleEliminationResult::formatCode($position),
                'name' => $winner ? 'Ganador' : 'Clasificado ' . $position,
                'description' => 'Posición clasificatoria del grafo personalizado.',
                'result_type' => $winner ? 'WINNER' : 'POSITION',
                'position_from' => $position,
                'position_to' => $position,
                'quantity' => 1,
                'flow_mode' => 'CONSUME',
                'participant_status' => 'ACTIVE',
                'is_required' => true,
                'is_splittable' => false,
                'accepts_multiple_connections' => false,
                'priority' => $position * 10,
                'sort_order' => $position * 10,
                'status' => 'ACTIVE',
                'generation_source' => 'MANUAL',
                'is_locked' => true,
            ]);
        }

        $eliminated = $encounter->entrants_count - $encounter->qualifiers_count;
        $result = $encounter->results()->create([
            'sequence_number' => $encounter->qualifiers_count + 1,
            'code' => PhaseSingleEliminationResult::formatCode($encounter->qualifiers_count + 1),
            'name' => $eliminated === 1 ? 'Eliminado' : 'Eliminados',
            'description' => 'Participantes que dejan de competir en este encuentro.',
            'result_type' => 'ELIMINATED',
            'position_from' => $encounter->qualifiers_count + 1,
            'position_to' => $encounter->entrants_count,
            'quantity' => $eliminated,
            'flow_mode' => 'CONSUME',
            'participant_status' => 'ELIMINATED',
            'is_required' => true,
            'is_splittable' => false,
            'accepts_multiple_connections' => false,
            'priority' => 100,
            'sort_order' => 100,
            'status' => 'ACTIVE',
            'generation_source' => 'MANUAL',
            'is_locked' => true,
        ]);

        $exit = $this->ensureExit($phase, 'ELIMINATED', 'Eliminados', 0, 20);
        $sequence = (int) $phase->singleEliminationConnections()->max('sequence_number') + 1;
        $phase->singleEliminationConnections()->create([
            'sequence_number' => $sequence,
            'code' => PhaseSingleEliminationConnection::formatCode($sequence),
            'label' => $encounter->name . ' · Eliminados → ' . $exit->name,
            'source_type' => 'RESULT',
            'source_result_id' => $result->id,
            'target_type' => 'PHASE_EXIT',
            'target_phase_exit_id' => $exit->id,
            'allocation_mode' => 'ALL',
            'priority' => $sequence * 10,
            'condition_type' => 'ALWAYS',
            'status' => 'ACTIVE',
            'generation_source' => 'MANUAL',
            'is_locked' => true,
            'settings' => [
                'automatic_elimination_route' => true,
            ],
        ]);
    }

    private function ensureExit(
        PhaseTemplate $phase,
        string $selectorType,
        string $name,
        int $quantity,
        int $priority
    ): PhaseExit {
        $exit = PhaseExit::withTrashed()
            ->where('phase_template_id', $phase->id)
            ->where('selector_type', $selectorType)
            ->orderBy('sequence_number')
            ->first();

        if ($exit?->trashed()) {
            $exit->restore();
        }

        if (! $exit) {
            $sequence = (int) PhaseExit::withTrashed()
                ->where('phase_template_id', $phase->id)
                ->max('sequence_number') + 1;
            $exit = $phase->exits()->create([
                'sequence_number' => $sequence,
                'code' => PhaseExit::formatCode($sequence),
                'name' => $name,
                'description' => 'Salida administrada por el grafo interno personalizado.',
                'selector_type' => $selectorType,
                'resolution_mode' => 'INTERNAL_GRAPH',
                'exit_timing' => 'PHASE_END',
                'priority' => $priority,
                'sort_order' => $priority,
                'status' => 'ACTIVE',
            ]);
        }

        $exit->update([
            'name' => $name,
            'resolution_mode' => 'INTERNAL_GRAPH',
            'min_participants' => $quantity,
            'max_participants' => $quantity,
            'exact_participants' => $quantity,
            'status' => 'ACTIVE',
            'settings' => array_merge($exit->settings ?? [], [
                'managed_by_custom_graph' => true,
            ]),
        ]);

        return $exit->fresh();
    }

    private function refreshStageTotals(PhaseSingleEliminationRound $round): void
    {
        $round->update([
            'participants_expected' => (int) $round->encounters()->where('status', 'ACTIVE')->sum('entrants_count'),
            'qualifiers_expected' => (int) $round->encounters()->where('status', 'ACTIVE')->sum('qualifiers_count'),
        ]);
    }

    private function refreshExitTotals(PhaseTemplate $phase): void
    {
        $participants = (int) data_get(
            $this->settingsService->ensure($phase)->settings,
            'custom_graph_participants',
            0
        );
        $eliminated = (int) $phase->singleEliminationEncounters()
            ->where('status', 'ACTIVE')
            ->get(['entrants_count', 'qualifiers_count'])
            ->sum(fn($encounter) => $encounter->entrants_count - $encounter->qualifiers_count);

        $this->ensureExit($phase, 'SURVIVORS', 'Campeón', 1, 10);
        $this->ensureExit(
            $phase,
            'ELIMINATED',
            'Eliminados',
            $participants > 0 ? min($participants - 1, $eliminated) : $eliminated,
            20
        );
    }

    private function touchStructure(PhaseTemplate $phase): void
    {
        $settings = $this->settingsService->ensure($phase);
        $settings->update([
            'configuration_mode' => 'ADVANCED',
            'routing_mode' => 'MANUAL',
            'structure_mode' => 'MANUAL',
            'structure_status' => 'GENERATED',
            'structure_version' => (int) $settings->structure_version + 1,
            'structure_fingerprint' => null,
            'structure_generated_at' => now(),
            'structure_validated_at' => null,
        ]);
    }

    private function lockPhase(PhaseTemplate $phaseTemplate): PhaseTemplate
    {
        return PhaseTemplate::query()
            ->whereKey($phaseTemplate->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensureRoundBelongsTo(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationRound $round
    ): void {
        abort_unless((int) $round->phase_template_id === (int) $phaseTemplate->id, 404);
    }

    private function ensureEncounterBelongsTo(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationEncounter $encounter
    ): void {
        abort_unless((int) $encounter->phase_template_id === (int) $phaseTemplate->id, 404);
    }

    private function ensureCorrectType(PhaseTemplate $phaseTemplate): void
    {
        abort_unless($phaseTemplate->phase_type === 'SINGLE_ELIMINATION', 404);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'graph' => [$message],
        ]);
    }
}
