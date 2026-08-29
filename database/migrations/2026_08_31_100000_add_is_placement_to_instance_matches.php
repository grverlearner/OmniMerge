<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Una batalla de puestos no es una ronda del cuadro
|--------------------------------------------------------------------------
|
| Cuando una fase disputa el 3.o, el 7.o o el 13.o, el motor crea rondas
| nuevas DESPUES de la final. Son batallas de verdad y se guardan como
| tales, pero no son parte del cuadro, y confundirlas con una ronda mas
| tiene consecuencias:
|
|   - «hasta donde llego» (round_reached) se calcula con el numero de ronda
|     mas alto en el que aparece cada uno. Sin distinguir, quien jugo el
|     desempate por el 13.o en la ronda 10 figuraba como si hubiera llegado
|     mas lejos que el campeon, que gano la final en la 4. Eso decide el
|     orden del que cuelgan los premios por puesto.
|
|   - la pantalla del cuadro las dibujaba como columnas suyas, rompiendo el
|     embudo: cada ronda tiene la mitad de gente que la anterior, y cuatro
|     batallas para separar 9.o-16.o no encajan ahi.
|
| Se marca en la fila y no se deduce de la etiqueta del grupo, que ya
| significa otra cosa en fase de grupos.
|
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_instance_matches', function (Blueprint $table) {

            $table->boolean('is_placement')
                ->default(false)
                ->after('group_label');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_instance_matches', function (Blueprint $table) {
            $table->dropColumn('is_placement');
        });
    }
};
