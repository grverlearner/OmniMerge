<?php

namespace App\Services\Tournaments\RoundRobin;

use App\Models\PhaseRoundRobinSetting;
use App\Models\PhaseTemplate;

class RoundRobinValidator
{
    public function validate(
        PhaseTemplate $phaseTemplate,
        PhaseRoundRobinSetting $settings,
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
            'ROUND_ROBIN'
        ) {
            $errors[] =
                'La Fase no es de tipo Todos contra todos.';

            return $errors;
        }

        /*
        |--------------------------------------------------------------------------
        | Mínimo general
        |--------------------------------------------------------------------------
        */

        if ($participants < 2) {
            $errors[] =
                'Round Robin necesita al menos 2 participantes.';
        }

        /*
        |--------------------------------------------------------------------------
        | Cantidad exacta
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
        | Mínimo de PhaseTemplate
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
        | Ciclos
        |--------------------------------------------------------------------------
        */

        if (
            $settings->cycles < 1
            ||
            $settings->cycles > 10
        ) {
            $errors[] =
                'La cantidad de ciclos debe estar entre 1 y 10.';
        }

        /*
        |--------------------------------------------------------------------------
        | Schedule
        |--------------------------------------------------------------------------
        */

        if (
            $settings->schedule_mode
            !==
            'BALANCED'
        ) {
            $errors[] =
                'El modo de calendario seleccionado todavía no está disponible.';
        }

        return $errors;
    }
}
