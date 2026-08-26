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
                $settings,
                $phaseTemplate
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
        PhaseGroupStageSetting $settings,
        ?PhaseTemplate $phaseTemplate = null
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
            return $this->fillByGates(
                $groups,
                $participants,
                $phaseTemplate
            );
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

    /*
     * Reparto por orden de entrada: se van repartiendo como cartas.
     *
     * El primero al grupo A, el segundo al B, el tercero al C, y al llegar
     * al ultimo se vuelve a empezar. Con 12 participantes y 4 grupos:
     *
     *   A  1  5  9
     *   B  2  6 10
     *   C  3  7 11
     *   D  4  8 12
     *
     * Antes se llenaba un grupo entero antes de pasar al siguiente, asi que
     * los cuatro primeros en llegar acababan juntos en el grupo A. Eso hace
     * que el orden de llegada decida el grupo en bloque, que es justo lo
     * contrario de lo que se espera de un reparto: repartiendo, dos
     * inscripciones seguidas caen en grupos distintos.
     *
     * Un grupo que se llena deja de recibir y el reparto sigue con los
     * demas, para que los cupos desiguales no descuadren la vuelta.
     */
    /*
     * Reparto manual: lo dictan las puertas de entrada.
     *
     * Una puerta de fase de grupos dice QUE TRAMO de los que llegan va a QUE
     * GRUPO -del entrante 1 al 4, al Grupo A-. Este es el unico modo donde
     * esas puertas mandan de verdad; en los demas decide el algoritmo y las
     * puertas se guardan pero no se aplican.
     *
     * Antes este modo devolvia los grupos con huecos vacios etiquetados
     * "Asignacion manual 1, 2, 3...", asi que la estructura salia en blanco
     * por mucho que hubieras configurado las puertas: se podia dejar todo
     * apuntado y no ver nada.
     *
     * Vive en el repartidor y no en la Super Edicion a proposito: asi el
     * motor reparte igual que el editor dibuja, en vez de que uno ensene una
     * cosa y el otro juegue otra.
     */
    private function fillByGates(
        array $groups,
        int $participants,
        ?PhaseTemplate $phaseTemplate
    ): array {

        $pending = range(1, $participants);

        $byCode = [];

        foreach ($groups as $index => $group) {
            $byCode[$group['code']] = $index;
        }

        $gates = $phaseTemplate
            ? $phaseTemplate->inputGates()
                ->where('status', 'ACTIVE')
                ->get()
            : collect();

        /*
         * Primero las puertas, en su orden: cada una reclama su tramo de
         * llegada y lo lleva a su grupo, hasta donde quepa.
         */
        foreach ($gates as $gate) {

            $code = $gate->settings['target_group_code'] ?? null;
            $range = $gate->settings['entry_range'] ?? null;

            if ($code === null || ! isset($byCode[$code]) || ! is_array($range)) {
                continue;
            }

            $index = $byCode[$code];

            $from = (int) ($range['from'] ?? 0);
            $to = (int) ($range['to'] ?? $from);

            if ($from < 1) {
                continue;
            }

            for ($arrival = $from; $arrival <= $to; $arrival++) {

                if (! in_array($arrival, $pending, true)) {
                    continue;
                }

                if (
                    count($groups[$index]['members'])
                    >=
                    $groups[$index]['size']
                ) {
                    break;
                }

                $groups[$index]['members'][] = [
                    'seed' => $arrival,
                    'label' => 'Seed ' . $arrival,
                ];

                $pending = array_values(array_diff($pending, [$arrival]));
            }
        }

        /*
         * Lo que ninguna puerta reclamo se reparte por orden de llegada en
         * los huecos que queden. Sin esto, configurar una sola puerta
         * dejaria a todos los demas fuera de la estructura.
         */
        foreach ($groups as $index => $group) {

            while (
                count($groups[$index]['members']) < $group['size']
                && $pending !== []
            ) {
                $arrival = array_shift($pending);

                $groups[$index]['members'][] = [
                    'seed' => $arrival,
                    'label' => 'Seed ' . $arrival,
                ];
            }
        }

        return $groups;
    }

    private function fillSequential(
        array $groups,
        array $seeds
    ): array {
        $keys = array_keys($groups);

        $cursor = 0;

        foreach ($seeds as $seed) {

            $placed = false;

            /*
             * Se da una vuelta completa como maximo: si ninguno admite a
             * nadie, es que estan todos llenos y sobra gente.
             */
            for ($attempt = 0; $attempt < count($keys); $attempt++) {

                $key = $keys[$cursor % count($keys)];

                $cursor++;

                if (
                    count($groups[$key]['members'])
                    >=
                    $groups[$key]['size']
                ) {
                    continue;
                }

                $groups[$key]['members'][] = [
                    'seed' =>
                    $seed,

                    'label' =>
                    'Seed '
                        .
                        $seed,
                ];

                $placed = true;

                break;
            }

            if (! $placed) {
                break;
            }
        }

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
