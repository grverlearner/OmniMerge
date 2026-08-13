<?php

namespace App\Services\Tournaments\RoundRobin;

class RoundRobinRankingDefinitionService
{
    /*
    |--------------------------------------------------------------------------
    | Criterio principal
    |--------------------------------------------------------------------------
    |
    | PUNTOS siempre será el criterio primario.
    | Los demás se aplican únicamente cuando existe empate.
    |
    */

    public function primaryCriterion(): array
    {
        return [
            'key' =>
            'POINTS',

            'label' =>
            'Puntos',

            'description' =>
            'Ordena inicialmente a los participantes por la cantidad total de puntos acumulados.',

            'direction' =>
            'DESC',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Criterios de desempate
    |--------------------------------------------------------------------------
    */

    public function criteria(): array
    {
        return [
            'WINS' => [
                'label' =>
                'Victorias',

                'description' =>
                'Prioriza al participante con más series ganadas.',

                'direction' =>
                'DESC',
            ],

            'FEWEST_LOSSES' => [
                'label' =>
                'Menos derrotas',

                'description' =>
                'Prioriza al participante con menos derrotas.',

                'direction' =>
                'ASC',
            ],

            'HEAD_TO_HEAD' => [
                'label' =>
                'Enfrentamiento directo',

                'description' =>
                'Compara los resultados entre los participantes empatados.',

                'direction' =>
                'DESC',
            ],

            'SCORE_DIFFERENCE' => [
                'label' =>
                'Diferencia de score',

                'description' =>
                'Score a favor menos score en contra.',

                'direction' =>
                'DESC',
            ],

            'SCORE_FOR' => [
                'label' =>
                'Score a favor',

                'description' =>
                'Prioriza al participante con mayor score acumulado.',

                'direction' =>
                'DESC',
            ],

            'GAME_DIFFERENCE' => [
                'label' =>
                'Diferencia de partidas',

                'description' =>
                'Partidas internas ganadas menos partidas internas perdidas.',

                'direction' =>
                'DESC',
            ],

            'GAME_WINS' => [
                'label' =>
                'Partidas ganadas',

                'description' =>
                'Prioriza al participante con más partidas internas ganadas.',

                'direction' =>
                'DESC',
            ],

            'SEED' => [
                'label' =>
                'Seed inicial',

                'description' =>
                'Utiliza el orden inicial como criterio final.',

                'direction' =>
                'ASC',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */

    public function defaultCriteria(): array
    {
        return [
            'WINS',
            'SCORE_DIFFERENCE',
            'SCORE_FOR',
            'HEAD_TO_HEAD',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Cutoff Policies
    |--------------------------------------------------------------------------
    */

    public function cutoffPolicies(): array
    {
        return [
            'USE_TIEBREAKERS' => [
                'label' =>
                'Aplicar desempates',

                'description' =>
                'Utiliza todos los criterios configurados hasta resolver el orden.',
            ],

            'MANUAL_RESOLUTION' => [
                'label' =>
                'Resolución manual',

                'description' =>
                'Si el empate sigue afectando una clasificación, un usuario deberá resolverlo.',
            ],

            'RANDOM_RESOLUTION' => [
                'label' =>
                'Resolución aleatoria',

                'description' =>
                'El Runtime podrá resolver aleatoriamente un empate no resuelto.',
            ],

            'INCLUDE_ALL_TIED' => [
                'label' =>
                'Incluir todos los empatados',

                'description' =>
                'La salida podrá contener más participantes de los inicialmente previstos.',
            ],

            'REQUIRE_PLAYOFF' => [
                'label' =>
                'Requerir playoff',

                'description' =>
                'El empate quedará marcado para que posteriormente pueda resolverse mediante otra Fase.',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Standings
    |--------------------------------------------------------------------------
    */

    public function standingsColumns(): array
    {
        return [
            'POSITION' => '#',
            'PARTICIPANT' => 'Participante',
            'PLAYED' => 'PJ',
            'WINS' => 'PG',
            'DRAWS' => 'PE',
            'LOSSES' => 'PP',
            'POINTS' => 'PTS',
            'SCORE_FOR' => 'SF',
            'SCORE_AGAINST' => 'SC',
            'SCORE_DIFFERENCE' => 'DIF',
            'GAME_WINS' => 'JG',
            'GAME_LOSSES' => 'JP',
            'GAME_DIFFERENCE' => 'JDIF',
        ];
    }
}
