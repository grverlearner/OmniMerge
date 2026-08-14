<?php

namespace Tests\Unit;

use App\Models\PhaseExit;
use App\Services\Tournaments\Graph\Preview\PreviewExitResolver;
use PHPUnit\Framework\TestCase;

class PreviewExitResolverTest
extends TestCase
{
    private PreviewExitResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver =
            new PreviewExitResolver();
    }

    public function test_top_n_and_remaining_do_not_overlap(): void
    {
        $top =
            new PhaseExit([
                'id' => 1,
                'code' => 'EXT001',
                'name' => 'Clasificados',
                'selector_type' => 'TOP_N',
                'selector_from' => 4,
                'priority' => 10,
                'sort_order' => 10,
                'status' => 'ACTIVE',
            ]);

        $remaining =
            new PhaseExit([
                'id' => 2,
                'code' => 'EXT002',
                'name' => 'Eliminados',
                'selector_type' => 'REMAINING',
                'priority' => 20,
                'sort_order' => 20,
                'status' => 'ACTIVE',
            ]);

        $result =
            $this->resolver
            ->resolve(
                $this->participants(10),
                collect([
                    $top,
                    $remaining,
                ]),
                'ORDERED',
                123
            );

        $this->assertCount(
            4,
            $result['assignments'][1]['participants']
        );

        $this->assertCount(
            6,
            $result['assignments'][2]['participants']
        );

        $this->assertCount(
            0,
            $result['remaining']
        );
    }

    public function test_match_winners_are_marked_as_provisional(): void
    {
        $exit =
            new PhaseExit([
                'id' => 1,
                'code' => 'EXT001',
                'name' => 'Ganadores',
                'selector_type' => 'MATCH_WINNERS',
                'priority' => 10,
                'sort_order' => 10,
                'status' => 'ACTIVE',
            ]);

        $result =
            $this->resolver
            ->resolve(
                $this->participants(8),
                collect([$exit]),
                'ORDERED',
                123
            );

        $this->assertTrue(
            $result['assignments'][1]['provisional']
        );

        $this->assertCount(
            4,
            $result['assignments'][1]['participants']
        );

        $this->assertNotEmpty(
            $result['warnings']
        );
    }

    private function participants(
        int $count
    ): array {
        $participants = [];

        for (
            $index = 1;
            $index <= $count;
            $index++
        ) {
            $participants[] = [
                'preview_id' =>
                'P'
                    .
                    $index,

                'name' =>
                'Participante '
                    .
                    $index,

                'journey' =>
                [],
            ];
        }

        return $participants;
    }
}
