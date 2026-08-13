<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'phase_group_stage_tiebreakers',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('phase_template_id')
                    ->constrained('phase_templates')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Métrica
                |--------------------------------------------------------------------------
                |
                | POINTS
                | WINS
                | SCORE_DIFFERENCE
                | SCORE_FOR
                | GAME_DIFFERENCE
                | GAME_WINS
                | SEED
                |
                */

                $table
                    ->string('criterion', 40);

                /*
                |--------------------------------------------------------------------------
                | Normalización
                |--------------------------------------------------------------------------
                |
                | DEFAULT
                | RAW
                | PER_MATCH
                |
                */

                $table
                    ->string('normalization', 20)
                    ->default('DEFAULT');

                /*
                |--------------------------------------------------------------------------
                | Dirección
                |--------------------------------------------------------------------------
                |
                | AUTO
                | ASC
                | DESC
                |
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
                        'normalization',
                    ],
                    'pgst_phase_criterion_norm_unique'
                );

                $table->index(
                    [
                        'phase_template_id',
                        'sort_order',
                    ],
                    'pgst_phase_order_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'phase_group_stage_tiebreakers'
        );
    }
};
