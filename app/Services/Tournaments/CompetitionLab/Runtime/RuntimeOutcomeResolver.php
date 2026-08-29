<?php

namespace App\Services\Tournaments\CompetitionLab\Runtime;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RuntimeOutcomeResolver
{
    /*
     * Salidas «lo que quede».
     *
     * Un «#3 lugar» y unos «Eliminados» hablan de la misma gente: el tercero
     * perdio, luego esta eliminado. Tratando las dos como salidas especificas,
     * cualquier fase que reparta puestos chocaba consigo misma —«las Phase
     * Exits intentan consumir al mismo participante mas de una vez»— y no
     * habia forma de configurarla.
     *
     * Estas tres no describen un puesto, describen un resto: se evaluan
     * DESPUES de las especificas y sobre quien todavia no ha salido por
     * ninguna. «Eliminados» sigue queriendo decir lo mismo que siempre; lo
     * unico que cambia es que ya no reclama a quien salio por la puerta del
     * tercer puesto.
     *
     * SURVIVORS se queda fuera a proposito: es la salida con la que una fase
     * alimenta a la siguiente, y convertirla en un resto podria vaciar en
     * silencio el reparto de un torneo que hoy funciona.
     */
    private const CATCH_ALL = [
        'REMAINING',
        'ALL',
        'ELIMINATED',
    ];

