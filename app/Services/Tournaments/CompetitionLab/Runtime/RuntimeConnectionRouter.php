<?php

namespace App\Services\Tournaments\CompetitionLab\Runtime;

use App\Models\TournamentPhaseConnection;
use App\Services\Tournaments\Graph\Flow\EntryPortMergePolicy;
use Illuminate\Support\Collection;

class RuntimeConnectionRouter
{
    public function route(
        array $state,
        Collection $connections,
        array $participantIds,
        string $sourceType,
        int $sourceId,
        ?int $sourceExitId = null,
        bool $finalize = true
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
                array_unique(
                    $participantIds
                )
            );

        $remaining =
            $original;

        $touchedNodeIds =
            [];

        $touchedTerminalIds =
            [];

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

            if (
                $connection->allocation_mode
                !==
                'ALL'
            ) {
                $remaining =
                    array_values(
                        array_diff(
                            $remaining,
                            $selected
                        )
                    );
            } else {
                $remaining =
                    [];
            }

            $connectionId =
                (int)
                $connection->id;

            $previouslyRouted =
                $state['connections'][$connectionId]['participant_ids']
                ?? [];

            $routed = array_values(array_unique([
                ...$previouslyRouted,
                ...$selected,
            ]));

            $newlyRouted = array_values(array_diff(
                $selected,
                $previouslyRouted
            ));

            $state['connections'][$connectionId]['status'] =
                $finalize
                ? ($routed === [] ? 'CLOSED_EMPTY' : 'ROUTED')
                : 'ROUTING';

            $state['connections'][$connectionId]['participant_ids'] =
                $routed;

            $state['connections'][$connectionId]['routed_count'] =
                count($routed);

            if (
                $connection->target_type
                ===
                'TERMINAL'
            ) {
                $terminalId =
                    (int)
                    $connection->target_terminal_id;

                $state =
                    $this->deliverToTerminal(
                        $state,
                        $connection,
                        $newlyRouted
                    );

                $touchedTerminalIds[] =
                    $terminalId;
            } else {
                $portId =
                    (int)
                    $connection->target_entry_port_id;

                $nodeId =
                    (int)
                    $connection
                        ->targetEntryPort
                        ?->tournament_phase_node_id;

                $state =
                    $this->deliverToPort(
                        $state,
                        $connection,
                        $routed
                    );

                if ($nodeId > 0) {
                    $touchedNodeIds[] =
                        $nodeId;
                }
            }

