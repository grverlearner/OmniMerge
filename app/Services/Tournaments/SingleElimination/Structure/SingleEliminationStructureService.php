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

        $validation =
            $this->validateAndPersist(
                $phaseTemplate->fresh()
            );

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
                    $this->validator
                    ->validate(
                        $phaseTemplate
                    );

                $settings->update([
                    'structure_status' =>
                    $validation['valid']
                        ? 'VALID'
                        : 'INVALID',

                    'structure_validated_at' =>
                    now(),
                ]);

                return $validation;
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
            $this->validator
            ->validate(
                $phaseTemplate
            );

        /*
         * El validador carga su propio conjunto de relaciones.
         * Después se carga el grafo visual completo para evitar N+1
         * en el presentador y en los parciales Blade.
         */
        $phaseTemplate->load([
            'inputGates.contextualEntryPorts',
            'inputGates.outgoingConnections',

            'singleEliminationRounds.encounters.slots.incomingConnections',
            'singleEliminationRounds.encounters.results.outgoingConnections',

            'singleEliminationConnections.sourceInputGate',
            'singleEliminationConnections.sourceResult.encounter',
            'singleEliminationConnections.targetSlot.encounter',
            'singleEliminationConnections.targetPhaseExit',

            'exits.incomingInternalConnections',
        ]);

        $freshSettings =
            $settings->fresh();

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
