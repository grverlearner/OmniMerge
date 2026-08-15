<?php

namespace App\Services\Tournaments\Graph;

use App\Models\PhaseTemplate;
use App\Models\TournamentPhaseNode;
use App\Models\TournamentTemplate;
use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationEntryPortSynchronizer;
use Illuminate\Support\Facades\DB;

class TournamentGraphNodeService
{
    public function __construct(
        private readonly
        SingleEliminationEntryPortSynchronizer $entryPortSynchronizer
    ) {}
    public function create(
        TournamentTemplate $tournamentTemplate,
        PhaseTemplate $phaseTemplate,
        array $data
    ): TournamentPhaseNode {
        return DB::transaction(
            function () use (
                $tournamentTemplate,
                $phaseTemplate,
                $data
            ) {
                TournamentTemplate::query()
                    ->whereKey(
                        $tournamentTemplate->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                $sequence =
                    $this->nextSequence(
                        $tournamentTemplate
                    );


                $node =
                    $tournamentTemplate
                    ->graphNodes()
                    ->create([
                        'phase_template_id' =>
                        $phaseTemplate->id,

                        'sequence_number' =>
                        $sequence,

                        'code' =>
                        TournamentPhaseNode::formatCode(
                            $sequence
                        ),

                        'name' =>
                        $data['name']
                            ?:
                            $phaseTemplate->name,

                        'description' =>
                        $data['description']
                            ??
                            null,

                        'x_position' =>
                        $data['x_position']
                            ??
                            420,

                        'y_position' =>
                        $data['y_position']
                            ??
                            (
                                120
                                +
                                (($sequence - 1) * 210)
                            ),

                        'status' =>
                        $data['status']
                            ??
                            'ACTIVE',
                    ]);

                /*
                |--------------------------------------------------------------------------
                | Puertas de entrada contextuales
                |--------------------------------------------------------------------------
                |
                | Si la plantilla ya tiene puertas de definición, se proyectan
                | hacia el Node.
                |
                | Las plantillas antiguas conservan su Entrada principal.
                |
                */

                if (
                    $phaseTemplate
                    ->inputGates()
                    ->exists()
                ) {
                    $this
                        ->entryPortSynchronizer
                        ->syncNode(
                            $node,
                            $phaseTemplate
                                ->inputGates()
                                ->get()
                        );
                } else {
                    $node
                        ->entryPorts()
                        ->create([
                            'sequence_number' =>
                            1,

                            'code' =>
                            'IN001',

                            'name' =>
                            'Entrada principal',

                            'description' =>
                            'Entrada competitiva principal del Node.',

                            'merge_policy' =>
                            'APPEND',

                            'is_required' =>
                            true,

                            'accepts_multiple_connections' =>
                            true,

                            'sort_order' =>
                            10,

                            'status' =>
                            'ACTIVE',
                        ]);
                }


                return $node
                    ->fresh([
                        'phaseTemplate.exits',
                        'entryPorts',
                    ]);
            }
        );
    }


    public function update(
        TournamentPhaseNode $node,
        array $data
    ): TournamentPhaseNode {
        $node->update(
            $data
        );

        return $node->fresh();
    }


    public function updatePosition(
        TournamentPhaseNode $node,
        int $x,
        int $y
    ): TournamentPhaseNode {
        $node->update([
            'x_position' =>
            max(
                0,
                $x
            ),

            'y_position' =>
            max(
                0,
                $y
            ),
        ]);

        return $node->fresh();
    }


    public function duplicate(
        TournamentPhaseNode $source
    ): TournamentPhaseNode {
        return DB::transaction(
            function () use (
                $source
            ) {
                $source->load(
                    'entryPorts'
                );

                $template =
                    TournamentTemplate::query()
                    ->whereKey(
                        $source
                            ->tournament_template_id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                $sequence =
                    $this->nextSequence(
                        $template
                    );


                $copy =
                    $template
                    ->graphNodes()
                    ->create([
                        'phase_template_id' =>
                        $source
                            ->phase_template_id,

                        'sequence_number' =>
                        $sequence,

                        'code' =>
                        TournamentPhaseNode::formatCode(
                            $sequence
                        ),

                        'name' =>
                        'Copia de '
                            .
                            $source->name,

                        'description' =>
                        $source->description,

                        'x_position' =>
                        $source->x_position
                            +
                            80,

                        'y_position' =>
                        $source->y_position
                            +
                            80,

                        'status' =>
                        $source->status,

                        'settings' =>
                        $source->settings,
                    ]);


                foreach (
                    $source->entryPorts
                    as
                    $entryPort
                ) {
                    $copy
                        ->entryPorts()
                        ->create([
                            'phase_input_gate_id' =>
                            $entryPort
                                ->phase_input_gate_id,
                            'sequence_number' =>
                            $entryPort
                                ->sequence_number,

                            'code' =>
                            $entryPort->code,

                            'name' =>
                            $entryPort->name,

                            'description' =>
                            $entryPort
                                ->description,

                            'merge_policy' =>
                            $entryPort
                                ->merge_policy,

                            'is_required' =>
                            $entryPort
                                ->is_required,

                            'accepts_multiple_connections' =>
                            $entryPort
                                ->accepts_multiple_connections,

                            'min_participants' =>
                            $entryPort
                                ->min_participants,

                            'max_participants' =>
                            $entryPort
                                ->max_participants,

                            'exact_participants' =>
                            $entryPort
                                ->exact_participants,

                            'sort_order' =>
                            $entryPort
                                ->sort_order,

                            'status' =>
                            $entryPort
                                ->status,

                            'settings' =>
                            $entryPort
                                ->settings,
                        ]);
                }


                return $copy;
            }
        );
    }


    public function delete(
        TournamentPhaseNode $node
    ): void {
        /*
         * Las conexiones dependientes desaparecen
         * por las FK CASCADE.
         */

        $node->delete();
    }


    public function createEntryPort(
        TournamentPhaseNode $node,
        array $data
    ) {
        return DB::transaction(
            function () use (
                $node,
                $data
            ) {
                TournamentPhaseNode::query()
                    ->whereKey(
                        $node->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                $sequence =
                    (
                        (int)
                        $node
                            ->entryPorts()
                            ->max(
                                'sequence_number'
                            )
                    )
                    +
                    1;


                $data['sequence_number'] =
                    $sequence;

                $data['code'] =
                    sprintf(
                        'IN%03d',
                        $sequence
                    );

                $data['sort_order'] =
                    $data['sort_order']
                    ??
                    ($sequence * 10);


                return $node
                    ->entryPorts()
                    ->create(
                        $data
                    );
            }
        );
    }


    private function nextSequence(
        TournamentTemplate $template
    ): int {
        return (
            (int)
            $template
                ->graphNodes()
                ->max(
                    'sequence_number'
                )
        )
            +
            1;
    }
}
