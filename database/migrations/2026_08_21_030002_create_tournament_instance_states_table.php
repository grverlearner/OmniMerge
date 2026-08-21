<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Estado vivo del motor
    |--------------------------------------------------------------------------
    |
    | Es exactamente el mismo array que hoy viaja cifrado en el token del
    | Competition Lab (participants, starts, nodes, connections, terminals,
    | timeline, summary, graph_runtime), pero guardado en base de datos.
    |
    | Sustituye al token y a sessionStorage: el torneo deja de depender de
    | la sesión del usuario.
    |
    | No se normaliza porque el motor trabaja sobre el array completo;
    | normalizarlo obligaría a reescribir el motor. Las tablas de
    | participantes, fases, encuentros y eventos son PROYECCIONES
    | consultables de este estado, no su sustituto.
    |
    | revision implementa bloqueo optimista: dos pestañas abiertas no
    | pueden pisarse los resultados.
    |
    */

    public function up(): void
    {
        Schema::create(
            'tournament_instance_states',
            function (Blueprint $table) {

                $table->id();

                $table
                    ->foreignId('tournament_instance_id')
                    ->constrained('tournament_instances')
                    ->cascadeOnDelete();

                $table
                    ->unsignedInteger('schema_version')
                    ->default(1);

                $table
                    ->unsignedInteger('revision')
                    ->default(0);

                $table->longText(
                    'state'
                );

                $table->timestamps();

                $table->unique(
                    'tournament_instance_id',
                    'tstate_instance_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'tournament_instance_states'
        );
    }
};
