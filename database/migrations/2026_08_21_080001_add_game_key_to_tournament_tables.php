<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| El juego se elige en el torneo del Universo y se congela en la instancia
| al arrancar, igual que el snapshot de la plantilla: cambiar el juego del
| torneo después no debe alterar una competición ya en curso.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'universe_tournaments',
            function (Blueprint $table) {

                $table->string('game_key', 60)
                    ->nullable()
                    ->after('tournament_template_id');
            }
        );

        Schema::table(
            'tournament_instances',
            function (Blueprint $table) {

                $table->string('game_key', 60)
                    ->nullable()
                    ->after('universe_tournament_id');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'universe_tournaments',
            fn(Blueprint $table) => $table->dropColumn('game_key')
        );

        Schema::table(
            'tournament_instances',
            fn(Blueprint $table) => $table->dropColumn('game_key')
        );
    }
};
