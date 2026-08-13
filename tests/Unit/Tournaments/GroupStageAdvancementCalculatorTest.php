<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseExit;
use App\Models\PhaseGroupStageAdvancementRule;
use App\Services\Tournaments\GroupStage\GroupStageAdvancementCalculator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class GroupStageAdvancementCalculatorTest extends TestCase
{
    /*
    |--------------------------------------------------------------------------
    | Grupos ficticios para las pruebas
    |--------------------------------------------------------------------------
    |
    | No usamos el nombre groups() porque PHPUnit\TestCase
    | ya posee un método final con ese nombre.
    |
    */

    private function sampleGroups(): array
    {
        $groups = [];

        for (
            $index = 1;
            $index <= 6;
            $index++
        ) {
            $groups[] = [
                'index' =>
                $index,

                'definition_id' =>
                $index,

                'name' =>
                'Grupo '
                    .
                    $index,

                'size' =>
                4,
            ];
        }

        return $groups;
    }

    public function test_top_two_plus_four_best_thirds_leaves_eight_remaining(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Puertas ficticias
        |--------------------------------------------------------------------------
        */

        $directExit =
            new PhaseExit([
                'name' =>
                'Clasificados',
            ]);

        $directExit->id =
            1;


        $thirdExit =
            new PhaseExit([
                'name' =>
                'Mejores terceros',
            ]);

        $thirdExit->id =
            2;


        $outExit =
            new PhaseExit([
                'name' =>
                'Eliminados',
            ]);

        $outExit->id =
            3;


        /*
        |--------------------------------------------------------------------------
        | Regla 1
        |--------------------------------------------------------------------------
        |
        | Top 2 de cada grupo.
        |
        | 6 grupos × 2 = 12
        |
        */

        $topTwo =
            new PhaseGroupStageAdvancementRule([
                'phase_exit_id' =>
                1,

                'rule_type' =>
                'EACH_GROUP_TOP_N',

                'take' =>
                2,

                'sort_order' =>
                10,

                'status' =>
                'ACTIVE',
            ]);

        $topTwo->setRelation(
            'phaseExit',
            $directExit
        );


        /*
        |--------------------------------------------------------------------------
        | Regla 2
        |--------------------------------------------------------------------------
        |
        | Los 4 mejores terceros.
        |
        */

        $bestThirds =
            new PhaseGroupStageAdvancementRule([
                'phase_exit_id' =>
                2,

                'rule_type' =>
                'CROSS_GROUP_POSITION_TOP_N',

                'position_from' =>
                3,

                'take' =>
                4,

                'sort_order' =>
                20,

                'status' =>
                'ACTIVE',
            ]);

        $bestThirds->setRelation(
            'phaseExit',
            $thirdExit
        );


        /*
        |--------------------------------------------------------------------------
        | Regla 3
        |--------------------------------------------------------------------------
        |
        | Todos los demás quedan eliminados.
        |
        */

        $remaining =
            new PhaseGroupStageAdvancementRule([
                'phase_exit_id' =>
                3,

                'rule_type' =>
                'REMAINING',

                'sort_order' =>
                30,

                'status' =>
                'ACTIVE',
            ]);

        $remaining->setRelation(
            'phaseExit',
            $outExit
        );


        /*
        |--------------------------------------------------------------------------
        | Colección de reglas
        |--------------------------------------------------------------------------
        */

        $rules =
            new Collection([
                $topTwo,
                $bestThirds,
                $remaining,
            ]);


        /*
        |--------------------------------------------------------------------------
        | Ejecutar forecast
        |--------------------------------------------------------------------------
        */

        $result =
            (
                new GroupStageAdvancementCalculator()
            )
            ->forecast(
                $this->sampleGroups(),
                $rules,
                'USE_TIEBREAKERS'
            );


        /*
        |--------------------------------------------------------------------------
        | Comprobaciones generales
        |--------------------------------------------------------------------------
        |
        | 6 grupos × 4 participantes = 24
        |
        */

        $this->assertSame(
            24,
            $result['participants']
        );

        $this->assertSame(
            24,
            $result['selected_count']
        );

        $this->assertSame(
            0,
            $result['unselected_count']
        );


        /*
        |--------------------------------------------------------------------------
        | Comprobar cada puerta
        |--------------------------------------------------------------------------
        */

        $outputs =
            collect(
                $result['outputs']
            )
            ->keyBy(
                'exit_id'
            );


        /*
         * 2 mejores × 6 grupos
         * =
         * 12
         */

        $this->assertSame(
            12,
            $outputs[1]['expected_count']
        );


        /*
         * 4 mejores terceros
         */

        $this->assertSame(
            4,
            $outputs[2]['expected_count']
        );


        /*
         * 24 - 12 - 4
         * =
         * 8 eliminados
         */

        $this->assertSame(
            8,
            $outputs[3]['expected_count']
        );
    }
}
