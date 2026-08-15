<?php

namespace App\Services\Tournaments\SingleElimination\Structure;

use App\Models\PhaseEntryPort;
use App\Models\PhaseInputGate;
use App\Models\PhaseTemplate;
use App\Models\TournamentPhaseNode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SingleEliminationEntryPortSynchronizer
{
    /*
    |--------------------------------------------------------------------------
    | Sincronizar todos los usos de una plantilla
    |--------------------------------------------------------------------------
    */

    public function syncPhase(
        PhaseTemplate $phaseTemplate
    ): void {
        $phaseTemplate->load([
            'inputGates',
            'tournamentPhaseNodes.entryPorts.incomingConnections',
        ]);

        foreach (
            $phaseTemplate->tournamentPhaseNodes
            as
            $node
        ) {
            $this->syncNode(
                $node,
                $phaseTemplate->inputGates
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Sincronizar un Node concreto
    |--------------------------------------------------------------------------
    */

    public function syncNode(
        TournamentPhaseNode $node,
        ?Collection $gates = null
    ): Collection {
        return DB::transaction(
            function () use (
                $node,
                $gates
            ) {
                $lockedNode =
                    TournamentPhaseNode::query()
                    ->whereKey($node->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $gates =
                    $gates
                    ??
                    $lockedNode
                    ->phaseTemplate
                    ->inputGates()
                    ->get();

                $synchronizedIds = [];

                foreach (
                    $gates
                    as
                    $gate
                ) {
                    $port =
                        $this->findReusablePort(
                            $lockedNode,
                            $gate
                        );

                    $payload =
                        $this->payload(
                            $gate
                        );

                    if ($port) {
                        $port->update(
                            $payload
                        );
                    } else {
                        $port =
                            $lockedNode
                            ->entryPorts()
                            ->create(
                                $payload
                            );
                    }

                    $synchronizedIds[] =
                        $port->id;
                }

                /*
                |--------------------------------------------------------------------------
                | Puertos anteriormente vinculados que ya no tienen puerta
                |--------------------------------------------------------------------------
                |
                | Si tienen conexiones externas, no se eliminan.
                | Se desactivan para no destruir el Tournament Graph.
                |
                */

                $stalePorts =
                    $lockedNode
                    ->entryPorts()
                    ->whereNotNull(
                        'phase_input_gate_id'
                    )
                    ->when(
                        $synchronizedIds !== [],
                        fn($query) =>
                        $query->whereNotIn(
                            'id',
                            $synchronizedIds
                        )
                    )
                    ->withCount(
                        'incomingConnections'
                    )
                    ->get();

                foreach (
                    $stalePorts
                    as
                    $stalePort
                ) {
                    if (
                        $stalePort
                        ->incoming_connections_count
                        >
                        0
                    ) {
                        $stalePort->update([
                            'phase_input_gate_id' =>
                            null,

                            'status' =>
                            'INACTIVE',

                            'settings' =>
                            array_merge(
                                $stalePort->settings
                                    ??
                                    [],
                                [
                                    'sync_warning' =>
                                    'La puerta de definición fue eliminada.',
                                ]
                            ),
                        ]);

                        continue;
                    }

                    $stalePort->delete();
                }

                return $lockedNode
                    ->entryPorts()
                    ->with(
                        'inputGate'
                    )
                    ->get();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar un puerto reutilizable
    |--------------------------------------------------------------------------
    */

    private function findReusablePort(
        TournamentPhaseNode $node,
        PhaseInputGate $gate
    ): ?PhaseEntryPort {
        $linked =
            $node
            ->entryPorts()
            ->where(
                'phase_input_gate_id',
                $gate->id
            )
            ->first();

        if ($linked) {
            return $linked;
        }

        /*
         * Permite vincular la antigua Entrada principal IN001
         * con la primera puerta generada GIN001.
         */
        return $node
            ->entryPorts()
            ->whereNull(
                'phase_input_gate_id'
            )
            ->where(
                'sequence_number',
                $gate->sequence_number
            )
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Proyección de la puerta de definición
    |--------------------------------------------------------------------------
    */

    private function payload(
        PhaseInputGate $gate
    ): array {
        return [
            'phase_input_gate_id' =>
            $gate->id,

            'sequence_number' =>
            $gate->sequence_number,

            'code' =>
            PhaseEntryPort::formatCode(
                $gate->sequence_number
            ),

            'name' =>
            $gate->name,

            'description' =>
            $gate->description,

            'merge_policy' =>
            $gate->merge_policy,

            'is_required' =>
            $gate->is_required,

            'accepts_multiple_connections' =>
            $gate
                ->accepts_multiple_connections,

            'min_participants' =>
            $gate->min_participants,

            'max_participants' =>
            $gate->max_participants,

            'exact_participants' =>
            $gate->exact_participants,

            'sort_order' =>
            $gate->sort_order,

            'status' =>
            $gate->status,

            'settings' => [
                'definition_gate_code' =>
                $gate->code,

                'input_type' =>
                $gate->input_type,

                'accepts_batch' =>
                $gate->accepts_batch,

                'distribution_mode' =>
                $gate->distribution_mode,

                'empty_behavior' =>
                $gate->empty_behavior,
            ],
        ];
    }
}
