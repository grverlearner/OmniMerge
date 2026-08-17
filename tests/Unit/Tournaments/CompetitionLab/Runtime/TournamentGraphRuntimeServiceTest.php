<?php

namespace Tests\Unit\Tournaments\CompetitionLab\Runtime;

use App\Models\PhaseExit;
use App\Models\PhaseTemplate;
use App\Models\TournamentPhaseConnection;
use App\Models\TournamentPhaseNode;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\CompetitionLab\Engines\LabPhaseEngineManager;
use App\Services\Tournaments\CompetitionLab\Runtime\RuntimeConnectionRouter;
use App\Services\Tournaments\CompetitionLab\Runtime\RuntimeOutcomeResolver;
use App\Services\Tournaments\CompetitionLab\Runtime\TournamentGraphRuntimeService;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class TournamentGraphRuntimeServiceTest extends TestCase
{
    public function test_on_elimination_routes_only_new_events_and_keeps_an_explicit_ledger(): void
    {
        [$service, $template, $state] =
            $this->timedEliminationFixture();

        $state = $this->invokePrivate(
            $service,
            'routeTimedOutputs',
            [$state, $template, 10]
        );

        $this->assertSame(
            ['P2'],
            $state['terminals'][99]['participant_ids']
        );
        $this->assertCount(1, $state['graph_runtime']['emission_ledger']);
        $this->assertCount(1, $state['graph_runtime']['phase_exit_emissions']);
        $this->assertSame(
            ['P2'],
            $state['nodes'][10]['runtime']['timed_outcomes'][50]['participant_ids']
        );
        $this->assertCount(
            1,
            collect($state['participants']['P2']['journey'])
                ->where('type', 'CONNECTION')
        );

        /*
         * Recalcular el mismo estado no vuelve a emitir el mismo evento.
         */
        $state = $this->invokePrivate(
            $service,
            'routeTimedOutputs',
            [$state, $template, 10]
        );

        $this->assertSame(
            ['P2'],
            $state['terminals'][99]['participant_ids']
        );
        $this->assertCount(1, $state['graph_runtime']['emission_ledger']);
        $this->assertCount(1, $state['graph_runtime']['phase_exit_emissions']);
        $this->assertCount(
            1,
            collect($state['participants']['P2']['journey'])
                ->where('type', 'CONNECTION')
        );

        /*
         * Una eliminación nueva sí produce un delta nuevo.
         */
        $state['nodes'][10]['runtime']['eliminations'][] = [
            'id' => 'ELIMINATION:M2:P3',
            'participant_id' => 'P3',
            'round_number' => 1,
            'round_participants' => 4,
            'match_id' => 'M2',
            'source' => 'MATCH_RESULT',
        ];
        $state['nodes'][10]['runtime']['eliminated_ids'][] = 'P3';

        $state = $this->invokePrivate(
            $service,
            'routeTimedOutputs',
            [$state, $template, 10]
        );

        $this->assertSame(
            ['P2', 'P3'],
            $state['terminals'][99]['participant_ids']
        );
        $this->assertCount(2, $state['graph_runtime']['emission_ledger']);
        $this->assertCount(2, $state['graph_runtime']['phase_exit_emissions']);
        $this->assertSame(
            ['P2', 'P3'],
            $state['nodes'][10]['runtime']['timed_outcomes'][50]['participant_ids']
        );
        $this->assertCount(
            1,
            collect($state['participants']['P3']['journey'])
                ->where('type', 'CONNECTION')
        );
    }

    public function test_on_elimination_round_filter_only_emits_the_requested_round_band(): void
    {
        [$service, $template, $state] =
            $this->timedEliminationFixture('ELIMINATED_IN_ROUND', 8);

        $state['nodes'][10]['runtime']['eliminations'] = [
            [
                'id' => 'ELIMINATION:R8:P8',
                'participant_id' => 'P8',
                'round_number' => 1,
                'round_participants' => 8,
                'match_id' => 'R8',
                'source' => 'MATCH_RESULT',
            ],
            [
                'id' => 'ELIMINATION:R4:P4',
                'participant_id' => 'P4',
                'round_number' => 2,
                'round_participants' => 4,
                'match_id' => 'R4',
                'source' => 'MATCH_RESULT',
            ],
        ];
        $state['nodes'][10]['runtime']['eliminated_ids'] = ['P8', 'P4'];
        $state['nodes'][10]['participant_ids'] = ['P1', 'P4', 'P8'];
        $state['participants']['P8'] = $this->participant('P8');
        $state['participants']['P4'] = $this->participant('P4');

        $state = $this->invokePrivate(
            $service,
            'routeTimedOutputs',
            [$state, $template, 10]
        );

        $this->assertSame(
            ['P8'],
            $state['terminals'][99]['participant_ids']
        );
        $this->assertCount(1, $state['graph_runtime']['emission_ledger']);
    }

    public function test_on_rule_trigger_waits_for_an_explicit_engine_outcome_and_then_emits_once(): void
    {
        $service = $this->service();
        $exit = $this->exit(60, 'ENGINE_RULES', 'ON_RULE_TRIGGER');

        $phase = new PhaseTemplate();
        $phase->forceFill(['id' => 6]);
        $phase->setRelation('exits', collect([$exit]));

        $nodeModel = new TournamentPhaseNode();
        $nodeModel->forceFill(['id' => 11]);
        $nodeModel->setRelation('phaseTemplate', $phase);

        $connection = new TournamentPhaseConnection();
        $connection->forceFill([
            'id' => 71,
            'sequence_number' => 1,
            'code' => 'CON071',
            'label' => 'Regla → Terminal',
            'source_type' => 'PHASE_EXIT',
            'source_node_id' => 11,
            'source_phase_exit_id' => 60,
            'target_type' => 'TERMINAL',
            'target_terminal_id' => 98,
            'allocation_mode' => 'ALL',
            'priority' => 10,
            'status' => 'ACTIVE',
        ]);

        $template = new TournamentTemplate();
        $template->setRelation('graphNodes', collect([$nodeModel]));
        $template->setRelation('graphConnections', collect([$connection]));

        $state = [
            'nodes' => [
                11 => [
                    'id' => 11,
                    'name' => 'Fase con regla',
                    'participant_ids' => ['P1', 'P2'],
                    'runtime' => [
                        'status' => 'RUNNING',
                        'outcomes' => [],
                        'timed_outcomes' => [],
                    ],
                ],
            ],
            'connections' => [
                71 => [
                    'id' => 71,
                    'status' => 'WAITING',
                    'participant_ids' => [],
                    'routed_count' => 0,
                ],
            ],
            'terminals' => [
                98 => [
                    'id' => 98,
                    'code' => 'TRM098',
                    'name' => 'Regla',
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
                'status' => 'RUNNING',
                'operation_queue' => [],
                'processed_operations' => [],
                'operation_count' => 0,
                'diagnostics' => [],
                'stranded_participant_ids' => [],
                'emission_ledger' => [],
                'phase_exit_emissions' => [],
            ],
            'timeline' => [],
        ];

        $state = $this->invokePrivate(
            $service,
            'routeTimedOutputs',
            [$state, $template, 11]
        );

        $this->assertSame([], $state['terminals'][98]['participant_ids']);
        $this->assertSame([], $state['graph_runtime']['emission_ledger']);

        $state['nodes'][11]['runtime']['outcomes'][] = [
            'exit_id' => 60,
            'exit_name' => 'Salida 60',
            'participant_ids' => ['P1'],
        ];

        $state = $this->invokePrivate(
            $service,
            'routeTimedOutputs',
            [$state, $template, 11]
        );

        $this->assertSame(['P1'], $state['terminals'][98]['participant_ids']);
        $this->assertCount(1, $state['graph_runtime']['emission_ledger']);

        $state = $this->invokePrivate(
            $service,
            'routeTimedOutputs',
            [$state, $template, 11]
        );

        $this->assertSame(['P1'], $state['terminals'][98]['participant_ids']);
        $this->assertCount(1, $state['graph_runtime']['emission_ledger']);
    }

    public function test_phase_end_rejects_an_outcome_that_breaks_exit_contract(): void
    {
        $service = $this->service();

        $exit = $this->exit(50, 'SURVIVORS', 'PHASE_END');
        $exit->exact_participants = 2;

        $phase = new PhaseTemplate();
        $phase->forceFill(['id' => 5]);
        $phase->setRelation('exits', collect([$exit]));

        $nodeModel = new TournamentPhaseNode();
        $nodeModel->forceFill(['id' => 10]);
        $nodeModel->setRelation('phaseTemplate', $phase);

        $template = new TournamentTemplate();
        $template->setRelation('graphNodes', collect([$nodeModel]));
        $template->setRelation('graphConnections', collect());

        $state = [
            'nodes' => [
                10 => [
                    'id' => 10,
                    'name' => 'Eliminación',
                    'participant_ids' => ['P1', 'P2'],
                    'status' => 'COMPLETED',
                    'runtime' => [
                        'status' => 'COMPLETED',
                        'outcomes' => [
                            [
                                'exit_id' => 50,
                                'exit_name' => 'Clasificados',
                                'participant_ids' => ['P1'],
                            ],
                        ],
                        'timed_outcomes' => [],
                        'standings' => [
                            [
                                'position' => 1,
                                'position_from' => 1,
                                'position_to' => 1,
                                'participant_id' => 'P1',
                            ],
                            [
                                'position' => 2,
                                'position_from' => 2,
                                'position_to' => 2,
                                'participant_id' => 'P2',
                            ],
                        ],
                    ],
                ],
            ],
            'connections' => [],
            'participants' => [
                'P1' => $this->participant('P1'),
                'P2' => $this->participant('P2'),
            ],
            'graph_runtime' => [
                'stranded_participant_ids' => [],
                'operation_queue' => [],
                'processed_operations' => [],
                'diagnostics' => [],
            ],
            'timeline' => [],
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('debe producir exactamente 2 participante(s), pero produjo 1');

        $this->invokePrivate(
            $service,
            'routeNode',
            [$state, $template, 10]
        );
    }

    private function timedEliminationFixture(
        string $selectorType = 'ELIMINATED',
        ?int $roundSize = null
    ): array {
        $service = $this->service();
        $exit = $this->exit(50, $selectorType, 'ON_ELIMINATION', $roundSize);

        $phase = new PhaseTemplate();
        $phase->forceFill(['id' => 5]);
        $phase->setRelation('exits', collect([$exit]));

        $nodeModel = new TournamentPhaseNode();
        $nodeModel->forceFill(['id' => 10]);
        $nodeModel->setRelation('phaseTemplate', $phase);

        $connection = new TournamentPhaseConnection();
        $connection->forceFill([
            'id' => 70,
            'sequence_number' => 1,
            'code' => 'CON070',
            'label' => 'Eliminados → Terminal',
            'source_type' => 'PHASE_EXIT',
            'source_node_id' => 10,
            'source_phase_exit_id' => 50,
            'target_type' => 'TERMINAL',
            'target_terminal_id' => 99,
            'allocation_mode' => 'ALL',
            'priority' => 10,
            'status' => 'ACTIVE',
        ]);

        $template = new TournamentTemplate();
        $template->setRelation('graphNodes', collect([$nodeModel]));
        $template->setRelation('graphConnections', collect([$connection]));

        $state = [
            'nodes' => [
                10 => [
                    'id' => 10,
                    'code' => 'NOD010',
                    'name' => 'Eliminación',
                    'participant_ids' => ['P1', 'P2', 'P3'],
                    'status' => 'RUNNING',
                    'runtime' => [
                        'status' => 'RUNNING',
                        'eliminated_ids' => ['P2'],
                        'eliminations' => [
                            [
                                'id' => 'ELIMINATION:M1:P2',
                                'participant_id' => 'P2',
                                'round_number' => 1,
                                'round_participants' => 4,
                                'match_id' => 'M1',
                                'source' => 'MATCH_RESULT',
                            ],
                        ],
                        'outcomes' => [],
                        'timed_outcomes' => [],
                        'rounds' => [
                            [
                                'number' => 1,
                                'participants_in_round' => 4,
                            ],
                        ],
                    ],
                ],
            ],
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
                    'name' => 'Eliminados',
                    'participant_ids' => [],
                    'received_connection_ids' => [],
                    'expected_participants' => null,
                    'status' => 'WAITING',
                ],
            ],
            'participants' => [
                'P1' => $this->participant('P1'),
                'P2' => $this->participant('P2'),
                'P3' => $this->participant('P3'),
            ],
            'graph_runtime' => [
                'status' => 'RUNNING',
                'operation_queue' => [],
                'processed_operations' => [],
                'operation_count' => 0,
                'diagnostics' => [],
                'stranded_participant_ids' => [],
                'emission_ledger' => [],
                'phase_exit_emissions' => [],
            ],
            'timeline' => [],
            'updated_at' => null,
        ];

        return [$service, $template, $state];
    }

    private function service(): TournamentGraphRuntimeService
    {
        return new TournamentGraphRuntimeService(
            new RuntimeConnectionRouter(),
            new RuntimeOutcomeResolver(),
            $this->createMock(LabPhaseEngineManager::class)
        );
    }

    private function exit(
        int $id,
        string $selectorType,
        string $timing,
        ?int $roundSize = null
    ): PhaseExit {
        $exit = new PhaseExit();
        $exit->forceFill([
            'id' => $id,
            'name' => 'Salida ' . $id,
            'selector_type' => $selectorType,
            'selector_round_size' => $roundSize,
            'resolution_mode' => 'SELECTOR',
            'exit_timing' => $timing,
            'priority' => 10,
            'sort_order' => 10,
            'status' => 'ACTIVE',
        ]);

        return $exit;
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

    private function invokePrivate(
        object $object,
        string $method,
        array $arguments
    ): mixed {
        $reflection = new ReflectionMethod(
            $object,
            $method
        );

        $reflection->setAccessible(true);

        return $reflection->invokeArgs(
            $object,
            $arguments
        );
    }
}
