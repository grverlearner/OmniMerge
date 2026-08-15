<?php

namespace App\Services\Tournaments\SingleElimination;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;

class SingleEliminationValidator
{
    public function validate(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationSetting $settings,
        int $participants
    ): array {
        $errors = [];

        if (
            $phaseTemplate->phase_type
            !==
            'SINGLE_ELIMINATION'
        ) {
            return [
                'La Fase no es de tipo Eliminación directa.',
            ];
        }

        if (
            $phaseTemplate->exact_participants
            !==
            null
            &&
            $participants
            !==
            (int)
            $phaseTemplate->exact_participants
        ) {
            $errors[] =
                'Esta Fase requiere exactamente '
                .
                $phaseTemplate->exact_participants
                .
                ' participantes.';
        }

        if (
            $participants
            <
            (int)
            $phaseTemplate->min_participants
        ) {
            $errors[] =
                'La Fase requiere al menos '
                .
                $phaseTemplate->min_participants
                .
                ' participantes.';
        }

        if (
            $phaseTemplate->max_participants
            !==
            null
            &&
            $participants
            >
            (int)
            $phaseTemplate->max_participants
        ) {
            $errors[] =
                'La Fase admite como máximo '
                .
                $phaseTemplate->max_participants
                .
                ' participantes.';
        }

        if (
            $phaseTemplate->participant_multiple
            !==
            null
            &&
            $participants
            %
            (int)
            $phaseTemplate->participant_multiple
            !==
            0
        ) {
            $errors[] =
                'La cantidad debe ser múltiplo de '
                .
                $phaseTemplate->participant_multiple
                .
                '.';
        }

        $target =
            max(
                1,
                (int)
                $settings->target_survivors
            );

        if (
            $settings->configuration_mode
            !==
            'ADVANCED'
            &&
            ! $this->isPowerOfTwo(
                $target
            )
        ) {
            $errors[] =
                'En modo básico el objetivo de supervivientes debe ser una potencia de 2.';
        }

        if ($participants <= $target) {
            $errors[] =
                'La cantidad de participantes debe ser mayor que el objetivo de supervivientes.';
        }

        if (
            $settings->configuration_mode
            !==
            'ADVANCED'
            &&
            ! $phaseTemplate->allow_byes
            &&
            ! $this->isPowerOfTwo(
                $participants
            )
        ) {
            $errors[] =
                'Esta Fase no permite BYEs. En modo básico la cantidad debe ser una potencia de 2.';
        }

        if (
            $settings->configuration_mode
            ===
            'ADVANCED'
        ) {
            $errors =
                array_merge(
                    $errors,
                    $this->validateAdvanced(
                        $phaseTemplate,
                        $settings
                    )
                );
        }

        return array_values(
            array_unique(
                $errors
            )
        );
    }

    private function validateAdvanced(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationSetting $settings
    ): array {
        $errors = [];

        $entrants =
            (int)
            $settings->entrants_per_match;

        $qualifiers =
            (int)
            $settings->qualifiers_per_match;

        if ($entrants < 2) {
            $errors[] =
                'Cada encuentro debe recibir al menos 2 participantes.';
        }

        if (
            $qualifiers < 1
            ||
            $qualifiers >= $entrants
        ) {
            $errors[] =
                'Los clasificados por encuentro deben ser al menos 1 y menores que los participantes del encuentro.';
        }

        if (
            $settings->encounter_profile
            ===
            'DUEL'
            &&
            (
                $entrants !== 2
                ||
                $qualifiers !== 1
            )
        ) {
            $errors[] =
                'El perfil Duelo exige exactamente 2 participantes y 1 clasificado.';
        }

        if (
            $settings->encounter_profile
            ===
            'MULTI_COMPETITOR'
            &&
            $entrants < 3
        ) {
            $errors[] =
                'El perfil Multicompetidor exige al menos 3 participantes por encuentro.';
        }

        if (
            $settings->remainder_policy
            ===
            'BYE'
            &&
            ! $phaseTemplate->allow_byes
        ) {
            $errors[] =
                'La política de sobrantes BYE no puede usarse porque la Fase no permite BYEs.';
        }

        if (
            $settings->input_mode
            ===
            'PER_SEED'
            &&
            $phaseTemplate->exact_participants
            ===
            null
        ) {
            $errors[] =
                'La entrada Por seed necesita un contrato de participantes exacto.';
        }

        return $errors;
    }

    public function isPowerOfTwo(
        int $value
    ): bool {
        return
            $value > 0
            &&
            (
                $value
                &
                ($value - 1)
            )
            ===
            0;
    }
}
