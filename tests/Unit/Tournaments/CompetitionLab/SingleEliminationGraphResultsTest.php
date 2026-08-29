<?php

namespace Tests\Unit\Tournaments\CompetitionLab;

use App\Services\Tournaments\CompetitionLab\Engines\SingleEliminationGraphRuntime;
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureExecutionPolicy;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureFingerprint;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureValidator;
use Tests\TestCase;
use App\Services\Tournaments\CompetitionLab\Runtime\PlacementPlanner;

class SingleEliminationGraphResultsTest extends TestCase
{
    public function test_k_to_q_produces_ordered_results_canonical_eliminations_and_placement_bands(): void
    {
        $engine = $this->engine();

        $runtime = $engine->submitSelection(
            $this->runtime(),
            'SE-G-10',
            ['P3', 'P1']
        );

        $this->assertSame(
            ['P3', 'P1'],
            $runtime['encounters'][10]['qualifier_ids']
        );

        $this->assertSame(
            ['P2', 'P4'],
            $runtime['encounters'][10]['eliminated_ids']
        );

        $this->assertSame(
            ['P3', 'P1'],
            $runtime['exit_participants'][100]
        );

        $this->assertSame(
            ['P2', 'P4'],
            $runtime['exit_participants'][200]
        );

        $this->assertSame('COMPLETED', $runtime['status']);
        $this->assertCount(2, $runtime['eliminations']);

        $this->assertSame(
            [
                'ELIMINATION:SE-G-10:P2',
                'ELIMINATION:SE-G-10:P4',
            ],
            array_column($runtime['eliminations'], 'id')
        );

        foreach ($runtime['eliminations'] as $event) {
            $this->assertSame(1, $event['round_number']);
            $this->assertSame(4, $event['round_participants']);
            $this->assertSame('SE-G-10', $event['match_id']);
            $this->assertSame('MATCH_RESULT', $event['source']);
        }

        $standings = collect($runtime['standings'])->keyBy('participant_id');

        foreach (['P3', 'P1'] as $participantId) {
            $this->assertSame(1, $standings[$participantId]['position_from']);
            $this->assertSame(2, $standings[$participantId]['position_to']);
            $this->assertSame(
                'UNRANKED_SURVIVOR',
                $standings[$participantId]['placement_status']
            );
        }

        foreach (['P2', 'P4'] as $participantId) {
            $this->assertSame(3, $standings[$participantId]['position_from']);
            $this->assertSame(4, $standings[$participantId]['position_to']);
            $this->assertSame(
                'TIED_BAND',
                $standings[$participantId]['placement_status']
            );
        }
    }

    public function test_same_participant_cannot_be_eliminated_twice(): void
    {
        $runtime = $this->runtime();
        $runtime['eliminations'][] = [
            'id' => 'ELIMINATION:OLD:P2',
            'participant_id' => 'P2',
            'round_number' => 0,
            'match_id' => 'OLD',
            'source' => 'MATCH_RESULT',
        ];
        $runtime['eliminated_ids'][] = 'P2';

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('P2 ya había sido eliminado');

        $this->engine()->submitSelection(
            $runtime,
            'SE-G-10',
            ['P3', 'P1']
        );
    }

    private function engine(): SingleEliminationGraphRuntime
    {
        return new SingleEliminationGraphRuntime(
            $this->createMock(SingleEliminationStructureValidator::class),
            new SingleEliminationStructureExecutionPolicy(),
            new SingleEliminationStructureFingerprint(),
            new SingleEliminationSettingsService(),
            new PlacementPlanner()
        );
    }

