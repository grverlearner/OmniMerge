<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Fases que ejecuta una competición
    |--------------------------------------------------------------------------
    |
    | Proyección de state.nodes: una fila por nodo del Tournament Graph
    | congelado, con su estado actual.
    |
    | node_id NO es una foreign key a tournament_phase_nodes: apunta al
    | nodo tal y como quedó congelado en el snapshot. Si el nodo se borra
    | de la plantilla viva, la competición histórica sigue siendo legible.
    |
    */

    public function up(): void
    {
        Schema::create(
            'tournament_instance_phases',
            function (Blueprint $table) {

                $table->id();

                $table
                    ->foreignId('tournament_instance_id')
                    ->constrained('tournament_instances')
                    ->cascadeOnDelete();

                $table->unsignedBigInteger(
                    'node_id'
                );

                $table
                    ->string('node_code', 30)
                    ->nullable();

                $table->string(
                    'node_name',
                    150
                );

                $table
                    ->string('phase_type', 40)
                    ->nullable();

                $table
                    ->string('status', 30)
                    ->default('LOCKED');

                $table
                    ->unsignedInteger('participant_count')
                    ->default(0);

                $table->timestamps();

                $table->unique(
                    [
                        'tournament_instance_id',
                        'node_id',
                    ],
                    'tphase_instance_node_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'tournament_instance_phases'
        );
    }
};
