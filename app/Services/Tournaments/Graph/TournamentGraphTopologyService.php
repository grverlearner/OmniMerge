<?php

namespace App\Services\Tournaments\Graph;

class TournamentGraphTopologyService
{
    /*
    |--------------------------------------------------------------------------
    | Cycle Detection
    |--------------------------------------------------------------------------
    |
    | $nodeIds = [1,2,3]
    |
    | $edges = [
    |     [1,2],
    |     [2,3],
    | ]
    |
    */

    public function hasCycle(
        array $nodeIds,
        array $edges
    ): bool {
        $adjacency = [];

        foreach (
            $nodeIds
            as
            $nodeId
        ) {
            $adjacency[(int)
                $nodeId] = [];
        }


        foreach (
            $edges
            as
            [$source, $target]
        ) {
            $source =
                (int)
                $source;

            $target =
                (int)
                $target;

            if (
                ! isset(
                    $adjacency[$source]
                )
            ) {
                $adjacency[$source] = [];
            }

            $adjacency[$source][] =
                $target;
        }


        /*
         * 0 = no visitado
         * 1 = visitando
         * 2 = terminado
         */

        $state = [];

        foreach (
            array_keys(
                $adjacency
            )
            as
            $nodeId
        ) {
            $state[$nodeId] = 0;
        }


        foreach (
            array_keys(
                $adjacency
            )
            as
            $nodeId
        ) {
            if (
                $state[$nodeId] === 0
                &&
                $this->visitForCycle(
                    $nodeId,
                    $adjacency,
                    $state
                )
            ) {
                return true;
            }
        }

        return false;
    }


    private function visitForCycle(
        int $nodeId,
        array $adjacency,
        array &$state
    ): bool {
        $state[$nodeId] = 1;


        foreach (
            $adjacency[$nodeId]
                ??
                []
            as
            $targetId
        ) {
            if (
                (
                    $state[$targetId]
                    ??
                    0
                )
                ===
                1
            ) {
                return true;
            }

            if (
                (
                    $state[$targetId]
                    ??
                    0
                )
                ===
                0
                &&
                $this->visitForCycle(
                    $targetId,
                    $adjacency,
                    $state
                )
            ) {
                return true;
            }
        }


        $state[$nodeId] = 2;

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Reachability
    |--------------------------------------------------------------------------
    */

    public function reachableFrom(
        array $startNodeIds,
        array $edges
    ): array {
        $adjacency = [];

        foreach (
            $edges
            as
            [$source, $target]
        ) {
            $adjacency[(int)
                $source][] =
                (int)
                $target;
        }


        $queue =
            array_values(
                array_unique(
                    array_map(
                        'intval',
                        $startNodeIds
                    )
                )
            );

        $visited = [];


        while (
            $queue !== []
        ) {
            $nodeId =
                array_shift(
                    $queue
                );

            if (
                isset(
                    $visited[$nodeId]
                )
            ) {
                continue;
            }

            $visited[$nodeId] = true;


            foreach (
                $adjacency[$nodeId]
                    ??
                    []
                as
                $targetId
            ) {
                if (
                    ! isset(
                        $visited[$targetId]
                    )
                ) {
                    $queue[] =
                        $targetId;
                }
            }
        }


        return array_map(
            'intval',
            array_keys(
                $visited
            )
        );
    }
}
