<?php

namespace Tests\Feature\Tournaments;

use App\Models\PhaseTemplate;
use App\Models\User;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleEliminationStructureGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_a_classic_eight_participant_structure(): void
    {
        $phase =
            $this->createPhase(
                8
            );

        $result =
            app(
                SingleEliminationStructureService::class
            )
            ->generate(
                $phase,
                8
            );

        $phase->refresh();

        $this->assertTrue(
            $result['validation']['valid']
        );

        $this->assertSame(
            1,
            $phase
                ->inputGates()
                ->count()
        );

        $this->assertSame(
            3,
            $phase
                ->singleEliminationRounds()
                ->count()
        );

        $this->assertSame(
            7,
            $phase
                ->singleEliminationEncounters()
                ->count()
        );

        $this->assertSame(
            22,
            $phase
                ->singleEliminationConnections()
                ->count()
        );

        $this->assertDatabaseHas(
            'phase_single_elimination_settings',
            [
                'phase_template_id' =>
                $phase->id,

                'structure_status' =>
                'VALID',

                'structure_version' =>
                1,
            ]
        );

        $this->assertDatabaseHas(
            'phase_exits',
            [
                'phase_template_id' =>
                $phase->id,

                'selector_type' =>
                'SURVIVORS',

                'resolution_mode' =>
                'INTERNAL_GRAPH',

                'exact_participants' =>
                1,
            ]
        );

        $this->assertDatabaseHas(
            'phase_exits',
            [
                'phase_template_id' =>
                $phase->id,

                'selector_type' =>
                'ELIMINATED',

                'resolution_mode' =>
                'INTERNAL_GRAPH',

                'exact_participants' =>
                7,
            ]
        );
    }

    public function test_it_represents_byes_without_fake_participants(): void
    {
        $phase =
            $this->createPhase(
                6
            );

        $result =
            app(
                SingleEliminationStructureService::class
            )
            ->generate(
                $phase,
                6
            );

        $this->assertTrue(
            $result['validation']['valid']
        );

        $this->assertSame(
            5,
            $phase
                ->singleEliminationEncounters()
                ->count()
        );

        $this->assertSame(
            6,
            $phase
                ->inputGates()
                ->first()
                ->outgoingConnections()
                ->count()
        );

        $firstRound =
            $phase
            ->singleEliminationRounds()
            ->first();

        $this->assertSame(
            2,
            (int)
            (
                $firstRound
                    ->settings['byes']
                ??
                0
            )
        );
    }

    private function createPhase(
        int $participants
    ): PhaseTemplate {
        $user =
            User::factory()
            ->create();

        $phase =
            $user
            ->phaseTemplates()
            ->create([
                'sequence_number' =>
                1,

                'code' =>
                'PHS000001',

                'name' =>
                'Eliminación de prueba',

                'slug' =>
                'eliminacion-de-prueba',

                'phase_type' =>
                'SINGLE_ELIMINATION',

                'participant_mode' =>
                'INDIVIDUAL',

                'min_participants' =>
                2,

                'max_participants' =>
                512,

                'exact_participants' =>
                $participants,

                'participant_multiple' =>
                null,

                'allow_byes' =>
                true,

                'best_of' =>
                1,

                'status' =>
                'DRAFT',

                'visibility' =>
                'PRIVATE',

                'allow_cloning' =>
                true,
            ]);

        $phase
            ->singleEliminationSetting()
            ->create([
                'configuration_mode' =>
                'BASIC',

                'input_mode' =>
                'POOL',

                'routing_mode' =>
                'AUTOMATIC',

                'entrants_per_match' =>
                2,

                'qualifiers_per_match' =>
                1,

                'encounter_profile' =>
                'DUEL',

                'remainder_policy' =>
                'BYE',

                'completion_mode' =>
                'WINNER',

                'target_survivors' =>
                1,

                'seeding_mode' =>
                'INPUT_ORDER',

                'pairing_mode' =>
                'STANDARD_SEEDED',

                'bye_assignment' =>
                'TOP_SEEDS',

                'reseed_each_round' =>
                false,

                'series_format' =>
                'BEST_OF',

                'default_best_of' =>
                1,

                'fixed_games' =>
                1,
            ]);

        return $phase->fresh();
    }
}
