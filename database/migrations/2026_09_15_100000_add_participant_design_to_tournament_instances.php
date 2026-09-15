<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| La configuracion de participantes de cada edicion
|--------------------------------------------------------------------------
|
| Una edicion puede usar lo que el torneo configuro en su sala de
| participantes, o tener lo suyo: otras condiciones, otro reparto por
| puertas, otras caras. Se guarda aqui para poder retocarla mientras no
| haya empezado y para que la siguiente edicion pueda copiarla.
|
| Solo añade una columna vacia: no toca ninguna fila.
|
| Ver docs/md/80-Participantes-De-Cada-Edicion.md
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_instances', function (Blueprint $table) {
            $table->json('participant_design')->nullable()->after('start_rules');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_instances', function (Blueprint $table) {
            $table->dropColumn('participant_design');
        });
    }
};
