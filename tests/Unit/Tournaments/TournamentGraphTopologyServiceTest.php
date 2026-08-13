<?php

namespace Tests\Unit\Tournaments;

use App\Services\Tournaments\Graph\TournamentGraphTopologyService;
use PHPUnit\Framework\TestCase;

class TournamentGraphTopologyServiceTest
extends TestCase
{
    public function test_linear_graph_has_no_cycle(): void
    {
        $service =
            new TournamentGraphTopologyService();


        $result =
            $service->hasCycle(
                [
                    1,
                    2,
                    3,
                    4,
                ],

                [
                    [1, 2],
                    [2, 3],
                    [3, 4],
                ]
            );


        $this->assertFalse(
            $result
        );
    }


    public function test_branch_and_merge_is_valid_dag(): void
    {
        /*
         *       2
         *     /   \
         *  1       4
         *     \   /
         *       3
         */

        $service =
            new TournamentGraphTopologyService();


        $result =
            $service->hasCycle(
                [
                    1,
                    2,
                    3,
                    4,
                ],

                [
                    [1, 2],
                    [1, 3],
                    [2, 4],
                    [3, 4],
                ]
            );


        $this->assertFalse(
            $result
        );
    }


    public function test_graph_detects_cycle(): void
    {
        $service =
            new TournamentGraphTopologyService();


        $result =
            $service->hasCycle(
                [
                    1,
                    2,
                    3,
                ],

                [
                    [1, 2],
                    [2, 3],
                    [3, 1],
                ]
            );


        $this->assertTrue(
            $result
        );
    }


    public function test_reachable_nodes_include_branches_and_merge(): void
    {
        $service =
            new TournamentGraphTopologyService();


        $reachable =
            $service->reachableFrom(
                [
                    1,
                ],

                [
                    [1, 2],
                    [1, 3],
                    [2, 4],
                    [3, 4],
                    [4, 5],
                ]
            );


        sort(
            $reachable
        );


        $this->assertSame(
            [
                1,
                2,
                3,
                4,
                5,
            ],
            $reachable
        );
    }


    public function test_disconnected_node_is_not_reachable(): void
    {
        $service =
            new TournamentGraphTopologyService();


        $reachable =
            $service->reachableFrom(
                [
                    1,
                ],

                [
                    [1, 2],
                    [2, 3],
                ]
            );


        $this->assertNotContains(
            99,
            $reachable
        );
    }
}
