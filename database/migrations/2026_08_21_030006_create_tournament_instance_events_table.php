<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Ledger de eventos
    |--------------------------------------------------------------------------
    |
    | Append-only. Recoge el timeline que el motor ya genera hoy
    | (torneo iniciado, resultado registrado, participante eliminado,
    | salida activada, conexión resuelta, fase completada, torneo
    | finalizado...) y lo hace permanente.
    |
    | Nunca se reescribe ni se borra fila a fila: una vez ocurrido, un
    | evento es historia. Por eso solo lleva created_at.
    |
    */

    public function up(): void
    {
        Schema::create(
            'tournament_instance_events',
            function (Blueprint $table) {

                $table->id();

                $table
                    ->foreignId('tournament_instance_id')
                    ->constrained('tournament_instances')
                    ->cascadeOnDelete();

                /*
                 * Posición dentro del timeline del motor. Permite
                 * añadir solo los eventos nuevos tras cada acción.
                 */
                $table->unsignedInteger(
                    'sequence'
                );

                $table->string(
                    'type',
                    60
                );

                $table
                    ->string('level', 20)
                    ->default('INFO');

                $table->text(
                    'message'
                );

                $table
                    ->json('context')
                    ->nullable();

                $table->timestamp(
                    'created_at'
                )->nullable();

                $table->unique(
                    [
                        'tournament_instance_id',
                        'sequence',
                    ],
                    'tevent_instance_sequence_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'tournament_instance_events'
        );
    }
};
