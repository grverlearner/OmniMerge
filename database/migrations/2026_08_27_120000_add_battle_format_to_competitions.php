<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| El formato de batalla vive en la COMPETICIÓN
|--------------------------------------------------------------------------
|
| Cuántos juegos tiene un enfrentamiento no describe la forma de un torneo:
| describe cómo se juega una edición concreta. La misma plantilla puede
| jugarse al mejor de 3 este año y al mejor de 5 el que viene, sin que la
| plantilla cambie ni una coma.
|
| Las columnas siguen existiendo en las tablas de ajustes de fase -el motor
| las lee y son el valor por defecto- pero dejan de editarse ahí. Quien
| decide es la competición.
|
| Dos niveles:
|
|   tournament_instances        el formato de toda la competición
|   tournament_instance_phases  la excepción de una fase concreta
|
| Una fase con NULL hereda el de su competición. Es lo normal: lo habitual
| es "todo al mejor de 3", y lo excepcional "menos la final, que es al 5".
|
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_instances', function (Blueprint $table) {

            $table->string('series_format', 20)
                ->default('BEST_OF')
                ->after('tournament_template_id');

            $table->unsignedTinyInteger('best_of')
                ->default(1)
                ->after('series_format');

            $table->unsignedTinyInteger('fixed_games')
                ->default(1)
                ->after('best_of');
        });

        Schema::table('tournament_instance_phases', function (Blueprint $table) {

            /*
             * Nulos a proposito: null significa "lo que diga la
             * competicion", no un formato en si. Con un valor por defecto
             * cada fase nacería con una excepcion que nadie pidio.
             */
            $table->string('series_format', 20)
                ->nullable()
                ->after('phase_type');

            $table->unsignedTinyInteger('best_of')
                ->nullable()
                ->after('series_format');

            $table->unsignedTinyInteger('fixed_games')
                ->nullable()
                ->after('best_of');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_instances', function (Blueprint $table) {
            $table->dropColumn(['series_format', 'best_of', 'fixed_games']);
        });

        Schema::table('tournament_instance_phases', function (Blueprint $table) {
            $table->dropColumn(['series_format', 'best_of', 'fixed_games']);
        });
    }
};
