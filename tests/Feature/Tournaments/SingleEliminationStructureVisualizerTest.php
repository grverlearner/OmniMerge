<?php

namespace Tests\Feature\Tournaments;

use App\Models\PhaseTemplate;
use App\Models\User;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleEliminationStructureVisualizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_navigable_visualizer_payload(): void
    {
        $phase =
            $this->createGeneratedPhase(
                4
            );

        $payload =
            app(
                SingleEliminationStructureService::class
            )
            ->payload(
                $phase
            );

        $visualizer =
            $payload['visualizer'];

        $this->assertSame(
            $phase->id,
            $visualizer['phase']['id']
        );

        $this->assertSame(
            'PHASE_TEMPLATE',
            $visualizer['phase']['kind']
        );

        $this->assertSame(
            'PHASE_TEMPLATE:'
                .
                $phase->id,
            $visualizer['phase']['key']
        );

        $this->assertIsArray(
            $visualizer['input_gates']
        );

        $this->assertIsArray(
            $visualizer['rounds']
        );

        $this->assertIsArray(
            $visualizer['connections']
        );

        $this->assertIsArray(
            $visualizer['exits']
        );

        $this->assertIsArray(
            $visualizer['issues']
        );

        $this->assertNotEmpty(
            $visualizer['input_gates']
        );

        $this->assertNotEmpty(
            $visualizer['rounds']
        );

        $this->assertNotEmpty(
            $visualizer['connections']
        );

        $this->assertNotEmpty(
            $visualizer['exits']
        );

        $firstGate =
            $visualizer['input_gates'][0];

        $this->assertSame(
            'INPUT_GATE',
            $firstGate['kind']
        );

        $this->assertArrayHasKey(
            'routes',
            $firstGate
        );

        $this->assertNotEmpty(
            $firstGate['routes']
        );

        $firstRound =
            $visualizer['rounds'][0];

        $this->assertSame(
            'ROUND',
            $firstRound['kind']
        );

        $this->assertArrayHasKey(
            'encounters',
            $firstRound
        );

        $this->assertNotEmpty(
            $firstRound['encounters']
        );

        $firstEncounter =
            $firstRound['encounters'][0];

        $this->assertSame(
            'ENCOUNTER',
            $firstEncounter['kind']
        );

        $this->assertArrayHasKey(
            'slots',
            $firstEncounter
        );

        $this->assertArrayHasKey(
            'results',
            $firstEncounter
        );

        $this->assertArrayHasKey(
            'source_labels',
            $firstEncounter
        );

        $this->assertArrayHasKey(
            'destination_labels',
            $firstEncounter
        );

        $this->assertArrayHasKey(
            'route_keys',
            $firstEncounter
        );

        $this->assertNotEmpty(
            $firstEncounter['slots']
        );

        $this->assertNotEmpty(
            $firstEncounter['results']
        );

        $firstSlot =
            $firstEncounter['slots'][0];

        $this->assertSame(
            'SLOT',
            $firstSlot['kind']
        );

        $this->assertArrayHasKey(
            'routes',
            $firstSlot
        );

        $firstResult =
            $firstEncounter['results'][0];

        $this->assertSame(
            'RESULT',
            $firstResult['kind']
        );

        $this->assertArrayHasKey(
            'routes',
            $firstResult
        );

        $firstConnection =
            $visualizer['connections'][0];

        $this->assertSame(
            'CONNECTION',
            $firstConnection['kind']
        );

        $this->assertNotEmpty(
            $firstConnection['source_owner_key']
        );

        $this->assertNotEmpty(
            $firstConnection['target_owner_key']
        );

        $this->assertArrayHasKey(
            'source_label',
            $firstConnection
        );

        $this->assertArrayHasKey(
            'target_label',
            $firstConnection
        );

        $firstExit =
            $visualizer['exits'][0];

        $this->assertSame(
            'PHASE_EXIT',
            $firstExit['kind']
        );

        $this->assertArrayHasKey(
            'routes',
            $firstExit
        );
    }

    private function createGeneratedPhase(
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
                'Visualizador de prueba',

                'slug' =>
                'visualizador-de-prueba',

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

        app(
            SingleEliminationStructureService::class
        )
            ->generate(
                $phase,
                $participants
            );

        return $phase->fresh();
    }
}
