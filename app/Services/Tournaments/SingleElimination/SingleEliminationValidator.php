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

        /*
        |--------------------------------------------------------------------------
        | Tipo
        |--------------------------------------------------------------------------
        */

        if (
            $phaseTemplate->phase_type
            !==
            'SINGLE_ELIMINATION'
        ) {
            $errors[] =
                'La Fase no es de tipo Eliminación directa.';

            return $errors;
        }

        /*
        |--------------------------------------------------------------------------
        | Contrato exacto
        |--------------------------------------------------------------------------
        */

        if (
            $phaseTemplate->exact_participants
            !==
            null
            &&
            $participants
            !==
            $phaseTemplate->exact_participants
        ) {
            $errors[] =
                'Esta Fase requiere exactamente '
                .
                $phaseTemplate->exact_participants
                .
                ' participantes.';
        }

        /*
        |--------------------------------------------------------------------------
        | Mínimo
        |--------------------------------------------------------------------------
        */

        if (
            $participants
            <
            $phaseTemplate->min_participants
        ) {
            $errors[] =
                'La Fase requiere al menos '
                .
                $phaseTemplate->min_participants
                .
                ' participantes.';
        }

        /*
        |--------------------------------------------------------------------------
        | Máximo
        |--------------------------------------------------------------------------
        */

        if (
            $phaseTemplate->max_participants
            !==
            null
            &&
            $participants
            >
            $phaseTemplate->max_participants
        ) {
            $errors[] =
                'La Fase admite como máximo '
                .
                $phaseTemplate->max_participants
                .
                ' participantes.';
        }

        /*
        |--------------------------------------------------------------------------
        | Múltiplo
        |--------------------------------------------------------------------------
        */

        if (
            $phaseTemplate->participant_multiple
            !==
            null
            &&
            $participants
            %
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

        /*
        |--------------------------------------------------------------------------
        | Target
        |--------------------------------------------------------------------------
        */

        $target =
            $settings->target_survivors;

        if (
            ! $this->isPowerOfTwo(
                $target
            )
        ) {
            $errors[] =
                'El objetivo de supervivientes debe ser una potencia de 2.';
        }

        if (
            $participants
            <=
            $target
        ) {
            $errors[] =
                'La cantidad de participantes debe ser mayor que el objetivo de supervivientes.';
        }

        /*
        |--------------------------------------------------------------------------
        | BYEs
        |--------------------------------------------------------------------------
        */

        if (
            ! $phaseTemplate->allow_byes
            &&
            ! $this->isPowerOfTwo(
                $participants
            )
        ) {
            $errors[] =
                'Esta Fase no permite BYEs. La cantidad de participantes debe ser una potencia de 2.';
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
