<?php

namespace Tests\Unit\Tournaments\CompetitionLab;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\CompetitionLab\Engines\LabManualDecisionManager;
use App\Services\Tournaments\GroupStage\GroupStageAllocator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class LabManualDecisionManagerSingleEliminationTest extends TestCase
{
    public function test_preparation_rejects_duplicate_single_elimination_participants(): void
    {
        $manager = $this->manager();
        $phase = $this->phase();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('participantes duplicados');

        $manager->preparationDecision($phase, ['A', 'B', 'B', 'C']);
    }

    public function test_manual_order_rejects_duplicates_instead_of_deduplicating_them(): void
    {
        $manager = $this->manager();
        $phase = $this->phase();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El orden manual no puede repetir participantes.');

        $manager->resolvePreparation(
            $phase,
            $this->pendingRuntime([
                'requires_order' => true,
                'bye_count' => 0,
            ]),
            $this->participants(),
            [
                'decision_id' => 'DEC-SE',
                'ordered_participant_ids' => ['A', 'A', 'B', 'C'],
                'selected_participant_ids' => [],
            ],
            fn() => $this->fail('El engine no debería prepararse con una decisión inválida.')
        );
    }

    public function test_manual_bye_selection_rejects_duplicate_ids(): void
    {
        $manager = $this->manager();
        $phase = $this->phase();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('La selección manual de BYEs no puede repetir participantes.');

        $manager->resolvePreparation(
            $phase,
            $this->pendingRuntime([
                'requires_order' => false,
                'bye_count' => 2,
            ]),
            $this->participants(),
            [
                'decision_id' => 'DEC-SE',
                'selected_participant_ids' => ['A', 'A'],
            ],
            fn() => $this->fail('El engine no debería prepararse con una decisión inválida.')
        );
    }

    private function manager(): LabManualDecisionManager
    {
        return new LabManualDecisionManager(
            $this->createMock(GroupStageAllocator::class)
        );
    }

    private function phase(): PhaseTemplate
    {
        $phase = new PhaseTemplate();
        $phase->forceFill([
            'phase_type' => 'SINGLE_ELIMINATION',
        ]);

        return $phase;
    }

    private function pendingRuntime(array $constraints): array
    {
        return [
            'engine' => 'SINGLE_ELIMINATION',
            'status' => 'AWAITING_DECISION',
            'manual_decision' => [
                'id' => 'DEC-SE',
                'scope' => 'PREPARATION',
                'type' => 'SINGLE_ELIMINATION_SETUP',
                'eligible_participant_ids' => ['A', 'B', 'C', 'D'],
                'constraints' => $constraints,
            ],
        ];
    }

    private function participants(): array
    {
        return [
            'A' => ['id' => 'A'],
            'B' => ['id' => 'B'],
            'C' => ['id' => 'C'],
            'D' => ['id' => 'D'],
        ];
    }
}