            foreach (
                $newlyRouted
                as
                $participantId
            ) {
                if (
                    ! isset(
                        $state['participants'][$participantId]
                    )
                ) {
                    continue;
                }

                $state['participants'][$participantId]['journey'][] = [
                    'type' =>
                    'CONNECTION',

                    'id' =>
                    $connectionId,

                    'code' =>
                    $connection->code,

                    'name' =>
                    $connection->label
                        ?:
                        $connection->source_label
                        .
                        ' → '
                        .
                        $connection->target_label,

                    'source_type' =>
                    $sourceType,

                    'source_id' =>
                    $sourceId,

                    'source_exit_id' =>
                    $sourceExitId,

                    'target_type' =>
                    $connection->target_type,
                ];
            }
        }

        return [
            'state' =>
            $state,

            'remaining_ids' =>
            $remaining,

            'touched_node_ids' =>
            array_values(
                array_unique(
                    $touchedNodeIds
                )
            ),

            'touched_terminal_ids' =>
            array_values(
                array_unique(
                    $touchedTerminalIds
                )
            ),
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
                    (int)
                    $connection->allocation_value
                )
            ),

            'PERCENTAGE' =>
            array_slice(
                $remaining,
                0,
                (int)
                floor(
                    count($original)
                        *
                        (
                            max(
                                0,
                                min(
                                    100,
                                    (float)
                                    $connection->allocation_value
                                )
                            )
                            /
                            100
                        )
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

    private function deliverToPort(
        array $state,
        TournamentPhaseConnection $connection,
        array $participantIds
    ): array {
        $portId =
            (int)
            $connection->target_entry_port_id;

        $nodeId =
            (int)
            $connection
                ->targetEntryPort
                ?->tournament_phase_node_id;

        if (
            ! isset(
                $state['nodes'][$nodeId]['entry_ports'][$portId]
            )
        ) {
            $this->diagnostic(
                $state,
                'ERROR',
                'ENTRY_PORT_NOT_FOUND',
                "La conexión {$connection->code} apunta a un puerto inexistente."
            );

            return $state;
        }

        $port =
            &$state['nodes'][$nodeId]['entry_ports'][$portId];

        $connectionId = (int) $connection->id;

        $port['connection_payloads'] ??= [];
        $port['connection_payloads'][$connectionId] =
            array_values(array_unique($participantIds));

        $port['received_connection_ids'] =
            array_values(
                array_unique([
                    ...(
                        $port['received_connection_ids']
                        ??
                        []
                    ),
                    $connectionId,
                ])
            );

        $merged = EntryPortMergePolicy::merge(
            $port['merge_policy'] ?? 'APPEND',
            $port['incoming_connection_ids'] ?? [],
            $port['received_connection_ids'] ?? [],
            $port['connection_payloads']
        );

        $port['participant_ids'] =
            $merged;

        $maximum =
            $port['exact_participants']
            ??
            $port['max_participants'];

        if (
            $maximum !== null
            &&
            count($merged)
            >
            $maximum
        ) {
            $port['status'] =
                'OVER_CAPACITY';

            $this->diagnostic(
                $state,
                'ERROR',
                'ENTRY_PORT_OVERFLOW',
                "El puerto {$port['name']} recibió más participantes de los permitidos."
            );
        } else {
            $port['status'] =
                $merged === []
                ? 'EMPTY'
                : 'RECEIVING';
        }

        unset($port);

        return $state;
    }

    private function deliverToTerminal(
        array $state,
        TournamentPhaseConnection $connection,
        array $participantIds
    ): array {
        $terminalId =
            (int)
            $connection->target_terminal_id;

        if (
            ! isset(
                $state['terminals'][$terminalId]
            )
        ) {
            $this->diagnostic(
                $state,
                'ERROR',
                'TERMINAL_NOT_FOUND',
                "La conexión {$connection->code} apunta a un terminal inexistente."
            );

            return $state;
        }

        $terminal =
            &$state['terminals'][$terminalId];

        $terminal['participant_ids'] =
            array_values(
                array_unique([
                    ...$terminal['participant_ids'],
                    ...$participantIds,
                ])
            );

        $terminal['received_connection_ids'] =
            array_values(
                array_unique([
                    ...(
                        $terminal['received_connection_ids']
                        ??
                        []
                    ),
                    (int)
                    $connection->id,
                ])
            );

        $expected =
            $terminal['expected_participants'];

        if (
            $expected !== null
            &&
            count(
                $terminal['participant_ids']
            )
            >
            $expected
        ) {
            $terminal['status'] =
                'OVER_CAPACITY';

            $this->diagnostic(
                $state,
                'ERROR',
                'TERMINAL_OVER_CAPACITY',
                "El terminal {$terminal['name']} superó su cantidad esperada."
            );
        } else {
            $terminal['status'] =
                $participantIds === []
                ? $terminal['status']
                : 'COMPLETED';
        }

        foreach (
            $participantIds
            as
            $participantId
        ) {
            if (
                ! isset(
                    $state['participants'][$participantId]
                )
            ) {
                continue;
            }

            $location = [
                'type' =>
                'TERMINAL',

                'id' =>
                $terminalId,

                'code' =>
                $terminal['code'],

                'name' =>
                $terminal['name'],
            ];

            $state['participants'][$participantId]['status'] =
                'FINISHED';

            $state['participants'][$participantId]['current_location'] =
                $location;

            $state['participants'][$participantId]['journey'][] =
                $location;
        }

        unset($terminal);

        return $state;
    }

    private function diagnostic(
        array &$state,
        string $level,
        string $code,
        string $message
    ): void {
        $state['graph_runtime']['diagnostics'][] = [
            'level' =>
            $level,

            'code' =>
            $code,

            'message' =>
            $message,
        ];
    }
}
