<?php

namespace App\Services\Tournaments\Swiss;

class SwissDefinitionService
{
    public function completionModes(): array
    {
        return [
            'FIXED_ROUNDS' => [
                'label' =>
                'Rondas fijas',

                'description' =>
                'Todos los participantes activos disputan una cantidad previamente definida de rondas y después se utiliza la clasificación final.',
            ],

            'RECORD_THRESHOLDS' => [
                'label' =>
                'Umbrales de récord',

                'description' =>
                'Un participante abandona la Fase al alcanzar el número configurado de victorias o derrotas.',
            ],
        ];
    }

    public function pairingAlgorithms(): array
    {
        return [
            'OMNIMERGE_SCORE_GROUP' => [
                'label' =>
                'OmniMerge Score Group',

                'description' =>
                'Busca rivales con rendimiento similar, evita rematches según la política elegida y permite cruzar score groups cuando es necesario.',
            ],

            'ADJACENT_STANDINGS' => [
                'label' =>
                'Clasificación adyacente',

                'description' =>
                'Ordena a los participantes y trata de emparejar posiciones consecutivas.',
            ],

            'RANDOM_WITHIN_SCORE' => [
                'label' =>
                'Aleatorio dentro del score',

                'description' =>
                'Prioriza el mismo score y utiliza una variación determinista del orden dentro de grupos equivalentes.',
            ],
        ];
    }

    public function pairingBases(): array
    {
        return [
            'MATCH_POINTS' => [
                'label' =>
                'Puntos',

                'description' =>
                'Agrupa principalmente según los puntos acumulados.',
            ],

            'WIN_LOSS_RECORD' => [
                'label' =>
                'Récord W/D/L',

                'description' =>
                'Prioriza participantes con una combinación de victorias, empates y derrotas similar.',
            ],

            'PAIRING_SCORE' => [
                'label' =>
                'Pairing Score',

                'description' =>
                'Utiliza una puntuación específica de emparejamiento separada de la clasificación real.',
            ],
        ];
    }

    public function firstRoundModes(): array
    {
        return [
            'INPUT_ORDER' => [
                'label' =>
                'Orden de entrada',

                'description' =>
                '1 vs 2, 3 vs 4, 5 vs 6...',
            ],

            'RANDOM' => [
                'label' =>
                'Aleatorio',

                'description' =>
                'Mezcla el orden inicial de forma determinista para el preview.',
            ],

            'SEEDED_HALVES' => [
                'label' =>
                'Mitades por seed',

                'description' =>
                'La mitad superior se enfrenta con la mitad inferior.',
            ],

            'TOP_VS_BOTTOM' => [
                'label' =>
                'Mejor contra peor seed',

                'description' =>
                '1 vs último, 2 vs penúltimo, etc.',
            ],
        ];
    }

    public function rematchPolicies(): array
    {
        return [
            'STRICT_NO_REMATCH' =>
            'Nunca repetir rival',

            'AVOID_IF_POSSIBLE' =>
            'Evitar rematches si existe alternativa',

            'ALLOW_REMATCH' =>
            'Permitir rematches',
        ];
    }

    public function byePolicies(): array
    {
        return [
            'DISABLED' =>
            'BYE desactivado',

            'LOWEST_STANDING_WITHOUT_BYE' =>
            'Peor clasificado sin BYE previo',

            'LOWEST_SEED_WITHOUT_BYE' =>
            'Seed más bajo sin BYE previo',

            'RANDOM_ELIGIBLE' =>
            'Elegible aleatorio',

            'MANUAL' =>
            'Asignación manual',
        ];
    }

    public function floaterPolicies(): array
    {
        return [
            'MINIMIZE_SCORE_GAP' =>
            'Minimizar diferencia de rendimiento',

            'LOWEST_SEED_FIRST' =>
            'Preferir mover seeds bajos',

            'HIGHEST_SEED_FIRST' =>
            'Preferir mover seeds altos',

            'AVOID_REPEAT_FLOAT' =>
            'Evitar floaters repetidos',
        ];
    }

    public function sidePolicies(): array
    {
        return [
            'NONE' =>
            'No considerar orientación',

            'PREFER_BALANCE' =>
            'Preferir equilibrio Side A / Side B',
        ];
    }

    public function accelerationModes(): array
    {
        return [
            'NONE' => [
                'label' =>
                'Sin aceleración',

                'description' =>
                'Todos utilizan únicamente su score real/de pairing.',
            ],

            'GENERIC_VIRTUAL_POINTS' => [
                'label' =>
                'Puntos virtuales por seed',

                'description' =>
                'Los primeros seeds reciben puntos virtuales únicamente para pairing durante las primeras rondas.',
            ],
        ];
    }

    public function fallbackPolicies(): array
    {
        return [
            'FINAL_RANKING' =>
            'Resolver por clasificación final',

            'MANUAL_RESOLUTION' =>
            'Resolver manualmente',

            'REMAINING_EXIT' =>
            'Enviar activos restantes a una salida',
        ];
    }

    public function cutoffPolicies(): array
    {
        return [
            'USE_TIEBREAKERS' =>
            'Aplicar cadena de desempate',

            'MANUAL_RESOLUTION' =>
            'Resolución manual',

            'RANDOM_RESOLUTION' =>
            'Resolución aleatoria',

            'INCLUDE_ALL_TIED' =>
            'Incluir todos los empatados',

            'REQUIRE_PLAYOFF' =>
            'Requerir playoff',
        ];
    }

    public function tiebreakerCriteria(): array
    {
        return [
            'WINS' =>
            'Victorias',

            'FEWEST_LOSSES' =>
            'Menos derrotas',

            'OPPONENT_SCORE_SUM' =>
            'Opponent Score Sum',

            'OPPONENT_SCORE_CUT_LOWEST' =>
            'Opponent Score Sum descartando bajos',

            'SONNEBORN_BERGER' =>
            'Sonneborn-Berger',

            'CUMULATIVE_SCORE' =>
            'Score acumulativo',

            'SCORE_DIFFERENCE' =>
            'Diferencia de score',

            'SCORE_FOR' =>
            'Score a favor',

            'GAME_DIFFERENCE' =>
            'Diferencia de partidas',

            'GAME_WINS' =>
            'Partidas ganadas',

            'HEAD_TO_HEAD' =>
            'Enfrentamiento directo',

            'SEED' =>
            'Seed inicial',
        ];
    }

    public function roundRuleTypes(): array
    {
        return [
            'ROUND_NUMBER' =>
            'Ronda específica',

            'QUALIFICATION_MATCH' =>
            'Partido de clasificación',

            'ELIMINATION_MATCH' =>
            'Partido de eliminación',

            'QUALIFICATION_OR_ELIMINATION' =>
            'Clasificación o eliminación',

            'EXACT_RECORD' =>
            'Récord específico',
        ];
    }

    public function advancementRuleTypes(): array
    {
        return [
            'WIN_THRESHOLD' =>
            'Alcanzar victorias',

            'LOSS_THRESHOLD' =>
            'Alcanzar derrotas',

            'EXACT_RECORD' =>
            'Récord exacto',

            'FINAL_TOP_N' =>
            'Top N final',

            'FINAL_BOTTOM_N' =>
            'Bottom N final',

            'FINAL_RANK_POSITION' =>
            'Posición final',

            'FINAL_RANK_RANGE' =>
            'Rango final',

            'REMAINING' =>
            'Participantes restantes',
        ];
    }

    public function defaultTiebreakers(): array
    {
        return [
            'OPPONENT_SCORE_SUM',
            'WINS',
            'SCORE_DIFFERENCE',
            'SEED',
        ];
    }
}
