<?php

namespace Tests\Unit\Tournaments\CompetitionLab\Runtime;

use App\Models\TournamentPhaseConnection;
use App\Services\Tournaments\CompetitionLab\Runtime\RuntimeConnectionRouter;
use Tests\TestCase;

class RuntimeConnectionRouterTest extends TestCase
{
    public function test_repeated_delta_routing_does_not_duplicate_terminal_or_journey(): void
    {
        $router = new RuntimeConnectionRouter();
        $connection = $this->terminalConnection();
        $connections = collect([$connection]);
        $state = $this->state();

        $first = $router->route(
            $state,
            $connections,
            ['P1'],
            'NODE',
            10,
            50,
            false
        );

        $second = $router->route(
            $first['state'],
            $connections,
            ['P1', 'P2'],
            'NODE',
            10,
            50,
            false
        );

        $third = $router->route(
            $second['state'],
            $connections,
            ['P1', 'P2'],
            'NODE',
            10,
            50,
            true
        );

        $state = $third['state'];

        $this->assertSame(
            ['P1', 'P2'],
            $state['terminals'][99]['participant_ids']
        );

        $this->assertSame(
            ['P1', 'P2'],
            $state['connections'][70]['participant_ids']
        );

        $this->assertSame(
            'ROUTED',
            $state['connections'][70]['status']
        );

        foreach (['P1', 'P2'] as $participantId) {
            $connectionJourneys = collect(
                $state['participants'][$participantId]['journey']
            )->where('type', 'CONNECTION');

            $this->assertCount(1, $connectionJourneys);
        }
    }

    private function terminalConnection(): TournamentPhaseConnection
    {
        $connection = new TournamentPhaseConnection();

        $connection->forceFill([
            'id' => 70,
            'sequence_number' => 1,
            'code' => 'CON070',
            'label' => 'Eliminados → Fin',
            'source_type' => 'PHASE_EXIT',
            'source_node_id' => 10,
            'source_phase_exit_id' => 50,
            'target_type' => 'TERMINAL',
            'target_terminal_id' => 99,
            'allocation_mode' => 'ALL',
            'allocation_value' => null,
            'priority' => 10,
            'status' => 'ACTIVE',
        ]);

        return $connection;
    }

    private function state(): array
    {
        return [
            'connections' => [
                70 => [
                    'id' => 70,
                    'status' => 'WAITING',
                    'participant_ids' => [],
                    'routed_count' => 0,
                ],
            ],
            'terminals' => [
                99 => [
                    'id' => 99,
                    'code' => 'TRM099',
                    'name' => 'Fin eliminados',
                    'participant_ids' => [],
                    'received_connection_ids' => [],
                    'expected_participants' => null,
                    'status' => 'WAITING',
                ],
            ],
            'participants' => [
                'P1' => $this->participant('P1'),
                'P2' => $this->participant('P2'),
            ],
            'graph_runtime' => [
                'diagnostics' => [],
            ],
        ];
    }

    private function participant(string $id): array
    {
        return [
            'lab_id' => $id,
            'status' => 'COMPETING',
            'current_location' => null,
            'journey' => [],
        ];
    }
}
