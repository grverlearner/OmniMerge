<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Encuentros de una competición
    |--------------------------------------------------------------------------
    |
    | Proyección consultable de los encuentros que viven dentro del estado
    | del motor. Permite listar "qué queda por jugar" y "qué se jugó" sin
    | recorrer el JSON.
    |
    | Los participantes se referencian por runtime_key (la clave del motor)
    | y además se copia el nombre, para que un encuentro histórico siga
    | siendo legible aunque el competidor desaparezca del Universo.
    |
    | series guarda los juegos individuales cuando la fase usa BO3/BO5.
    |
    */

    public function up(): void
    {
        Schema::create(
            'tournament_instance_matches',
            function (Blueprint $table) {

                $table->id();

                $table
                    ->foreignId('tournament_instance_id')
                    ->constrained('tournament_instances')
                    ->cascadeOnDelete();

                $table->unsignedBigInteger(
                    'node_id'
                );

                /*
                 * Identificador del encuentro dentro del runtime.
                 */
                $table->string(
                    'runtime_match_id',
                    120
                );

                $table
                    ->unsignedInteger('round_number')
                    ->nullable();

                $table
                    ->string('label', 190)
                    ->nullable();

                $table
                    ->string('status', 30)
                    ->default('PENDING');

                /*
                |--------------------------------------------------------------------------
                | Participantes
                |--------------------------------------------------------------------------
                */

                $table->string('participant_a_key', 60)->nullable();
                $table->string('participant_b_key', 60)->nullable();

                $table->string('participant_a_name', 150)->nullable();
                $table->string('participant_b_name', 150)->nullable();

                /*
                |--------------------------------------------------------------------------
                | Resultado
                |--------------------------------------------------------------------------
                */

                $table->integer('score_a')->nullable();
                $table->integer('score_b')->nullable();

                $table->string('winner_key', 60)->nullable();
                $table->string('loser_key', 60)->nullable();

                $table->boolean('is_draw')->default(false);

                $table->json('series')->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'tournament_instance_id',
                        'runtime_match_id',
                    ],
                    'tmatch_instance_match_unique'
                );

                $table->index(
                    [
                        'tournament_instance_id',
                        'status',
                    ],
                    'tmatch_instance_status_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'tournament_instance_matches'
        );
    }
};
