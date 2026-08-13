<?php

namespace App\Services\Tournaments\GroupStage;

use App\Models\PhaseGroupStageSetting;
use App\Models\PhaseTemplate;
use Illuminate\Support\Collection;

class GroupStageAllocator
{
    public function __construct(
        private readonly
        GroupStageValidator $validator
    ) {}

    public function allocate(
        PhaseTemplate $phaseTemplate,
        PhaseGroupStageSetting $settings,
        Collection $groupDefinitions,
        int $participants
    ): array {
        $errors =
            $this
            ->validator
            ->validateBase(
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

        $structure =
            $this->resolveStructure(
                $settings,
                $groupDefinitions,
                $participants
            );

        if (! $structure['valid']) {
            return $structure;
        }

        $sizeErrors =
            $this
            ->validator
            ->validateGroupSizes(
                $settings,
                $structure['sizes']
            );

        if ($sizeErrors !== []) {
            return [
                'valid' =>
                false,

                'errors' =>
                $sizeErrors,

                'participants' =>
                $participants,
            ];
        }

        $groups =
            $this->buildGroups(
                $structure,
                $participants,
                $settings
            );

        return [
            'valid' =>
            true,

            'errors' =>
            [],

            'participants' =>
            $participants,

            'group_count' =>
            count(
                $groups
            ),

            'sizes' =>
            array_column(
                $groups,
                'size'
            ),

            'min_size' =>
            min(
                array_column(
                    $groups,
                    'size'
                )
            ),

            'max_size' =>
            max(
                array_column(
                    $groups,
                    'size'
                )
            ),

            'manual_assignment_required' =>
            $settings->distribution_mode
                ===
                'MANUAL',

            'groups' =>
            $groups,
        ];
    }

    private function resolveStructure(
        PhaseGroupStageSetting $settings,
        Collection $definitions,
        int $participants
    ): array {
        $active =
            $definitions
            ->where(
                'is_active',
                true
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | CUSTOM_GROUPS
        |--------------------------------------------------------------------------
        */

        if (
            $settings->group_count_mode
            ===
            'CUSTOM_GROUPS'
        ) {
            if ($active->count() < 2) {
                return $this->invalid(
                    $participants,
                    'Configura al menos 2 grupos personalizados.'
                );
            }

            $sizes = [];

            foreach (
                $active
                as
                $group
            ) {
                if (
                    ! $group->capacity
                    ||
                    $group->capacity < 1
                ) {
                    return $this->invalid(
                        $participants,
                        'Todos los grupos personalizados deben tener una capacidad.'
                    );
                }

                $sizes[] =
                    (int)
                    $group->capacity;
            }

            if (
                array_sum($sizes)
                !==
                $participants
            ) {
                return $this->invalid(
                    $participants,
                    'Las capacidades de los grupos personalizados suman '
                        .
                        array_sum($sizes)
                        .
                        ', pero el preview utiliza '
                        .
                        $participants
                        .
                        ' participantes.'
                );
            }

            return [
                'valid' =>
                true,

                'sizes' =>
                $sizes,

                'definitions' =>
                $active,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | TARGET_GROUP_SIZE
        |--------------------------------------------------------------------------
        */

        if (
            $settings->group_count_mode
            ===
            'TARGET_GROUP_SIZE'
        ) {
            $target =
                max(
                    2,
                    (int)
                    $settings->target_group_size
                );

            $groupCount =
                (int)
                ceil(
                    $participants
                        /
                        $target
                );

            $groupCount =
                max(
                    2,
                    $groupCount
                );

            $sizes =
                $this->balancedSizes(
                    $participants,
                    $groupCount,
                    $settings->remainder_policy
                );

            return [
                'valid' =>
                true,

                'sizes' =>
                $sizes,

                'definitions' =>
                collect(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | FIXED_GROUP_COUNT
        |--------------------------------------------------------------------------
        */

        $groupCount =
            max(
                2,
                (int)
                $settings->group_count
            );

        if (
            $groupCount
            >
            $participants
        ) {
            return $this->invalid(
                $participants,
                'No puedes crear más grupos que participantes.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Capacidades manuales
        |--------------------------------------------------------------------------
        */

        if (
            $settings->remainder_policy
            ===
            'MANUAL'
        ) {
            $fixedDefinitions =
                $active
                ->take(
                    $groupCount
                )
                ->values();

            if (
                $fixedDefinitions->count()
                !==
                $groupCount
            ) {
                return $this->invalid(
                    $participants,
                    'Faltan definiciones de grupo para utilizar capacidades manuales.'
                );
            }

            $sizes = [];

            foreach (
                $fixedDefinitions
                as
                $group
            ) {
                if (! $group->capacity) {
                    return $this->invalid(
                        $participants,
                        'Cada grupo necesita una capacidad cuando la política de sobrantes es Manual.'
                    );
                }

                $sizes[] =
                    (int)
                    $group->capacity;
            }

            if (
                array_sum($sizes)
                !==
                $participants
            ) {
                return $this->invalid(
                    $participants,
                    'Las capacidades manuales suman '
                        .
                        array_sum($sizes)
                        .
                        ', pero deben sumar '
                        .
                        $participants
                        .
                        '.'
                );
            }

            return [
                'valid' =>
                true,

                'sizes' =>
                $sizes,

                'definitions' =>
                $fixedDefinitions,
            ];
        }

        return [
            'valid' =>
            true,

            'sizes' =>
            $this->balancedSizes(
                $participants,
                $groupCount,
                $settings->remainder_policy
            ),

            'definitions' =>
            $active
                ->take(
                    $groupCount
                )
                ->values(),
        ];
    }

    private function balancedSizes(
        int $participants,
        int $groupCount,
        string $policy
    ): array {
        $base =
            intdiv(
                $participants,
                $groupCount
            );

        $remainder =
            $participants
            %
            $groupCount;

        $sizes =
            array_fill(
                0,
                $groupCount,
                $base
            );

        if ($remainder === 0) {
            return $sizes;
        }

        if (
            $policy
            ===
            'LAST_GROUPS'
        ) {
            for (
                $index =
                    $groupCount
                    -
                    $remainder;
                $index < $groupCount;
                $index++
            ) {
                $sizes[$index]++;
            }

            return $sizes;
        }

        /*
         * BALANCED y FIRST_GROUPS:
         * los tamaños siempre difieren como máximo en 1.
         */

        for (
            $index = 0;
            $index < $remainder;
            $index++
        ) {
            $sizes[$index]++;
        }

        return $sizes;
    }

    private function buildGroups(
        array $structure,
        int $participants,
        PhaseGroupStageSetting $settings
    ): array {
        $groups = [];

        foreach (
            $structure['sizes']
            as
            $index => $size
        ) {
            $definition =
                $structure['definitions']
                ->get(
                    $index
                );

            $groups[] = [
                'index' =>
                $index + 1,

                'definition_id' =>
                $definition?->id,

                'code' =>
                $definition?->code
                    ??
                    sprintf(
                        'VGR%03d',
                        $index + 1
                    ),

                'name' =>
                $definition?->name
                    ??
                    'Grupo '
                    .
                    $this->alphabeticLabel(
                        $index + 1
                    ),

                'size' =>
                $size,

                'members' =>
                [],
            ];
        }

        if (
            $settings->distribution_mode
            ===
            'MANUAL'
        ) {
            foreach (
                $groups
                as
                &$group
            ) {
                for (
                    $slot = 1;
                    $slot <= $group['size'];
                    $slot++
                ) {
                    $group['members'][] = [
                        'seed' =>
                        null,

                        'label' =>
                        'Asignación manual '
                            .
                            $slot,
                    ];
                }
            }

            unset($group);

            return $groups;
        }

        $seeds =
            range(
                1,
                $participants
            );

        if (
            $settings->distribution_mode
            ===
            'RANDOM'
        ) {
            usort(
                $seeds,
                function (
                    int $left,
                    int $right
                ) use (
                    $settings,
                    $participants
                ) {
                    $salt =
                        (string)
                        (
                            $settings->phase_template_id
                            ?? 0
                        );

                    $leftHash =
                        crc32(
                            $salt
                                .
                                ':'
                                .
                                $participants
                                .
                                ':'
                                .
                                $left
                        );

                    $rightHash =
                        crc32(
                            $salt
                                .
                                ':'
                                .
                                $participants
                                .
                                ':'
                                .
                                $right
                        );

                    return $leftHash
                        <=>
                        $rightHash;
                }
            );

            return $this->fillSequential(
                $groups,
                $seeds
            );
        }

        if (
            $settings->distribution_mode
            ===
            'INPUT_ORDER'
        ) {
            return $this->fillSequential(
                $groups,
                $seeds
            );
        }

        if (
            $settings->distribution_mode
            ===
            'POT_DRAW'
        ) {
            return $this->fillByPots(
                $groups,
                $seeds,
                $settings
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SNAKE_SEEDED
        |--------------------------------------------------------------------------
        */

        return $this->fillSnake(
            $groups,
            $seeds
        );
    }

    private function fillSequential(
        array $groups,
        array $seeds
    ): array {
        $seedIndex = 0;

        foreach (
            $groups
            as
            &$group
        ) {
            while (
                count(
                    $group['members']
                )
                <
                $group['size']
                &&
                isset(
                    $seeds[$seedIndex]
                )
            ) {
                $seed =
                    $seeds[$seedIndex];

                $group['members'][] = [
                    'seed' =>
                    $seed,

                    'label' =>
                    'Seed '
                        .
                        $seed,
                ];

                $seedIndex++;
            }
        }

        unset($group);

        return $groups;
    }

    private function fillSnake(
        array $groups,
        array $seeds
    ): array {
        $seedIndex = 0;

        $direction =
            1;

        while (
            $seedIndex
            <
            count(
                $seeds
            )
        ) {
            $indexes =
                $direction === 1
                ? array_keys(
                    $groups
                )
                : array_reverse(
                    array_keys(
                        $groups
                    )
                );

            $placedThisPass =
                0;

            foreach (
                $indexes
                as
                $groupIndex
            ) {
                if (
                    $seedIndex
                    >=
                    count(
                        $seeds
                    )
                ) {
                    break;
                }

                if (
                    count(
                        $groups[$groupIndex]['members']
                    )
                    >=
                    $groups[$groupIndex]['size']
                ) {
                    continue;
                }

                $seed =
                    $seeds[$seedIndex];

                $groups[$groupIndex]['members'][] = [
                    'seed' =>
                    $seed,

                    'label' =>
                    'Seed '
                        .
                        $seed,
                ];

                $seedIndex++;
                $placedThisPass++;
            }

            if ($placedThisPass === 0) {
                break;
            }

            $direction *= -1;
        }

        return $groups;
    }

    private function fillByPots(
        array $groups,
        array $seeds,
        PhaseGroupStageSetting $settings
    ): array {
        $potCount =
            $settings->pot_count
            ?: max(
                array_column(
                    $groups,
                    'size'
                )
            );

        $potCount =
            max(
                1,
                min(
                    $potCount,
                    count($seeds)
                )
            );

        $potSize =
            (int)
            ceil(
                count($seeds)
                    /
                    $potCount
            );

        $pots =
            array_chunk(
                $seeds,
                $potSize
            );

        $direction = 1;

        foreach (
            $pots
            as
            $pot
        ) {
            $indexes =
                $direction === 1
                ? array_keys(
                    $groups
                )
                : array_reverse(
                    array_keys(
                        $groups
                    )
                );

            $groupPointer = 0;

            foreach (
                $pot
                as
                $seed
            ) {
                $attempts = 0;

                while (
                    $attempts
                    <
                    count(
                        $indexes
                    )
                ) {
                    $groupIndex =
                        $indexes[$groupPointer
                            %
                            count(
                                $indexes
                            )];

                    $groupPointer++;
                    $attempts++;

                    if (
                        count(
                            $groups[$groupIndex]['members']
                        )
                        >=
                        $groups[$groupIndex]['size']
                    ) {
                        continue;
                    }

                    $groups[$groupIndex]['members'][] = [
                        'seed' =>
                        $seed,

                        'label' =>
                        'Seed '
                            .
                            $seed,
                    ];

                    break;
                }
            }

            $direction *= -1;
        }

        return $groups;
    }

    private function alphabeticLabel(
        int $number
    ): string {
        $label = '';

        while ($number > 0) {
            $number--;

            $label =
                chr(
                    65
                        +
                        ($number % 26)
                )
                .
                $label;

            $number =
                intdiv(
                    $number,
                    26
                );
        }

        return $label;
    }

    private function invalid(
        int $participants,
        string $message
    ): array {
        return [
            'valid' =>
            false,

            'errors' => [
                $message,
            ],

            'participants' =>
            $participants,
        ];
    }
}
