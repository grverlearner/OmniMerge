<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'phase_single_elimination_settings',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | PhaseTemplate
                |--------------------------------------------------------------------------
                |
                | Solo puede existir una configuración SINGLE_ELIMINATION
                | por PhaseTemplate.
                |
                */

                $table
                    ->foreignId('phase_template_id')
                    ->unique()
                    ->constrained('phase_templates')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Finalización
                |--------------------------------------------------------------------------
                |
                | WINNER
                |   La Fase continúa hasta dejar un único superviviente.
                |
                | SURVIVORS
                |   La Fase termina al alcanzar target_survivors.
                |
                */

                $table
                    ->string('completion_mode', 20)
                    ->default('WINNER');

                $table
                    ->unsignedSmallInteger('target_survivors')
                    ->default(1);

                /*
                |--------------------------------------------------------------------------
                | Seeding
                |--------------------------------------------------------------------------
                |
                | INPUT_ORDER
                | RANDOM
                | RANKING
                | MANUAL
                |
                */

                $table
                    ->string('seeding_mode', 30)
                    ->default('INPUT_ORDER');

                /*
                |--------------------------------------------------------------------------
                | Pairing
                |--------------------------------------------------------------------------
                |
                | STANDARD_SEEDED
                | SEQUENTIAL
                | RANDOM
                |
                */

                $table
                    ->string('pairing_mode', 30)
                    ->default('STANDARD_SEEDED');

                /*
                |--------------------------------------------------------------------------
                | Asignación de BYE
                |--------------------------------------------------------------------------
                |
                | TOP_SEEDS
                | RANDOM
                | MANUAL
                |
                */

                $table
                    ->string('bye_assignment', 30)
                    ->default('TOP_SEEDS');

                /*
                |--------------------------------------------------------------------------
                | Reseeding
                |--------------------------------------------------------------------------
                */

                $table
                    ->boolean('reseed_each_round')
                    ->default(false);

                /*
                |--------------------------------------------------------------------------
                | Best Of
                |--------------------------------------------------------------------------
                |
                | Regla por defecto.
                | Las rondas específicas pueden sobrescribirla.
                |
                */

                $table
                    ->unsignedTinyInteger('default_best_of')
                    ->default(1);

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
            'phase_single_elimination_settings'
        );
    }
};
