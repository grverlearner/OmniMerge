<?php

namespace App\Services\Tournaments\SingleElimination\Structure;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
use App\Services\Tournaments\SingleElimination\Visualization\SingleEliminationStructureEditor;
use App\Services\Tournaments\SingleElimination\Visualization\SingleEliminationStructurePresenter;
use Illuminate\Support\Facades\DB;

class SingleEliminationStructureService
{
    public function __construct(
        private readonly
        SingleEliminationStructureGenerator $generator,

        private readonly
        SingleEliminationStructureValidator $validator,

        private readonly
        SingleEliminationStructureExecutionPolicy $executionPolicy,

        private readonly
        SingleEliminationStructureFingerprint $fingerprint,

        private readonly
        SingleEliminationSettingsService $settingsService,

        private readonly
        SingleEliminationStructurePresenter $presenter,

        private readonly
        SingleEliminationStructureEditor $editor
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Generar y validar
    |--------------------------------------------------------------------------
    */

    public function generate(
        PhaseTemplate $phaseTemplate,
        int $participants,
        bool $replaceManual = false
    ): array {
        $generation =
            $this->generator
            ->generate(
                $phaseTemplate,
                $participants,
                $replaceManual
            );

        $settings =
            $this->settingsService
            ->ensure(
                $phaseTemplate->fresh()
            );

        $metadata =
            is_array($settings->settings)
            ? $settings->settings
            : [];

        $metadata['custom_graph_participants'] =
            $participants;

        $settings->update([
            'settings' =>
            $metadata,
        ]);

        $validation =
            $this->validateAndPersist(
                $phaseTemplate->fresh()
            );

        $generation['fingerprint'] =
            $validation['fingerprint'];

        return [
            'generation' =>
            $generation,

            'validation' =>
            $validation,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validar y persistir estado
    |--------------------------------------------------------------------------
    */

    public function validateAndPersist(
        PhaseTemplate $phaseTemplate
    ): array {
        return DB::transaction(
            function () use (
                $phaseTemplate
            ) {
                $settings =
                    $this->settingsService
                    ->ensure(
                        $phaseTemplate
                    );

                $validation =
                    $this->executionPolicy
                    ->apply(
                        $this->validator
                        ->validate(
                            $phaseTemplate
                        )
                    );

                $fingerprint =
                    $this->fingerprint
                    ->forPhase(
                        $phaseTemplate->fresh()
                    );

                $structureStatus =
                    ! ($validation['valid'] ?? false)
                    ? 'INVALID'
                    : (
                        ! ($validation['executable'] ?? false)
                        ? 'BLOCKED'
                        : 'VALID'
                    );

                $settings->update([
                    'structure_status' =>
                    $structureStatus,

                    'structure_fingerprint' =>
                    $fingerprint,

                    'structure_validated_at' =>
                    now(),
                ]);

                return array_merge(
                    $validation,
                    [
                        'structure_status' =>
                        $structureStatus,

                        'fingerprint' =>
                        $fingerprint,

                        'fingerprint_matches' =>
                        true,

                        'runtime_ready' =>
                        $structureStatus === 'VALID',
                    ]
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Edición segura desde el inspector
    |--------------------------------------------------------------------------
    */

    public function updateElement(
        PhaseTemplate $phaseTemplate,
        string $elementType,
        int $elementId,
        array $data
    ): array {
        $element =
            $this->editor
            ->update(
                $phaseTemplate,
                $elementType,
                $elementId,
                $data
            );

        $validation =
            $this->validateAndPersist(
                $phaseTemplate->fresh()
            );

        return [
            'element' =>
            $element,

            'validation' =>
            $validation,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Payload para la vista
    |--------------------------------------------------------------------------
    */

    public function payload(
        PhaseTemplate $phaseTemplate
    ): array {
        $settings =
            $this->settingsService
            ->ensure(
                $phaseTemplate
            );

        $validation =
            $this->executionPolicy
            ->apply(
                $this->validator
                ->validate(
                    $phaseTemplate
                )
            );

        $currentFingerprint =
            $this->fingerprint
            ->forPhase(
                $phaseTemplate->fresh()
            );

        /*
         * El validador carga su propio conjunto de relaciones.
         * Después se carga el grafo visual completo para evitar N+1
         * en el presentador y en los parciales Blade.
         */
        $phaseTemplate->load([
            'inputGates.contextualEntryPorts',
            'inputGates.outgoingConnections.targetSlot.encounter.round',

            'singleEliminationRounds.encounters.slots.incomingConnections',
            'singleEliminationRounds.encounters.results.outgoingConnections',

            'singleEliminationConnections.sourceInputGate',
            'singleEliminationConnections.sourceResult.encounter',
            'singleEliminationConnections.targetSlot.encounter',
            'singleEliminationConnections.targetPhaseExit',

            'exits.incomingInternalConnections.sourceInputGate',
            'exits.incomingInternalConnections.sourceResult.encounter',
        ]);

        $freshSettings =
            $settings->fresh();

        $storedFingerprint =
            (string) (
                $freshSettings->structure_fingerprint
                ??
                ''
            );

        $fingerprintMatches =
            $storedFingerprint !== ''
            &&
            hash_equals(
                $storedFingerprint,
                $currentFingerprint
            );

        $validation['structure_status'] =
            $freshSettings->structure_status;

        $validation['fingerprint'] =
            $currentFingerprint;

        $validation['fingerprint_matches'] =
            $fingerprintMatches;

        $validation['runtime_ready'] =
            $freshSettings->structure_status === 'VALID'
            &&
            ($validation['valid'] ?? false)
            &&
            ($validation['executable'] ?? false)
            &&
            $fingerprintMatches;

        $visualizer =
            $this->presenter
            ->present(
                $phaseTemplate,
                $freshSettings,
                $validation
            );

        return [
            'settings' =>
            $freshSettings,

            'inputGates' =>
            $phaseTemplate->inputGates,

            'rounds' =>
            $phaseTemplate
                ->singleEliminationRounds,

            'connections' =>
            $phaseTemplate
                ->singleEliminationConnections,

            'exits' =>
            $phaseTemplate->exits,

            'validation' =>
            $validation,

            'visualizer' =>
            $visualizer,
        ];
    }
}
