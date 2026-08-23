<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Qué parte de la clasificación se lleva el bonus
|--------------------------------------------------------------------------
|
| "Los N primeros" era demasiado poco. Un torneo interesante quiere decir
| cosas como "el subcampeón de la liga arranca los grupos con ventaja",
| "los semifinalistas eliminados se llevan algo", o "los cuatro últimos
| entran penalizados". Todo eso son cortes distintos sobre la misma tabla.
|
| Se adopta el mismo vocabulario que ya usan las puertas de salida, para
| no inventar un segundo idioma para lo mismo:
|
|   TOP_N          los N primeros
|   RANK_POSITION  un puesto exacto — el 2º, el 5º
|   RANK_RANGE     del puesto X al Y — semifinalistas son 3º y 4º
|   BOTTOM_N       los N últimos
|
| threshold desaparece: era el caso TOP_N y nada más. Ninguna regla lo
| usaba todavía.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'universe_tournament_modifiers',
            function (Blueprint $table) {

                $table->string('selector_type', 20)
                    ->nullable()
                    ->after('award_phase');

                $table->unsignedSmallInteger('selector_from')
                    ->nullable()
                    ->after('selector_type');

                $table->unsignedSmallInteger('selector_to')
                    ->nullable()
                    ->after('selector_from');
            }
        );

        /* Lo poco que hubiera, traducido en vez de perdido */
        DB::table('universe_tournament_modifiers')
            ->whereNotNull('threshold')
            ->update([
                'selector_type' => 'TOP_N',
                'selector_from' => DB::raw('threshold'),
            ]);

        Schema::table(
            'universe_tournament_modifiers',
            function (Blueprint $table) {

                $table->dropColumn('threshold');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'universe_tournament_modifiers',
            function (Blueprint $table) {

                $table->unsignedTinyInteger('threshold')->nullable();

                $table->dropColumn(['selector_type', 'selector_from', 'selector_to']);
            }
        );
    }
};
