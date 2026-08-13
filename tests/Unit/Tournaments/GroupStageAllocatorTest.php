<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseGroupStageSetting;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\GroupStage\GroupStageAllocator;
use App\Services\Tournaments\GroupStage\GroupStageValidator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class GroupStageAllocatorTest extends TestCase
{
    private function allocator(): GroupStageAllocator
    {
        return new GroupStageAllocator(
            new GroupStageValidator()
        );
    }

    private function phase(): PhaseTemplate
    {
        return new PhaseTemplate([
            'phase_type' =>
            'GROUP_STAGE',

            'min_participants' =>
            4,

            'max_participants' =>
            512,
        ]);
    }

    private function settings(
        int $groupCount = 4
    ): PhaseGroupStageSetting {
        return new PhaseGroupStageSetting([
            'group_count_mode' =>
            'FIXED_GROUP_COUNT',

            'group_count' =>
            $groupCount,

            'target_group_size' =>
            4,

            'min_group_size' =>
            2,

            'max_group_size' =>
            16,

            'remainder_policy' =>
            'BALANCED',

            'distribution_mode' =>
            'SNAKE_SEEDED',

            'internal_engine_type' =>
            'ROUND_ROBIN',

            'internal_cycles' =>
            1,

            'internal_best_of' =>
            1,
        ]);
    }

    public function test_thirty_two_participants_create_eight_groups_of_four(): void
    {
        $result =
            $this
            ->allocator()
            ->allocate(
                $this->phase(),
                $this->settings(8),
                new Collection(),
                32
            );

        $this->assertTrue(
            $result['valid']
        );

        $this->assertSame(
            8,
            $result['group_count']
        );

        $this->assertSame(
            array_fill(
                0,
                8,
                4
            ),
            $result['sizes']
        );
    }

    public function test_ten_participants_in_three_groups_becomes_four_three_three(): void
    {
        $result =
            $this
            ->allocator()
            ->allocate(
                $this->phase(),
                $this->settings(3),
                new Collection(),
                10
            );

        $this->assertTrue(
            $result['valid']
        );

        $this->assertSame(
            [
                4,
                3,
                3,
            ],
            $result['sizes']
        );
    }

    public function test_snake_distribution_balances_seed_positions(): void
    {
        $result =
            $this
            ->allocator()
            ->allocate(
                $this->phase(),
                $this->settings(4),
                new Collection(),
                16
            );

        $groupASeeds =
            array_column(
                $result['groups'][0]['members'],
                'seed'
            );

        $this->assertSame(
            [
                1,
                8,
                9,
                16,
            ],
            $groupASeeds
        );
    }
}
