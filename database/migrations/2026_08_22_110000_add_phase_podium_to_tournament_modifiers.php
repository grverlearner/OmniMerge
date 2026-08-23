<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Bonos que se ganan jugando
|--------------------------------------------------------------------------
|
| Hasta ahora un bonus temporal se decidía de antemano: "el anfitrión
| tiene +1 velocidad". Se sabía a quién beneficiaba antes de empezar.
|
| Esto añade la otra mitad, que es la interesante: un bonus que se GANA
| durante el torneo. "Los 3 primeros de la liga entran a los grupos con
| +1 de techo". Quién lo recibe no se sabe hasta que la fase termina, y
| ahí es cuando se concede, ya listo para la fase siguiente.
|
|   award_phase   la fase cuyo podio lo concede
|   threshold     cuántos del podio lo reciben
|
| Sigue siendo temporal: vive en el estado de esa competición y muere con
| ella. Para una consecuencia permanente están las recompensas.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'universe_tournament_modifiers',
            function (Blueprint $table) {

                $table->string('award_phase', 120)
                    ->nullable()
                    ->after('scope_value');

                $table->unsignedTinyInteger('threshold')
                    ->nullable()
                    ->after('award_phase');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'universe_tournament_modifiers',
            function (Blueprint $table) {

                $table->dropColumn(['award_phase', 'threshold']);
            }
        );
    }
};
