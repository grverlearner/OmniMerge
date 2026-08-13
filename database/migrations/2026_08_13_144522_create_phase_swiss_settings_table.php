<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'phase_swiss_settings',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Phase
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'phase_template_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Finalización
                |--------------------------------------------------------------------------
                |
                | FIXED_ROUNDS
                | RECORD_THRESHOLDS
                |
                */

                $table
                    ->string('completion_mode', 40)
                    ->default('FIXED_ROUNDS');

                $table
                    ->unsignedSmallInteger('fixed_rounds')
                    ->nullable()
                    ->default(5);

                $table
                    ->unsignedSmallInteger('qualification_wins')
                    ->nullable()
                    ->default(3);

                $table
                    ->unsignedSmallInteger('elimination_losses')
                    ->nullable()
                    ->default(3);

                /*
                 * Especialmente importante cuando
                 * pueden existir empates.
                 */
                $table
                    ->unsignedSmallInteger('max_rounds')
                    ->nullable()
                    ->default(5);

                /*
                |--------------------------------------------------------------------------
                | Pairing
                |--------------------------------------------------------------------------
                |
                | OMNIMERGE_SCORE_GROUP
                | ADJACENT_STANDINGS
                | RANDOM_WITHIN_SCORE
                |
                */

                $table
                    ->string('pairing_algorithm', 40)
                    ->default('OMNIMERGE_SCORE_GROUP');

                /*
                |--------------------------------------------------------------------------
                | Base del pairing
                |--------------------------------------------------------------------------
                |
                | MATCH_POINTS
                | WIN_LOSS_RECORD
                | PAIRING_SCORE
                |
                */

                $table
                    ->string('pairing_basis', 30)
                    ->default('MATCH_POINTS');

                /*
                |--------------------------------------------------------------------------
                | Primera ronda
                |--------------------------------------------------------------------------
                |
                | INPUT_ORDER
                | RANDOM
                | SEEDED_HALVES
                | TOP_VS_BOTTOM
                |
                */

                $table
                    ->string('first_round_mode', 30)
                    ->default('SEEDED_HALVES');

                /*
                |--------------------------------------------------------------------------
                | Rematches
                |--------------------------------------------------------------------------
                |
                | STRICT_NO_REMATCH
                | AVOID_IF_POSSIBLE
                | ALLOW_REMATCH
                |
                */

                $table
                    ->string('rematch_policy', 30)
                    ->default('STRICT_NO_REMATCH');

                /*
                |--------------------------------------------------------------------------
                | Floater
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('floater_policy', 40)
                    ->default('MINIMIZE_SCORE_GAP');

                /*
                |--------------------------------------------------------------------------
                | Side / orientación
                |--------------------------------------------------------------------------
                |
                | NONE
                | PREFER_BALANCE
                |
                */

                $table
                    ->string('side_balance_policy', 30)
                    ->default('PREFER_BALANCE');

                /*
                |--------------------------------------------------------------------------
                | Scoring
                |--------------------------------------------------------------------------
                */

                $table
                    ->boolean('allow_draws')
                    ->default(true);

                $table
                    ->decimal('win_points', 10, 2)
                    ->default(1);

                $table
                    ->decimal('draw_points', 10, 2)
                    ->default(0.5);

                $table
                    ->decimal('loss_points', 10, 2)
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Encounter format
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedTinyInteger('default_best_of')
                    ->default(1);

                /*
                |--------------------------------------------------------------------------
                | BYE
                |--------------------------------------------------------------------------
                |
                | DISABLED
                | LOWEST_STANDING_WITHOUT_BYE
                | LOWEST_SEED_WITHOUT_BYE
                | RANDOM_ELIGIBLE
                | MANUAL
                |
                */

                $table
                    ->string('bye_policy', 40)
                    ->default(
                        'LOWEST_STANDING_WITHOUT_BYE'
                    );

                $table
                    ->decimal('bye_points', 10, 2)
                    ->default(1);

                $table
                    ->unsignedTinyInteger(
                        'max_byes_per_participant'
                    )
                    ->default(1);

                /*
                |--------------------------------------------------------------------------
                | Pairing Score inicial
                |--------------------------------------------------------------------------
                |
                | ZERO
                | EXTERNAL_SCORE
                |
                */

                $table
                    ->string(
                        'initial_pairing_score_mode',
                        30
                    )
                    ->default('ZERO');

                /*
                |--------------------------------------------------------------------------
                | Accelerated Swiss
                |--------------------------------------------------------------------------
                |
                | NONE
                | GENERIC_VIRTUAL_POINTS
                |
                */

                $table
                    ->string('acceleration_mode', 40)
                    ->default('NONE');

                $table
                    ->unsignedSmallInteger(
                        'acceleration_rounds'
                    )
                    ->nullable();

                $table
                    ->unsignedSmallInteger(
                        'acceleration_seed_count'
                    )
                    ->nullable();

                $table
                    ->decimal(
                        'acceleration_virtual_points',
                        10,
                        2
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Cutoff / fallback
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('cutoff_tie_policy', 40)
                    ->default('USE_TIEBREAKERS');

                /*
                 * Cuando RECORD_THRESHOLDS llega al máximo de rondas
                 * con competidores aún activos.
                 *
                 * FINAL_RANKING
                 * MANUAL_RESOLUTION
                 * REMAINING_EXIT
                 */
                $table
                    ->string('fallback_policy', 40)
                    ->default('FINAL_RANKING');

                /*
                |--------------------------------------------------------------------------
                | Extensión
                |--------------------------------------------------------------------------
                */

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Índices / FK
                |--------------------------------------------------------------------------
                |
                | Nombres cortos intencionalmente para MySQL.
                |
                */

                $table->unique(
                    'phase_template_id',
                    'pss_phase_uq'
                );

                $table
                    ->foreign(
                        'phase_template_id',
                        'pss_phase_fk'
                    )
                    ->references('id')
                    ->on('phase_templates')
                    ->cascadeOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'phase_swiss_settings'
        );
    }
};
