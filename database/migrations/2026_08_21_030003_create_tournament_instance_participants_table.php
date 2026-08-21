<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Participantes congelados de una ejecución
    |--------------------------------------------------------------------------
    |
    | La competición NO depende de la lista actual de UniverseCompetitors.
    | El nombre y el seed se copian al crear la competición y ya no cambian.
    |
    | universe_competitor_id y entity_id son nullOnDelete: si más adelante
    | se retira ese competidor del Universo o se borra la entidad, el
    | histórico conserva el nombre congelado y solo pierde el enlace.
    |
    */

    public function up(): void
    {
        Schema::create(
            'tournament_instance_participants',
            function (Blueprint $table) {

                $table->id();

                $table
                    ->foreignId('tournament_instance_id')
                    ->constrained('tournament_instances')
                    ->cascadeOnDelete();

                /*
                 * Clave del participante dentro del estado del motor.
                 * Es el puente entre la proyección y el JSON.
                 */
                $table->string(
                    'runtime_key',
                    60
                );

                $table
                    ->foreignId('universe_competitor_id')
                    ->nullable()
                    ->constrained('universe_competitors')
                    ->nullOnDelete();

                $table
                    ->foreignId('entity_id')
                    ->nullable()
                    ->constrained('entities')
                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Datos congelados
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'name',
                    150
                );

                $table
                    ->unsignedInteger('seed')
                    ->default(0);

                $table
                    ->unsignedBigInteger('source_start_id')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Estado y estadísticas (proyección del runtime)
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('status', 30)
                    ->default('WAITING');

                $table->unsignedInteger('matches')->default(0);
                $table->unsignedInteger('wins')->default(0);
                $table->unsignedInteger('draws')->default(0);
                $table->unsignedInteger('losses')->default(0);
                $table->integer('points')->default(0);

                $table
                    ->string('final_location_type', 30)
                    ->nullable();

                $table
                    ->string('final_location_name', 150)
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'tournament_instance_id',
                        'runtime_key',
                    ],
                    'tpart_instance_key_unique'
                );

                $table->index(
                    [
                        'tournament_instance_id',
                        'status',
                    ],
                    'tpart_instance_status_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'tournament_instance_participants'
        );
    }
};
