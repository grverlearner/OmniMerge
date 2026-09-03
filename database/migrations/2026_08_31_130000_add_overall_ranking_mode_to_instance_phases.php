<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Una edición puede ordenar sus grupos a su manera
|--------------------------------------------------------------------------
|
| Cómo se construye la lista única de una fase de grupos —comparando a todos,
| o por el puesto que ocupó cada uno en su grupo— lo decide la plantilla. Es
| lo correcto: es una regla de forma, y la forma es de la plantilla.
|
| Pero también es exactamente el tipo de cosa que una edición concreta quiere
| cambiar sin tocar la plantilla, igual que ya cambia el juego o el formato de
| batalla de una fase suelta. Un año se reparten las plazas por rendimiento
| puro y al siguiente se decide que ganar tu grupo tiene que valer más.
|
| Nulo —que es el valor de todas las filas existentes— significa «lo que diga
| la plantilla», que es el caso normal y el que no hay que tocar.
|
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_instance_phases', function (Blueprint $table) {

            $table->string('overall_ranking_mode', 40)
                ->nullable()
                ->after('game_key');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_instance_phases', function (Blueprint $table) {
            $table->dropColumn('overall_ranking_mode');
        });
    }
};
