<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseExit;
use App\Models\PhaseSwissAdvancementRule;
use App\Services\Tournaments\Swiss\SwissAdvancementForecastService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class SwissAdvancementForecastServiceTest extends TestCase
{
    public function test_fixed_top_eight_and_remaining_are_deterministic(): void
    {
        $qualifiedExit =
            new PhaseExit([
                'name' =>
                'Clasificados',
            ]);

        $qualifiedExit->id = 1;

        $outExit =
            new PhaseExit([
                'name' =>
                'Eliminados',
            ]);

        $outExit->id = 2;


        $topEight =
            new PhaseSwissAdvancementRule([
                'phase_exit_id' =>
                1,

                'rule_type' =>
                'FINAL_TOP_N',

                'take' =>
                8,

                'sort_order' =>
                10,

                'status' =>
                'ACTIVE',
            ]);

        $topEight->setRelation(
            'phaseExit',
            $qualifiedExit
        );


        $remaining =
            new PhaseSwissAdvancementRule([
                'phase_exit_id' =>
                2,

                'rule_type' =>
                'REMAINING',

                'sort_order' =>
                20,

                'status' =>
                'ACTIVE',
            ]);

        $remaining->setRelation(
            'phaseExit',
            $outExit
        );


        $result =
            (
                new SwissAdvancementForecastService()
            )
            ->forecast(
                16,
                new Collection([
                    $topEight,
                    $remaining,
                ])
            );


        $outputs =
            collect(
                $result['outputs']
            )
            ->keyBy(
                'exit_id'
            );


        $this->assertTrue(
            $result['fully_deterministic']
        );

        $this->assertSame(
            8,
            $outputs[1]['expected_count']
        );

        $this->assertSame(
            8,
            $outputs[2]['expected_count']
        );
    }

    public function test_win_threshold_is_marked_variable(): void
    {
        $exit =
            new PhaseExit([
                'name' =>
                'Clasificados',
            ]);

        $exit->id = 1;


        $rule =
            new PhaseSwissAdvancementRule([
                'phase_exit_id' =>
                1,

                'rule_type' =>
                'WIN_THRESHOLD',

                'threshold_wins' =>
                3,

                'sort_order' =>
                10,

                'status' =>
                'ACTIVE',
            ]);

        $rule->setRelation(
            'phaseExit',
            $exit
        );


        $result =
            (
                new SwissAdvancementForecastService()
            )
            ->forecast(
                16,
                new Collection([
                    $rule,
                ])
            );


        $this->assertFalse(
            $result['fully_deterministic']
        );

        $this->assertTrue(
            $result['outputs'][0]['variable']
        );

        $this->assertNull(
            $result['outputs'][0]['expected_count']
        );
    }
}
