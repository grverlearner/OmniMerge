<?php

namespace App\Services\Tournaments\SingleElimination;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use Illuminate\Support\Collection;

class SingleEliminationAdvancedCalculator
{
    public function calculate(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationSetting $settings,
        int $participants,
        Collection $roundRules
    ): array {
        $errors = [];
        $warnings = [];
        $rounds = [];

        $rulesByRound =
            $roundRules->keyBy(
                'participants_in_round'
            );

        $target =
            max(
                1,
                (int) $settings->target_survivors
            );

        $current =
            $participants;

        $roundNumber =
            1;

        $totalSeries =
            0;

        $initialByes =
            0;

        $complete =
            true;

        $executable =
            true;

        if ($participants <= $target) {
            $errors[] =
                'La cantidad de participantes debe ser mayor que el objetivo de supervivientes.';
        }

        while (
            $errors === []
            &&
            $current > $target
            &&
            $roundNumber <= 64
        ) {
            $rule =
                $rulesByRound->get(
                    $current
                );

            $entrants =
                (int) (
                    $rule?->entrants_per_match
                    ??
                    $settings->entrants_per_match
                );

            $qualifiers =
                (int) (
                    $rule?->qualifiers_per_match
                    ??
                    $settings->qualifiers_per_match
                );

            $profile =
                (string) (
                    $rule?->encounter_profile
                    ??
                    $settings->encounter_profile
                );

            $shapeErrors =
                $this->shapeErrors(
                    $entrants,
                    $qualifiers,
                    $profile
                );

            if ($shapeErrors !== []) {
                $errors =
                    array_merge(
                        $errors,
                        $shapeErrors
                    );

                break;
            }

            if ($profile === 'CUSTOM') {
                $warnings[] =
                    'El perfil personalizado puede definirse y previsualizarse, pero su ejecución completa se habilitará con el motor avanzado del Competition Lab.';

                $executable =
                    false;
            }

            $policy =
                (string)
                $settings->remainder_policy;

            $resolution =
                $this->resolveRound(
                    $phaseTemplate,
                    $policy,
                    $current,
                    $target,
                    $entrants,
                    $qualifiers
                );

            if (isset($resolution['error'])) {
                $errors[] =
                    $resolution['error'];

                break;
            }

            if ($resolution['manual']) {
                $warnings[] =
                    $resolution['warning'];

                $complete =
                    false;

                $executable =
                    false;

                break;
            }

            $next =
                $resolution['next'];

            if ($next >= $current) {
                $errors[] =
                    'La configuración no reduce participantes en la ronda de '
                    .
                    $current
                    .
                    '.';

                break;
            }

            if ($next < $target) {
                $errors[] =
                    'La ronda de '
                    .
                    $current
                    .
                    ' participantes bajaría hasta '
                    .
                    $next
                    .
                    ' y sobrepasaría el objetivo de '
                    .
                    $target
                    .
                    '. Agrega una regla especial para esa ronda.';

                break;
            }

            $seriesFormat =
                (string) (
                    $rule?->series_format
                    ??
                    $settings->series_format
                    ??
                    'BEST_OF'
                );

            $bestOf =
                (int) (
                    $rule?->best_of
                    ??
                    $settings->default_best_of
                    ??
                    1
                );

            $fixedGames =
                (int) (
                    $rule?->fixed_games
                    ??
                    $settings->fixed_games
                    ??
                    1
                );

            $rounds[] = [
                'number' =>
                $roundNumber,

                'key' =>
                'ROUND_'
                    .
                    $current,

                'label' =>
                $this->roundLabel(
                    $current,
                    $next,
                    $target,
                    $resolution['preliminary']
                ),

                'slots' =>
                $current,

                'participants' =>
                $current,

                'series' =>
                $resolution['series'],

                'byes' =>
                $resolution['byes'],

                'eliminated' =>
                $current
                    -
                    $next,

                'survivors' =>
                $next,

                'series_format' =>
                $seriesFormat,

                'best_of' =>
                $bestOf,

                'fixed_games' =>
                $fixedGames,

                'series_label' =>
                $seriesFormat === 'FIXED_GAMES'
                    ? $fixedGames
                    .
                    ' '
                    .
                    (
                        $fixedGames === 1
                        ? 'enfrentamiento'
                        : 'enfrentamientos'
                    )
                    : 'BO'
                    .
                    $bestOf,

                'wins_required' =>
                $seriesFormat === 'BEST_OF'
                    ? intdiv(
                        $bestOf,
                        2
                    ) + 1
                    : null,

                'has_override' =>
                $rule !== null,

                'entrants_per_match' =>
                $entrants,

                'qualifiers_per_match' =>
                $qualifiers,

                'encounter_profile' =>
                $profile,

                'remainder' =>
                $current
                    %
                    $entrants,

                'remainder_policy' =>
                $policy,

                'distribution' =>
                $resolution['distribution'],

                'preliminary' =>
                $resolution['preliminary'],
            ];

            if ($roundNumber === 1) {
                $initialByes =
                    $resolution['byes'];
            }

            $totalSeries +=
                $resolution['series'];

            $current =
                $next;

            $roundNumber++;
        }

        if (
            $roundNumber > 64
            &&
            $current > $target
        ) {
            $errors[] =
                'La configuración superó el límite de 64 rondas. Revisa sus reglas de clasificación.';
        }

        return [
            'valid' =>
            $errors === [],

            'complete' =>
            $complete
                &&
                $current === $target,

            'executable' =>
            $executable
                &&
                $errors === []
                &&
                $current === $target,

            'errors' =>
            array_values(
                array_unique(
                    $errors
                )
            ),

            'warnings' =>
            array_values(
                array_unique(
                    $warnings
                )
            ),

            'participants' =>
            $participants,

            'bracket_size' =>
            $participants,

            'target_survivors' =>
            $target,

            'initial_byes' =>
            $initialByes,

            'round_count' =>
            count(
                $rounds
            ),

            'total_series' =>
            $totalSeries,

            'total_eliminated' =>
            $participants
                -
                $current,

            'survivors_count' =>
            $current,

            'eliminated_count' =>
            $participants
                -
                $current,

            'rounds' =>
            $rounds,
        ];
    }

