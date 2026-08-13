<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | TOURNAMENT PHASE NODES
        |--------------------------------------------------------------------------
        |
        | Uso contextual de un PhaseTemplate dentro de un TournamentTemplate.
        |
        */

        Schema::create(
            'tournament_phase_nodes',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'tournament_template_id'
                );

                $table->unsignedBigInteger(
                    'phase_template_id'
                );

                $table->unsignedInteger(
                    'sequence_number'
                );

                $table->string(
                    'code',
                    30
                );

                /*
                 * Alias contextual.
                 *
                 * El PhaseTemplate podría llamarse:
                 *
                 * "Eliminación directa BO3"
                 *
                 * pero dentro de este torneo:
                 *
                 * "Playoffs principales"
                 */
                $table->string(
                    'name',
                    150
                );

                $table
                    ->text('description')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Canvas
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedInteger('x_position')
                    ->default(420);

                $table
                    ->unsignedInteger('y_position')
                    ->default(160);

                $table
                    ->string('status', 20)
                    ->default('ACTIVE');

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'tournament_template_id',
                        'sequence_number',
                    ],
                    'tpn_template_seq_uq'
                );

                $table->unique(
                    [
                        'tournament_template_id',
                        'code',
                    ],
                    'tpn_template_code_uq'
                );

                $table->index(
                    'phase_template_id',
                    'tpn_phase_idx'
                );


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                |
                | Nombres cortos intencionales para evitar el límite de MySQL.
                |
                */

                $table
                    ->foreign(
                        'tournament_template_id',
                        'tpn_template_fk'
                    )
                    ->references('id')
                    ->on('tournament_templates')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'phase_template_id',
                        'tpn_phase_fk'
                    )
                    ->references('id')
                    ->on('phase_templates')
                    ->restrictOnDelete();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | PHASE ENTRY PORTS
        |--------------------------------------------------------------------------
        |
        | Puertas de entrada contextuales de un Node.
        |
        */

        Schema::create(
            'phase_entry_ports',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'tournament_phase_node_id'
                );

                $table->unsignedInteger(
                    'sequence_number'
                );

                $table->string(
                    'code',
                    30
                );

                $table->string(
                    'name',
                    120
                );

                $table
                    ->text('description')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Merge Policy
                |--------------------------------------------------------------------------
                |
                | APPEND
                |     Une participantes provenientes de todas las conexiones.
                |
                | WAIT_ALL
                |     El Runtime deberá esperar que todas las conexiones
                |     hayan terminado antes de activar el Node.
                |
                | FIRST_AVAILABLE
                |     La primera entrada disponible puede activar el puerto.
                |
                | PRIORITY
                |     Las conexiones se consideran por prioridad.
                |
                */

                $table
                    ->string('merge_policy', 30)
                    ->default('APPEND');

                $table
                    ->boolean('is_required')
                    ->default(true);

                $table
                    ->boolean('accepts_multiple_connections')
                    ->default(true);

                /*
                |--------------------------------------------------------------------------
                | Contrato local del puerto
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedSmallInteger(
                        'min_participants'
                    )
                    ->nullable();

                $table
                    ->unsignedSmallInteger(
                        'max_participants'
                    )
                    ->nullable();

                $table
                    ->unsignedSmallInteger(
                        'exact_participants'
                    )
                    ->nullable();

                $table
                    ->unsignedInteger('sort_order')
                    ->default(10);

                $table
                    ->string('status', 20)
                    ->default('ACTIVE');

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();


                $table->unique(
                    [
                        'tournament_phase_node_id',
                        'sequence_number',
                    ],
                    'pep_node_seq_uq'
                );

                $table->unique(
                    [
                        'tournament_phase_node_id',
                        'code',
                    ],
                    'pep_node_code_uq'
                );

                $table
                    ->foreign(
                        'tournament_phase_node_id',
                        'pep_node_fk'
                    )
                    ->references('id')
                    ->on('tournament_phase_nodes')
                    ->cascadeOnDelete();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | TOURNAMENT STARTS
        |--------------------------------------------------------------------------
        |
        | Fuentes iniciales de participantes.
        |
        | Un torneo puede tener una o muchas.
        |
        */

        Schema::create(
            'tournament_starts',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'tournament_template_id'
                );

                $table->unsignedInteger(
                    'sequence_number'
                );

                $table->string(
                    'code',
                    30
                );

                $table->string(
                    'name',
                    120
                );

                $table
                    ->text('description')
                    ->nullable();

                /*
                 * MAIN_POOL
                 * SEEDED_POOL
                 * QUALIFIER_POOL
                 * INVITED_POOL
                 * CUSTOM
                 */
                $table
                    ->string('source_type', 30)
                    ->default('MAIN_POOL');

                $table
                    ->unsignedSmallInteger(
                        'expected_participants'
                    )
                    ->nullable();

                $table
                    ->unsignedInteger('x_position')
                    ->default(80);

                $table
                    ->unsignedInteger('y_position')
                    ->default(160);

                $table
                    ->string('status', 20)
                    ->default('ACTIVE');

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();


                $table->unique(
                    [
                        'tournament_template_id',
                        'sequence_number',
                    ],
                    'tgs_template_seq_uq'
                );

                $table->unique(
                    [
                        'tournament_template_id',
                        'code',
                    ],
                    'tgs_template_code_uq'
                );

                $table
                    ->foreign(
                        'tournament_template_id',
                        'tgs_template_fk'
                    )
                    ->references('id')
                    ->on('tournament_templates')
                    ->cascadeOnDelete();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | TOURNAMENT TERMINALS
        |--------------------------------------------------------------------------
        |
        | Destinos finales.
        |
        */

        Schema::create(
            'tournament_terminals',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'tournament_template_id'
                );

                $table->unsignedInteger(
                    'sequence_number'
                );

                $table->string(
                    'code',
                    30
                );

                $table->string(
                    'name',
                    120
                );

                $table
                    ->text('description')
                    ->nullable();

                /*
                 * CHAMPION
                 * QUALIFIED
                 * ELIMINATED
                 * SECONDARY
                 * PLACEMENT
                 * CUSTOM
                 */
                $table
                    ->string('terminal_type', 30)
                    ->default('ELIMINATED');

                $table
                    ->unsignedSmallInteger(
                        'expected_participants'
                    )
                    ->nullable();

                $table
                    ->unsignedInteger('x_position')
                    ->default(1800);

                $table
                    ->unsignedInteger('y_position')
                    ->default(160);

                $table
                    ->string('status', 20)
                    ->default('ACTIVE');

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();


                $table->unique(
                    [
                        'tournament_template_id',
                        'sequence_number',
                    ],
                    'tgt_template_seq_uq'
                );

                $table->unique(
                    [
                        'tournament_template_id',
                        'code',
                    ],
                    'tgt_template_code_uq'
                );

                $table
                    ->foreign(
                        'tournament_template_id',
                        'tgt_template_fk'
                    )
                    ->references('id')
                    ->on('tournament_templates')
                    ->cascadeOnDelete();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | TOURNAMENT PHASE CONNECTIONS
        |--------------------------------------------------------------------------
        |
        | Edge del grafo.
        |
        | SOURCE:
        |
        | START
        | PHASE_EXIT
        |
        | TARGET:
        |
        | ENTRY_PORT
        | TERMINAL
        |
        */

        Schema::create(
            'tournament_phase_connections',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'tournament_template_id'
                );

                $table->unsignedInteger(
                    'sequence_number'
                );

                $table->string(
                    'code',
                    30
                );

                $table
                    ->string('label', 120)
                    ->nullable();

                $table
                    ->text('description')
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | SOURCE
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'source_type',
                    20
                );

                $table
                    ->unsignedBigInteger(
                        'source_start_id'
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'source_node_id'
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'source_phase_exit_id'
                    )
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | TARGET
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'target_type',
                    20
                );

                $table
                    ->unsignedBigInteger(
                        'target_entry_port_id'
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'target_terminal_id'
                    )
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Allocation
                |--------------------------------------------------------------------------
                |
                | ALL
                |     Todo lo que sale por el origen.
                |
                | TAKE_N
                |     Toma N participantes.
                |
                | PERCENTAGE
                |     Porcentaje del flujo.
                |
                | REMAINDER
                |     Lo que quede luego de conexiones anteriores.
                |
                */

                $table
                    ->string('allocation_mode', 20)
                    ->default('ALL');

                $table
                    ->decimal(
                        'allocation_value',
                        10,
                        2
                    )
                    ->nullable();

                /*
                 * Importante cuando un mismo origen
                 * tiene múltiples ramas.
                 */
                $table
                    ->unsignedInteger('priority')
                    ->default(10);

                $table
                    ->string('status', 20)
                    ->default('ACTIVE');

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();


                $table->unique(
                    [
                        'tournament_template_id',
                        'sequence_number',
                    ],
                    'tpc_template_seq_uq'
                );

                $table->unique(
                    [
                        'tournament_template_id',
                        'code',
                    ],
                    'tpc_template_code_uq'
                );

                $table->index(
                    [
                        'source_node_id',
                        'source_phase_exit_id',
                    ],
                    'tpc_source_phase_idx'
                );

                $table->index(
                    'target_entry_port_id',
                    'tpc_target_port_idx'
                );


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreign(
                        'tournament_template_id',
                        'tpc_template_fk'
                    )
                    ->references('id')
                    ->on('tournament_templates')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'source_start_id',
                        'tpc_start_fk'
                    )
                    ->references('id')
                    ->on('tournament_starts')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'source_node_id',
                        'tpc_source_node_fk'
                    )
                    ->references('id')
                    ->on('tournament_phase_nodes')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'source_phase_exit_id',
                        'tpc_exit_fk'
                    )
                    ->references('id')
                    ->on('phase_exits')
                    ->restrictOnDelete();

                $table
                    ->foreign(
                        'target_entry_port_id',
                        'tpc_entry_fk'
                    )
                    ->references('id')
                    ->on('phase_entry_ports')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'target_terminal_id',
                        'tpc_terminal_fk'
                    )
                    ->references('id')
                    ->on('tournament_terminals')
                    ->cascadeOnDelete();
            }
        );
    }


    public function down(): void
    {
        /*
         * Se eliminan en orden inverso
         * para respetar las FK.
         */

        Schema::dropIfExists(
            'tournament_phase_connections'
        );

        Schema::dropIfExists(
            'tournament_terminals'
        );

        Schema::dropIfExists(
            'tournament_starts'
        );

        Schema::dropIfExists(
            'phase_entry_ports'
        );

        Schema::dropIfExists(
            'tournament_phase_nodes'
        );
    }
};
