<?php

namespace App\Services\Tournaments\Graph;

use App\Models\TournamentTemplate;
use Illuminate\Support\Facades\DB;

class TournamentGraphLayoutService
{
    public function layout(
        TournamentTemplate $template
    ): void {
        $template->load([
            'graphNodes',
            'graphStarts',

            'graphTerminals',

            'graphConnections.targetEntryPort',
        ]);


        DB::transaction(
            function () use (
                $template
            ) {
                /*
                |--------------------------------------------------------------------------
                | Starts
                |--------------------------------------------------------------------------
                */

                foreach (
                    $template->graphStarts
                    as
                    $index => $start
                ) {
                    $start->update([
                        'x_position' =>
                        80,

                        'y_position' =>
                        120
                            +
                            ($index * 190),
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Node Layers
                |--------------------------------------------------------------------------
                */

                $layers = [];

                foreach (
                    $template->graphNodes
                    as
                    $node
                ) {
                    $layers[$node->id] =
                        1;
                }


                /*
                 * Nodes alcanzados directamente
                 * desde Start permanecen en layer 1.
                 */

                $edges =
                    $template
                    ->graphConnections
                    ->filter(
                        fn($connection) =>
                        $connection
                            ->source_type
                            ===
                            'PHASE_EXIT'
                            &&
                            $connection
                            ->target_type
                            ===
                            'ENTRY_PORT'
                            &&
                            $connection
                            ->targetEntryPort
                    )
                    ->map(
                        fn($connection) => [
                            (int)
                            $connection
                                ->source_node_id,

                            (int)
                            $connection
                                ->targetEntryPort
                                ->tournament_phase_node_id,
                        ]
                    )
                    ->values();


                /*
                 * Longest-path layering para DAG.
                 */

                for (
                    $iteration = 0;
                    $iteration
                        <
                        max(
                            1,
                            $template
                                ->graphNodes
                                ->count()
                        );
                    $iteration++
                ) {
                    $changed =
                        false;


                    foreach (
                        $edges
                        as
                        [$source, $target]
                    ) {
                        $candidate =
                            (
                                $layers[$source]
                                ??
                                1
                            )
                            +
                            1;


                        if (
                            $candidate
                            >
                            (
                                $layers[$target]
                                ??
                                1
                            )
                        ) {
                            $layers[$target] =
                                $candidate;

                            $changed =
                                true;
                        }
                    }


                    if (! $changed) {
                        break;
                    }
                }


                $grouped = [];

                foreach (
                    $template->graphNodes
                    as
                    $node
                ) {
                    $layer =
                        $layers[$node->id]
                        ??
                        1;

                    $grouped[$layer][] =
                        $node;
                }


                foreach (
                    $grouped
                    as
                    $layer => $nodes
                ) {
                    foreach (
                        $nodes
                        as
                        $index => $node
                    ) {
                        $node->update([
                            'x_position' =>
                            420
                                +
                                (
                                    ($layer - 1)
                                    *
                                    430
                                ),

                            'y_position' =>
                            100
                                +
                                ($index * 260),
                        ]);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Terminals
                |--------------------------------------------------------------------------
                */

                $maxLayer =
                    $layers !== []
                    ? max(
                        $layers
                    )
                    : 1;


                foreach (
                    $template->graphTerminals
                    as
                    $index => $terminal
                ) {
                    $terminal->update([
                        'x_position' =>
                        420
                            +
                            ($maxLayer * 430),

                        'y_position' =>
                        120
                            +
                            ($index * 190),
                    ]);
                }
            }
        );
    }
}