    private function resolveRound(
        PhaseTemplate $phaseTemplate,
        string $policy,
        int $participants,
        int $target,
        int $entrants,
        int $qualifiers
    ): array {
        $remainder =
            $participants
            %
            $entrants;

        $fullSeries =
            intdiv(
                $participants,
                $entrants
            );

        if ($remainder === 0) {
            return [
                'manual' =>
                false,

                'series' =>
                $fullSeries,

                'byes' =>
                0,

                'next' =>
                $fullSeries
                    *
                    $qualifiers,

                'distribution' =>
                array_fill(
                    0,
                    $fullSeries,
                    $entrants
                ),

                'preliminary' =>
                false,
            ];
        }

        if ($policy === 'REJECT') {
            return [
                'error' =>
                'La ronda de '
                    .
                    $participants
                    .
                    ' deja '
                    .
                    $remainder
                    .
                    ' participante(s) sobrante(s). Cambia la política o agrega una regla especial.',
            ];
        }

        if ($policy === 'MANUAL') {
            return [
                'manual' =>
                true,

                'warning' =>
                'La política Manual deja pendiente la distribución de la ronda de '
                    .
                    $participants
                    .
                    ' participantes.',
            ];
        }

        if ($policy === 'BYE') {
            if (! $phaseTemplate->allow_byes) {
                return [
                    'error' =>
                    'La Fase no permite BYEs para resolver los sobrantes.',
                ];
            }

            return [
                'manual' =>
                false,

                'series' =>
                $fullSeries,

                'byes' =>
                $remainder,

                'next' => (
                    $fullSeries
                    *
                    $qualifiers
                )
                    +
                    $remainder,

                'distribution' =>
                array_fill(
                    0,
                    $fullSeries,
                    $entrants
                ),

                'preliminary' =>
                false,
            ];
        }

        if ($policy === 'INCOMPLETE_MATCH') {
            if (
                $remainder < 2
                ||
                $qualifiers >= $remainder
            ) {
                return [
                    'error' =>
                    'El encuentro incompleto necesita al menos 2 participantes y menos clasificados que participantes.',
                ];
            }

            return [
                'manual' =>
                false,

                'series' =>
                $fullSeries + 1,

                'byes' =>
                0,

                'next' => (
                    $fullSeries + 1
                )
                    *
                    $qualifiers,

                'distribution' =>
                array_merge(
                    array_fill(
                        0,
                        $fullSeries,
                        $entrants
                    ),
                    [
                        $remainder,
                    ]
                ),

                'preliminary' =>
                false,
            ];
        }

        if ($policy === 'BALANCED') {
            $series =
                (int)
                ceil(
                    $participants
                        /
                        $entrants
                );

            $minimumSize =
                intdiv(
                    $participants,
                    $series
                );

            if (
                $minimumSize < 2
                ||
                $qualifiers >= $minimumSize
            ) {
                return [
                    'error' =>
                    'La distribución balanceada produciría encuentros demasiado pequeños para esa cantidad de clasificados.',
                ];
            }

            $largeSeries =
                $participants
                %
                $series;

            $smallSeries =
                $series
                -
                $largeSeries;

            return [
                'manual' =>
                false,

                'series' =>
                $series,

                'byes' =>
                0,

                'next' =>
                $series
                    *
                    $qualifiers,

                'distribution' =>
                array_merge(
                    array_fill(
                        0,
                        $largeSeries,
                        $minimumSize + 1
                    ),
                    array_fill(
                        0,
                        $smallSeries,
                        $minimumSize
                    )
                ),

                'preliminary' =>
                false,
            ];
        }

        if ($policy === 'PRELIMINARY') {
            for (
                $series = 1;
                $series <= $fullSeries;
                $series++
            ) {
                $next =
                    $participants
                    -
                    (
                        $series
                        *
                        (
                            $entrants
                            -
                            $qualifiers
                        )
                    );

                if (
                    $next >= $target
                    &&
                    $next < $participants
                    &&
                    $next % $entrants === 0
                    &&
                    (
                        $series
                        *
                        $entrants
                    ) <= $participants
                ) {
                    return [
                        'manual' =>
                        false,

                        'series' =>
                        $series,

                        'byes' =>
                        $participants
                            -
                            (
                                $series
                                *
                                $entrants
                            ),

                        'next' =>
                        $next,

                        'distribution' =>
                        array_fill(
                            0,
                            $series,
                            $entrants
                        ),

                        'preliminary' =>
                        true,
                    ];
                }
            }

            return [
                'error' =>
                'No existe una ronda preliminar automática que elimine el sobrante sin sobrepasar el objetivo.',
            ];
        }

        return [
            'error' =>
            'La política de sobrantes seleccionada no es válida.',
        ];
    }

    private function shapeErrors(
        int $entrants,
        int $qualifiers,
        string $profile
    ): array {
        $errors = [];

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
                'Los clasificados deben ser al menos 1 y menores que los participantes del encuentro.';
        }

        if (
            $profile === 'DUEL'
            &&
            (
                $entrants !== 2
                ||
                $qualifiers !== 1
            )
        ) {
            $errors[] =
                'El perfil Duelo exige una relación 2 → 1.';
        }

        if (
            $profile === 'MULTI_COMPETITOR'
            &&
            $entrants < 3
        ) {
            $errors[] =
                'El perfil Multicompetidor exige al menos 3 participantes.';
        }

        return $errors;
    }

    private function roundLabel(
        int $participants,
        int $survivors,
        int $target,
        bool $preliminary
    ): string {
        if ($preliminary) {
            return
                'Ronda preliminar · '
                .
                $participants
                .
                ' participantes';
        }

        if (
            $survivors === $target
            &&
            $target === 1
        ) {
            return 'Final';
        }

        return
            'Ronda de '
            .
            $participants;
    }
}
