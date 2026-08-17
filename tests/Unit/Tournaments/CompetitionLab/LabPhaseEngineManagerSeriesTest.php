<?php

namespace Tests\Unit\Tournaments\CompetitionLab;

use App\Services\Tournaments\CompetitionLab\Engines\GroupStageLabEngine;
use App\Services\Tournaments\CompetitionLab\Engines\LabManualDecisionManager;
use App\Services\Tournaments\CompetitionLab\Engines\LabPhaseEngineManager;
use App\Services\Tournaments\CompetitionLab\Engines\RoundRobinLabEngine;
use App\Services\Tournaments\CompetitionLab\Engines\SingleEliminationLabEngine;
use App\Services\Tournaments\CompetitionLab\Engines\SwissLabEngine;
use App\Services\Tournaments\CompetitionLab\Runtime\MatchSeriesRuntime;
use App\Services\Tournaments\GroupStage\GroupStageAllocator;
use PHPUnit\Framework\TestCase;

class LabPhaseEngineManagerSeriesTest extends TestCase
{
    public function test_single_elimination_engine_receives_only_the_completed_bo3_series(): void
    {
        $singleElimination =
            $this->createMock(
                SingleEliminationLabEngine::class
            );

        $singleElimination
            ->method('supports')
            ->willReturnCallback(
                fn(string $phaseType): bool =>
                $phaseType
                ===
                'SINGLE_ELIMINATION'
            );

        $singleElimination
            ->expects(
                $this->once()
            )
            ->method('submit')
            ->with(
                $this->callback(
                    fn(array $runtime): bool =>
                    ($runtime['series']['M1']['status'] ?? null)
                        ===
                        'COMPLETED'
                    &&
                    ($runtime['series']['M1']['game_wins_a'] ?? null)
                        ===
                        2
                    &&
                    ($runtime['series']['M1']['game_wins_b'] ?? null)
                        ===
                        0
                ),
                'M1',
                2,
                0
            )
            ->willReturnCallback(
                function (
                    array $runtime,
                    string $matchId,
                    int $scoreA,
                    int $scoreB
                ): array {
                    $runtime['engine_received'] = [
                        'match_id' =>
                        $matchId,

                        'score_a' =>
                        $scoreA,

                        'score_b' =>
                        $scoreB,
                    ];

                    return $runtime;
                }
            );

        $manager =
            $this->manager(
                $singleElimination
            );

        $first =
            $manager->submit(
                'SINGLE_ELIMINATION',
                $this->runtime(),
                'M1',
                4,
                2
            );

        $this->assertArrayNotHasKey(
            'engine_received',
            $first
        );

        $this->assertSame(
            'RUNNING',
            $first['series']['M1']['status']
        );

        $this->assertSame(
            1,
            $first['series']['M1']['game_wins_a']
        );

        $second =
            $manager->submit(
                'SINGLE_ELIMINATION',
                $first,
                'M1',
                3,
                1
            );

        $this->assertSame(
            [
                'match_id' => 'M1',
                'score_a' => 2,
                'score_b' => 0,
            ],
            $second['engine_received']
        );
    }

    private function manager(
        SingleEliminationLabEngine $singleElimination
    ): LabPhaseEngineManager {
        $roundRobin =
            $this->createMock(
                RoundRobinLabEngine::class
            );

        $roundRobin
            ->method('supports')
            ->willReturn(false);

        $groupStage =
            $this->createMock(
                GroupStageLabEngine::class
            );

        $groupStage
            ->method('supports')
            ->willReturn(false);

        $swiss =
            $this->createMock(
                SwissLabEngine::class
            );

        $swiss
            ->method('supports')
            ->willReturn(false);

        $manualDecisions =
            new LabManualDecisionManager(
                $this->createMock(
                    GroupStageAllocator::class
                )
            );

        return new LabPhaseEngineManager(
            $singleElimination,
            $roundRobin,
            $groupStage,
            $swiss,
            $manualDecisions,
            new MatchSeriesRuntime()
        );
    }

    private function runtime(): array
    {
        return [
            'engine' =>
            'SINGLE_ELIMINATION',

            'status' =>
            'RUNNING',

            'rounds' => [
                [
                    'number' =>
                    1,

                    'status' =>
                    'RUNNING',

                    'matches' => [
                        [
                            'id' =>
                            'M1',

                            'participant_a_id' =>
                            'A',

                            'participant_b_id' =>
                            'B',

                            'series_format' =>
                            'BEST_OF',

                            'best_of' =>
                            3,

                            'fixed_games' =>
                            1,

                            'status' =>
                            'PENDING',
                        ],
                    ],
                ],
            ],
        ];
    }
}
