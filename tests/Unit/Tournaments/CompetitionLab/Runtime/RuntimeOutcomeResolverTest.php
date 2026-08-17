<?php

namespace Tests\Unit\Tournaments\CompetitionLab\Runtime;

use App\Models\PhaseExit;
use App\Services\Tournaments\CompetitionLab\Runtime\RuntimeOutcomeResolver;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RuntimeOutcomeResolverTest extends TestCase
{
    public function test_winner_and_remaining_are_resolved_without_reinterpreting_rankings(): void
    {
        $resolver = new RuntimeOutcomeResolver();

        $winner = $this->exit(1, 'WINNER', 10);
        $remaining = $this->exit(2, 'REMAINING', 20);

        $result = $resolver->resolve(
            collect([$winner, $remaining]),
            [
                'standings' => [
                    $this->standing('P1', 1, 1),
                    $this->standing('P2', 2, 2),
                    $this->standing('P3', 3, 3),
                ],
                'survivor_ids' => ['P1'],
                'eliminated_ids' => ['P2', 'P3'],
            ],
            ['P1', 'P2', 'P3']
        );

        $outcomes = collect($result['outcomes'])->keyBy('exit_id');

        $this->assertSame(['P1'], $outcomes[1]['participant_ids']);
        $this->assertSame(['P2', 'P3'], $outcomes[2]['participant_ids']);
        $this->assertSame([], $result['unassigned_ids']);
    }

    public function test_top_n_cannot_cut_a_tied_placement_band(): void
    {
        $resolver = new RuntimeOutcomeResolver();
        $exit = $this->exit(1, 'TOP_N', 10, selectorFrom: 3);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('TOP_N=3 corta una banda de empate 3–4');

        $resolver->resolve(
            collect([$exit]),
            [
                'standings' => [
                    $this->standing('P1', 1, 1),
                    $this->standing('P2', 2, 2),
                    $this->standing('P3', 3, 4),
                    $this->standing('P4', 3, 4),
                ],
            ],
            ['P1', 'P2', 'P3', 'P4']
        );
    }

    public function test_position_cannot_invent_an_individual_place_inside_a_tied_band(): void
    {
        $resolver = new RuntimeOutcomeResolver();
        $exit = $this->exit(1, 'POSITION', 10, selectorFrom: 3);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('posición 3 pertenece a una banda empatada 3–4');

        $resolver->resolve(
            collect([$exit]),
            [
                'standings' => [
                    $this->standing('P1', 1, 1),
                    $this->standing('P2', 2, 2),
                    $this->standing('P3', 3, 4),
                    $this->standing('P4', 3, 4),
                ],
            ],
            ['P1', 'P2', 'P3', 'P4']
        );
    }

    public function test_rank_range_can_select_a_complete_tied_band(): void
    {
        $resolver = new RuntimeOutcomeResolver();
        $exit = $this->exit(
            1,
            'RANK_RANGE',
            10,
            selectorFrom: 3,
            selectorTo: 4
        );

        $result = $resolver->resolve(
            collect([$exit]),
            [
                'standings' => [
                    $this->standing('P1', 1, 1),
                    $this->standing('P2', 2, 2),
                    $this->standing('P3', 3, 4),
                    $this->standing('P4', 3, 4),
                ],
            ],
            ['P1', 'P2', 'P3', 'P4']
        );

        $this->assertSame(
            ['P3', 'P4'],
            $result['outcomes'][0]['participant_ids']
        );
    }

    public function test_global_selectors_do_not_reinterpret_top_n_after_another_exit_consumed_winner(): void
    {
        $resolver = new RuntimeOutcomeResolver();

        $winner = $this->exit(1, 'WINNER', 10);
        $topTwo = $this->exit(2, 'TOP_N', 20, selectorFrom: 2);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('fan-out competitivo implícito');

        $resolver->resolve(
            collect([$winner, $topTwo]),
            [
                'standings' => [
                    $this->standing('P1', 1, 1),
                    $this->standing('P2', 2, 2),
                    $this->standing('P3', 3, 3),
                ],
            ],
            ['P1', 'P2', 'P3']
        );
    }

    public function test_eliminated_in_round_prefers_canonical_elimination_events(): void
    {
        $resolver = new RuntimeOutcomeResolver();
        $exit = $this->exit(
            1,
            'ELIMINATED_IN_ROUND',
            10,
            selectorRoundSize: 8
        );

        $result = $resolver->resolve(
            collect([$exit]),
            [
                'eliminations' => [
                    [
                        'id' => 'ELIMINATION:M1:P8',
                        'participant_id' => 'P8',
                        'round_number' => 1,
                        'round_participants' => 8,
                        'match_id' => 'M1',
                        'source' => 'MATCH_RESULT',
                    ],
                    [
                        'id' => 'ELIMINATION:M5:P4',
                        'participant_id' => 'P4',
                        'round_number' => 2,
                        'round_participants' => 4,
                        'match_id' => 'M5',
                        'source' => 'MATCH_RESULT',
                    ],
                ],
                'eliminated_ids' => ['P8', 'P4'],
            ],
            ['P1', 'P4', 'P8']
        );

        $this->assertSame(
            ['P8'],
            $result['outcomes'][0]['participant_ids']
        );
    }

    public function test_match_losers_supports_multi_competitor_eliminated_ids(): void
    {
        $resolver = new RuntimeOutcomeResolver();
        $exit = $this->exit(1, 'MATCH_LOSERS', 10);

        $result = $resolver->resolve(
            collect([$exit]),
            [
                'rounds' => [
                    [
                        'matches' => [
                            [
                                'status' => 'COMPLETED',
                                'participant_ids' => ['P1', 'P2', 'P3', 'P4'],
                                'qualifier_ids' => ['P3', 'P1'],
                                'eliminated_ids' => ['P2', 'P4'],
                            ],
                        ],
                    ],
                ],
            ],
            ['P1', 'P2', 'P3', 'P4']
        );

        $this->assertSame(
            ['P2', 'P4'],
            $result['outcomes'][0]['participant_ids']
        );
    }

    private function exit(
        int $id,
        string $selectorType,
        int $priority,
        ?int $selectorFrom = null,
        ?int $selectorTo = null,
        ?int $selectorRoundSize = null
    ): PhaseExit {
        $exit = new PhaseExit();

        $exit->forceFill([
            'id' => $id,
            'name' => 'Exit ' . $id,
            'selector_type' => $selectorType,
            'selector_from' => $selectorFrom,
            'selector_to' => $selectorTo,
            'selector_round_size' => $selectorRoundSize,
            'priority' => $priority,
            'sort_order' => $priority,
            'status' => 'ACTIVE',
            'exit_timing' => 'PHASE_END',
        ]);

        return $exit;
    }

    private function standing(
        string $participantId,
        int $from,
        int $to
    ): array {
        return [
            'position' => $from,
            'position_from' => $from,
            'position_to' => $to,
            'participant_id' => $participantId,
        ];
    }
}
