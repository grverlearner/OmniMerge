<?php

namespace App\Services\Tournaments\Graph;

use App\Models\PhaseEntryPort;
use App\Models\PhaseExit;
use App\Models\TournamentPhaseConnection;
use App\Models\TournamentPhaseNode;
use App\Models\TournamentStart;
use App\Models\TournamentTemplate;
use App\Models\TournamentTerminal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TournamentGraphConnectionService
{
    public function __construct(
        private readonly
        TournamentGraphTopologyService $topologyService
    ) {}


    public function create(
        TournamentTemplate $template,
        array $data
    ): TournamentPhaseConnection {
        return DB::transaction(
            function () use (
                $template,
                $data
            ) {
                TournamentTemplate::query()
                    ->whereKey(
                        $template->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                $data =
                    $this->normalize(
                        $data
                    );


                $this->validateEndpoints(
                    $template,
                    $data
                );


                $this->validateFanOut(
                    $template,
                    $data
                );


                $this->validateCycle(
                    $template,
                    $data
                );


                $sequence =
                    (
                        (int)
                        $template
                            ->graphConnections()
                            ->max(
                                'sequence_number'
                            )
                    )
                    +
                    1;


                $data['sequence_number'] =
                    $sequence;

                $data['code'] =
                    TournamentPhaseConnection::formatCode(
                        $sequence
                    );


                return $template
                    ->graphConnections()
                    ->create(
                        $data
                    );
            }
        );
    }


    public function update(
        TournamentPhaseConnection $connection,
        array $data
    ): TournamentPhaseConnection {
        $data =
            $this->normalizeAllocation(
                $data
            );

        /*
         * Los extremos no se modifican.
         * Si quieres cambiar una ruta:
         *
         * delete + create.
         *
         * Esto evita estados ambiguos en el builder.
         */

        $candidate =
            array_merge(
                [
                    'source_type' =>
                    $connection
                        ->source_type,

                    'source_start_id' =>
                    $connection
                        ->source_start_id,

                    'source_node_id' =>
                    $connection
                        ->source_node_id,

                    'source_phase_exit_id' =>
                    $connection
                        ->source_phase_exit_id,
                ],
                $data
            );


        $this->validateFanOut(
            $connection
                ->tournamentTemplate,
            $candidate,
            $connection->id
        );


        $connection->update(
            $data
        );

        return $connection->fresh();
    }


    public function delete(
        TournamentPhaseConnection $connection
    ): void {
        $connection->delete();
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize
    |--------------------------------------------------------------------------
    */

    private function normalize(
        array $data
    ): array {
        if (
            $data['source_type']
            ===
            'START'
        ) {
            $data['source_node_id'] =
                null;

            $data['source_phase_exit_id'] =
                null;
        } else {
            $data['source_start_id'] =
                null;
        }


        if (
            $data['target_type']
            ===
            'ENTRY_PORT'
        ) {
            $data['target_terminal_id'] =
                null;
        } else {
            $data['target_entry_port_id'] =
                null;
        }


        return $this->normalizeAllocation(
            $data
        );
    }


    private function normalizeAllocation(
        array $data
    ): array {
        if (
            in_array(
                $data['allocation_mode'],
                [
                    'ALL',
                    'REMAINDER',
                ],
                true
            )
        ) {
            $data['allocation_value'] =
                null;
        }

        return $data;
    }


    /*
    |--------------------------------------------------------------------------
    | Endpoint Validation
    |--------------------------------------------------------------------------
    */

    private function validateEndpoints(
        TournamentTemplate $template,
        array $data
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Source
        |--------------------------------------------------------------------------
        */

        if (
            $data['source_type']
            ===
            'START'
        ) {
            $start =
                TournamentStart::query()
                ->find(
                    $data['source_start_id']
                );

            if (
                ! $start
                ||
                $start
                ->tournament_template_id
                !==
                $template->id
            ) {
                $this->fail(
                    'source_start_id',
                    'El inicio no pertenece a este torneo.'
                );
            }
        } else {
            $node =
                TournamentPhaseNode::query()
                ->with(
                    'phaseTemplate'
                )
                ->find(
                    $data['source_node_id']
                );

            $exit =
                PhaseExit::query()
                ->find(
                    $data['source_phase_exit_id']
                );


            if (
                ! $node
                ||
                $node
                ->tournament_template_id
                !==
                $template->id
            ) {
                $this->fail(
                    'source_node_id',
                    'El Node origen no pertenece a este torneo.'
                );
            }


            if (
                ! $exit
                ||
                $exit
                ->phase_template_id
                !==
                $node->phase_template_id
            ) {
                $this->fail(
                    'source_phase_exit_id',
                    'La puerta de salida no pertenece al PhaseTemplate usado por el Node origen.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Target
        |--------------------------------------------------------------------------
        */

        if (
            $data['target_type']
            ===
            'ENTRY_PORT'
        ) {
            $port =
                PhaseEntryPort::query()
                ->with(
                    'node'
                )
                ->find(
                    $data['target_entry_port_id']
                );


            if (
                ! $port
                ||
                ! $port->node
                ||
                $port
                ->node
                ->tournament_template_id
                !==
                $template->id
            ) {
                $this->fail(
                    'target_entry_port_id',
                    'El puerto de entrada no pertenece a este torneo.'
                );
            }


            if (
                ! $port
                    ->accepts_multiple_connections
                &&
                TournamentPhaseConnection::query()
                ->where(
                    'target_entry_port_id',
                    $port->id
                )
                ->exists()
            ) {
                $this->fail(
                    'target_entry_port_id',
                    'Este puerto no permite múltiples conexiones.'
                );
            }


            if (
                $data['source_type']
                ===
                'PHASE_EXIT'
                &&
                $port
                ->tournament_phase_node_id
                ===
                (int)
                $data['source_node_id']
            ) {
                $this->fail(
                    'target_entry_port_id',
                    'Un Node no puede conectarse directamente consigo mismo.'
                );
            }
        } else {
            $terminal =
                TournamentTerminal::query()
                ->find(
                    $data['target_terminal_id']
                );


            if (
                ! $terminal
                ||
                $terminal
                ->tournament_template_id
                !==
                $template->id
            ) {
                $this->fail(
                    'target_terminal_id',
                    'El terminal no pertenece a este torneo.'
                );
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Fan-Out
    |--------------------------------------------------------------------------
    */

    private function validateFanOut(
        TournamentTemplate $template,
        array $data,
        ?int $ignoreConnectionId = null
    ): void {
        $query =
            TournamentPhaseConnection::query()
            ->where(
                'tournament_template_id',
                $template->id
            );


        if (
            $data['source_type']
            ===
            'START'
        ) {
            $query
                ->where(
                    'source_type',
                    'START'
                )
                ->where(
                    'source_start_id',
                    $data['source_start_id']
                );
        } else {
            $query
                ->where(
                    'source_type',
                    'PHASE_EXIT'
                )
                ->where(
                    'source_node_id',
                    $data['source_node_id']
                )
                ->where(
                    'source_phase_exit_id',
                    $data['source_phase_exit_id']
                );
        }


        if ($ignoreConnectionId) {
            $query->whereKeyNot(
                $ignoreConnectionId
            );
        }


        $siblings =
            $query->get();


        /*
         * ALL consume conceptualmente todo el flujo.
         * No puede coexistir con otra rama.
         */

        if (
            $data['allocation_mode']
            ===
            'ALL'
            &&
            $siblings->isNotEmpty()
        ) {
            $this->fail(
                'allocation_mode',
                'Una conexión ALL no puede coexistir con otras ramas del mismo origen.'
            );
        }


        if (
            $siblings
            ->where(
                'allocation_mode',
                'ALL'
            )
            ->isNotEmpty()
        ) {
            $this->fail(
                'allocation_mode',
                'El origen ya tiene una conexión ALL. Cámbiala o elimínala antes de crear otra rama.'
            );
        }


        if (
            $data['allocation_mode']
            ===
            'REMAINDER'
            &&
            $siblings
            ->where(
                'allocation_mode',
                'REMAINDER'
            )
            ->isNotEmpty()
        ) {
            $this->fail(
                'allocation_mode',
                'Solo puede existir una conexión REMAINDER por origen.'
            );
        }


        if (
            $data['allocation_mode']
            ===
            'PERCENTAGE'
        ) {
            $used =
                (float)
                $siblings
                    ->where(
                        'allocation_mode',
                        'PERCENTAGE'
                    )
                    ->sum(
                        'allocation_value'
                    );

            $new =
                (float)
                $data['allocation_value'];

            if (
                $used
                +
                $new
                >
                100
            ) {
                $this->fail(
                    'allocation_value',
                    'Las ramas porcentuales de un mismo origen no pueden superar el 100%.'
                );
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Cycle
    |--------------------------------------------------------------------------
    */

    private function validateCycle(
        TournamentTemplate $template,
        array $data
    ): void {
        /*
         * START → Node no puede producir ciclo.
         * Node → Terminal tampoco.
         */

        if (
            $data['source_type']
            !==
            'PHASE_EXIT'
            ||
            $data['target_type']
            !==
            'ENTRY_PORT'
        ) {
            return;
        }


        $targetPort =
            PhaseEntryPort::query()
            ->findOrFail(
                $data['target_entry_port_id']
            );


        $nodeIds =
            $template
            ->graphNodes()
            ->pluck('id')
            ->map(
                fn($id) =>
                (int)
                $id
            )
            ->all();


        $edges =
            TournamentPhaseConnection::query()
            ->where(
                'tournament_template_id',
                $template->id
            )
            ->where(
                'source_type',
                'PHASE_EXIT'
            )
            ->where(
                'target_type',
                'ENTRY_PORT'
            )
            ->with(
                'targetEntryPort'
            )
            ->get()
            ->map(
                function (
                    TournamentPhaseConnection $connection
                ) {
                    return [
                        (int)
                        $connection
                            ->source_node_id,

                        (int)
                        $connection
                            ->targetEntryPort
                            ->tournament_phase_node_id,
                    ];
                }
            )
            ->all();


        $edges[] = [
            (int)
            $data['source_node_id'],

            (int)
            $targetPort
                ->tournament_phase_node_id,
        ];


        if (
            $this
            ->topologyService
            ->hasCycle(
                $nodeIds,
                $edges
            )
        ) {
            $this->fail(
                'target_entry_port_id',
                'Esta conexión produciría un ciclo. En T6 el Tournament Graph debe ser un DAG.'
            );
        }
    }


    private function fail(
        string $field,
        string $message
    ): never {
        throw ValidationException::withMessages([
            $field => [
                $message,
            ],
        ]);
    }
}
