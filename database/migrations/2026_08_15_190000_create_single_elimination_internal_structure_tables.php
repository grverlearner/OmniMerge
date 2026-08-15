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
        | Puertas de entrada reutilizables de la Fase
        |--------------------------------------------------------------------------
        |
        | Estas puertas pertenecen al PhaseTemplate.
        |
        | No deben confundirse con phase_entry_ports, que representan
        | el uso contextual de una puerta dentro de un TournamentPhaseNode.
        |
        */

        Schema::create(
            'phase_input_gates',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->unsignedBigInteger(
                        'phase_template_id'
                    );

                $table
                    ->unsignedInteger(
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
                 * POOL
                 * PER_SEED
                 * GROUPED
                 * HYBRID
                 * CUSTOM
                 */
                $table
                    ->string('input_type', 30)
                    ->default('POOL');

                /*
                 * APPEND
                 * WAIT_ALL
                 * FIRST_AVAILABLE
                 * PRIORITY
                 */
                $table
                    ->string('merge_policy', 30)
                    ->default('APPEND');

                /*
                 * INPUT_ORDER
                 * RANKING
                 * RANDOM
                 * BALANCED
                 * EXTREMES
                 * MANUAL
                 * CUSTOM
                 */
                $table
                    ->string('distribution_mode', 30)
                    ->default('INPUT_ORDER');

                /*
                 * ERROR
                 * WAIT
                 * SKIP
                 * ALLOW_EMPTY
                 * MANUAL
                 */
                $table
                    ->string('empty_behavior', 30)
                    ->default('ERROR');

                /*
                |--------------------------------------------------------------------------
                | Contrato
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
                    ->boolean('is_required')
                    ->default(true);

                $table
                    ->boolean('accepts_batch')
                    ->default(true);

                $table
                    ->boolean(
                        'accepts_multiple_connections'
                    )
                    ->default(true);

                $table
                    ->unsignedSmallInteger('priority')
                    ->default(10);

                $table
                    ->unsignedInteger('sort_order')
                    ->default(10);

                $table
                    ->string('status', 20)
                    ->default('ACTIVE');

                /*
                 * GENERATED
                 * MANUAL
                 */
                $table
                    ->string('generation_source', 20)
                    ->default('GENERATED');

                $table
                    ->boolean('is_locked')
                    ->default(false);

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'phase_template_id',
                        'sequence_number',
                    ],
                    'pig_phase_sequence_uq'
                );

                $table->unique(
                    [
                        'phase_template_id',
                        'code',
                    ],
                    'pig_phase_code_uq'
                );

                $table->index(
                    [
                        'phase_template_id',
                        'sort_order',
                    ],
                    'pig_phase_order_idx'
                );

                $table
                    ->foreign(
                        'phase_template_id',
                        'pig_phase_fk'
                    )
                    ->references('id')
                    ->on('phase_templates')
                    ->cascadeOnDelete();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Rondas estructurales
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'phase_single_elimination_rounds',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->unsignedBigInteger(
                        'phase_template_id'
                    );

                $table
                    ->unsignedInteger(
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
                 * Posición topológica general.
                 */
                $table
                    ->unsignedSmallInteger(
                        'stage_number'
                    );

                /*
                 * MAIN
                 * REPECHAGE
                 * SECONDARY
                 * CUSTOM
                 */
                $table
                    ->string('branch_code', 40)
                    ->default('MAIN');

                /*
                 * PRELIMINARY
                 * MAIN
                 * REPECHAGE
                 * PLACEMENT
                 * CUSTOM
                 */
                $table
                    ->string('round_type', 30)
                    ->default('MAIN');

                $table
                    ->unsignedSmallInteger(
                        'participants_expected'
                    )
                    ->nullable();

                $table
                    ->unsignedSmallInteger(
                        'qualifiers_expected'
                    )
                    ->nullable();

                $table
                    ->unsignedInteger('sort_order')
                    ->default(10);

                $table
                    ->string('status', 20)
                    ->default('ACTIVE');

                $table
                    ->string('generation_source', 20)
                    ->default('GENERATED');

                $table
                    ->boolean('is_locked')
                    ->default(false);

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'phase_template_id',
                        'sequence_number',
                    ],
                    'pse_round_phase_seq_uq'
                );

                $table->unique(
                    [
                        'phase_template_id',
                        'code',
                    ],
                    'pse_round_phase_code_uq'
                );

                $table->index(
                    [
                        'phase_template_id',
                        'stage_number',
                        'branch_code',
                    ],
                    'pse_round_stage_idx'
                );

                $table
                    ->foreign(
                        'phase_template_id',
                        'pse_round_phase_fk'
                    )
                    ->references('id')
                    ->on('phase_templates')
                    ->cascadeOnDelete();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Encuentros internos
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'phase_single_elimination_encounters',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->unsignedBigInteger(
                        'phase_template_id'
                    );

                $table
                    ->unsignedBigInteger(
                        'round_id'
                    );

                $table
                    ->unsignedInteger(
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

                $table
                    ->unsignedSmallInteger('position');

                /*
                |--------------------------------------------------------------------------
                | Formato K → Q
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedSmallInteger(
                        'entrants_count'
                    );

                $table
                    ->unsignedSmallInteger(
                        'qualifiers_count'
                    );

                $table
                    ->unsignedSmallInteger(
                        'min_entrants_to_start'
                    )
                    ->default(2);

                /*
                 * DUEL
                 * MULTI_COMPETITOR
                 * CUSTOM
                 */
                $table
                    ->string(
                        'encounter_profile',
                        30
                    )
                    ->default('DUEL');

                /*
                 * ALL_REQUIRED
                 * MINIMUM_REACHED
                 * FIRST_AVAILABLE
                 * MANUAL
                 */
                $table
                    ->string(
                        'activation_policy',
                        30
                    )
                    ->default('ALL_REQUIRED');

                $table
                    ->boolean('allows_incomplete')
                    ->default(false);

                /*
                 * BEST_OF
                 * FIXED_GAMES
                 * NONE
                 */
                $table
                    ->string('series_format', 30)
                    ->default('BEST_OF');

                $table
                    ->unsignedSmallInteger(
                        'best_of'
                    )
                    ->nullable();

                $table
                    ->unsignedSmallInteger(
                        'fixed_games'
                    )
                    ->nullable();

                $table
                    ->unsignedInteger('sort_order')
                    ->default(10);

                $table
                    ->string('status', 20)
                    ->default('ACTIVE');

                $table
                    ->string('generation_source', 20)
                    ->default('GENERATED');

                $table
                    ->boolean('is_locked')
                    ->default(false);

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'phase_template_id',
                        'sequence_number',
                    ],
                    'pse_enc_phase_seq_uq'
                );

                $table->unique(
                    [
                        'phase_template_id',
                        'code',
                    ],
                    'pse_enc_phase_code_uq'
                );

                $table->unique(
                    [
                        'round_id',
                        'position',
                    ],
                    'pse_enc_round_position_uq'
                );

                $table->index(
                    [
                        'round_id',
                        'sort_order',
                    ],
                    'pse_enc_round_order_idx'
                );

                $table
                    ->foreign(
                        'phase_template_id',
                        'pse_enc_phase_fk'
                    )
                    ->references('id')
                    ->on('phase_templates')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'round_id',
                        'pse_enc_round_fk'
                    )
                    ->references('id')
                    ->on(
                        'phase_single_elimination_rounds'
                    )
                    ->cascadeOnDelete();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Slots
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'phase_single_elimination_slots',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->unsignedBigInteger(
                        'encounter_id'
                    );

                $table->string(
                    'code',
                    30
                );

                $table
                    ->unsignedSmallInteger('position');

                /*
                 * PARTICIPANT
                 * BYE
                 * OPTIONAL
                 * MANUAL
                 */
                $table
                    ->string('slot_type', 20)
                    ->default('PARTICIPANT');

                $table
                    ->unsignedSmallInteger('capacity')
                    ->default(1);

                $table
                    ->boolean('is_required')
                    ->default(true);

                /*
                 * SINGLE
                 * FIRST_AVAILABLE
                 * PRIORITY
                 * CONDITIONAL
                 * MANUAL
                 */
                $table
                    ->string('source_policy', 30)
                    ->default('SINGLE');

                /*
                 * WAIT
                 * ERROR
                 * BYE
                 * ALLOW_EMPTY
                 * MANUAL
                 */
                $table
                    ->string('empty_behavior', 30)
                    ->default('WAIT');

                /*
                 * POSITIONAL
                 * SEEDED
                 * RANDOM
                 * RANKING
                 * MANUAL
                 * CUSTOM
                 */
                $table
                    ->string('assignment_rule', 30)
                    ->default('POSITIONAL');

                $table
                    ->unsignedInteger('sort_order')
                    ->default(10);

                $table
                    ->string('status', 20)
                    ->default('ACTIVE');

                $table
                    ->string('generation_source', 20)
                    ->default('GENERATED');

                $table
                    ->boolean('is_locked')
                    ->default(false);

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'encounter_id',
                        'position',
                    ],
                    'pse_slot_enc_position_uq'
                );

                $table->unique(
                    [
                        'encounter_id',
                        'code',
                    ],
                    'pse_slot_enc_code_uq'
                );

                $table->index(
                    [
                        'encounter_id',
                        'sort_order',
                    ],
                    'pse_slot_enc_order_idx'
                );

                $table
                    ->foreign(
                        'encounter_id',
                        'pse_slot_enc_fk'
                    )
                    ->references('id')
                    ->on(
                        'phase_single_elimination_encounters'
                    )
                    ->cascadeOnDelete();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Resultados internos
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'phase_single_elimination_results',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->unsignedBigInteger(
                        'encounter_id'
                    );

                $table
                    ->unsignedInteger(
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
                 * WINNER
                 * LOSER
                 * POSITION
                 * TOP_N
                 * QUALIFIED
                 * ELIMINATED
                 * SURVIVOR
                 * SCORE_THRESHOLD
                 * MANUAL
                 * CUSTOM
                 */
                $table->string(
                    'result_type',
                    30
                );

                $table
                    ->unsignedSmallInteger(
                        'position_from'
                    )
                    ->nullable();

                $table
                    ->unsignedSmallInteger(
                        'position_to'
                    )
                    ->nullable();

                $table
                    ->unsignedSmallInteger('quantity');

                /*
                 * CONSUME
                 * INFORMATIONAL
                 */
                $table
                    ->string('flow_mode', 20)
                    ->default('CONSUME');

                /*
                 * ACTIVE
                 * ELIMINATED
                 * EXITING
                 * NEUTRAL
                 */
                $table
                    ->string('participant_status', 20)
                    ->default('ACTIVE');

                $table
                    ->boolean('is_required')
                    ->default(true);

                $table
                    ->boolean('is_splittable')
                    ->default(false);

                $table
                    ->boolean(
                        'accepts_multiple_connections'
                    )
                    ->default(false);

                $table
                    ->unsignedSmallInteger('priority')
                    ->default(10);

                $table
                    ->unsignedInteger('sort_order')
                    ->default(10);

                $table
                    ->string('status', 20)
                    ->default('ACTIVE');

                $table
                    ->string('generation_source', 20)
                    ->default('GENERATED');

                $table
                    ->boolean('is_locked')
                    ->default(false);

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'encounter_id',
                        'sequence_number',
                    ],
                    'pse_result_enc_seq_uq'
                );

                $table->unique(
                    [
                        'encounter_id',
                        'code',
                    ],
                    'pse_result_enc_code_uq'
                );

                $table->index(
                    [
                        'encounter_id',
                        'sort_order',
                    ],
                    'pse_result_enc_order_idx'
                );

                $table
                    ->foreign(
                        'encounter_id',
                        'pse_result_enc_fk'
                    )
                    ->references('id')
                    ->on(
                        'phase_single_elimination_encounters'
                    )
                    ->cascadeOnDelete();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Conexiones internas
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'phase_single_elimination_connections',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->unsignedBigInteger(
                        'phase_template_id'
                    );

                $table
                    ->unsignedInteger(
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
                | Origen
                |--------------------------------------------------------------------------
                |
                | INPUT_GATE
                | RESULT
                |
                */

                $table->string(
                    'source_type',
                    20
                );

                $table
                    ->unsignedBigInteger(
                        'source_input_gate_id'
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'source_result_id'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Destino
                |--------------------------------------------------------------------------
                |
                | SLOT
                | PHASE_EXIT
                |
                */

                $table->string(
                    'target_type',
                    20
                );

                $table
                    ->unsignedBigInteger(
                        'target_slot_id'
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'target_phase_exit_id'
                    )
                    ->nullable();

                /*
                 * ALL
                 * TAKE_N
                 * POSITION
                 * REMAINDER
                 * CONDITIONAL
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

                $table
                    ->unsignedInteger('priority')
                    ->default(10);

                /*
                 * ALWAYS
                 * RULE
                 * MANUAL
                 * CUSTOM
                 */
                $table
                    ->string('condition_type', 20)
                    ->default('ALWAYS');

                $table
                    ->json('condition')
                    ->nullable();

                $table
                    ->string('status', 20)
                    ->default('ACTIVE');

                $table
                    ->string('generation_source', 20)
                    ->default('GENERATED');

                $table
                    ->boolean('is_locked')
                    ->default(false);

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'phase_template_id',
                        'sequence_number',
                    ],
                    'pse_conn_phase_seq_uq'
                );

                $table->unique(
                    [
                        'phase_template_id',
                        'code',
                    ],
                    'pse_conn_phase_code_uq'
                );

                $table->index(
                    [
                        'source_input_gate_id',
                        'source_result_id',
                    ],
                    'pse_conn_source_idx'
                );

                $table->index(
                    [
                        'target_slot_id',
                        'target_phase_exit_id',
                    ],
                    'pse_conn_target_idx'
                );

                $table
                    ->foreign(
                        'phase_template_id',
                        'pse_conn_phase_fk'
                    )
                    ->references('id')
                    ->on('phase_templates')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'source_input_gate_id',
                        'pse_conn_gate_fk'
                    )
                    ->references('id')
                    ->on('phase_input_gates')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'source_result_id',
                        'pse_conn_result_fk'
                    )
                    ->references('id')
                    ->on(
                        'phase_single_elimination_results'
                    )
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'target_slot_id',
                        'pse_conn_slot_fk'
                    )
                    ->references('id')
                    ->on(
                        'phase_single_elimination_slots'
                    )
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'target_phase_exit_id',
                        'pse_conn_exit_fk'
                    )
                    ->references('id')
                    ->on('phase_exits')
                    ->restrictOnDelete();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Estado estructural de Eliminación Simple
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'phase_single_elimination_settings',
            function (Blueprint $table) {
                /*
                 * AUTO
                 * HYBRID
                 * MANUAL
                 */
                $table
                    ->string('structure_mode', 20)
                    ->default('AUTO');

                /*
                 * NOT_GENERATED
                 * GENERATED
                 * VALID
                 * INVALID
                 * STALE
                 */
                $table
                    ->string('structure_status', 20)
                    ->default('NOT_GENERATED');

                $table
                    ->unsignedInteger('structure_version')
                    ->default(0);

                $table
                    ->string(
                        'structure_fingerprint',
                        64
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'structure_generated_at'
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'structure_validated_at'
                    )
                    ->nullable();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Vinculación con el puerto contextual del Tournament Graph
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'phase_entry_ports',
            function (Blueprint $table) {
                $table
                    ->unsignedBigInteger(
                        'phase_input_gate_id'
                    )
                    ->nullable()
                    ->after(
                        'tournament_phase_node_id'
                    );

                $table->index(
                    'phase_input_gate_id',
                    'pep_input_gate_idx'
                );

                $table
                    ->foreign(
                        'phase_input_gate_id',
                        'pep_input_gate_fk'
                    )
                    ->references('id')
                    ->on('phase_input_gates')
                    ->nullOnDelete();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Contrato estructural de PhaseExit
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'phase_exits',
            function (Blueprint $table) {
                /*
                 * SELECTOR
                 * INTERNAL_GRAPH
                 */
                $table
                    ->string('resolution_mode', 30)
                    ->default('SELECTOR')
                    ->after('selector_type');

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
            }
        );
    }

    public function down(): void
    {
        /*
         * Primero se eliminan las referencias agregadas
         * a tablas que ya existían.
         */

        Schema::table(
            'phase_entry_ports',
            function (Blueprint $table) {
                $table->dropForeign(
                    'pep_input_gate_fk'
                );

                $table->dropIndex(
                    'pep_input_gate_idx'
                );

                $table->dropColumn(
                    'phase_input_gate_id'
                );
            }
        );

        Schema::table(
            'phase_exits',
            function (Blueprint $table) {
                $table->dropColumn([
                    'resolution_mode',
                    'min_participants',
                    'max_participants',
                    'exact_participants',
                ]);
            }
        );

        Schema::table(
            'phase_single_elimination_settings',
            function (Blueprint $table) {
                $table->dropColumn([
                    'structure_mode',
                    'structure_status',
                    'structure_version',
                    'structure_fingerprint',
                    'structure_generated_at',
                    'structure_validated_at',
                ]);
            }
        );

        /*
         * Orden inverso por las Foreign Keys.
         */

        Schema::dropIfExists(
            'phase_single_elimination_connections'
        );

        Schema::dropIfExists(
            'phase_single_elimination_results'
        );

        Schema::dropIfExists(
            'phase_single_elimination_slots'
        );

        Schema::dropIfExists(
            'phase_single_elimination_encounters'
        );

        Schema::dropIfExists(
            'phase_single_elimination_rounds'
        );

        Schema::dropIfExists(
            'phase_input_gates'
        );
    }
};
