<?php

namespace App\Services\Tournaments\SingleElimination\Visualization;

use App\Models\PhaseExit;
use App\Models\PhaseInputGate;
use App\Models\PhaseSingleEliminationConnection;
use App\Models\PhaseSingleEliminationEncounter;
use App\Models\PhaseSingleEliminationResult;
use App\Models\PhaseSingleEliminationRound;
use App\Models\PhaseSingleEliminationSlot;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SingleEliminationStructureEditor
{
    public function __construct(
        private readonly
        SingleEliminationSettingsService $settingsService
    ) {}

    public function update(
        PhaseTemplate $phaseTemplate,
        string $elementType,
        int $elementId,
        array $data
    ): Model {
        abort_unless(
            $phaseTemplate->phase_type
                ===
                'SINGLE_ELIMINATION',
            404
        );

        return DB::transaction(
            function () use (
                $phaseTemplate,
                $elementType,
                $elementId,
                $data
            ) {
                $lockedPhase =
                    PhaseTemplate::query()
                    ->whereKey(
                        $phaseTemplate->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $element =
                    $this->resolveElement(
                        $lockedPhase,
                        $elementType,
                        $elementId
                    );

                $element->fill(
                    $this->payloadFor(
                        $elementType,
                        $data
                    )
                );

                if (
                    $elementType
                    !==
                    'PHASE_EXIT'
                ) {
                    $element->setAttribute(
                        'generation_source',
                        'MANUAL'
                    );
                }

                $element->save();

                $settings =
                    $this->settingsService
                    ->ensure(
                        $lockedPhase
                    );

                $settings->update([
                    'structure_status' =>
                    'GENERATED',

                    'structure_version' =>
                    (int)
                    $settings->structure_version
                        +
                        1,

                    'structure_validated_at' =>
                    null,
                ]);

                return $element->fresh();
            }
        );
    }

    private function resolveElement(
        PhaseTemplate $phaseTemplate,
        string $elementType,
        int $elementId
    ): Model {
        return match ($elementType) {
            'INPUT_GATE' =>
            PhaseInputGate::query()
                ->where(
                    'phase_template_id',
                    $phaseTemplate->id
                )
                ->findOrFail(
                    $elementId
                ),

            'ROUND' =>
            PhaseSingleEliminationRound::query()
                ->where(
                    'phase_template_id',
                    $phaseTemplate->id
                )
                ->findOrFail(
                    $elementId
                ),

            'ENCOUNTER' =>
            PhaseSingleEliminationEncounter::query()
                ->where(
                    'phase_template_id',
                    $phaseTemplate->id
                )
                ->findOrFail(
                    $elementId
                ),

            'SLOT' =>
            PhaseSingleEliminationSlot::query()
                ->whereHas(
                    'encounter',
                    fn($query) =>
                    $query->where(
                        'phase_template_id',
                        $phaseTemplate->id
                    )
                )
                ->findOrFail(
                    $elementId
                ),

            'RESULT' =>
            PhaseSingleEliminationResult::query()
                ->whereHas(
                    'encounter',
                    fn($query) =>
                    $query->where(
                        'phase_template_id',
                        $phaseTemplate->id
                    )
                )
                ->findOrFail(
                    $elementId
                ),

            'CONNECTION' =>
            PhaseSingleEliminationConnection::query()
                ->where(
                    'phase_template_id',
                    $phaseTemplate->id
                )
                ->findOrFail(
                    $elementId
                ),

            'PHASE_EXIT' =>
            PhaseExit::query()
                ->where(
                    'phase_template_id',
                    $phaseTemplate->id
                )
                ->findOrFail(
                    $elementId
                ),

            default =>
            abort(404),
        };
    }

    private function payloadFor(
        string $elementType,
        array $data
    ): array {
        $fields = match ($elementType) {
            'INPUT_GATE' => [
                'name', 'description', 'input_type', 'merge_policy', 'distribution_mode', 'empty_behavior',
                'min_participants', 'max_participants', 'exact_participants', 'is_required', 'accepts_batch',
                'accepts_multiple_connections', 'priority', 'sort_order', 'status', 'is_locked',
            ],

            'ROUND' => [
                'name', 'description', 'stage_number', 'branch_code', 'round_type', 'participants_expected',
                'qualifiers_expected', 'sort_order', 'status', 'is_locked',
            ],

            'ENCOUNTER' => [
                'name', 'description', 'position', 'entrants_count', 'qualifiers_count', 'min_entrants_to_start',
                'encounter_profile', 'activation_policy', 'allows_incomplete', 'series_format', 'best_of',
                'fixed_games', 'sort_order', 'status', 'is_locked',
            ],

            'RESULT' => [
                'name', 'description', 'result_type', 'position_from', 'position_to', 'quantity', 'flow_mode',
                'participant_status', 'is_required', 'is_splittable', 'accepts_multiple_connections', 'priority',
                'sort_order', 'status', 'is_locked',
            ],

            'CONNECTION' => [
                'label', 'description', 'source_type', 'source_input_gate_id', 'source_result_id', 'target_type',
                'target_slot_id', 'target_phase_exit_id', 'allocation_mode', 'allocation_value', 'priority',
                'condition_type', 'status', 'is_locked',
            ],

            'SLOT' => [
                'position', 'slot_type', 'capacity', 'is_required', 'source_policy', 'empty_behavior',
                'assignment_rule', 'sort_order', 'status', 'is_locked',
            ],

            'PHASE_EXIT' => [
                'name',
                'description',
                'status',
            ],

            default => [],
        };

        $payload =
            Arr::only(
                $data,
                $fields
            );

        foreach ([
            'is_locked',
            'allows_incomplete',
            'is_required',
            'accepts_batch',
            'accepts_multiple_connections',
            'is_splittable',
        ] as $booleanField) {
            if (in_array($booleanField, $fields, true)) {
                $payload[$booleanField] = (bool) ($data[$booleanField] ?? false);
            }
        }

        if ($elementType === 'CONNECTION') {
            if (($payload['source_type'] ?? null) === 'INPUT_GATE') {
                $payload['source_result_id'] = null;
            } elseif (($payload['source_type'] ?? null) === 'RESULT') {
                $payload['source_input_gate_id'] = null;
            }

            if (($payload['target_type'] ?? null) === 'SLOT') {
                $payload['target_phase_exit_id'] = null;
            } elseif (($payload['target_type'] ?? null) === 'PHASE_EXIT') {
                $payload['target_slot_id'] = null;
            }
        }

        return $payload;
    }
}
