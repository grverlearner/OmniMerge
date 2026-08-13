<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseSwissSetting;
use App\Services\Tournaments\Swiss\SwissRecordMapService;
use PHPUnit\Framework\TestCase;

class SwissRecordMapServiceTest extends TestCase
{
    public function test_three_win_three_loss_map_reaches_terminal_records(): void
    {
        $settings =
            new PhaseSwissSetting([
                'completion_mode' =>
                'RECORD_THRESHOLDS',

                'qualification_wins' =>
                3,

                'elimination_losses' =>
                3,

                'max_rounds' =>
                5,

                'allow_draws' =>
                false,
            ]);

        $map =
            (
                new SwissRecordMapService()
            )
            ->build(
                $settings
            );

        $allStates =
            collect($map)
            ->flatten(1);

        $qualified =
            $allStates->first(
                fn($state) =>
                $state['wins'] === 3
                    &&
                    $state['losses'] === 0
            );

        $eliminated =
            $allStates->first(
                fn($state) =>
                $state['wins'] === 0
                    &&
                    $state['losses'] === 3
            );

        $this->assertNotNull(
            $qualified
        );

        $this->assertSame(
            'QUALIFIED',
            $qualified['status']
        );

        $this->assertNotNull(
            $eliminated
        );

        $this->assertSame(
            'ELIMINATED',
            $eliminated['status']
        );

        $this->assertArrayHasKey(
            5,
            $map
        );
    }
}
