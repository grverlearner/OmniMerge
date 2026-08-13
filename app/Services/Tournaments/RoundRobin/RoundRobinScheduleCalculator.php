<?php

namespace App\Services\Tournaments\RoundRobin;

use App\Models\PhaseRoundRobinSetting;
use App\Models\PhaseTemplate;

class RoundRobinScheduleCalculator
{
    public function __construct(
        private readonly
        RoundRobinValidator $validator
    ) {}

    public function calculate(
        PhaseTemplate $phaseTemplate,
        PhaseRoundRobinSetting $settings,
        int $participants,
        int $previewRoundLimit = 20
    ): array {
        $errors =
            $this->validator
            ->validate(
                $phaseTemplate,
                $settings,
                $participants
            );

        if ($errors !== []) {
            return [
                'valid' =>
                false,

                'errors' =>
                $errors,

                'participants' =>
                $participants,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Matemática general
        |--------------------------------------------------------------------------
        */

        $isOdd =
            $participants % 2 !== 0;

        $roundsPerCycle =
            $isOdd
            ? $participants
            : $participants - 1;

        $seriesPerCycle =
            intdiv(
                $participants
                    *
                    ($participants - 1),
                2
            );

        $totalRounds =
            $roundsPerCycle
            *
            $settings->cycles;

        $totalSeries =
            $seriesPerCycle
            *
            $settings->cycles;

        $seriesPerRound =
            intdiv(
                $participants,
                2
            );

        $restsPerRound =
            $isOdd
            ? 1
            : 0;

        $totalRestAssignments =
            $isOdd
            ? $participants
            *
            $settings->cycles
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Blueprint limitado
        |--------------------------------------------------------------------------
        |
        | Con 512 participantes podrían existir cientos de jornadas.
        | No necesitamos renderizarlas todas para un preview.
        |
        */

        $rounds =
            $this->generatePreviewRounds(
                $participants,
                $settings->cycles,
                $previewRoundLimit
            );

        return [
            'valid' =>
            true,

            'errors' =>
            [],

            'participants' =>
            $participants,

            'cycles' =>
            $settings->cycles,

            'is_odd' =>
            $isOdd,

            'rounds_per_cycle' =>
            $roundsPerCycle,

            'total_rounds' =>
            $totalRounds,

            'series_per_cycle' =>
            $seriesPerCycle,

            'total_series' =>
            $totalSeries,

            'series_per_round' =>
            $seriesPerRound,

            'rests_per_round' =>
            $restsPerRound,

            'total_rest_assignments' =>
            $totalRestAssignments,

            'default_best_of' =>
            $settings->default_best_of,

            'wins_required' =>
            intdiv(
                $settings->default_best_of,
                2
            ) + 1,

            'allow_draws' =>
            $settings->allow_draws,

            'preview_round_limit' =>
            $previewRoundLimit,

            'preview_rounds_count' =>
            count($rounds),

            'has_more_rounds' =>
            $totalRounds
                >
                count($rounds),

            'rounds' =>
            $rounds,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Generador de Preview
    |--------------------------------------------------------------------------
    */

    private function generatePreviewRounds(
        int $participants,
        int $cycles,
        int $limit
    ): array {
        $rounds = [];

        $globalRound = 1;

        for (
            $cycle = 1;
            $cycle <= $cycles;
            $cycle++
        ) {
            /*
            |--------------------------------------------------------------------------
            | Slots
            |--------------------------------------------------------------------------
            */

            $rotation =
                range(
                    1,
                    $participants
                );

            /*
             * Con número impar agregamos un slot vacío.
             * El participante emparejado con NULL descansa.
             */
            if (
                $participants % 2 !== 0
            ) {
                $rotation[] =
                    null;
            }

            $slotCount =
                count(
                    $rotation
                );

            $roundsInThisCycle =
                $slotCount - 1;

            for (
                $roundInCycle = 1;
                $roundInCycle <= $roundsInThisCycle;
                $roundInCycle++
            ) {
                if (
                    count($rounds)
                    >=
                    $limit
                ) {
                    return $rounds;
                }

                $pairings = [];

                $restSeed = null;

                $half =
                    intdiv(
                        $slotCount,
                        2
                    );

                for (
                    $index = 0;
                    $index < $half;
                    $index++
                ) {
                    $left =
                        $rotation[$index];

                    $right =
                        $rotation[$slotCount
                            -
                            1
                            -
                            $index];

                    /*
                    |--------------------------------------------------------------------------
                    | Descanso
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $left === null
                        ||
                        $right === null
                    ) {
                        $restSeed =
                            $left
                            ??
                            $right;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Alternancia de orientación por ciclo
                    |--------------------------------------------------------------------------
                    |
                    | No significa local/visitante todavía.
                    | Solo hace visible que un segundo ciclo puede invertir
                    | el orden de presentación de una pareja.
                    |
                    */

                    if (
                        $cycle % 2 === 0
                    ) {
                        [$left, $right] =
                            [$right, $left];
                    }

                    $pairings[] = [
                        'seed_a' =>
                        $left,

                        'seed_b' =>
                        $right,

                        'participant_a' =>
                        'Seed '
                            .
                            $left,

                        'participant_b' =>
                        'Seed '
                            .
                            $right,
                    ];
                }

                $rounds[] = [
                    'number' =>
                    $globalRound,

                    'cycle' =>
                    $cycle,

                    'round_in_cycle' =>
                    $roundInCycle,

                    'label' =>
                    'Jornada '
                        .
                        $globalRound,

                    'cycle_label' =>
                    'Ciclo '
                        .
                        $cycle,

                    'series_count' =>
                    count(
                        $pairings
                    ),

                    'rest_seed' =>
                    $restSeed,

                    'rest_participant' =>
                    $restSeed !== null
                        ? 'Seed '
                        .
                        $restSeed
                        : null,

                    'pairings' =>
                    $pairings,
                ];

                /*
                |--------------------------------------------------------------------------
                | Circle Method
                |--------------------------------------------------------------------------
                |
                | El primer slot permanece fijo.
                | Los demás rotan una posición.
                |
                */

                $fixed =
                    array_shift(
                        $rotation
                    );

                $last =
                    array_pop(
                        $rotation
                    );

                array_unshift(
                    $rotation,
                    $last
                );

                array_unshift(
                    $rotation,
                    $fixed
                );

                $globalRound++;
            }
        }

        return $rounds;
    }
}
