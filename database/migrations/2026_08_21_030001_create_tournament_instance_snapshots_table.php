<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Snapshot inmutable de configuración
    |--------------------------------------------------------------------------
    |
    | Congela TODO lo que el motor lee de la plantilla en el momento de
    | crear la competición: TournamentTemplate, starts, nodes, cada
    | PhaseTemplate con sus salidas, puertas, puertos de entrada, ajustes
    | y reglas de SE/RR/GS, estructura interna de SE, grupos de GS,
    | conexiones y terminales.
    |
    | El alcance no es una suposición: es exactamente el árbol de
    | relaciones que carga TournamentGraphRuntimeService::loadGraph().
    |
    | Se escribe UNA vez. No tiene updated_at a propósito.
    |
    */

    public function up(): void
    {
        Schema::create(
            'tournament_instance_snapshots',
            function (Blueprint $table) {

                $table->id();

                $table
                    ->foreignId('tournament_instance_id')
                    ->constrained('tournament_instances')
                    ->cascadeOnDelete();

                /*
                 * Permite detectar y rechazar con un mensaje claro un
                 * snapshot con formato antiguo, en vez de romperse.
                 */
                $table
                    ->unsignedInteger('schema_version')
                    ->default(1);

                /*
                 * Huella del contenido congelado. Sirve para comprobar
                 * que un snapshot no fue alterado.
                 */
                $table
                    ->string('hash', 64)
                    ->nullable();

                $table->longText(
                    'snapshot'
                );

                $table->timestamp(
                    'created_at'
                )->nullable();

                $table->unique(
                    'tournament_instance_id',
                    'tsnap_instance_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'tournament_instance_snapshots'
        );
    }
};
