<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'phase_group_stage_settings',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('phase_template_id')
                    ->unique()
                    ->constrained('phase_templates')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Construcción de grupos
                |--------------------------------------------------------------------------
                |
                | FIXED_GROUP_COUNT
                | TARGET_GROUP_SIZE
                | CUSTOM_GROUPS
                |
                */

                $table
                    ->string('group_count_mode', 40)
                    ->default('FIXED_GROUP_COUNT');

                $table
                    ->unsignedSmallInteger('group_count')
                    ->nullable()
                    ->default(4);

                $table
                    ->unsignedSmallInteger('target_group_size')
                    ->nullable()
                    ->default(4);

                $table
                    ->unsignedSmallInteger('min_group_size')
                    ->default(2);

                $table
                    ->unsignedSmallInteger('max_group_size')
                    ->default(8);

                /*
                |--------------------------------------------------------------------------
                | Participantes sobrantes
                |--------------------------------------------------------------------------
                |
                | BALANCED
                | FIRST_GROUPS
                | LAST_GROUPS
                | MANUAL
                |
                */

                $table
                    ->string('remainder_policy', 30)
                    ->default('BALANCED');

                /*
                |--------------------------------------------------------------------------
                | Distribución
                |--------------------------------------------------------------------------
                |
                | INPUT_ORDER
                | RANDOM
                | SNAKE_SEEDED
                | POT_DRAW
                | MANUAL
                |
                */

                $table
                    ->string('distribution_mode', 30)
                    ->default('SNAKE_SEEDED');

                /*
                |--------------------------------------------------------------------------
                | Pots
                |--------------------------------------------------------------------------
                |
                | NULL = OmniMerge calcula la cantidad automáticamente.
                |
                */

                $table
                    ->unsignedSmallInteger('pot_count')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Motor interno
                |--------------------------------------------------------------------------
                |
                | T4 implementa ROUND_ROBIN.
                | La arquitectura queda lista para otros Engines.
                |
                */

                $table
                    ->string('internal_engine_type', 30)
                    ->default('ROUND_ROBIN');

                $table
                    ->unsignedTinyInteger('internal_cycles')
                    ->default(1);

                $table
                    ->string('internal_schedule_mode', 30)
                    ->default('BALANCED');

                $table
                    ->boolean('internal_allow_draws')
                    ->default(true);

                $table
                    ->decimal('internal_win_points', 10, 2)
                    ->default(3);

                $table
                    ->decimal('internal_draw_points', 10, 2)
                    ->default(1);

                $table
                    ->decimal('internal_loss_points', 10, 2)
                    ->default(0);

                $table
                    ->unsignedTinyInteger('internal_best_of')
                    ->default(1);

                /*
                |--------------------------------------------------------------------------
                | Comparación entre grupos
                |--------------------------------------------------------------------------
                |
                | RAW
                | PER_MATCH
                |
                */

                $table
                    ->string('cross_group_normalization', 30)
                    ->default('RAW');

                /*
                |--------------------------------------------------------------------------
                | Empate en el último cupo
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('cutoff_tie_policy', 40)
                    ->default('USE_TIEBREAKERS');

                /*
                |--------------------------------------------------------------------------
                | Finalización
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('completion_mode', 40)
                    ->default('ALL_GROUPS_COMPLETE');

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'phase_group_stage_settings'
        );
    }
};