    private function runtime(): array
    {
        return [
            'engine' => 'SINGLE_ELIMINATION',
            'mode' => 'STRUCTURE_GRAPH',
            'status' => 'RUNNING',
            'rounds' => [
                [
                    'id' => 1,
                    'number' => 1,
                    'participants_in_round' => 4,
                    'label' => 'Ronda de 4',
                    'status' => 'RUNNING',
                    'matches' => [
                        [
                            'id' => 'SE-G-10',
                            'number' => 1,
                            'round_number' => 1,
                            'round_participants' => 4,
                            'participant_ids' => ['P1', 'P2', 'P3', 'P4'],
                            'qualifiers_count' => 2,
                            'status' => 'PENDING',
                        ],
                    ],
                ],
            ],
            'round_order' => [1],
            'encounters' => [
                10 => [
                    'id' => 10,
                    'round_id' => 1,
                    'round_number' => 1,
                    'round_participants' => 4,
                    'match_id' => 'SE-G-10',
                    'code' => 'ENC010',
                    'name' => 'Ronda de 4 · Encuentro 1',
                    'entrants_count' => 4,
                    'qualifiers_count' => 2,
                    'min_entrants_to_start' => 4,
                    'allows_incomplete' => false,
                    'activation_policy' => 'ALL_REQUIRED',
                    'profile' => 'MULTI_COMPETITOR',
                    'resolution_mode' => 'RANKING',
                    'qualifier_ordering' => 'ORDERED',
                    'series_format' => null,
                    'best_of' => null,
                    'fixed_games' => null,
                    'status' => 'PENDING',
                    'participant_ids' => ['P1', 'P2', 'P3', 'P4'],
                    'qualifier_ids' => [],
                    'eliminated_ids' => [],
                    'score_a' => null,
                    'score_b' => null,
                ],
            ],
            'slots' => [],
            'results' => [
                101 => [
                    'id' => 101,
                    'encounter_id' => 10,
                    'name' => 'Posición 1',
                    'position_from' => 1,
                    'position_to' => 1,
                    'quantity' => 1,
                    'flow_mode' => 'CONSUME',
                    'result_type' => 'POSITION',
                    'participant_status' => 'ACTIVE',
                ],
                102 => [
                    'id' => 102,
                    'encounter_id' => 10,
                    'name' => 'Posición 2',
                    'position_from' => 2,
                    'position_to' => 2,
                    'quantity' => 1,
                    'flow_mode' => 'CONSUME',
                    'result_type' => 'POSITION',
                    'participant_status' => 'ACTIVE',
                ],
                103 => [
                    'id' => 103,
                    'encounter_id' => 10,
                    'name' => 'Eliminados',
                    'position_from' => 3,
                    'position_to' => 4,
                    'quantity' => 2,
                    'flow_mode' => 'CONSUME',
                    'result_type' => 'ELIMINATED',
                    'participant_status' => 'ELIMINATED',
                ],
            ],
            'connections' => [
                201 => $this->connection(201, 101, 100, 10),
                202 => $this->connection(202, 102, 100, 20),
                203 => $this->connection(203, 103, 200, 30),
            ],
            'exit_definitions' => [
                100 => [
                    'id' => 100,
                    'name' => 'Supervivientes',
                    'selector_type' => 'SURVIVORS',
                ],
                200 => [
                    'id' => 200,
                    'name' => 'Eliminados',
                    'selector_type' => 'ELIMINATED',
                ],
            ],
            'exit_participants' => [
                100 => [],
                200 => [],
            ],
            'outcomes' => [],
            'standings' => [],
            'survivor_ids' => [],
            'eliminated_ids' => [],
            'eliminations' => [],
            'matches_total' => 1,
            'matches_completed' => 0,
            'current_round' => 1,
        ];
    }

    private function connection(
        int $id,
        int $resultId,
        int $exitId,
        int $priority
    ): array {
        return [
            'id' => $id,
            'source_type' => 'RESULT',
            'source_id' => $resultId,
            'target_type' => 'PHASE_EXIT',
            'target_id' => $exitId,
            'allocation_mode' => 'ALL',
            'allocation_value' => null,
            'priority' => $priority,
        ];
    }
}
