<?php

namespace Tests\Unit;

use App\Models\TournamentPhaseConnection;
use App\Services\Tournaments\Graph\Preview\PreviewConnectionAllocator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PreviewConnectionAllocatorTest
extends TestCase
{
    private PreviewConnectionAllocator $allocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->allocator =
            new PreviewConnectionAllocator();
    }

    public function test_take_n_and_remainder_partition_the_pool(): void
    {
        $participants =
            $this->participants(10);

        $take =
            new TournamentPhaseConnection([
                'id' => 1,
                'code' => 'CON001',
                'allocation_mode' => 'TAKE_N',
                'allocation_value' => 4,
                'priority' => 10,
                'sequence_number' => 1,
                'status' => 'ACTIVE',
            ]);

        $remainder =
            new TournamentPhaseConnection([
                'id' => 2,
                'code' => 'CON002',
                'allocation_mode' => 'REMAINDER',
                'priority' => 20,
                'sequence_number' => 2,
                'status' => 'ACTIVE',
            ]);

        $result =
            $this->allocator
            ->distribute(
                $participants,
                new Collection([
                    $take,
                    $remainder,
                ])
            );

        $this->assertCount(
            4,
            $result['allocations'][1]['participants']
        );

        $this->assertCount(
            6,
            $result['allocations'][2]['participants']
        );

        $this->assertCount(
            0,
            $result['remaining']
        );
    }

    public function test_percentage_uses_floor_and_remainder_receives_rest(): void
    {
        $participants =
            $this->participants(7);

        $percentage =
            new TournamentPhaseConnection([
                'id' => 1,
                'code' => 'CON001',
                'allocation_mode' => 'PERCENTAGE',
                'allocation_value' => 50,
                'priority' => 10,
                'sequence_number' => 1,
                'status' => 'ACTIVE',
            ]);

        $remainder =
            new TournamentPhaseConnection([
                'id' => 2,
                'code' => 'CON002',
                'allocation_mode' => 'REMAINDER',
                'priority' => 20,
                'sequence_number' => 2,
                'status' => 'ACTIVE',
            ]);

        $result =
            $this->allocator
            ->distribute(
                $participants,
                collect([
                    $percentage,
                    $remainder,
                ])
            );

        $this->assertCount(
            3,
            $result['allocations'][1]['participants']
        );

        $this->assertCount(
            4,
            $result['allocations'][2]['participants']
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
            ];
        }

        return $participants;
    }
}
