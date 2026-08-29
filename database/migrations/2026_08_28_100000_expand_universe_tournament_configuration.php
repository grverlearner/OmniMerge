<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Lo que un torneo oficial decide para todas sus competiciones
|--------------------------------------------------------------------------
|
| Un torneo es una marca: "la Copa". Lo que se guarda aqui es lo que TODAS
| sus ediciones heredan salvo que una diga otra cosa.
|
| Hasta ahora solo guardaba nombre, plantilla, juego y recurrencia. Faltaba
| todo lo que define como se pelea dentro y quien puede entrar.
|
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universe_tournaments', function (Blueprint $table) {

            /*
             * Un juego para todo el torneo, o uno distinto por edicion.
             *
             * SINGLE: la Copa se juega SIEMPRE a Highest Number.
             * VARIED: cada edicion elige el suyo, y `game_key` es solo la
             *         sugerencia por defecto.
             */
            $table->string('game_mode', 12)
                ->default('SINGLE')
                ->after('game_key');

            /*
             * Cuantos caben en una batalla: 2 son un duelo, 3 o mas una
             * batalla campal. NULL significa "lo decide cada fase", porque
             * un torneo puede tener grupos de cuatro y una final a dos.
             */
            $table->unsignedTinyInteger('battle_participants')
                ->nullable()
                ->after('game_mode');

            /*
             * El formato de batalla por defecto. La competicion puede
             * pisarlo, pero hereda esto si no dice nada.
             */
            $table->string('series_format', 20)
                ->default('BEST_OF')
                ->after('battle_participants');

            $table->unsignedTinyInteger('best_of')
                ->default(1)
                ->after('series_format');

            $table->unsignedTinyInteger('fixed_games')
                ->default(1)
                ->after('best_of');

            /*
             * Como se decide quien gana una batalla.
             *
             * SERIES_THEN_POINTS  manda el marcador; si empata, deciden las
             *                     anotaciones. Es lo que el motor ya hacia.
             * POINTS_ONLY         solo cuentan las anotaciones acumuladas,
             *                     aunque el marcador diga otra cosa.
             */
            $table->string('decision_mode', 24)
                ->default('SERIES_THEN_POINTS')
                ->after('fixed_games');

            /*
             * Si una batalla puede quedar en empate. Casi siempre no: un
             * cuadro necesita que alguien avance.
             */
            $table->boolean('allow_draws')
                ->default(false)
                ->after('decision_mode');

            /*
             * Quien puede competir, por atributos de las entidades del
             * universo. NULL = todo el mundo.
             *
             * JSON y no tablas: es un filtro, no una entidad. Su forma la
             * define UniverseTournamentEligibility.
             */
            $table->json('eligibility')
                ->nullable()
                ->after('allow_draws');
        });
    }

    public function down(): void
    {
        Schema::table('universe_tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'game_mode',
                'battle_participants',
                'series_format',
                'best_of',
                'fixed_games',
                'decision_mode',
                'allow_draws',
                'eligibility',
            ]);
        });
    }
};
