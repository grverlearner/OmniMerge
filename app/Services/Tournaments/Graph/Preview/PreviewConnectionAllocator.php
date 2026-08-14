<?php

namespace App\Services\Tournaments\Graph\Preview;

use App\Models\TournamentPhaseConnection;
use Illuminate\Support\Collection;

class PreviewConnectionAllocator
{
    public function distribute(
        array $participants,
        Collection $connections
    ): array {
        $connections =
            $connections
            ->where(
                'status',
                'ACTIVE'
            )
            ->sortBy(
                fn($connection) =>
                sprintf(
                    '%010d-%010d-%010d',
                    $connection->priority,
                    $connection->sequence_number,
                    $connection->id
                )
            )
            ->values();

        $original =
            array_values(
                $participants
            );

        $remaining =
            $original;

        $allocations = [];

        foreach (
            $connections
            as
            $connection
        ) {
            $selected =
                $this->select(
                    $original,
                    $remaining,
                    $connection
                );

            $selectedIds =
                array_column(
                    $selected,
                    'preview_id'
                );

            if (
                $connection->allocation_mode
                !==
                'ALL'
            ) {
                $remaining =
                    array_values(
                        array_filter(
                            $remaining,
                            fn($participant) =>
                            ! in_array(
                                $participant['preview_id'],
                                $selectedIds,
                                true
                            )
                        )
                    );
            } else {
                $remaining = [];
            }

            $allocations[$connection->id] = [
                'connection_id' =>
                (int) $connection->id,

                'connection_code' =>
                $connection->code,

                'mode' =>
                $connection->allocation_mode,

                'requested_value' =>
                $connection->allocation_value,

                'participants' =>
                $selected,

                'participant_ids' =>
                $selectedIds,

                'count' =>
                count($selected),
            ];
        }

        return [
            'allocations' =>
            $allocations,

            'remaining' =>
            $remaining,

            'remaining_ids' =>
            array_column(
                $remaining,
                'preview_id'
            ),

            'distributed_count' =>
            collect(
                $allocations
            )
                ->sum('count'),
        ];
    }

    private function select(
        array $original,
        array $remaining,
        TournamentPhaseConnection $connection
    ): array {
        return match ($connection->allocation_mode) {
            'ALL' =>
            array_values(
                $remaining
            ),

            'TAKE_N' =>
            array_slice(
                $remaining,
                0,
                max(
                    0,
                    (int) $connection
                        ->allocation_value
                )
            ),

            'PERCENTAGE' =>
            array_slice(
                $remaining,
                0,
                $this->percentageQuantity(
                    count($original),
                    (float) $connection
                        ->allocation_value
                )
            ),

            'REMAINDER' =>
            array_values(
                $remaining
            ),

            default =>
            [],
        };
    }

    private function percentageQuantity(
        int $originalCount,
        float $percentage
    ): int {
        $percentage =
            max(
                0,
                min(
                    100,
                    $percentage
                )
            );

        return (int) floor(
            $originalCount
                *
                (
                    $percentage
                    /
                    100
                )
        );
    }
}
