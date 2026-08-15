<?php

namespace Tests\Feature\Tournaments;

use App\Models\PhaseSingleEliminationConnection;
use App\Models\PhaseTemplate;
use App\Models\User;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureService;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleEliminationStructureValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_detects_a_required_slot_without_source(): void
    {
        $phase =
            $this->createGeneratedPhase();

        $slot =
            $phase
            ->singleEliminationEncounters()
            ->orderBy('sequence_number')
            ->firstOrFail()
            ->slots()
            ->orderBy('position')
            ->firstOrFail();

        $phase
            ->singleEliminationConnections()
            ->where(
                'target_slot_id',
                $slot->id
            )
            ->delete();

        $validation =
            app(
                SingleEliminationStructureValidator::class
            )
            ->validate(
                $phase->fresh()
            );

        $codes =
            collect(
                $validation['errors']
            )
            ->pluck('code');

        $this->assertFalse(
            $validation['valid']
        );

        $this->assertTrue(
            $codes->contains(
                'REQUIRED_SLOT_WITHOUT_SOURCE'
            )
        );
    }

    public function test_it_detects_an_internal_cycle(): void
    {
        $phase =
            $this->createGeneratedPhase();

        $firstEncounter =
            $phase
            ->singleEliminationEncounters()
            ->orderBy('sequence_number')
            ->firstOrFail();

        $firstSlot =
            $firstEncounter
            ->slots()
            ->orderBy('position')
            ->firstOrFail();

        $finalEncounter =
            $phase
            ->singleEliminationEncounters()
            ->orderByDesc('sequence_number')
            ->firstOrFail();

        $finalWinner =
            $finalEncounter
            ->results()
            ->where(
                'participant_status',
                'ACTIVE'
            )
            ->firstOrFail();

        $sequence =
            (
                (int)
                $phase
                    ->singleEliminationConnections()
                    ->max(
                        'sequence_number'
                    )
            )
            +
            1;

        $phase
            ->singleEliminationConnections()
            ->create([
                'sequence_number' =>
                $sequence,

                'code' =>
                PhaseSingleEliminationConnection::formatCode(
                    $sequence
                ),

                'label' =>
                'Conexión circular de prueba',

                'source_type' =>
                'RESULT',

                'source_result_id' =>
                $finalWinner->id,

                'target_type' =>
                'SLOT',

                'target_slot_id' =>
                $firstSlot->id,

                'allocation_mode' =>
                'ALL',

                'priority' =>
                999,

                'condition_type' =>
                'ALWAYS',

                'status' =>
                'ACTIVE',

                'generation_source' =>
                'MANUAL',

                'is_locked' =>
                false,
            ]);

        $validation =
            app(
                SingleEliminationStructureValidator::class
            )
            ->validate(
                $phase->fresh()
            );

        $codes =
            collect(
                $validation['errors']
            )
            ->pluck('code');

        $this->assertFalse(
            $validation['valid']
        );

        $this->assertTrue(
            $codes->contains(
                'INTERNAL_GRAPH_CYCLE'
            )
        );
    }

    private function createGeneratedPhase(): PhaseTemplate
    {
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
                'Validador de prueba',

                'slug' =>
                'validador-de-prueba',

                'phase_type' =>
                'SINGLE_ELIMINATION',

                'participant_mode' =>
                'INDIVIDUAL',

                'min_participants' =>
                2,

                'max_participants' =>
                512,

                'exact_participants' =>
                4,

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
                4
            );

        return $phase->fresh();
    }
}
