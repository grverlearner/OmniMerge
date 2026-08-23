<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Detalle de la tirada
|--------------------------------------------------------------------------
|
| Hasta ahora se guardaba el valor con el que se compite y nada más. Para
| Highest Number eso basta: el número que sale es el número que cuenta.
|
| Rounded Number no funciona así. Ahí se genera 2.68 y se compite con 3, y
| el 2.68 explica el 3: sin él un empate a 3 parece un azar plano cuando en
| realidad uno sacó 2.51 y el otro 3.49. Ese dato lo devuelve el engine en
| detail y se perdía al guardar.
|
| Es una columna genérica, no una columna "raw": cada engine decide qué
| detalle merece sobrevivir al enfrentamiento.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'game_encounter_participants',
            function (Blueprint $table) {

                $table->json('detail')
                    ->nullable()
                    ->after('stats_used');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'game_encounter_participants',
            function (Blueprint $table) {

                $table->dropColumn('detail');
            }
        );
    }
};