    public function resolve(
        Collection $phaseExits,
        array $runtime,
        array $participantIds
    ): array {
        $standings =
            collect(
                $runtime['standings']
                    ??
                    []
            )
            ->sortBy(
                fn($row) =>
                (int) (
                    $row['position_from']
                    ??
                    $row['position']
                    ??
                    PHP_INT_MAX
                )
            )
            ->values();

        $outcomes =
            [];

        $selected =
            [];

        /*
        |--------------------------------------------------------------------------
        | Outcomes producidos directamente por el Engine
        |--------------------------------------------------------------------------
        */

        $directOutcomes = array_merge(
            $runtime['outcomes'] ?? [],
            array_values($runtime['timed_outcomes'] ?? [])
        );

        foreach (
            $directOutcomes
            as
            $engineOutcome
        ) {
            $exitId =
                $engineOutcome['exit_id']
                ??
                null;

            if (
                $exitId === null
                ||
                ! $phaseExits->contains(
                    fn($exit) => (int) $exit->id === (int) $exitId
                )
            ) {
                continue;
            }

            $ids =
                array_values(
                    array_unique(
                        $engineOutcome['participant_ids']
                            ??
                            []
                    )
                );

            if (
                $exitId === null
                ||
                $ids === []
            ) {
                continue;
            }

            $outcomes[$exitId] ??= [
                'exit_id' =>
                (int)
                $exitId,

                'exit_name' =>
                $engineOutcome['exit_name']
                    ??
                    $phaseExits
                    ->firstWhere(
                        'id',
                        (int)
                        $exitId
                    )
                    ?->name
                    ??
                    'Salida',

                'selector_type' =>
                $phaseExits
                    ->firstWhere('id', (int) $exitId)
                    ?->selector_type
                    ?? 'ENGINE_RULES',

                'participant_ids' =>
                [],
            ];

            foreach (
                $ids
                as
                $participantId
            ) {
                if (
                    ! in_array(
                        $participantId,
                        $participantIds,
                        true
                    )
                ) {
                    continue;
                }

                if (
                    isset($selected[$participantId])
                    &&
                    (int) $selected[$participantId]
                    !==
                    (int) $exitId
                ) {
                    $this->fail(
                        "El participante {$participantId} fue producido por más de una Phase Exit. Stable V1 no permite fan-out competitivo implícito."
                    );
                }

                if (
                    ! in_array(
                        $participantId,
                        $outcomes[$exitId]['participant_ids'],
                        true
                    )
                ) {
                    $outcomes[$exitId]['participant_ids'][] =
                        $participantId;
                }

                $selected[$participantId] =
                    (int) $exitId;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Phase Exits genéricos
        |--------------------------------------------------------------------------
        */

        foreach (
            $phaseExits
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->sortBy(
                    fn($exit) =>
                    sprintf(
                        '%01d-%010d-%010d-%010d',

                        /* Las de «lo que quede», al final */
                        in_array($exit->selector_type, self::CATCH_ALL, true)
                            ? 1
                            : 0,

                        $exit->priority,
                        $exit->sort_order,
                        $exit->id
                    )
                )
            as
            $exit
        ) {
            if (
                isset(
                    $outcomes[$exit->id]
                )
            ) {
                continue;
            }

            $available =
                array_values(
                    array_filter(
                        $participantIds,
                        fn($participantId) =>
                        ! isset(
                            $selected[$participantId]
                        )
                    )
                );

            /*
             * Un selector describe posiciones/resultados globales de la fase,
             * asi que se evalua sobre la fase entera: reinterpretar TOP_N
             * sobre "los mejores que quedan" produciria resultados falsos.
             *
             * Las de CATCH_ALL son la excepcion, y no por comodidad: no
             * describen un puesto, describen un resto. Ver la nota de la
             * constante.
             */
            $selectionUniverse =
                in_array($exit->selector_type, self::CATCH_ALL, true)
                ? $available
                : $participantIds;

            $selection =
                $this->select(
                    $exit->selector_type,
                    $exit->selector_from,
                    $exit->selector_to,
                    $exit->selector_round_size,
                    $runtime,
                    $standings,
                    $selectionUniverse
                );

            $selection =
                array_values(
                    array_unique(
                        array_filter(
                            $selection,
                            fn($participantId) =>
                            in_array(
                                $participantId,
                                $participantIds,
                                true
                            )
                        )
                    )
                );

            $overlap =
                array_values(
                    array_filter(
                        $selection,
                        fn($participantId) =>
                        isset($selected[$participantId])
                    )
                );

            if ($overlap !== []) {
                $this->fail(
                    'Las Phase Exits intentan consumir al mismo participante más de una vez. '
                    . 'Stable V1 no permite fan-out competitivo implícito.'
                );
            }

            foreach (
                $selection
                as
                $participantId
            ) {
                $selected[$participantId] =
                    (int) $exit->id;
            }

            $outcomes[$exit->id] = [
                'exit_id' =>
                (int)
                $exit->id,

                'exit_name' =>
                $exit->name,

                'selector_type' =>
                $exit->selector_type,

                'participant_ids' =>
                $selection,
            ];
        }

        $unassigned =
            array_values(
                array_filter(
                    $participantIds,
                    fn($participantId) =>
                    ! isset(
                        $selected[$participantId]
                    )
                )
            );

        return [
            'outcomes' =>
            array_values(
                $outcomes
            ),

            'selected_ids' =>
            array_values(
                array_keys(
                    $selected
                )
            ),

            'unassigned_ids' =>
            $unassigned,
        ];
    }

    private function select(
        string $selectorType,
        ?int $from,
        ?int $to,
        ?int $roundSize,
        array $runtime,
        Collection $standings,
        array $available
    ): array {
        $availableMap =
            array_fill_keys(
                $available,
                true
            );

        $ranked =
            $standings
            ->filter(
                fn($row) =>
                isset(
                    $availableMap[$row['participant_id']]
                )
            )
            ->values();

        return match ($selectorType) {
            'SURVIVORS' =>
            array_values(
                array_intersect(
                    $runtime['survivor_ids']
                        ??
                        [],
                    $available
                )
            ),

            'ELIMINATED' =>
            array_values(
                array_intersect(
                    $runtime['eliminated_ids']
                        ??
                        [],
                    $available
                )
            ),

            'ELIMINATED_IN_ROUND' =>
            $this->eliminatedInRoundIds(
                $runtime,
                $roundSize,
                $availableMap
            ),

            'WINNER' =>
            $this->rankPositionIds(
                $ranked,
                1,
                true
            ),

            'RUNNER_UP' =>
            $this->rankPositionIds(
                $ranked,
                2,
                true
            ),

            'TOP_N' =>
            $this->topNIds(
                $ranked,
                max(0, (int) $from)
            ),

            'BOTTOM_N' =>
            $this->bottomNIds(
                $ranked,
                max(0, (int) $from)
            ),

            'POSITION',
            'RANK_POSITION' =>
            $this->rankPositionIds(
                $ranked,
                (int) $from,
                true
            ),

            'RANK_RANGE' =>
            $this->rankRangeIds(
                $ranked,
                (int) $from,
                (int) $to
            ),

            'MATCH_WINNERS' =>
            $this->matchResultIds(
                $runtime,
                'winner_id',
                $availableMap
            ),

            'MATCH_LOSERS' =>
            $this->matchLoserIds(
                $runtime,
                $availableMap
            ),

            'ALL',
            'REMAINING' =>
            $available,

            /*
             * ENGINE_RULES ya fue procesado utilizando runtime.outcomes.
             */
            'ENGINE_RULES' =>
            [],

            default =>
            [],
        };
    }

    private function eliminatedInRoundIds(
        array $runtime,
        ?int $roundSize,
        array $availableMap
    ): array {
        if ($roundSize === null || $roundSize <= 1) {
            return [];
        }

        $eliminated = [];

        foreach ($runtime['eliminations'] ?? [] as $event) {
            $eventRoundSize =
                (int) (
                    $event['round_participants']
                    ??
                    $this->roundParticipantsForEvent(
                        $runtime,
                        $event
                    )
                );

            if ($eventRoundSize !== $roundSize) {
                continue;
            }

            $participantId =
                $event['participant_id']
                ??
                null;

            if (
                $participantId !== null
                &&
                isset($availableMap[$participantId])
            ) {
                $eliminated[] =
                    $participantId;
            }
        }

        if ($eliminated !== []) {
            return array_values(array_unique($eliminated));
        }

        foreach ($runtime['rounds'] ?? [] as $round) {
            if ((int) ($round['participants_in_round'] ?? 0) !== $roundSize) {
                continue;
            }

            foreach ($round['matches'] ?? [] as $match) {
                foreach (
                    array_values(array_unique([
                        ...($match['eliminated_ids'] ?? []),
                        ...(($match['loser_id'] ?? null) !== null
                            ? [$match['loser_id']]
                            : []),
                    ]))
                    as $participantId
                ) {
                    if (isset($availableMap[$participantId])) {
                        $eliminated[] = $participantId;
                    }
                }
            }
        }

        return array_values(array_unique($eliminated));
    }


    private function topNIds(
        Collection $ranked,
        int $quantity
    ): array {
        if ($quantity <= 0) {
            return [];
        }

        foreach ($ranked as $row) {
            [$from, $to] =
                $this->placementRange(
                    $row
                );

            if ($from <= $quantity && $to > $quantity) {
                $this->fail(
                    "TOP_N={$quantity} corta una banda de empate {$from}–{$to}. "
                    . 'Define una salida que incluya la banda completa o resuelve esa posición competitivamente.'
                );
            }
        }

        return $ranked
            ->filter(function ($row) use ($quantity) {
                [, $to] =
                    $this->placementRange(
                        $row
                    );

                return $to <= $quantity;
            })
            ->pluck('participant_id')
            ->values()
            ->all();
    }

    private function bottomNIds(
        Collection $ranked,
        int $quantity
    ): array {
        if ($quantity <= 0 || $ranked->isEmpty()) {
            return [];
        }

        $maximum =
            $ranked
            ->map(
                fn($row) =>
                $this->placementRange($row)[1]
            )
            ->max();

        $fromPosition =
            max(
                1,
                (int) $maximum - $quantity + 1
            );

        foreach ($ranked as $row) {
            [$from, $to] =
                $this->placementRange(
                    $row
                );

            if ($from < $fromPosition && $to >= $fromPosition) {
                $this->fail(
                    "BOTTOM_N={$quantity} corta una banda de empate {$from}–{$to}."
                );
            }
        }

        return $ranked
            ->filter(function ($row) use ($fromPosition) {
                [$from] =
                    $this->placementRange(
                        $row
                    );

                return $from >= $fromPosition;
            })
            ->pluck('participant_id')
            ->values()
            ->all();
    }

    private function rankPositionIds(
        Collection $ranked,
        int $position,
        bool $requireUniqueBand
    ): array {
        if ($position < 1) {
            return [];
        }

        $rows =
            $ranked
            ->filter(function ($row) use ($position) {
                [$from, $to] =
                    $this->placementRange(
                        $row
                    );

                return $from <= $position && $to >= $position;
            })
            ->values();

        if ($rows->isEmpty()) {
            return [];
        }

        [$from, $to] =
            $this->placementRange(
                $rows->first()
            );

        if ($requireUniqueBand && $from !== $to) {
            $this->fail(
                "La posición {$position} pertenece a una banda empatada {$from}–{$to}; no existe una posición individual demostrada."
            );
        }

        return $rows
            ->pluck('participant_id')
            ->unique()
            ->values()
            ->all();
    }

    private function rankRangeIds(
        Collection $ranked,
        int $fromPosition,
        int $toPosition
    ): array {
        if (
            $fromPosition < 1
            ||
            $toPosition < $fromPosition
        ) {
            return [];
        }

        foreach ($ranked as $row) {
            [$from, $to] =
                $this->placementRange(
                    $row
                );

            $intersects =
                $from <= $toPosition
                &&
                $to >= $fromPosition;

            if (! $intersects) {
                continue;
            }

            if (
                $from < $fromPosition
                ||
                $to > $toPosition
            ) {
                $this->fail(
                    "El rango {$fromPosition}–{$toPosition} corta una banda de empate {$from}–{$to}."
                );
            }
        }

        return $ranked
            ->filter(function ($row) use ($fromPosition, $toPosition) {
                [$from, $to] =
                    $this->placementRange(
                        $row
                    );

                return
                    $from >= $fromPosition
                    &&
                    $to <= $toPosition;
            })
            ->pluck('participant_id')
            ->unique()
            ->values()
            ->all();
    }

    private function placementRange(
        array $row
    ): array {
        $from =
            max(
                1,
                (int) (
                    $row['position_from']
                    ??
                    $row['position']
                    ??
                    1
                )
            );

        $to =
            max(
                $from,
                (int) (
                    $row['position_to']
                    ??
                    $row['position']
                    ??
                    $from
                )
            );

        return [
            $from,
            $to,
        ];
    }

    private function roundParticipantsForEvent(
        array $runtime,
        array $event
    ): int {
        $roundNumber =
            (int) (
                $event['round_number']
                ??
                0
            );

        $round =
            collect(
                $runtime['rounds']
                ??
                []
            )
            ->first(
                fn($candidate) =>
                (int) ($candidate['number'] ?? 0)
                ===
                $roundNumber
            );

        return (int) (
            $round['participants_in_round']
            ??
            0
        );
    }

    private function matchResultIds(
        array $runtime,
        string $field,
        array $availableMap
    ): array {
        return collect(
            $runtime['rounds']
                ??
                []
        )
            ->flatMap(
                fn($round) =>
                $round['matches']
                    ??
                    []
            )
            ->pluck(
                $field
            )
            ->filter(
                fn($participantId) =>
                isset(
                    $availableMap[$participantId]
                )
            )
            ->unique()
            ->values()
            ->all();
    }

    private function matchLoserIds(
        array $runtime,
        array $availableMap
    ): array {
        $losers =
            [];

        foreach (
            $runtime['rounds']
                ??
                []
            as
            $round
        ) {
            foreach (
                $round['matches']
                    ??
                    []
                as
                $match
            ) {
                if (
                    ($match['status'] ?? null)
                    !==
                    'COMPLETED'
                ) {
                    continue;
                }

                foreach (
                    $match['eliminated_ids']
                    ??
                    []
                    as
                    $participantId
                ) {
                    if (isset($availableMap[$participantId])) {
                        $losers[] =
                            $participantId;
                    }
                }

                if (($match['eliminated_ids'] ?? []) !== []) {
                    continue;
                }

                $winnerId =
                    $match['winner_id']
                    ??
                    null;

                foreach (
                    [
                        $match['participant_a_id']
                            ??
                            null,

                        $match['participant_b_id']
                            ??
                            null,
                    ]
                    as
                    $participantId
                ) {
                    if (
                        ! $participantId
                        ||
                        $participantId
                        ===
                        $winnerId
                        ||
                        ! isset(
                            $availableMap[$participantId]
                        )
                    ) {
                        continue;
                    }

                    $losers[] =
                        $participantId;
                }
            }
        }

        return array_values(
            array_unique(
                $losers
            )
        );
    }

    private function fail(
        string $message
    ): never {
        throw ValidationException::withMessages([
            'outcomes' => [
                $message,
            ],
        ]);
    }
}
