<?php

namespace App\Services\Tournaments\Graph\Flow;

use App\Models\PhaseExit;

class TournamentGraphCapacityCalculator
{
    public function unknown(): array
    {
        return [
            'min' => 0,
            'max' => null,
            'exact' => null,
            'known' => false,
        ];
    }

    public function exact(int $quantity): array
    {
        $quantity = max(0, $quantity);

        return [
            'min' => $quantity,
            'max' => $quantity,
            'exact' => $quantity,
            'known' => true,
        ];
    }

    public function range(
        int $minimum,
        ?int $maximum
    ): array {
        $minimum = max(0, $minimum);

        if ($maximum !== null) {
            $maximum = max(
                $minimum,
                $maximum
            );
        }

        return [
            'min' => $minimum,
            'max' => $maximum,
            'exact' =>
            $maximum !== null
                &&
                $minimum === $maximum
                ? $minimum
                : null,
            'known' =>
            $maximum !== null,
        ];
    }

    public function sum(array $forecasts): array
    {
        if ($forecasts === []) {
            return $this->exact(0);
        }

        $minimum = 0;
        $maximum = 0;
        $allMaximumsKnown = true;

        foreach ($forecasts as $forecast) {
            $forecast =
                $this->normalize(
                    $forecast
                );

            $minimum += $forecast['min'];

            if ($forecast['max'] === null) {
                $allMaximumsKnown = false;
            } elseif ($allMaximumsKnown) {
                $maximum += $forecast['max'];
            }
        }

        return $this->range(
            $minimum,
            $allMaximumsKnown
                ? $maximum
                : null
        );
    }

    public function combineForPort(
        array $forecasts,
        string $mergePolicy
    ): array {
        if ($forecasts === []) {
            return $this->exact(0);
        }

        $forecasts =
            array_values(
                array_map(
                    fn(array $forecast) =>
                    $this->normalize(
                        $forecast
                    ),
                    $forecasts
                )
            );

        return match ($mergePolicy) {
            'APPEND',
            'WAIT_ALL' =>
            $this->sum(
                $forecasts
            ),

            'FIRST_AVAILABLE' =>
            $this->firstAvailable(
                $forecasts
            ),

            'PRIORITY' =>
            $this->priority(
                $forecasts
            ),

            default =>
            $this->sum(
                $forecasts
            ),
        };
    }

    public function allocate(
        array $source,
        string $allocationMode,
        int|float|null $allocationValue
    ): array {
        $source =
            $this->normalize(
                $source
            );

        return match ($allocationMode) {
            'ALL' =>
            $source,

            'TAKE_N' =>
            $this->takeN(
                $source,
                (int) $allocationValue
            ),

            'PERCENTAGE' =>
            $this->percentage(
                $source,
                (float) $allocationValue
            ),

            'REMAINDER' =>
            $this->range(
                0,
                $source['max']
            ),

            default =>
            $this->unknown(),
        };
    }

    public function fromExit(
        array $phaseInput,
        PhaseExit $exit
    ): array {
        $phaseInput =
            $this->normalize(
                $phaseInput
            );

        return match ($exit->selector_type) {
            'TOP_N',
            'BOTTOM_N' =>
            $this->limitedSelection(
                $phaseInput,
                (int) (
                    $exit->selector_from
                    ?? 0
                )
            ),

            'RANK_POSITION' =>
            $this->limitedSelection(
                $phaseInput,
                1
            ),

            'RANK_RANGE' =>
            $this->rankRange(
                $phaseInput,
                $exit->selector_from,
                $exit->selector_to
            ),

            'ALL' =>
            $phaseInput,

            'REMAINING',
            'ELIMINATED',
            'ELIMINATED_IN_ROUND',
            'MATCH_LOSERS' =>
            $this->range(
                0,
                $phaseInput['max']
            ),

            'SURVIVORS',
            'MATCH_WINNERS',
            'ENGINE_RULES' =>
            $this->range(
                0,
                $phaseInput['max']
            ),

            default =>
            $this->unknown(),
        };
    }

    public function label(array $forecast): string
    {
        $forecast =
            $this->normalize(
                $forecast
            );

        if ($forecast['exact'] !== null) {
            return
                $forecast['exact']
                .
                ' exactos';
        }

        if ($forecast['max'] === null) {
            if ($forecast['min'] > 0) {
                return
                    $forecast['min']
                    .
                    ' o más';
            }

            return 'Cantidad variable';
        }

        return
            $forecast['min']
            .
            '–'
            .
            $forecast['max'];
    }

