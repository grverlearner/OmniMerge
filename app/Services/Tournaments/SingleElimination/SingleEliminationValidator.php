<?php

namespace App\Services\Tournaments\SingleElimination;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;

class SingleEliminationValidator
{
    private const BEST_OF_VALUES = [
        1,
        3,
        5,
        7,
        9,
    ];

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

        $errors =
            array_merge(
                $errors,
                $this->validateSeriesConfiguration(
                    (string)
                    (
                        $settings->series_format
                        ?:
                        'BEST_OF'
                    ),
                    $settings->default_best_of,
                    $settings->fixed_games,
                    'La configuración general'
                )
            );

        $target =
            max(
                1,
                (int)
                $settings->target_survivors
            );

        $advanced =
            $settings->configuration_mode
            ===
            'ADVANCED';

        if (
            ! $advanced
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

        if (! $advanced) {
            $errors =
                array_merge(
                    $errors,
                    $this->validateBasic(
                        $phaseTemplate,
                        $settings,
                        $participants
                    )
                );
        } else {
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

    public function validateSeriesConfiguration(
        string $format,
        ?int $bestOf,
        ?int $fixedGames,
        string $context = 'La serie'
    ): array {
        $errors = [];

        $format =
            strtoupper(
                trim(
                    $format
                )
            );

        if (
            ! in_array(
                $format,
                [
                    'BEST_OF',
                    'FIXED_GAMES',
                ],
                true
            )
        ) {
            return [
                $context
                .
                ' usa un formato de serie no soportado.',
            ];
        }

        if ($format === 'BEST_OF') {
            $bestOf =
                $bestOf
                ??
                1;

            if (
                ! in_array(
                    (int)
                    $bestOf,
                    self::BEST_OF_VALUES,
                    true
                )
            ) {
                $errors[] =
                    $context
                    .
                    ' debe usar BO1, BO3, BO5, BO7 o BO9.';
            }

            return $errors;
        }

        if (
            $fixedGames === null
            ||
            $fixedGames < 1
            ||
            $fixedGames > 99
        ) {
            $errors[] =
                $context
                .
                ' debe usar una cantidad fija entre 1 y 99 juegos.';
        }

        return $errors;
    }

    private function validateBasic(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationSetting $settings,
        int $participants
    ): array {
        $errors = [];

        if (
            $settings->input_mode !== null
            &&
            $settings->input_mode !== 'POOL'
        ) {
            $errors[] =
                'En modo básico la entrada debe usar Bolsa común (POOL).';
        }

        if (
            $settings->routing_mode !== null
            &&
            $settings->routing_mode !== 'AUTOMATIC'
        ) {
            $errors[] =
                'En modo básico el enrutamiento debe ser Automático.';
        }

        if (
            $settings->encounter_profile !== null
            &&
            $settings->encounter_profile !== 'DUEL'
        ) {
            $errors[] =
                'En modo básico el perfil de encuentro debe ser Duelo 2 → 1.';
        }

        if (
            $settings->remainder_policy !== null
            &&
            ! in_array(
                $settings->remainder_policy,
                [
                    'BYE',
                    'REJECT',
                ],
                true
            )
        ) {
            $errors[] =
                'En modo básico los sobrantes solo admiten BYE o Rechazar.';
        }

        if (
            $settings->remainder_policy === 'BYE'
            &&
            ! $phaseTemplate->allow_byes
        ) {
            $errors[] =
                'La política BYE no puede usarse porque la Fase no permite BYEs.';
        }

        $rejectIrregular =
            $settings->remainder_policy === 'REJECT'
            ||
            ! $phaseTemplate->allow_byes;

        if (
            $rejectIrregular
            &&
            ! $this->isPowerOfTwo(
                $participants
            )
        ) {
            $errors[] =
                'La cantidad de participantes debe ser una potencia de 2 cuando los BYEs no pueden utilizarse.';
        }

        if (
            (bool)
            $settings->reseed_each_round
            &&
            $settings->pairing_mode !== 'STANDARD_SEEDED'
        ) {
            $errors[] =
                'El reseeding del modo básico solo es compatible con Pairing Seeded estándar.';
        }

        return $errors;
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
