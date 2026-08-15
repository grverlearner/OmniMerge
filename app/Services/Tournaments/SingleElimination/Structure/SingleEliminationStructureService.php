<?php

namespace App\Services\Tournaments\SingleElimination\Structure;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
use Illuminate\Support\Facades\DB;

class SingleEliminationStructureService
{
    public function __construct(
        private readonly
        SingleEliminationStructureGenerator $generator,

        private readonly
        SingleEliminationStructureValidator $validator,

        private readonly
        SingleEliminationSettingsService $settingsService
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

        $phaseTemplate->load([
            'inputGates.contextualEntryPorts',

            'singleEliminationRounds.encounters.slots.incomingConnections',
            'singleEliminationRounds.encounters.results.outgoingConnections',

            'singleEliminationConnections.sourceInputGate',
            'singleEliminationConnections.sourceResult.encounter',
            'singleEliminationConnections.targetSlot.encounter',
            'singleEliminationConnections.targetPhaseExit',

            'exits.incomingInternalConnections',
        ]);

        $validation =
            $this->validator
            ->validate(
                $phaseTemplate
            );

        return [
            'settings' =>
            $settings->fresh(),

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
        ];
    }
}
