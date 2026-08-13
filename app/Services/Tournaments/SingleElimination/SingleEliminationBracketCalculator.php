<?php

namespace App\Services\Tournaments\SingleElimination;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use Illuminate\Support\Collection;

class SingleEliminationBracketCalculator
{
    public function __construct(
        private readonly
        SingleEliminationValidator $validator
    ) {}

    public function calculate(
        PhaseTemplate $phaseTemplate,
        PhaseSingleEliminationSetting $settings,
        int $participants,
        Collection $roundRules
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
                'valid' => false,
                'errors' => $errors,

                'participants' =>
                $participants,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Bracket
        |--------------------------------------------------------------------------
        */

        $bracketSize =
            $this->nextPowerOfTwo(
                $participants
            );

        $target =
            $settings->target_survivors;

        /*
        |--------------------------------------------------------------------------
        | Reglas por tamaño de ronda
        |--------------------------------------------------------------------------
        */

        $rulesByRound =
            $roundRules->keyBy(
                'participants_in_round'
            );

        /*
        |--------------------------------------------------------------------------
        | Construcción
        |--------------------------------------------------------------------------
        */

        $rounds = [];

        $currentSlots =
            $bracketSize;

        $currentParticipants =
            $participants;

        $roundNumber = 1;

        while (
            $currentSlots
            >
            $target
        ) {
            $nextSlots =
                intdiv(
                    $currentSlots,
                    2
                );

            /*
             * Cuántas eliminaciones necesitamos
             * para llenar la siguiente ronda.
             */
            $seriesCount =
                max(
                    0,
                    $currentParticipants
                        -
                        $nextSlots
                );

            /*
             * Los participantes restantes avanzan
             * por BYE.
             */
            $byes =
                max(
                    0,
                    $nextSlots
                        -
                        $seriesCount
                );

            $rule =
                $rulesByRound->get(
                    $currentSlots
                );

            $bestOf =
                $rule?->best_of
                ??
                $settings->default_best_of;

            $winsRequired =
                intdiv(
                    $bestOf,
                    2
                ) + 1;

            $rounds[] = [
                'number' =>
                $roundNumber,

                'key' =>
                'ROUND_'
                    .
                    $currentSlots,

                'label' =>
                $this->roundLabel(
                    $currentSlots
                ),

                'slots' =>
                $currentSlots,

                'participants' =>
                $currentParticipants,

                'series' =>
                $seriesCount,

                'byes' =>
                $byes,

                'eliminated' =>
                $seriesCount,

                'survivors' =>
                $nextSlots,

                'best_of' =>
                $bestOf,

                'wins_required' =>
                $winsRequired,

                'has_override' =>
                $rule !== null,
            ];

            /*
            |--------------------------------------------------------------------------
            | Desde la segunda ronda el bracket ya está completo
            |--------------------------------------------------------------------------
            */

            $currentParticipants =
                $nextSlots;

            $currentSlots =
                $nextSlots;

            $roundNumber++;
        }

        /*
        |--------------------------------------------------------------------------
        | Totales
        |--------------------------------------------------------------------------
        */

        $totalSeries =
            $participants
            -
            $target;

        $initialByes =
            max(
                0,
                $bracketSize
                    -
                    $participants
            );

        return [
            'valid' =>
            true,

            'errors' =>
            [],

            /*
             * Entrada
             */
            'participants' =>
            $participants,

            /*
             * Bracket
             */
            'bracket_size' =>
            $bracketSize,

            'target_survivors' =>
            $target,

            'initial_byes' =>
            $initialByes,

            /*
             * Totales
             */
            'round_count' =>
            count(
                $rounds
            ),

            'total_series' =>
            $totalSeries,

            'total_eliminated' =>
            $totalSeries,

            /*
             * Salidas
             */
            'survivors_count' =>
            $target,

            'eliminated_count' =>
            $participants
                -
                $target,

            /*
             * Rondas
             */
            'rounds' =>
            $rounds,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Potencia de 2 siguiente
    |--------------------------------------------------------------------------
    */

    public function nextPowerOfTwo(
        int $value
    ): int {
        if ($value <= 1) {
            return 1;
        }

        $power =
            1;

        while (
            $power
            <
            $value
        ) {
            $power *= 2;
        }

        return $power;
    }

    /*
    |--------------------------------------------------------------------------
    | Etiqueta de ronda
    |--------------------------------------------------------------------------
    */

    public function roundLabel(
        int $slots
    ): string {
        return match ($slots) {
            2 =>
            'Final',

            4 =>
            'Semifinal',

            8 =>
            'Cuartos de final',

            16 =>
            'Ronda de 16',

            32 =>
            'Ronda de 32',

            64 =>
            'Ronda de 64',

            128 =>
            'Ronda de 128',

            256 =>
            'Ronda de 256',

            512 =>
            'Ronda de 512',

            default =>
            'Ronda de '
                .
                $slots,
        };
    }
}
