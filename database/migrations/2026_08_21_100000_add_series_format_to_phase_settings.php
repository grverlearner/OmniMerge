<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Formato de serie en Round Robin y Group Stage
|--------------------------------------------------------------------------
|
| MatchSeriesRuntime lleva soportando FIXED_GAMES desde el principio, pero
| estos dos motores emitian 'BEST_OF' y 'fixed_games' => 1 a fuego: no
| habia forma de pedir "dos enfrentamientos fijos" en una liga o en un
| grupo, aunque el motor supiera jugarlos.
|
| BEST_OF y FIXED_GAMES no son lo mismo:
|
|   BO3       gana quien llegue a 2. Puede acabar en 2 juegos.
|   FIXED 2   se juegan los 2 SIEMPRE y decide el acumulado.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'phase_round_robin_settings',
            function (Blueprint $table) {

                $table->string('series_format', 20)
                    ->default('BEST_OF')
                    ->after('default_best_of');

                $table->unsignedTinyInteger('fixed_games')
                    ->default(1)
                    ->after('series_format');
            }
        );

        Schema::table(
            'phase_group_stage_settings',
            function (Blueprint $table) {

                $table->string('internal_series_format', 20)
                    ->default('BEST_OF')
                    ->after('internal_best_of');

                $table->unsignedTinyInteger('internal_fixed_games')
                    ->default(1)
                    ->after('internal_series_format');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'phase_round_robin_settings',
            fn(Blueprint $table) => $table->dropColumn(['series_format', 'fixed_games'])
        );

        Schema::table(
            'phase_group_stage_settings',
            fn(Blueprint $table) => $table->dropColumn(['internal_series_format', 'internal_fixed_games'])
        );
    }
};
