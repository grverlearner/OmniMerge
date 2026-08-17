<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseExit;
use App\Services\Tournaments\CompetitionLab\Runtime\RuntimeOutcomeResolver;
use App\Services\Tournaments\Graph\Flow\EntryPortMergePolicy;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PriorityOneParityTest extends TestCase
{
    public function test_merge_policy_append_keeps_all_sources_in_priority_order(): void
    {
        $this->assertSame(
            ['A', 'B', 'C'],
            EntryPortMergePolicy::merge(
                'APPEND',
                [20, 10],
                [10, 20],
                [
                    10 => ['C'],
                    20 => ['A', 'B'],
                ]
            )
        );
    }

    public function test_merge_policy_first_available_matches_preview_priority_order(): void
    {
        $this->assertSame(
            ['A', 'B'],
            EntryPortMergePolicy::merge(
                'FIRST_AVAILABLE',
                [20, 10],
                [10, 20],
                [
                    10 => ['C'],
                    20 => ['A', 'B'],
                ]
            )
        );
    }

    public function test_merge_policy_priority_uses_configured_connection_order(): void
    {
        $this->assertSame(
            ['A', 'B'],
            EntryPortMergePolicy::merge(
                'PRIORITY',
                [20, 10],
                [10, 20],
                [
                    10 => ['C'],
                    20 => ['A', 'B'],
                ]
            )
        );
    }

    public function test_eliminated_in_round_only_returns_losers_from_requested_bracket_size(): void
    {
        $exit = new PhaseExit();
        $exit->id = 7;
        $exit->status = 'ACTIVE';
        $exit->name = 'Eliminados en cuartos';
        $exit->selector_type = 'ELIMINATED_IN_ROUND';
        $exit->selector_round_size = 8;
        $exit->priority = 1;
        $exit->sort_order = 1;

        $runtime = [
            'standings' => [],
            'eliminated_ids' => ['P1', 'P2'],
            'rounds' => [
                [
                    'participants_in_round' => 8,
                    'matches' => [
                        ['loser_id' => 'P1', 'eliminated_ids' => ['P1']],
                    ],
                ],
                [
                    'participants_in_round' => 4,
                    'matches' => [
                        ['loser_id' => 'P2', 'eliminated_ids' => ['P2']],
                    ],
                ],
            ],
        ];

        $resolution = (new RuntimeOutcomeResolver())->resolve(
            new Collection([$exit]),
            $runtime,
            ['P1', 'P2']
        );

        $this->assertSame(['P1'], $resolution['outcomes'][0]['participant_ids']);
    }
}
