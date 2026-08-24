<?php

namespace App\Services\Tournaments\GroupStage;

class GroupStageDefinitionService
{
    public function groupCountModes(): array
    {
        return [
            'FIXED_GROUP_COUNT' => [
                'label' =>
                'Cantidad fija de grupos',

                'description' =>
                'Define exactamente cuántos grupos tendrá la Fase.',
            ],

            'TARGET_GROUP_SIZE' => [
                'label' =>
                'Tamaño objetivo',

                'description' =>
                'OmniMerge calcula cuántos grupos necesita para aproximarse al tamaño elegido.',
            ],

            'CUSTOM_GROUPS' => [
                'label' =>
                'Grupos personalizados',

                'description' =>
                'Cada grupo puede tener nombre y capacidad propia.',
            ],
        ];
    }

    public function remainderPolicies(): array
    {
        return [
            'BALANCED' => [
                'label' =>
                'Equilibrada',

                'description' =>
                'Los grupos difieren como máximo en un participante.',
            ],

            'FIRST_GROUPS' => [
                'label' =>
                'Sobrantes a los primeros',

                'description' =>
                'Los primeros grupos reciben los participantes sobrantes.',
            ],

            'LAST_GROUPS' => [
                'label' =>
                'Sobrantes a los últimos',

                'description' =>
                'Los últimos grupos reciben los participantes sobrantes.',
            ],

            'MANUAL' => [
                'label' =>
                'Capacidades manuales',

                'description' =>
                'Cada grupo utiliza explícitamente su capacidad configurada.',
            ],
        ];
    }

    public function distributionModes(): array
    {
        return [
            'INPUT_ORDER' => [
                'label' =>
                'Orden de entrada',

                'description' =>
                'Llena los grupos utilizando el orden recibido.',
            ],

            'RANDOM' => [
                'label' =>
                'Aleatorio',

                'description' =>
                'Distribuye los participantes después de mezclar su orden.',
            ],

            'SNAKE_SEEDED' => [
                'label' =>
                'Snake Seeded',

                'description' =>
                'Distribuye los seeds alternando la dirección para equilibrar los grupos.',
            ],

            'POT_DRAW' => [
                'label' =>
                'Sorteo por Pots',

                'description' =>
                'Divide previamente los seeds en bombos y distribuye participantes de cada Pot entre los grupos.',
            ],

            'MANUAL' => [
                'label' =>
                'Manual',

                'description' =>
                'La asignación concreta se realizará posteriormente durante la ejecución.',
            ],
        ];
    }

    public function ruleTypes(): array
    {
        return [
            'EACH_GROUP_TOP_N' => [
                'label' =>
                'Los N primeros de cada grupo',

                'description' =>
                'La cantidad es POR GRUPO, no en total: con 4 grupos, N=2 '
                    . 'clasifica a 8. Si N llega al tamaño del grupo, pasan todos.',
            ],

            'EACH_GROUP_BOTTOM_N' => [
                'label' =>
                'Los N últimos de cada grupo',

                'description' =>
                'La cantidad es POR GRUPO, no en total: con 4 grupos, N=2 '
                    . 'toma a 8. Si N llega al tamaño del grupo, los toma a todos.',
            ],

            'EACH_GROUP_POSITION' => [
                'label' =>
                'Posición de cada grupo',

                'description' =>
                'Selecciona una posición concreta de cada grupo.',
            ],

            'EACH_GROUP_RANGE' => [
                'label' =>
                'Rango de cada grupo',

                'description' =>
                'Selecciona varias posiciones consecutivas de cada grupo.',
            ],

            'CROSS_GROUP_POSITION_TOP_N' => [
                'label' =>
                'Mejores N de una misma posición',

                'description' =>
                'La cantidad es el total, no por grupo. Ejemplo: los cuatro '
                    . 'mejores terceros entre todos los grupos.',
            ],

            'CROSS_GROUP_POSITION_BOTTOM_N' => [
                'label' =>
                'Peores N de una misma posición',

                'description' =>
                'La cantidad es el total, no por grupo. Ejemplo: los dos peores '
                    . 'ganadores de grupo.',
            ],

            'BEST_REMAINING' => [
                'label' =>
                'Mejores N restantes',

                'description' =>
                'Aquí la cantidad SÍ es el total: compara entre todos los grupos '
                    . 'a quienes no haya tomado una regla anterior y se queda con N.',
            ],

            'WORST_REMAINING' => [
                'label' =>
                'Peores N restantes',

                'description' =>
                'Aquí la cantidad SÍ es el total: compara entre todos los grupos '
                    . 'a quienes no haya tomado una regla anterior y toma a los N peores.',
            ],

            'SPECIFIC_GROUP_POSITION' => [
                'label' =>
                'Posición de un grupo específico',

                'description' =>
                'Selecciona una posición únicamente de un grupo concreto.',
            ],

            'SPECIFIC_GROUP_RANGE' => [
                'label' =>
                'Rango de un grupo específico',

                'description' =>
                'Selecciona varias posiciones de un grupo concreto.',
            ],

            'REMAINING' => [
                'label' =>
                'Todos los restantes',

                'description' =>
                'Selecciona todo participante no tomado por reglas anteriores.',
            ],
        ];
    }