    public function compareWithContract(
        array $forecast,
        ?int $minimum,
        ?int $maximum,
        ?int $exact
    ): array {
        $forecast =
            $this->normalize(
                $forecast
            );

        $problems = [];

        if ($exact !== null) {
            if (
                $forecast['max'] !== null
                &&
                $forecast['max'] < $exact
            ) {
                $problems[] = [
                    'type' => 'BELOW_EXACT',
                    'severity' => 'ERROR',
                    'expected' => $exact,
                    'message' =>
                    'puede recibir como máximo '
                        .
                        $forecast['max']
                        .
                        ', pero necesita exactamente '
                        .
                        $exact,
                ];
            } elseif ($forecast['min'] > $exact) {
                $problems[] = [
                    'type' => 'OVER_EXACT',
                    'severity' => 'ERROR',
                    'expected' => $exact,
                    'message' =>
                    'recibe como mínimo '
                        .
                        $forecast['min']
                        .
                        ', pero admite exactamente '
                        .
                        $exact,
                ];
            } elseif (
                $forecast['exact'] === null
                ||
                $forecast['exact'] !== $exact
            ) {
                $problems[] = [
                    'type' => 'EXACT_NOT_GUARANTEED',
                    'severity' => 'WARNING',
                    'expected' => $exact,
                    'message' =>
                    'necesita exactamente '
                        .
                        $exact
                        .
                        ', pero el flujo calculado es '
                        .
                        $this->label(
                            $forecast
                        ),
                ];
            }

            return $problems;
        }

        if (
            $minimum !== null
            &&
            $forecast['max'] !== null
            &&
            $forecast['max'] < $minimum
        ) {
            $problems[] = [
                'type' => 'BELOW_MINIMUM',
                'severity' => 'ERROR',
                'expected' => $minimum,
                'message' =>
                'puede recibir como máximo '
                    .
                    $forecast['max']
                    .
                    ', menos que el mínimo requerido de '
                    .
                    $minimum,
            ];
        } elseif (
            $minimum !== null
            &&
            $forecast['min'] < $minimum
        ) {
            $problems[] = [
                'type' => 'MINIMUM_NOT_GUARANTEED',
                'severity' => 'WARNING',
                'expected' => $minimum,
                'message' =>
                'requiere al menos '
                    .
                    $minimum
                    .
                    ', pero el flujo puede bajar hasta '
                    .
                    $forecast['min'],
            ];
        }

        if (
            $maximum !== null
            &&
            $forecast['min'] > $maximum
        ) {
            $problems[] = [
                'type' => 'OVER_MAXIMUM',
                'severity' => 'ERROR',
                'expected' => $maximum,
                'message' =>
                'recibe como mínimo '
                    .
                    $forecast['min']
                    .
                    ', más que el máximo permitido de '
                    .
                    $maximum,
            ];
        } elseif (
            $maximum !== null
            &&
            (
                $forecast['max'] === null
                ||
                $forecast['max'] > $maximum
            )
        ) {
            $problems[] = [
                'type' => 'MAXIMUM_CAN_OVERFLOW',
                'severity' => 'WARNING',
                'expected' => $maximum,
                'message' =>
                'admite como máximo '
                    .
                    $maximum
                    .
                    ', pero el flujo calculado es '
                    .
                    $this->label(
                        $forecast
                    ),
            ];
        }

        return $problems;
    }

    private function firstAvailable(
        array $forecasts
    ): array {
        $minimum =
            min(
                array_column(
                    $forecasts,
                    'min'
                )
            );

        $knownMaximums =
            array_values(
                array_filter(
                    array_column(
                        $forecasts,
                        'max'
                    ),
                    fn($value) =>
                    $value !== null
                )
            );

        $hasUnknownMaximum =
            count($knownMaximums)
            !==
            count($forecasts);

        return $this->range(
            $minimum,
            $hasUnknownMaximum
                ? null
                : max($knownMaximums)
        );
    }

    private function priority(
        array $forecasts
    ): array {
        $maximums =
            array_column(
                $forecasts,
                'max'
            );

        if (
            in_array(
                null,
                $maximums,
                true
            )
        ) {
            return $this->range(
                0,
                null
            );
        }

        return $this->range(
            0,
            max($maximums)
        );
    }

    private function takeN(
        array $source,
        int $quantity
    ): array {
        $quantity =
            max(
                0,
                $quantity
            );

        $minimum =
            min(
                $source['min'],
                $quantity
            );

        $maximum =
            $source['max'] === null
            ? $quantity
            : min(
                $source['max'],
                $quantity
            );

        return $this->range(
            $minimum,
            $maximum
        );
    }

    private function percentage(
        array $source,
        float $percentage
    ): array {
        $percentage =
            max(
                0,
                min(
                    100,
                    $percentage
                )
            );

        $factor =
            $percentage
            /
            100;

        $minimum =
            (int) floor(
                $source['min']
                    *
                    $factor
            );

        $maximum =
            $source['max'] === null
            ? null
            : (int) ceil(
                $source['max']
                    *
                    $factor
            );

        return $this->range(
            $minimum,
            $maximum
        );
    }

    private function limitedSelection(
        array $source,
        int $quantity
    ): array {
        $quantity =
            max(
                0,
                $quantity
            );

        if ($quantity === 0) {
            return $this->unknown();
        }

        $minimum =
            $source['min'] >= $quantity
            ? $quantity
            : 0;

        $maximum =
            $source['max'] === null
            ? $quantity
            : min(
                $quantity,
                $source['max']
            );

        return $this->range(
            $minimum,
            $maximum
        );
    }

    private function rankRange(
        array $source,
        ?int $from,
        ?int $to
    ): array {
        if (
            $from === null
            ||
            $to === null
            ||
            $to < $from
        ) {
            return $this->unknown();
        }

        $quantity =
            ($to - $from)
            +
            1;

        return $this->limitedSelection(
            $source,
            $quantity
        );
    }

    private function normalize(
        array $forecast
    ): array {
        $minimum =
            max(
                0,
                (int) (
                    $forecast['min']
                    ??
                    0
                )
            );

        $maximum =
            array_key_exists(
                'max',
                $forecast
            )
            &&
            $forecast['max'] !== null
            ? max(
                $minimum,
                (int) $forecast['max']
            )
            : null;

        $exact =
            $maximum !== null
            &&
            $minimum === $maximum
            ? $minimum
            : null;

        return [
            'min' => $minimum,
            'max' => $maximum,
            'exact' => $exact,
            'known' => $maximum !== null,
        ];
    }
}
