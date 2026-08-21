<?php

namespace Tests\Unit\Tournaments\CompetitionLab;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\CompetitionLab\Engines\SingleEliminationGraphRuntime;
use App\Services\Tournaments\CompetitionLab\Engines\SingleEliminationLabEngine;
use App\Services\Tournaments\SingleElimination\SingleEliminationAdvancedCalculator;
use App\Services\Tournaments\SingleElimination\SingleEliminationBracketCalculator;
use App\Services\Tournaments\SingleElimination\SingleEliminationConfigurationInspector;
use App\Services\Tournaments\SingleElimination\SingleEliminationRoundAvailabilityService;
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
use App\Services\Tournaments\SingleElimination\SingleEliminationValidator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class SingleEliminationLabEngineTest extends TestCase
{
    public function test_standard_seeded_uses_canonical_eight_player_layout(): void
    {
        $engine = $this->engine();
        $phase = $this->phase(8);
        $participants = $this->participantsWithSeeds(8);
        $ids = array_keys($participants);

        $runtime = $engine->prepare($phase, $ids, $participants);

        $this->assertSame(
            [
                ['P1', 'P8'],
                ['P4', 'P5'],
                ['P2', 'P7'],
                ['P3', 'P6'],
            ],
            $this->pairs($runtime['rounds'][0]['matches'])
        );
    }

    public function test_top_seed_byes_are_integrated_into_seeded_topology(): void
    {
        $engine = $this->engine();
        $phase = $this->phase(5);
        $participants = $this->participantsWithSeeds(5);

        $runtime = $engine->prepare(
            $phase,
            array_keys($participants),
            $participants
        );

        $matches = $runtime['rounds'][0]['matches'];

        $this->assertSame(
            [
                ['P1', null],
                ['P4', 'P5'],
                ['P2', null],
                ['P3', null],
            ],
            $this->pairs($matches)
        );
        $this->assertSame(3, $runtime['bye_count']);
        $this->assertSame(1, $runtime['matches_total']);
        $this->assertSame(0, $runtime['matches_completed']);
        $this->assertSame(4, $runtime['structural_matches_total']);
    }

    public function test_fixed_bracket_preserves_match_paths_after_first_round(): void
    {
        $engine = $this->engine();
        $phase = $this->phase(5);
        $participants = $this->participantsWithSeeds(5);

        $runtime = $engine->prepare(
            $phase,
            array_keys($participants),
            $participants
        );

        $runtime = $engine->submit($runtime, 'SE-R1-M2', 1, 0);

        $this->assertSame(2, $runtime['current_round']);
        $this->assertSame(
            [
                ['P1', 'P4'],
                ['P2', 'P3'],
            ],
            $this->pairs($runtime['rounds'][1]['matches'])
        );
    }

    public function test_manual_byes_can_override_top_seeds_without_losing_topology(): void
    {
        $engine = $this->engine();
        $settings = $this->settings([
            'bye_assignment' => 'MANUAL',
        ]);
        $phase = $this->phase(5, $settings);
        $participants = $this->participantsWithSeeds(5);
        $participants['P1']['manual_bye'] = true;
        $participants['P4']['manual_bye'] = true;
        $participants['P5']['manual_bye'] = true;

        $runtime = $engine->prepare(
            $phase,
            array_keys($participants),
            $participants
        );

        $byeRecipients = collect($runtime['rounds'][0]['matches'])
            ->where('status', 'BYE')
            ->pluck('winner_id')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['P1', 'P4', 'P5'], $byeRecipients);
        $this->assertSame(3, $runtime['bye_count']);
    }

    public function test_reseeding_changes_next_round_to_highest_vs_lowest_survivor(): void
    {
        $engine = $this->engine();
        $settings = $this->settings([
            'reseed_each_round' => true,
        ]);
        $phase = $this->phase(8, $settings);
        $participants = $this->participantsWithSeeds(8);

        $runtime = $engine->prepare(
            $phase,
            array_keys($participants),
            $participants
        );

        // Winners: seeds 1, 4, 7 and 6.
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R1-M3', 'P7');
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R1-M1', 'P1');
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R1-M4', 'P6');
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R1-M2', 'P4');

        $this->assertSame(
            [
                ['P1', 'P7'],
                ['P4', 'P6'],
            ],
            $this->pairs($runtime['rounds'][1]['matches'])
        );
    }

    public function test_duplicate_participants_are_rejected_instead_of_silently_deduplicated(): void
    {
        $engine = $this->engine();
        $phase = $this->phase(4);
        $participants = $this->participantsWithSeeds(4);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('participantes duplicados');

        $engine->prepare(
            $phase,
            ['P1', 'P2', 'P2', 'P3'],
            $participants
        );
    }

    public function test_ranking_requires_a_positive_unique_seed_for_every_participant(): void
    {
        $engine = $this->engine();
        $settings = $this->settings([
            'seeding_mode' => 'RANKING',
        ]);
        $phase = $this->phase(4, $settings);
        $participants = $this->participantsWithSeeds(4);
        unset($participants['P3']['seed']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('necesita un seed entero positivo');

        $engine->prepare(
            $phase,
            array_keys($participants),
            $participants
        );
    }

    public function test_ranking_rejects_duplicate_seeds(): void
    {
        $engine = $this->engine();
        $settings = $this->settings([
            'seeding_mode' => 'RANKING',
        ]);
        $phase = $this->phase(4, $settings);
        $participants = $this->participantsWithSeeds(4);
        $participants['P4']['seed'] = 3;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El seed 3 está repetido');

        $engine->prepare(
            $phase,
            array_keys($participants),
            $participants
        );
    }

    public function test_random_seeding_is_reproducible_for_same_phase_and_participants(): void
    {
        $engine = $this->engine();
        $settings = $this->settings([
            'seeding_mode' => 'RANDOM',
        ]);
        $phase = $this->phase(8, $settings);
        $participants = $this->participantsWithSeeds(8);
        $ids = array_keys($participants);

        $first = $engine->prepare($phase, $ids, $participants);
        $second = $engine->prepare($phase, $ids, $participants);

        $this->assertSame($first['seed'], $second['seed']);
        $this->assertSame(
            $this->pairs($first['rounds'][0]['matches']),
            $this->pairs($second['rounds'][0]['matches'])
        );
    }

    public function test_standings_use_placement_bands_independent_of_submit_order(): void
    {
        $engine = $this->engine();
        $phase = $this->phase(8);
        $participants = $this->participantsWithSeeds(8);
        $runtime = $engine->prepare(
            $phase,
            array_keys($participants),
            $participants
        );

        // Cuartos, deliberadamente enviados fuera del orden de los matches.
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R1-M4', 'P3');
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R1-M2', 'P4');
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R1-M1', 'P1');
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R1-M3', 'P2');

        // Semifinales: P2 gana primero, luego P1.
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R2-M2', 'P2');
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R2-M1', 'P1');

        // Final.
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R3-M1', 'P1');

        $this->assertSame('COMPLETED', $runtime['status']);
        $this->assertCount(7, $runtime['eliminations']);

        $byParticipant = collect($runtime['standings'])
            ->keyBy('participant_id');

        $this->assertSame([1, 1], [
            $byParticipant['P1']['position_from'],
            $byParticipant['P1']['position_to'],
        ]);
        $this->assertSame([2, 2], [
            $byParticipant['P2']['position_from'],
            $byParticipant['P2']['position_to'],
        ]);

        foreach (['P3', 'P4'] as $participantId) {
            $this->assertSame([3, 4], [
                $byParticipant[$participantId]['position_from'],
                $byParticipant[$participantId]['position_to'],
            ]);
        }

        foreach (['P5', 'P6', 'P7', 'P8'] as $participantId) {
            $this->assertSame([5, 8], [
                $byParticipant[$participantId]['position_from'],
                $byParticipant[$participantId]['position_to'],
            ]);
        }
    }

    public function test_runtime_counts_match_preview_for_five_participants_to_two_survivors(): void
    {
        $engine = $this->engine();
        $settings = $this->settings([
            'completion_mode' => 'SURVIVORS',
            'target_survivors' => 2,
        ]);
        $phase = $this->phase(5, $settings);
        $participants = $this->participantsWithSeeds(5);

        $preview = (new SingleEliminationBracketCalculator(
            new SingleEliminationValidator(),
            new SingleEliminationAdvancedCalculator()
        ))->calculate(
            $phase,
            $settings,
            5,
            new EloquentCollection()
        );

        $runtime = $engine->prepare(
            $phase,
            array_keys($participants),
            $participants
        );

        $runtime = $this->submitWinner($engine, $runtime, 'SE-R1-M2', 'P4');
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R2-M1', 'P1');
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R2-M2', 'P2');

        $this->assertSame('COMPLETED', $runtime['status']);
        $this->assertSame($preview['initial_byes'], $runtime['bye_count']);
        $this->assertSame($preview['round_count'], count($runtime['rounds']));
        $this->assertSame($preview['total_series'], $runtime['matches_total']);
        $this->assertSame($preview['survivors_count'], count($runtime['survivor_ids']));
    }

    public function test_two_survivors_share_first_place_band_without_fake_runner_up(): void
    {
        $engine = $this->engine();
        $settings = $this->settings([
            'completion_mode' => 'SURVIVORS',
            'target_survivors' => 2,
        ]);
        $phase = $this->phase(8, $settings);
        $participants = $this->participantsWithSeeds(8);
        $runtime = $engine->prepare(
            $phase,
            array_keys($participants),
            $participants
        );

        foreach ([
            'SE-R1-M1' => 'P1',
            'SE-R1-M2' => 'P4',
            'SE-R1-M3' => 'P2',
            'SE-R1-M4' => 'P3',
        ] as $matchId => $winnerId) {
            $runtime = $this->submitWinner($engine, $runtime, $matchId, $winnerId);
        }

        $runtime = $this->submitWinner($engine, $runtime, 'SE-R2-M1', 'P1');
        $runtime = $this->submitWinner($engine, $runtime, 'SE-R2-M2', 'P2');

        $this->assertSame('COMPLETED', $runtime['status']);
        $this->assertSame(['P1', 'P2'], $runtime['survivor_ids']);

        $survivors = collect($runtime['standings'])
            ->where('status', 'SURVIVOR')
            ->values();

        $this->assertCount(2, $survivors);
        foreach ($survivors as $standing) {
            $this->assertSame(1, $standing['position_from']);
            $this->assertSame(2, $standing['position_to']);
            $this->assertSame('UNRANKED_SURVIVOR', $standing['placement_status']);
        }
    }

    private function engine(): SingleEliminationLabEngine
    {
        $validator = new SingleEliminationValidator();
        $inspector = new SingleEliminationConfigurationInspector(
            new SingleEliminationRoundAvailabilityService(),
            $validator
        );

        return new SingleEliminationLabEngine(
            $validator,
            $inspector,
            $this->createMock(SingleEliminationGraphRuntime::class),
            new SingleEliminationSettingsService()
        );
    }

    private function phase(
        int $participants,
        ?PhaseSingleEliminationSetting $settings = null
    ): PhaseTemplate {
        $phase = new PhaseTemplate();
        $phase->forceFill([
            'id' => 3200 + $participants,
            'phase_type' => 'SINGLE_ELIMINATION',
            'min_participants' => 2,
            'max_participants' => 512,
            'exact_participants' => $participants,
            'participant_multiple' => null,
            'allow_byes' => true,
        ]);

        $phase->setRelation(
            'singleEliminationSetting',
            $settings ?? $this->settings()
        );
        $phase->setRelation(
            'singleEliminationRoundRules',
            new EloquentCollection()
        );

        return $phase;
    }

    private function settings(array $overrides = []): PhaseSingleEliminationSetting
    {
        $settings = new PhaseSingleEliminationSetting();
        $settings->forceFill(array_merge([
            'configuration_mode' => 'BASIC',
            'input_mode' => 'POOL',
            'routing_mode' => 'AUTOMATIC',
            'entrants_per_match' => 2,
            'qualifiers_per_match' => 1,
            'encounter_profile' => 'DUEL',
            'remainder_policy' => 'BYE',
            'completion_mode' => 'WINNER',
            'target_survivors' => 1,
            'seeding_mode' => 'RANKING',
            'pairing_mode' => 'STANDARD_SEEDED',
            'bye_assignment' => 'TOP_SEEDS',
            'reseed_each_round' => false,
            'series_format' => 'BEST_OF',
            'default_best_of' => 1,
            'fixed_games' => 1,
        ], $overrides));

        return $settings;
    }

    private function participantsWithSeeds(int $count): array
    {
        $participants = [];

        for ($seed = 1; $seed <= $count; $seed++) {
            $id = 'P' . $seed;
            $participants[$id] = [
                'id' => $id,
                'name' => 'Participant ' . $seed,
                'seed' => $seed,
            ];
        }

        return $participants;
    }

    private function pairs(array $matches): array
    {
        return array_map(
            fn($match) => [
                $match['participant_a_id'],
                $match['participant_b_id'],
            ],
            $matches
        );
    }

    private function submitWinner(
        SingleEliminationLabEngine $engine,
        array $runtime,
        string $matchId,
        string $winnerId
    ): array {
        $match = collect($runtime['rounds'])
            ->flatMap(fn($round) => $round['matches'])
            ->firstWhere('id', $matchId);

        $this->assertIsArray($match, "No existe el match {$matchId}.");
        $this->assertContains($winnerId, [
            $match['participant_a_id'],
            $match['participant_b_id'],
        ]);

        return $winnerId === $match['participant_a_id']
            ? $engine->submit($runtime, $matchId, 1, 0)
            : $engine->submit($runtime, $matchId, 0, 1);
    }
}
