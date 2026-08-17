<?php

namespace Tests\Feature\Tournaments;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use App\Models\User;
use App\Services\Tournaments\CompetitionLab\Engines\SingleEliminationGraphRuntime;
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureFingerprint;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SingleEliminationAdvancedRuntimeContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_executable_structure_persists_canonical_fingerprint_and_runtime_can_prepare(): void
    {
        $phase =
            $this->createAdvancedPhase(
                4
            );

        $result =
            app(
                SingleEliminationStructureService::class
            )
            ->generate(
                $phase,
                4
            );

        $phase->refresh();

        $settings =
            $phase
            ->singleEliminationSetting()
            ->firstOrFail();

        $this->assertTrue(
            $result['validation']['valid']
        );

        $this->assertTrue(
            $result['validation']['executable']
        );

        $this->assertSame(
            'VALID',
            $settings->structure_status
        );

        $this->assertNotEmpty(
            $settings->structure_fingerprint
        );

        $this->assertSame(
            $settings->structure_fingerprint,
            $result['validation']['fingerprint']
        );

        $this->assertSame(
            $settings->structure_fingerprint,
            $result['generation']['fingerprint']
        );

        $runtime =
            app(
                SingleEliminationGraphRuntime::class
            )
            ->prepare(
                $phase->fresh(),
                $this->participantIds(4)
            );

        $this->assertSame(
            'RUNNING',
            $runtime['status']
        );

        $this->assertSame(
            $settings->structure_fingerprint,
            $runtime['structure_fingerprint']
        );

        $this->assertTrue(
            collect($runtime['rounds'])
                ->flatMap(
                    fn(array $round) =>
                    $round['matches']
                )
                ->contains(
                    fn(array $match) =>
                    $match['status'] === 'PENDING'
                )
        );
    }

    public function test_invalid_structure_is_persisted_as_invalid_after_revalidation(): void
    {
        $phase =
            $this->createAdvancedPhase(
                4
            );

        $service =
            app(
                SingleEliminationStructureService::class
            );

        $service->generate(
            $phase,
            4
        );

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
            $service
            ->validateAndPersist(
                $phase->fresh()
            );

        $this->assertFalse(
            $validation['valid']
        );

        $this->assertSame(
            'INVALID',
            $validation['structure_status']
        );

        $this->assertSame(
            'INVALID',
            $phase
                ->singleEliminationSetting()
                ->firstOrFail()
                ->structure_status
        );
    }

    public function test_configuration_change_marks_valid_structure_stale_and_runtime_refuses_it(): void
    {
        $phase =
            $this->createAdvancedPhase(
                4
            );

        app(
            SingleEliminationStructureService::class
        )
            ->generate(
                $phase,
                4
            );

        $settings =
            $phase
            ->singleEliminationSetting()
            ->firstOrFail();

        $data =
            $settings->only([
                'configuration_mode',
                'input_mode',
                'routing_mode',
                'entrants_per_match',
                'qualifiers_per_match',
                'encounter_profile',
                'remainder_policy',
                'completion_mode',
                'target_survivors',
                'seeding_mode',
                'pairing_mode',
                'bye_assignment',
                'reseed_each_round',
                'series_format',
                'default_best_of',
                'fixed_games',
            ]);

        $data['default_best_of'] =
            3;

        app(
            SingleEliminationSettingsService::class
        )
            ->update(
                $phase->fresh(),
                $data
            );

        $this->assertSame(
            'STALE',
            $settings->fresh()->structure_status
        );

        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'Estructura desactualizada'
        );

        app(
            SingleEliminationGraphRuntime::class
        )
            ->prepare(
                $phase->fresh(),
                $this->participantIds(4)
            );
    }

    public function test_runtime_rejects_structure_whose_fingerprint_changed_after_validation(): void
    {
        $phase =
            $this->createAdvancedPhase(
                4
            );

        app(
            SingleEliminationStructureService::class
        )
            ->generate(
                $phase,
                4
            );

        $connection =
            $phase
            ->singleEliminationConnections()
            ->orderBy('id')
            ->firstOrFail();

        $connection->update([
            'priority' =>
            (int) $connection->priority
                +
                1,
        ]);

        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'cambió después de su última validación'
        );

        app(
            SingleEliminationGraphRuntime::class
        )
            ->prepare(
                $phase->fresh(),
                $this->participantIds(4)
            );
    }

    public function test_valid_but_unsupported_placeholder_structure_is_persisted_as_blocked(): void
    {
        $phase =
            $this->createAdvancedPhase(
                4,
                [
                    'input_mode' =>
                    'GROUPED',
                ]
            );

        $result =
            app(
                SingleEliminationStructureService::class
            )
            ->generate(
                $phase,
                4
            );

        $settings =
            $phase
            ->singleEliminationSetting()
            ->firstOrFail();

        $this->assertTrue(
            $result['validation']['valid']
        );

        $this->assertFalse(
            $result['validation']['executable']
        );

        $this->assertSame(
            'BLOCKED',
            $result['validation']['structure_status']
        );

        $this->assertSame(
            'BLOCKED',
            $settings->fresh()->structure_status
        );

        $this->assertSame(
            'Válida, pero no ejecutable',
            $settings->fresh()->structure_status_label
        );

        $this->assertContains(
            'INPUT_PLACEHOLDER_REQUIRES_REVIEW',
            collect(
                $result['validation']['blocking_issues']
            )
                ->pluck('code')
                ->all()
        );

        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'no está lista para ejecutarse'
        );

        app(
            SingleEliminationGraphRuntime::class
        )
            ->prepare(
                $phase->fresh(),
                $this->participantIds(4)
            );
    }

    public function test_duplicate_graph_participants_are_rejected_instead_of_deduplicated(): void
    {
        $phase =
            $this->createAdvancedPhase(
                4
            );

        app(
            SingleEliminationStructureService::class
        )
            ->generate(
                $phase,
                4
            );

        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'participantes duplicados'
        );

        app(
            SingleEliminationGraphRuntime::class
        )
            ->prepare(
                $phase->fresh(),
                [
                    'P1',
                    'P2',
                    'P2',
                    'P4',
                ]
            );
    }

    public function test_k_to_q_selection_is_ordered_and_duplicate_qualifiers_are_rejected(): void
    {
        $phase =
            $this->createAdvancedPhase(
                4,
                [
                    'entrants_per_match' =>
                    4,

                    'qualifiers_per_match' =>
                    2,

                    'encounter_profile' =>
                    'MULTI_COMPETITOR',

                    'remainder_policy' =>
                    'REJECT',

                    'completion_mode' =>
                    'SURVIVORS',

                    'target_survivors' =>
                    2,
                ]
            );

        app(
            SingleEliminationStructureService::class
        )
            ->generate(
                $phase,
                4
            );

        $engine =
            app(
                SingleEliminationGraphRuntime::class
            );

        $runtime =
            $engine
            ->prepare(
                $phase->fresh(),
                $this->participantIds(4)
            );

        $match =
            collect(
                $runtime['rounds']
            )
            ->flatMap(
                fn(array $round) =>
                $round['matches']
            )
            ->first();

        $this->assertIsArray(
            $match
        );

        $this->assertSame(
            2,
            $match['qualifiers_count']
        );

        try {
            $engine->submitSelection(
                $runtime,
                $match['id'],
                [
                    'P3',
                    'P3',
                ]
            );

            $this->fail(
                'La selección duplicada debía ser rechazada.'
            );
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'clasificados duplicados',
                $exception->getMessage()
            );
        }

        $runtime =
            $engine
            ->submitSelection(
                $runtime,
                $match['id'],
                [
                    'P3',
                    'P1',
                ]
            );

        $this->assertSame(
            'COMPLETED',
            $runtime['status']
        );

        $this->assertSame(
            [
                'P3',
                'P1',
            ],
            $runtime['survivor_ids']
        );

        $this->assertEqualsCanonicalizing(
            [
                'P2',
                'P4',
            ],
            $runtime['eliminated_ids']
        );
    }

    public function test_incomplete_advanced_encounter_becomes_pending_at_its_persisted_minimum(): void
    {
        $phase =
            $this->createAdvancedPhase(
                5,
                [
                    'entrants_per_match' =>
                    3,

                    'qualifiers_per_match' =>
                    1,

                    'encounter_profile' =>
                    'MULTI_COMPETITOR',

                    'remainder_policy' =>
                    'INCOMPLETE_MATCH',

                    'completion_mode' =>
                    'SURVIVORS',

                    'target_survivors' =>
                    2,
                ]
            );

        $result =
            app(
                SingleEliminationStructureService::class
            )
            ->generate(
                $phase,
                5
            );

        $this->assertTrue(
            $result['validation']['valid']
        );

        $this->assertTrue(
            $result['validation']['executable']
        );

        $runtime =
            app(
                SingleEliminationGraphRuntime::class
            )
            ->prepare(
                $phase->fresh(),
                $this->participantIds(5)
            );

        $matches =
            collect(
                $runtime['rounds'][0]['matches']
            )
            ->sortBy('number')
            ->values();

        $this->assertCount(
            2,
            $matches
        );

        $this->assertSame(
            'PENDING',
            $matches[0]['status']
        );

        $this->assertSame(
            'PENDING',
            $matches[1]['status']
        );

        $secondEncounterId =
            (int) str_replace(
                'SE-G-',
                '',
                $matches[1]['id']
            );

        $secondEncounter =
            $runtime['encounters'][$secondEncounterId];

        $this->assertTrue(
            $secondEncounter['allows_incomplete']
        );

        $this->assertSame(
            2,
            $secondEncounter['min_entrants_to_start']
        );

        $this->assertCount(
            2,
            $secondEncounter['participant_ids']
        );
    }

    public function test_advanced_bye_bypass_is_executable_without_fake_participants(): void
    {
        $phase =
            $this->createAdvancedPhase(
                5,
                [
                    'remainder_policy' =>
                    'BYE',
                ]
            );

        app(
            SingleEliminationStructureService::class
        )
            ->generate(
                $phase,
                5
            );

        $runtime =
            app(
                SingleEliminationGraphRuntime::class
            )
            ->prepare(
                $phase->fresh(),
                $this->participantIds(5)
            );

        $assignedIds =
            collect(
                $runtime['slots']
            )
            ->pluck('participant_id')
            ->filter()
            ->values()
            ->all();

        $this->assertCount(
            5,
            $assignedIds
        );

        $this->assertEqualsCanonicalizing(
            $this->participantIds(5),
            $assignedIds
        );

        $this->assertSame(
            count($assignedIds),
            count(array_unique($assignedIds))
        );

        $this->assertSame(
            'RUNNING',
            $runtime['status']
        );
    }

    public function test_cosmetic_encounter_name_change_does_not_invalidate_competitive_fingerprint(): void
    {
        $phase =
            $this->createAdvancedPhase(
                4
            );

        app(
            SingleEliminationStructureService::class
        )
            ->generate(
                $phase,
                4
            );

        $settings =
            $phase
            ->singleEliminationSetting()
            ->firstOrFail();

        $stored =
            $settings->structure_fingerprint;

        $encounter =
            $phase
            ->singleEliminationEncounters()
            ->orderBy('sequence_number')
            ->firstOrFail();

        $encounter->update([
            'name' =>
            'Nombre visual actualizado',
        ]);

        $current =
            app(
                SingleEliminationStructureFingerprint::class
            )
            ->forPhase(
                $phase->fresh()
            );

        $this->assertSame(
            $stored,
            $current
        );

        $runtime =
            app(
                SingleEliminationGraphRuntime::class
            )
            ->prepare(
                $phase->fresh(),
                $this->participantIds(4)
            );

        $this->assertSame(
            'RUNNING',
            $runtime['status']
        );
    }

    public function test_working_participant_preference_does_not_invalidate_structure_fingerprint(): void
    {
        $phase =
            $this->createAdvancedPhase(
                4
            );

        app(
            SingleEliminationStructureService::class
        )
            ->generate(
                $phase,
                4
            );

        $settings =
            $phase
            ->singleEliminationSetting()
            ->firstOrFail();

        $stored =
            $settings->structure_fingerprint;

        app(
            SingleEliminationSettingsService::class
        )
            ->rememberParticipants(
                $phase->fresh(),
                8
            );

        $current =
            app(
                SingleEliminationStructureFingerprint::class
            )
            ->forPhase(
                $phase->fresh()
            );

        $this->assertSame(
            $stored,
            $current
        );

        $runtime =
            app(
                SingleEliminationGraphRuntime::class
            )
            ->prepare(
                $phase->fresh(),
                $this->participantIds(4)
            );

        $this->assertSame(
            'RUNNING',
            $runtime['status']
        );
    }

    private function createAdvancedPhase(
        int $participants,
        array $overrides = []
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
                'Single Elimination Advanced Contract',

                'slug' =>
                'single-elimination-advanced-contract',

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

        $settings =
            new PhaseSingleEliminationSetting();

        $settings->forceFill(
            array_merge(
                [
                    'configuration_mode' =>
                    'ADVANCED',

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
                ],
                $overrides
            )
        );

        $phase
            ->singleEliminationSetting()
            ->create(
                $settings->getAttributes()
            );

        return $phase->fresh();
    }

    private function participantIds(
        int $count
    ): array {
        return array_map(
            fn(int $index) =>
            'P'
                .
                $index,
            range(
                1,
                $count
            )
        );
    }
}
