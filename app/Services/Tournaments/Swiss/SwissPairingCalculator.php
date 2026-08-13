<?php

namespace App\Services\Tournaments\Swiss;

use App\Models\PhaseSwissSetting;

class SwissPairingCalculator
{
    private const MAX_SEARCH_NODES = 50000;

    private int $searchNodes = 0;

    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    |
    | Cada participante puede traer:
    |
    | id
    | seed
    | label
    | wins
    | draws
    | losses
    | standing_score
    | pairing_score
    | opponents[]
    | bye_count
    | side_a_count
    | side_b_count
    | float_count
    | active
    |
    */

    public function generateRound(
        array $participants,
        PhaseSwissSetting $settings,
        int $roundNumber
    ): array {
        $pool =
            array_values(
                array_filter(
                    array_map(
                        fn($participant) =>
                        $this->normalizeParticipant(
                            $participant
                        ),
                        $participants
                    ),
                    fn($participant) =>
                    $participant['active']
                )
            );

        if (count($pool) < 2) {
            return [
                'valid' => false,

                'errors' => [
                    'No existen suficientes participantes activos para generar una ronda.',
                ],

                'warnings' => [],

                'pairings' => [],

                'bye' => null,

                'manual_bye_required' => false,
            ];
        }

        $warnings = [];

        /*
        |--------------------------------------------------------------------------
        | BYE
        |--------------------------------------------------------------------------
        */

        $bye = null;

        if (
            count($pool) % 2 !== 0
        ) {
            if (
                $settings->bye_policy
                ===
                'DISABLED'
            ) {
                return [
                    'valid' => false,

                    'errors' => [
                        'Existe una cantidad impar de participantes y el BYE está desactivado.',
                    ],

                    'warnings' => [],

                    'pairings' => [],

                    'bye' => null,

                    'manual_bye_required' => false,
                ];
            }

            if (
                $settings->bye_policy
                ===
                'MANUAL'
            ) {
                return [
                    'valid' => true,

                    'errors' => [],

                    'warnings' => [
                        'La política de BYE es manual. El Runtime deberá elegir quién descansa antes de generar los emparejamientos definitivos.',
                    ],

                    'pairings' => [],

                    'bye' => null,

                    'manual_bye_required' => true,
                ];
            }

            $bye =
                $this->selectBye(
                    $pool,
                    $settings,
                    $roundNumber
                );

            if ($bye === null) {
                return [
                    'valid' => false,

                    'errors' => [
                        'No existe un participante elegible para recibir BYE con la política actual.',
                    ],

                    'warnings' => [],

                    'pairings' => [],

                    'bye' => null,

                    'manual_bye_required' => false,
                ];
            }

            $pool =
                array_values(
                    array_filter(
                        $pool,
                        fn($participant) =>
                        $participant['id']
                            !==
                            $bye['id']
                    )
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Primera ronda
        |--------------------------------------------------------------------------
        */

        if ($roundNumber === 1) {
            $pairs =
                $this->firstRoundPairs(
                    $pool,
                    $settings
                );

            return [
                'valid' => true,
                'errors' => [],
                'warnings' => $warnings,

                'round_number' =>
                $roundNumber,

                'pairings' =>
                $this->formatPairs(
                    $pairs,
                    $settings
                ),

                'bye' =>
                $bye,

                'manual_bye_required' =>
                false,

                'search_nodes' =>
                0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Rondas dinámicas
        |--------------------------------------------------------------------------
        */

        $pool =
            $this->sortForPairing(
                $pool,
                $settings,
                $roundNumber
            );

        $this->searchNodes = 0;

        $pairs =
            $this->searchPairings(
                $pool,
                $settings,
                $roundNumber
            );

        if ($pairs === null) {
            return [
                'valid' => false,

                'errors' => [
                    'No se encontró un pairing compatible con las restricciones configuradas.',
                ],

                'warnings' => [
                    'Prueba con AVOID_IF_POSSIBLE si STRICT_NO_REMATCH vuelve imposible la ronda.',
                ],

                'round_number' =>
                $roundNumber,

                'pairings' => [],

                'bye' =>
                $bye,

                'manual_bye_required' =>
                false,

                'search_nodes' =>
                $this->searchNodes,
            ];
        }

        if (
            $this->containsRematch(
                $pairs
            )
        ) {
            $warnings[] =
                'Fue necesario utilizar al menos un rematch para completar la ronda.';
        }

        return [
            'valid' => true,
            'errors' => [],
            'warnings' => $warnings,

            'round_number' =>
            $roundNumber,

            'pairings' =>
            $this->formatPairs(
                $pairs,
                $settings
            ),

            'bye' =>
            $bye,

            'manual_bye_required' =>
            false,

            'search_nodes' =>
            $this->searchNodes,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | First Round
    |--------------------------------------------------------------------------
    */

    private function firstRoundPairs(
        array $participants,
        PhaseSwissSetting $settings
    ): array {
        $participants =
            $this->sortBySeed(
                $participants
            );

        if (
            $settings->first_round_mode
            ===
            'RANDOM'
        ) {
            usort(
                $participants,
                function (
                    array $left,
                    array $right
                ) use (
                    $settings
                ) {
                    return $this->deterministicHash(
                        $left['seed'],
                        $settings->phase_template_id
                    )
                        <=>
                        $this->deterministicHash(
                            $right['seed'],
                            $settings->phase_template_id
                        );
                }
            );

            return $this->adjacentPairs(
                $participants
            );
        }

        if (
            $settings->first_round_mode
            ===
            'INPUT_ORDER'
        ) {
            usort(
                $participants,
                fn(
                    array $left,
                    array $right
                ) =>
                $left['input_order']
                    <=>
                    $right['input_order']
            );

            return $this->adjacentPairs(
                $participants
            );
        }

        if (
            $settings->first_round_mode
            ===
            'TOP_VS_BOTTOM'
        ) {
            $pairs = [];

            while (
                count($participants) >= 2
            ) {
                $top =
                    array_shift(
                        $participants
                    );

                $bottom =
                    array_pop(
                        $participants
                    );

                $pairs[] = [
                    $top,
                    $bottom,
                ];
            }

            return $pairs;
        }

        /*
        |--------------------------------------------------------------------------
        | SEEDED_HALVES
        |--------------------------------------------------------------------------
        */

        $half =
            intdiv(
                count($participants),
                2
            );

        $top =
            array_slice(
                $participants,
                0,
                $half
            );

        $bottom =
            array_slice(
                $participants,
                $half
            );

        $pairs = [];

        for (
            $index = 0;
            $index < $half;
            $index++
        ) {
            $pairs[] = [
                $top[$index],
                $bottom[$index],
            ];
        }

        return $pairs;
    }

    /*
    |--------------------------------------------------------------------------
    | Dynamic pairing search
    |--------------------------------------------------------------------------
    */

    private function searchPairings(
        array $pool,
        PhaseSwissSetting $settings,
        int $roundNumber
    ): ?array {
        if ($pool === []) {
            return [];
        }

        $this->searchNodes++;

        if (
            $this->searchNodes
            >
            self::MAX_SEARCH_NODES
        ) {
            return null;
        }

        $first =
            array_shift(
                $pool
            );

        $candidates = [];

        foreach (
            $pool
            as
            $index => $candidate
        ) {
            if (
                ! $this->isHardValidPair(
                    $first,
                    $candidate,
                    $settings
                )
            ) {
                continue;
            }

            $candidates[] = [
                'index' =>
                $index,

                'participant' =>
                $candidate,

                'cost' =>
                $this->pairCost(
                    $first,
                    $candidate,
                    $settings,
                    $roundNumber
                ),
            ];
        }

        usort(
            $candidates,
            fn(
                array $left,
                array $right
            ) =>
            $left['cost']
                <=>
                $right['cost']
        );

        foreach (
            $candidates
            as
            $candidate
        ) {
            $remaining =
                $pool;

            unset(
                $remaining[$candidate['index']]
            );

            $remaining =
                array_values(
                    $remaining
                );

            $tail =
                $this->searchPairings(
                    $remaining,
                    $settings,
                    $roundNumber
                );

            if ($tail !== null) {
                return array_merge(
                    [
                        [
                            $first,
                            $candidate['participant'],
                        ],
                    ],
                    $tail
                );
            }
        }

        return null;
    }

    private function isHardValidPair(
        array $left,
        array $right,
        PhaseSwissSetting $settings
    ): bool {
        if (
            $left['id']
            ===
            $right['id']
        ) {
            return false;
        }

        $rematch =
            $this->havePlayed(
                $left,
                $right
            );

        if (
            $rematch
            &&
            $settings->rematch_policy
            ===
            'STRICT_NO_REMATCH'
        ) {
            return false;
        }

        return true;
    }

    private function pairCost(
        array $left,
        array $right,
        PhaseSwissSetting $settings,
        int $roundNumber
    ): float {
        $leftScore =
            $this->pairingValue(
                $left,
                $settings,
                $roundNumber
            );

        $rightScore =
            $this->pairingValue(
                $right,
                $settings,
                $roundNumber
            );

        $cost =
            abs(
                $leftScore
                    -
                    $rightScore
            )
            *
            1000;

        /*
        |--------------------------------------------------------------------------
        | Soft rematch
        |--------------------------------------------------------------------------
        */

        if (
            $this->havePlayed(
                $left,
                $right
            )
            &&
            $settings->rematch_policy
            ===
            'AVOID_IF_POSSIBLE'
        ) {
            $cost += 100000;
        }

        /*
        |--------------------------------------------------------------------------
        | Pairing algorithm
        |--------------------------------------------------------------------------
        */

        if (
            $settings->pairing_algorithm
            ===
            'ADJACENT_STANDINGS'
        ) {
            $cost +=
                abs(
                    $left['standing_position']
                        -
                        $right['standing_position']
                )
                *
                10;
        }

        if (
            $settings->pairing_algorithm
            ===
            'RANDOM_WITHIN_SCORE'
        ) {
            $cost +=
                $this->deterministicHash(
                    $left['seed']
                        +
                        $right['seed'],
                    $roundNumber
                )
                %
                100;
        }

        /*
        |--------------------------------------------------------------------------
        | Floater preferences
        |--------------------------------------------------------------------------
        */

        if (
            $settings->floater_policy
            ===
            'AVOID_REPEAT_FLOAT'
        ) {
            $cost +=
                (
                    $left['float_count']
                    +
                    $right['float_count']
                )
                *
                20;
        }

        if (
            $settings->floater_policy
            ===
            'LOWEST_SEED_FIRST'
        ) {
            $cost +=
                (
                    1000
                    -
                    min(
                        999,
                        max(
                            $left['seed'],
                            $right['seed']
                        )
                    )
                )
                /
                100;
        }

        if (
            $settings->floater_policy
            ===
            'HIGHEST_SEED_FIRST'
        ) {
            $cost +=
                min(
                    $left['seed'],
                    $right['seed']
                )
                /
                100;
        }

        /*
        |--------------------------------------------------------------------------
        | Side quality
        |--------------------------------------------------------------------------
        */

        if (
            $settings->side_balance_policy
            ===
            'PREFER_BALANCE'
        ) {
            $cost +=
                $this->bestOrientationCost(
                    $left,
                    $right
                );
        }

        return $cost;
    }

    /*
    |--------------------------------------------------------------------------
    | Pairing basis
    |--------------------------------------------------------------------------
    */

    private function pairingValue(
        array $participant,
        PhaseSwissSetting $settings,
        int $roundNumber
    ): float {
        $value =
            match ($settings->pairing_basis) {
                'WIN_LOSS_RECORD' => (
                    $participant['wins']
                    *
                    100
                )
                    +
                    (
                        $participant['draws']
                        *
                        25
                    )
                    -
                    (
                        $participant['losses']
                        *
                        100
                    ),

                'PAIRING_SCORE' =>
                $participant['pairing_score'],

                default =>
                $participant['standing_score'],
            };

        /*
        |--------------------------------------------------------------------------
        | Generic acceleration
        |--------------------------------------------------------------------------
        */

        if (
            $settings->acceleration_mode
            ===
            'GENERIC_VIRTUAL_POINTS'
            &&
            $settings->acceleration_rounds
            &&
            $roundNumber
            <=
            $settings->acceleration_rounds
            &&
            $settings->acceleration_seed_count
            &&
            $participant['seed']
            <=
            $settings->acceleration_seed_count
        ) {
            $value +=
                (float)
                $settings
                    ->acceleration_virtual_points;
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | BYE
    |--------------------------------------------------------------------------
    */

    private function selectBye(
        array $participants,
        PhaseSwissSetting $settings,
        int $roundNumber
    ): ?array {
        $eligible =
            array_values(
                array_filter(
                    $participants,
                    fn($participant) =>
                    $participant['bye_count']
                        <
                        $settings
                        ->max_byes_per_participant
                )
            );

        if ($eligible === []) {
            return null;
        }

        if (
            $settings->bye_policy
            ===
            'LOWEST_SEED_WITHOUT_BYE'
        ) {
            usort(
                $eligible,
                fn(
                    array $left,
                    array $right
                ) =>
                $right['seed']
                    <=>
                    $left['seed']
            );

            return $eligible[0];
        }

        if (
            $settings->bye_policy
            ===
            'RANDOM_ELIGIBLE'
        ) {
            usort(
                $eligible,
                fn(
                    array $left,
                    array $right
                ) =>
                $this->deterministicHash(
                    $left['seed'],
                    $roundNumber
                )
                    <=>
                    $this->deterministicHash(
                        $right['seed'],
                        $roundNumber
                    )
            );

            return $eligible[0];
        }

        /*
        |--------------------------------------------------------------------------
        | LOWEST_STANDING_WITHOUT_BYE
        |--------------------------------------------------------------------------
        */

        usort(
            $eligible,
            function (
                array $left,
                array $right
            ) {
                $scoreComparison =
                    $left['standing_score']
                    <=>
                    $right['standing_score'];

                if (
                    $scoreComparison !== 0
                ) {
                    return $scoreComparison;
                }

                /*
                 * Seed numéricamente mayor = seed inferior.
                 */
                return
                    $right['seed']
                    <=>
                    $left['seed'];
            }
        );

        return $eligible[0];
    }

    /*
    |--------------------------------------------------------------------------
    | Formatting / side balance
    |--------------------------------------------------------------------------
    */

    private function formatPairs(
        array $pairs,
        PhaseSwissSetting $settings
    ): array {
        $formatted = [];

        foreach (
            $pairs
            as
            $index => [$left, $right]
        ) {
            [$sideA, $sideB] =
                $this->orientPair(
                    $left,
                    $right,
                    $settings
                );

            $formatted[] = [
                'table' =>
                $index + 1,

                'participant_a' =>
                $sideA,

                'participant_b' =>
                $sideB,

                'is_rematch' =>
                $this->havePlayed(
                    $left,
                    $right
                ),

                'score_gap' =>
                abs(
                    $left['standing_score']
                        -
                        $right['standing_score']
                ),
            ];
        }

        return $formatted;
    }

    private function orientPair(
        array $left,
        array $right,
        PhaseSwissSetting $settings
    ): array {
        if (
            $settings->side_balance_policy
            ===
            'NONE'
        ) {
            return
                $left['seed']
                <=
                $right['seed']
                ? [$left, $right]
                : [$right, $left];
        }

        $normalCost =
            abs(
                (
                    $left['side_a_count']
                    +
                    1
                )
                    -
                    $left['side_b_count']
            )
            +
            abs(
                $right['side_a_count']
                    -
                    (
                        $right['side_b_count']
                        +
                        1
                    )
            );

        $reverseCost =
            abs(
                $left['side_a_count']
                    -
                    (
                        $left['side_b_count']
                        +
                        1
                    )
            )
            +
            abs(
                (
                    $right['side_a_count']
                    +
                    1
                )
                    -
                    $right['side_b_count']
            );

        return $normalCost
            <=
            $reverseCost
            ? [$left, $right]
            : [$right, $left];
    }

    private function bestOrientationCost(
        array $left,
        array $right
    ): int {
        $normal =
            abs(
                (
                    $left['side_a_count']
                    +
                    1
                )
                    -
                    $left['side_b_count']
            )
            +
            abs(
                $right['side_a_count']
                    -
                    (
                        $right['side_b_count']
                        +
                        1
                    )
            );

        $reverse =
            abs(
                $left['side_a_count']
                    -
                    (
                        $left['side_b_count']
                        +
                        1
                    )
            )
            +
            abs(
                (
                    $right['side_a_count']
                    +
                    1
                )
                    -
                    $right['side_b_count']
            );

        return min(
            $normal,
            $reverse
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function sortForPairing(
        array $participants,
        PhaseSwissSetting $settings,
        int $roundNumber
    ): array {
        usort(
            $participants,
            function (
                array $left,
                array $right
            ) use (
                $settings,
                $roundNumber
            ) {
                $leftValue =
                    $this->pairingValue(
                        $left,
                        $settings,
                        $roundNumber
                    );

                $rightValue =
                    $this->pairingValue(
                        $right,
                        $settings,
                        $roundNumber
                    );

                if (
                    $leftValue
                    !==
                    $rightValue
                ) {
                    return
                        $rightValue
                        <=>
                        $leftValue;
                }

                return
                    $left['seed']
                    <=>
                    $right['seed'];
            }
        );

        foreach (
            $participants
            as
            $index => &$participant
        ) {
            $participant['standing_position'] =
                $index + 1;
        }

        unset($participant);

        return $participants;
    }

    private function sortBySeed(
        array $participants
    ): array {
        usort(
            $participants,
            fn(
                array $left,
                array $right
            ) =>
            $left['seed']
                <=>
                $right['seed']
        );

        return $participants;
    }

    private function adjacentPairs(
        array $participants
    ): array {
        $pairs = [];

        for (
            $index = 0;
            $index < count($participants);
            $index += 2
        ) {
            $pairs[] = [
                $participants[$index],
                $participants[$index + 1],
            ];
        }

        return $pairs;
    }

    private function havePlayed(
        array $left,
        array $right
    ): bool {
        return in_array(
            $right['id'],
            $left['opponents'],
            true
        )
            ||
            in_array(
                $left['id'],
                $right['opponents'],
                true
            );
    }

    private function containsRematch(
        array $pairs
    ): bool {
        foreach (
            $pairs
            as
            [$left, $right]
        ) {
            if (
                $this->havePlayed(
                    $left,
                    $right
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function normalizeParticipant(
        array $participant
    ): array {
        static $inputOrder = 0;

        $inputOrder++;

        return [
            'id' =>
            (int)
            (
                $participant['id']
                ??
                $inputOrder
            ),

            'seed' =>
            (int)
            (
                $participant['seed']
                ??
                $inputOrder
            ),

            'input_order' =>
            (int)
            (
                $participant['input_order']
                ??
                $inputOrder
            ),

            'label' =>
            (string)
            (
                $participant['label']
                ??
                (
                    'Seed '
                    .
                    (
                        $participant['seed']
                        ??
                        $inputOrder
                    )
                )
            ),

            'wins' =>
            (int)
            (
                $participant['wins']
                ??
                0
            ),

            'draws' =>
            (int)
            (
                $participant['draws']
                ??
                0
            ),

            'losses' =>
            (int)
            (
                $participant['losses']
                ??
                0
            ),

            'standing_score' =>
            (float)
            (
                $participant['standing_score']
                ??
                0
            ),

            'pairing_score' =>
            (float)
            (
                $participant['pairing_score']
                ??
                $participant['standing_score']
                ??
                0
            ),

            'opponents' =>
            array_values(
                array_map(
                    'intval',
                    $participant['opponents']
                        ??
                        []
                )
            ),

            'bye_count' =>
            (int)
            (
                $participant['bye_count']
                ??
                0
            ),

            'side_a_count' =>
            (int)
            (
                $participant['side_a_count']
                ??
                0
            ),

            'side_b_count' =>
            (int)
            (
                $participant['side_b_count']
                ??
                0
            ),

            'float_count' =>
            (int)
            (
                $participant['float_count']
                ??
                0
            ),

            'standing_position' =>
            (int)
            (
                $participant['standing_position']
                ??
                $inputOrder
            ),

            'active' =>
            (bool)
            (
                $participant['active']
                ??
                true
            ),
        ];
    }

    private function deterministicHash(
        int $value,
        ?int $salt = null
    ): int {
        return (int)
        sprintf(
            '%u',
            crc32(
                (
                    $salt
                    ??
                    0
                )
                    .
                    ':'
                    .
                    $value
            )
        );
    }
}
