<?php

namespace Tests\Feature\Tournaments;

use App\Models\PhaseTemplate;
use App\Models\User;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureService;
use App\Services\Tournaments\SingleElimination\Visualization\SingleEliminationStructureEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SingleEliminationStructureEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_an_encounter_and_marks_it_as_manual(): void
    {
        $phase =
            $this->createGeneratedPhase(
                'Editor principal'
            );

        $encounter =
            $phase
            ->singleEliminationEncounters()
            ->orderBy(
                'sequence_number'
            )
            ->firstOrFail();

        $result =
            app(
                SingleEliminationStructureService::class
            )
            ->updateElement(
                $phase,
                'ENCOUNTER',
                $encounter->id,
                [
                    'name' =>
                    'Encuentro personalizado',

                    'description' =>
                    'Encuentro modificado desde el inspector visual.',

                    'status' =>
                    'ACTIVE',

                    'is_locked' =>
                    true,
                ]
            );

        $encounter->refresh();

        $this->assertSame(
            'Encuentro personalizado',
            $encounter->name
        );

        $this->assertSame(
            'Encuentro modificado desde el inspector visual.',
            $encounter->description
        );

        $this->assertSame(
            'ACTIVE',
            $encounter->status
        );

        $this->assertSame(
            'MANUAL',
            $encounter->generation_source
        );

        $this->assertTrue(
            $encounter->is_locked
        );

        $this->assertSame(
            $encounter->id,
            $result['element']->id
        );

        $this->assertTrue(
            $result['validation']['valid']
        );

        $this->assertDatabaseHas(
            'phase_single_elimination_encounters',
            [
                'id' =>
                $encounter->id,

                'phase_template_id' =>
                $phase->id,

                'name' =>
                'Encuentro personalizado',

                'status' =>
                'ACTIVE',

                'generation_source' =>
                'MANUAL',

                'is_locked' =>
                true,
            ]
        );
    }

    public function test_it_rejects_an_element_that_belongs_to_another_phase(): void
    {
        $firstPhase =
            $this->createGeneratedPhase(
                'Primera fase'
            );

        $secondPhase =
            $this->createGeneratedPhase(
                'Segunda fase'
            );

        $foreignEncounter =
            $secondPhase
            ->singleEliminationEncounters()
            ->orderBy(
                'sequence_number'
            )
            ->firstOrFail();

        $this->expectException(
            ValidationException::class
        );

        app(
            SingleEliminationStructureEditor::class
        )
            ->update(
                $firstPhase,
                'ENCOUNTER',
                $foreignEncounter->id,
                [
                    'name' =>
                    'Edición no permitida',

                    'description' =>
                    null,

                    'status' =>
                    'ACTIVE',

                    'is_locked' =>
                    false,
                ]
            );
    }

    private function createGeneratedPhase(
        string $name
    ): PhaseTemplate {
        static $sequence =
        0;

        $sequence++;

        $user =
            User::factory()
            ->create();

        $phase =
            $user
            ->phaseTemplates()
            ->create([
                'sequence_number' =>
                $sequence,

                'code' =>
                sprintf(
                    'PHS%06d',
                    $sequence
                ),

                'name' =>
                $name,

                'slug' =>
                'fase-editor-'
                    .
                    $sequence,

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
                4
            );

        return $phase->fresh();
    }
}
