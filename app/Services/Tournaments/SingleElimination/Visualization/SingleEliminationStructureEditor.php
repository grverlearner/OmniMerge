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
            'INPUT_GATE',
            'ROUND',
            'ENCOUNTER',
            'RESULT' => [
                'name',
                'description',
                'status',
                'is_locked',
            ],

            'CONNECTION' => [
                'label',
                'description',
                'status',
                'is_locked',
            ],

            'SLOT' => [
                'status',
                'is_locked',
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

        if (
            in_array(
                'is_locked',
                $fields,
                true
            )
        ) {
            $payload['is_locked'] =
                (bool) (
                    $data['is_locked']
                    ??
                    false
                );
        }

        return $payload;
    }
}
