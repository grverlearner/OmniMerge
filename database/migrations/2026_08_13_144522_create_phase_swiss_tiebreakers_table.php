<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'phase_swiss_tiebreakers',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'phase_template_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Criterion
                |--------------------------------------------------------------------------
                |
                | WINS
                | FEWEST_LOSSES
                | OPPONENT_SCORE_SUM
                | OPPONENT_SCORE_CUT_LOWEST
                | SONNEBORN_BERGER
                | CUMULATIVE_SCORE
                | SCORE_DIFFERENCE
                | SCORE_FOR
                | GAME_DIFFERENCE
                | GAME_WINS
                | HEAD_TO_HEAD
                | SEED
                |
                */

                $table->string(
                    'criterion',
                    50
                );

                /*
                 * Parámetro opcional.
                 *
                 * Ejemplo:
                 * OPPONENT_SCORE_CUT_LOWEST
                 * parameter_int = 1
                 */
                $table
                    ->unsignedSmallInteger(
                        'parameter_int'
                    )
                    ->nullable();

                /*
                 * AUTO
                 * ASC
                 * DESC
                 */
                $table
                    ->string('direction', 10)
                    ->default('AUTO');

                $table
                    ->unsignedInteger('sort_order')
                    ->default(10);

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'phase_template_id',
                        'criterion',
                    ],
                    'pst_phase_criterion_uq'
                );

                $table->index(
                    [
                        'phase_template_id',
                        'sort_order',
                    ],
                    'pst_phase_order_idx'
                );

                $table
                    ->foreign(
                        'phase_template_id',
                        'pst_phase_fk'
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
            'phase_swiss_tiebreakers'
        );
    }
};
