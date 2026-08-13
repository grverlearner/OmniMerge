<?php

namespace App\Services\Tournaments\Swiss;

use App\Models\PhaseSwissSetting;
use App\Models\PhaseTemplate;

class SwissValidator
{
    public function validate(
        PhaseTemplate $phaseTemplate,
        PhaseSwissSetting $settings,
        int $participants
    ): array {
        $errors = [];
        $warnings = [];

        if (
            $phaseTemplate->phase_type
            !==
            'SWISS'
        ) {
            $errors[] =
                'La Fase no es de tipo Sistema suizo.';

            return [
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        if ($participants < 2) {
            $errors[] =
                'Swiss necesita al menos 2 participantes.';
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

        /*
        |--------------------------------------------------------------------------
        | BYE
        |--------------------------------------------------------------------------
        */

        if (
            $participants % 2 !== 0
            &&
            $settings->bye_policy
            ===
            'DISABLED'
        ) {
            $errors[] =
                'Una cantidad impar necesita una política de BYE.';
        }

        /*
        |--------------------------------------------------------------------------
        | Best Of
        |--------------------------------------------------------------------------
        */

        if (
            ! in_array(
                $settings->default_best_of,
                [
                    1,
                    3,
                    5,
                    7,
                    9,
                ],
                true
            )
        ) {
            $errors[] =
                'El Best Of debe ser 1, 3, 5, 7 o 9.';
        }

        /*
        |--------------------------------------------------------------------------
        | Fixed rounds
        |--------------------------------------------------------------------------
        */

        if (
            $settings->completion_mode
            ===
            'FIXED_ROUNDS'
        ) {
            $rounds =
                (int)
                $settings->fixed_rounds;

            if ($rounds < 1) {
                $errors[] =
                    'Debes configurar al menos una ronda.';
            }

            if (
                $settings->rematch_policy
                ===
                'STRICT_NO_REMATCH'
            ) {
                $byeCapacity =
                    $participants % 2 !== 0
                    ? $settings
                    ->max_byes_per_participant
                    : 0;

                $maxUniqueRounds =
                    ($participants - 1)
                    +
                    $byeCapacity;

                if (
                    $rounds
                    >
                    $maxUniqueRounds
                ) {
                    $errors[] =
                        'Con '
                        .
                        $participants
                        .
                        ' participantes y rematches prohibidos, '
                        .
                        'la configuración exige más rondas de las que pueden resolverse con rivales únicos/BYEs permitidos.';
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Record thresholds
        |--------------------------------------------------------------------------
        */

        if (
            $settings->completion_mode
            ===
            'RECORD_THRESHOLDS'
        ) {
            if (
                (int)
                $settings->qualification_wins
                < 1
            ) {
                $errors[] =
                    'Configura al menos una victoria para clasificar.';
            }

            if (
                (int)
                $settings->elimination_losses
                < 1
            ) {
                $errors[] =
                    'Configura al menos una derrota para eliminar.';
            }

            if (
                (int)
                $settings->max_rounds
                < 1
            ) {
                $errors[] =
                    'Configura el máximo de rondas.';
            }

            $decisiveMaximum =
                (int)
                $settings->qualification_wins
                +
                (int)
                $settings->elimination_losses
                -
                1;

            if (
                ! $settings->allow_draws
                &&
                $settings->max_rounds
                <
                $decisiveMaximum
            ) {
                $warnings[] =
                    'El máximo de rondas es menor que el camino decisivo más largo ('
                    .
                    $decisiveMaximum
                    .
                    '). Algunos participantes podrían llegar al fallback antes de alcanzar un threshold.';
            }

            if (
                $settings->allow_draws
            ) {
                $warnings[] =
                    'Como se permiten empates, max_rounds actúa como límite de seguridad para evitar participantes activos indefinidamente.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Acceleration
        |--------------------------------------------------------------------------
        */

        if (
            $settings->acceleration_mode
            ===
            'GENERIC_VIRTUAL_POINTS'
        ) {
            if (
                ! $settings->acceleration_rounds
                ||
                $settings->acceleration_rounds < 1
            ) {
                $errors[] =
                    'La aceleración necesita una cantidad de rondas.';
            }

            if (
                ! $settings->acceleration_seed_count
                ||
                $settings->acceleration_seed_count < 1
            ) {
                $errors[] =
                    'La aceleración necesita una cantidad de seeds beneficiados.';
            }

            if (
                $settings->acceleration_virtual_points
                ===
                null
            ) {
                $errors[] =
                    'La aceleración necesita puntos virtuales.';
            }

            if (
                $settings->pairing_basis
                ===
                'WIN_LOSS_RECORD'
            ) {
                $warnings[] =
                    'Los puntos virtuales tienen más sentido con MATCH_POINTS o PAIRING_SCORE; WIN_LOSS_RECORD prioriza el récord.';
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