    public function crossGroupCriteria(): array
    {
        return [
            'POINTS' => [
                'label' =>
                'Puntos',

                'description' =>
                'Compara los puntos obtenidos.',
            ],

            'WINS' => [
                'label' =>
                'Victorias',

                'description' =>
                'Compara la cantidad de series ganadas.',
            ],

            'SCORE_DIFFERENCE' => [
                'label' =>
                'Diferencia de score',

                'description' =>
                'Score a favor menos score en contra.',
            ],

            'SCORE_FOR' => [
                'label' =>
                'Score a favor',

                'description' =>
                'Compara el score total a favor.',
            ],

            'GAME_DIFFERENCE' => [
                'label' =>
                'Diferencia de partidas',

                'description' =>
                'Partidas internas ganadas menos partidas perdidas.',
            ],

            'GAME_WINS' => [
                'label' =>
                'Partidas ganadas',

                'description' =>
                'Compara partidas internas ganadas.',
            ],

            'SEED' => [
                'label' =>
                'Seed inicial',

                'description' =>
                'Utiliza el seed como criterio final.',
            ],
        ];
    }

    public function cutoffPolicies(): array
    {
        return [
            'USE_TIEBREAKERS' =>
            'Aplicar toda la cadena de desempate',

            'MANUAL_RESOLUTION' =>
            'Resolver manualmente',

            'RANDOM_RESOLUTION' =>
            'Resolver aleatoriamente',

            'INCLUDE_ALL_TIED' =>
            'Incluir todos los empatados',

            'REQUIRE_PLAYOFF' =>
            'Requerir una Fase de playoff',
        ];
    }

    /**
     * El orden de desempate con el que nace una fase de grupos.
     *
     * Puntos, diferencia, anotados. Es el orden de una liga de verdad y
     * el que ya usa Round Robin, asi que las dos clasificaciones del
     * mismo torneo se leen igual.
     *
     * Las VICTORIAS iban antes en segundo lugar, por delante de la
     * diferencia. No es lo que espera nadie que mire una tabla: con los
     * mismos puntos, dos empates y una victoria adelantaban a quien habia
     * ganado por mucho. Ademas la tabla se pintaba por diferencia, asi
     * que el orden que se veia podia contradecir a quien pasaba de fase.
     * Siguen contando, detras.
     *
     * Quien quiera otro orden lo cambia en su plantilla: esto es solo el
     * punto de partida.
     */
    public function defaultCrossGroupCriteria(): array
    {
        return [
            [
                'criterion' =>
                'POINTS',

                'normalization' =>
                'DEFAULT',
            ],

            [
                'criterion' =>
                'SCORE_DIFFERENCE',

                'normalization' =>
                'DEFAULT',
            ],

            [
                'criterion' =>
                'SCORE_FOR',

                'normalization' =>
                'DEFAULT',
            ],

            [
                'criterion' =>
                'WINS',

                'normalization' =>
                'DEFAULT',
            ],

            [
                'criterion' =>
                'SEED',

                'normalization' =>
                'RAW',
            ],
        ];
    }
}
