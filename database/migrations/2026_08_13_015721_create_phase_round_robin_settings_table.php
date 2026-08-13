<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'phase_round_robin_settings',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | PhaseTemplate
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('phase_template_id')
                    ->unique()
                    ->constrained('phase_templates')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Ciclos
                |--------------------------------------------------------------------------
                |
                | 1 = cada pareja se enfrenta una vez.
                | 2 = cada pareja se enfrenta dos veces.
                | 3 = cada pareja se enfrenta tres veces.
                |
                */

                $table
                    ->unsignedTinyInteger('cycles')
                    ->default(1);

                /*
                |--------------------------------------------------------------------------
                | Orden inicial
                |--------------------------------------------------------------------------
                |
                | INPUT_ORDER
                | RANDOM
                | RANKING
                | MANUAL
                |
                */

                $table
                    ->string('initial_order_mode', 30)
                    ->default('INPUT_ORDER');

                /*
                |--------------------------------------------------------------------------
                | Calendario
                |--------------------------------------------------------------------------
                |
                | En T3 implementamos BALANCED.
                | La arquitectura queda preparada para más estrategias.
                |
                */

                $table
                    ->string('schedule_mode', 30)
                    ->default('BALANCED');

                /*
                |--------------------------------------------------------------------------
                | Resultados
                |--------------------------------------------------------------------------
                */

                $table
                    ->boolean('allow_draws')
                    ->default(true);

                /*
                |--------------------------------------------------------------------------
                | Puntuación
                |--------------------------------------------------------------------------
                |
                | DECIMAL permite:
                |
                | 3
                | 1
                | 0
                | -1
                | 2.5
                | etc.
                |
                */

                $table
                    ->decimal('win_points', 10, 2)
                    ->default(3);

                $table
                    ->decimal('draw_points', 10, 2)
                    ->default(1);

                $table
                    ->decimal('loss_points', 10, 2)
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Best Of
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedTinyInteger('default_best_of')
                    ->default(1);

                /*
                |--------------------------------------------------------------------------
                | Empate en frontera de clasificación
                |--------------------------------------------------------------------------
                |
                | USE_TIEBREAKERS
                | MANUAL_RESOLUTION
                | RANDOM_RESOLUTION
                | INCLUDE_ALL_TIED
                | REQUIRE_PLAYOFF
                |
                */

                $table
                    ->string('cutoff_tie_policy', 40)
                    ->default('USE_TIEBREAKERS');

                /*
                |--------------------------------------------------------------------------
                | Extensión
                |--------------------------------------------------------------------------
                */

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
            'phase_round_robin_settings'
        );
    }
};
