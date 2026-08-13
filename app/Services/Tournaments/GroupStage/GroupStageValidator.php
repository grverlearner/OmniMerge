<?php

namespace App\Services\Tournaments\GroupStage;

use App\Models\PhaseGroupStageSetting;
use App\Models\PhaseTemplate;

class GroupStageValidator
{
    public function validateBase(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageSetting $settings,
        int $participants
    ): array {
        $errors = [];

        if (
            $phaseTemplate->phase_type
            !==
            'GROUP_STAGE'
        ) {
            $errors[] =
                'La Fase no es de tipo Fase de grupos.';

            return $errors;
        }

        if ($participants < 4) {
            $errors[] =
                'Una Fase de grupos necesita al menos 4 participantes.';
        }

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

        if (
            $settings->internal_engine_type
            !==
            'ROUND_ROBIN'
        ) {
            $errors[] =
                'T4 actualmente admite Round Robin como motor interno.';
        }

        if (
            $settings->min_group_size
            <
            2
        ) {
            $errors[] =
                'Cada grupo debe permitir al menos 2 participantes.';
        }

        if (
            $settings->max_group_size
            <
            $settings->min_group_size
        ) {
            $errors[] =
                'El máximo por grupo no puede ser menor que el mínimo.';
        }

        if (
            $settings->group_count_mode
            ===
            'FIXED_GROUP_COUNT'
            &&
            (
                ! $settings->group_count
                ||
                $settings->group_count < 2
            )
        ) {
            $errors[] =
                'Debes configurar al menos 2 grupos.';
        }

        if (
            $settings->group_count_mode
            ===
            'TARGET_GROUP_SIZE'
            &&
            (
                ! $settings->target_group_size
                ||
                $settings->target_group_size < 2
            )
        ) {
            $errors[] =
                'El tamaño objetivo debe ser al menos 2.';
        }

        return $errors;
    }

    public function validateGroupSizes(
        PhaseGroupStageSetting $settings,
        array $sizes
    ): array {
        $errors = [];

        if (count($sizes) < 2) {
            $errors[] =
                'Una Fase de grupos debe producir al menos 2 grupos.';
        }

        foreach (
            $sizes
            as
            $index => $size
        ) {
            if (
                $size
                <
                $settings->min_group_size
            ) {
                $errors[] =
                    'El Grupo '
                    .
                    ($index + 1)
                    .
                    ' tendría solo '
                    .
                    $size
                    .
                    ' participantes y el mínimo configurado es '
                    .
                    $settings->min_group_size
                    .
                    '.';
            }

            if (
                $size
                >
                $settings->max_group_size
            ) {
                $errors[] =
                    'El Grupo '
                    .
                    ($index + 1)
                    .
                    ' tendría '
                    .
                    $size
                    .
                    ' participantes y el máximo configurado es '
                    .
                    $settings->max_group_size
                    .
                    '.';
            }
        }

        return $errors;
    }
}
