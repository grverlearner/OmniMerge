<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseExit;
use App\Models\PhaseInputGate;
use App\Models\PhaseSingleEliminationConnection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\TestCase;

class PhaseIoPresentationContractTest extends TestCase
{
    public function test_input_gate_exposes_human_labels_and_exact_position_coverage(): void
    {
        $gate = new PhaseInputGate();
        $gate->forceFill([
            'input_type' => 'POOL',
            'distribution_mode' => 'BALANCED',
            'exact_participants' => 4,
            'status' => 'ACTIVE',
        ]);

        $gate->setRelation(
            'outgoingConnections',
            new EloquentCollection([
                $this->connection('POSITION', 1, 'ACTIVE'),
                $this->connection('POSITION', 2, 'ACTIVE'),
                $this->connection('POSITION', 3, 'ACTIVE'),
                $this->connection('POSITION', 4, 'INACTIVE'),
            ])
        );

        $this->assertSame('Bolsa general', $gate->type_label);
        $this->assertSame('4 exactos', $gate->contract_label);
        $this->assertSame('Balanceada', $gate->distribution_label);
        $this->assertSame('Activa', $gate->status_label);
        $this->assertSame(
            '3 / 4 posiciones cubiertas',
            $gate->coverage_label
        );
    }

    public function test_input_gate_coverage_falls_back_to_active_routes_without_position_mapping(): void
    {
        $gate = new PhaseInputGate();
        $gate->forceFill([
            'exact_participants' => null,
            'min_participants' => null,
            'max_participants' => null,
            'status' => 'INACTIVE',
        ]);

        $gate->setRelation(
            'outgoingConnections',
            new EloquentCollection([
                $this->connection('ALL', null, 'ACTIVE'),
                $this->connection('ALL', null, 'INACTIVE'),
            ])
        );

        $this->assertSame('Flexible', $gate->contract_label);
        $this->assertSame('Inactiva', $gate->status_label);
        $this->assertSame('1 ruta activa', $gate->coverage_label);
    }

    public function test_phase_exit_exposes_selector_timing_contract_and_resolution_labels(): void
    {
        $exit = new PhaseExit();
        $exit->forceFill([
            'selector_type' => 'TOP_N',
            'selector_from' => 2,
            'exit_timing' => 'PHASE_END',
            'resolution_mode' => 'SELECTOR',
            'exact_participants' => 2,
        ]);

        $this->assertSame('Mejores N', $exit->selector_label);
        $this->assertSame('Los mejores 2', $exit->selection_summary);
        $this->assertSame('Al finalizar la Fase', $exit->timing_label);
        $this->assertSame('2 exactos', $exit->contract_label);
        $this->assertSame('Selector del Engine', $exit->resolution_mode_label);
    }

    private function connection(
        string $allocationMode,
        ?int $allocationValue,
        string $status
    ): PhaseSingleEliminationConnection {
        $connection = new PhaseSingleEliminationConnection();
        $connection->forceFill([
            'allocation_mode' => $allocationMode,
            'allocation_value' => $allocationValue,
            'status' => $status,
        ]);

        return $connection;
    }
}
