<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Un enfrentamiento puede tener mas de dos participantes
|--------------------------------------------------------------------------
|
| La tabla solo tenia sitio para dos -participant_a_* y participant_b_*-, y
| eso bastaba mientras todo fuese un duelo. No lo es: una fase puede cruzar
| de cuatro en cuatro, y entonces la proyeccion se quedaba con los dos
| primeros y tiraba a los otros dos.
|
| El sintoma era desconcertante: la competicion decia 16 participantes y al
| entrar en la fase salian 8. No faltaba nadie -el motor los tenia todos-,
| es que la pantalla lee de aqui y aqui solo cabian dos por encuentro.
|
| Las dos columnas viejas se quedan: media aplicacion las usa, un duelo
| sigue siendo el caso normal, y para un duelo dicen exactamente lo mismo
| que la lista.
|
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_instance_matches', function (Blueprint $table) {

            /*
             * [ {key, name, universe_entity_id, score, is_winner, is_out} ]
             *
             * En el orden en que el motor los coloco, que es el orden en el
             * que se ven en el cuadro.
             */
            $table->json('participants')
                ->nullable()
                ->after('participant_b_universe_entity_id');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_instance_matches', function (Blueprint $table) {
            $table->dropColumn('participants');
        });
    }
};
