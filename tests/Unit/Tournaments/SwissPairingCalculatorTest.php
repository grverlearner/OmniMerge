<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseSwissSetting;
use App\Services\Tournaments\Swiss\SwissPairingCalculator;
use PHPUnit\Framework\TestCase;

class SwissPairingCalculatorTest extends TestCase
{
    private function settings(
        array $override = []
    ): PhaseSwissSetting {
        return new PhaseSwissSetting(
            array_merge(
                [
                    'completion_mode' =>
                    'FIXED_ROUNDS',

                    'fixed_rounds' =>
                    5,

                    'pairing_algorithm' =>
                    'OMNIMERGE_SCORE_GROUP',

                    'pairing_basis' =>
                    'MATCH_POINTS',

                    'first_round_mode' =>
                    'SEEDED_HALVES',

                    'rematch_policy' =>
                    'STRICT_NO_REMATCH',

                    'floater_policy' =>
                    'MINIMIZE_SCORE_GAP',

                    'side_balance_policy' =>
                    'PREFER_BALANCE',

                    'allow_draws' =>
                    false,

                    'default_best_of' =>
                    1,

                    'bye_policy' =>
                    'LOWEST_STANDING_WITHOUT_BYE',

                    'bye_points' =>
                    1,

                    'max_byes_per_participant' =>
                    1,

                    'acceleration_mode' =>
                    'NONE',
                ],
                $override
            )
        );
    }

    private function sampleParticipants(
        int $count
    ): array {
        $participants = [];

        for (
            $seed = 1;
            $seed <= $count;
            $seed++
        ) {
            $participants[] = [
                'id' => $seed,
                'seed' => $seed,
                'label' => 'Seed ' . $seed,

                'wins' => 0,
                'draws' => 0,
                'losses' => 0,

                'standing_score' => 0,
                'pairing_score' => 0,

                'opponents' => [],

                'bye_count' => 0,

                'side_a_count' => 0,
                'side_b_count' => 0,

                'active' => true,
            ];
        }

        return $participants;
    }

    public function test_seeded_halves_first_round(): void
    {
        $calculator =
            new SwissPairingCalculator();

        $result =
            $calculator->generateRound(
                $this->sampleParticipants(8),
                $this->settings(),
                1
            );

        $this->assertTrue(
            $result['valid']
        );

        $pairs =
            array_map(
                fn($pairing) => [
                    $pairing['participant_a']['seed'],

                    $pairing['participant_b']['seed'],
                ],
                $result['pairings']
            );

        $normalized = [];

        foreach (
            $pairs
            as
            $pair
        ) {
            sort($pair);

            $normalized[] =
                $pair;
        }

        $this->assertSame(
            [
                [1, 5],
                [2, 6],
                [3, 7],
                [4, 8],
            ],
            $normalized
        );
    }

    public function test_odd_field_receives_one_bye(): void
    {
        $calculator =
            new SwissPairingCalculator();

        $result =
            $calculator->generateRound(
                $this->sampleParticipants(5),
                $this->settings(),
                1
            );

        $this->assertTrue(
            $result['valid']
        );

        $this->assertNotNull(
            $result['bye']
        );

        $this->assertSame(
            5,
            $result['bye']['seed']
        );

        $this->assertCount(
            2,
            $result['pairings']
        );
    }

    public function test_strict_policy_avoids_previous_opponents(): void
    {
        $participants =
            $this->sampleParticipants(
                4
            );

        foreach (
            $participants
            as
            &$participant
        ) {
            $participant['standing_score'] =
                1;
        }

        unset($participant);

        /*
         * 1 ya jugó con 2.
         * 3 ya jugó con 4.
         */
        $participants[0]['opponents'] = [2];
        $participants[1]['opponents'] = [1];

        $participants[2]['opponents'] = [4];
        $participants[3]['opponents'] = [3];

        $calculator =
            new SwissPairingCalculator();

        $result =
            $calculator->generateRound(
                $participants,
                $this->settings(),
                2
            );

        $this->assertTrue(
            $result['valid']
        );

        foreach (
            $result['pairings']
            as
            $pairing
        ) {
            $this->assertFalse(
                $pairing['is_rematch']
            );
        }
    }
}
