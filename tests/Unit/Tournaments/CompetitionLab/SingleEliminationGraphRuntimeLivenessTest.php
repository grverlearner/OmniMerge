<?php

namespace Tests\Unit\Tournaments\CompetitionLab;

use App\Services\Tournaments\CompetitionLab\Engines\SingleEliminationGraphRuntime;
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureExecutionPolicy;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureFingerprint;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureValidator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use App\Services\Tournaments\CompetitionLab\Runtime\PlacementPlanner;

class SingleEliminationGraphRuntimeLivenessTest extends TestCase
{
    public function test_refresh_rejects_a_waiting_graph_with_no_possible_runtime_progress(): void
    {
        $runtime =
            new SingleEliminationGraphRuntime(
                $this->createMock(
                    SingleEliminationStructureValidator::class
                ),
                new SingleEliminationStructureExecutionPolicy(),
                new SingleEliminationStructureFingerprint(),
                new SingleEliminationSettingsService(),
                new PlacementPlanner()
            );

        $state = [
            'status' =>
            'RUNNING',

            'encounters' => [
                10 => [
                    'id' =>
                    10,

                    'round_id' =>
                    1,

                    'match_id' =>
                    'SE-G-10',

                    'name' =>
                    'Encuentro bloqueado',

                    'entrants_count' =>
                    2,

                    'qualifiers_count' =>
                    1,

                    'min_entrants_to_start' =>
                    2,

                    'allows_incomplete' =>
                    false,

                    'resolution_mode' =>
                    'SCORE',

                    'qualifier_ordering' =>
                    'ORDERED',

                    'series_format' =>
                    'BEST_OF',

                    'best_of' =>
                    1,

                    'fixed_games' =>
                    null,

                    'status' =>
                    'WAITING',

                    'participant_ids' =>
                    [],

                    'qualifier_ids' =>
                    [],

                    'eliminated_ids' =>
                    [],

                    'score_a' =>
                    null,

                    'score_b' =>
                    null,
                ],
            ],

            'slots' => [
                100 => [
                    'id' =>
                    100,

                    'encounter_id' =>
                    10,

                    'position' =>
                    1,

                    'required' =>
                    true,

                    'participant_id' =>
                    null,
                ],

                101 => [
                    'id' =>
                    101,

                    'encounter_id' =>
                    10,

                    'position' =>
                    2,

                    'required' =>
                    true,

                    'participant_id' =>
                    null,
                ],
            ],

            'rounds' => [
                [
                    'id' =>
                    1,

                    'number' =>
                    1,

                    'label' =>
                    'Ronda 1',

                    'status' =>
                    'WAITING',

                    'matches' =>
                    [],
                ],
            ],

            'exit_participants' =>
            [],

            'exit_definitions' =>
            [],

            'eliminated_ids' =>
            [],

            'outcomes' =>
            [],

            'standings' =>
            [],

            'survivor_ids' =>
            [],

            'matches_total' =>
            0,

            'matches_completed' =>
            0,

            'current_round' =>
            1,
        ];

        $method =
            new ReflectionMethod(
                SingleEliminationGraphRuntime::class,
                'refresh'
            );

        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'ninguna transición puede producir nuevos participantes'
        );

        $method->invoke(
            $runtime,
            $state
        );
    }
}
