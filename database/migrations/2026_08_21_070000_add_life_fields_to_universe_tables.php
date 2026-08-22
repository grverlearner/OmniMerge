<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Fase 10 — lo que da vida al Universo
    |--------------------------------------------------------------------------
    |
    | Ver docs/md/28-Fase-10-Universo-Vivo.md
    |
    */

    public function up(): void
    {
        Schema::table(
            'universe_tournaments',
            function (Blueprint $table) {

                $table
                    ->string('image')
                    ->nullable()
                    ->after('description');

                /*
                 * Ambientación y reglas propias del Universo: lo que
                 * hace que un torneo sea "los Exámenes Chunin" y no
                 * "plantilla de eliminación 16".
                 */
                $table
                    ->text('context')
                    ->nullable()
                    ->after('image');

                /*
                 * Recurrencia calculada, no agendada: la definición dice
                 * cada cuánto ocurre y un método responde si toca en una
                 * temporada dada. Sin scheduler.
                 *
                 * ONCE | EVERY_SEASON | EVERY_N_SEASONS | MANUAL
                 */
                $table
                    ->string('recurrence_mode', 20)
                    ->default('ONCE')
                    ->after('context');

                $table
                    ->unsignedInteger('recurrence_interval')
                    ->nullable()
                    ->after('recurrence_mode');

                $table
                    ->unsignedInteger('first_season_number')
                    ->nullable()
                    ->after('recurrence_interval');
            }
        );

        Schema::table(
            'universe_entities',
            function (Blueprint $table) {

                /*
                 * Progresión PREPARADA, sin motor que la modifique.
                 *
                 * Forma: { "Poder": {"initial":50,"current":50,"min":1,"max":100} }
                 *
                 * Existe para que una recompensa futura tenga dónde
                 * escribir sin rehacer el modelo. Hoy nadie la cambia.
                 */
                $table
                    ->json('progression')
                    ->nullable()
                    ->after('version_snapshot');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'universe_tournaments',
            function (Blueprint $table) {

                $table->dropColumn([
                    'image',
                    'context',
                    'recurrence_mode',
                    'recurrence_interval',
                    'first_season_number',
                ]);
            }
        );

        Schema::table(
            'universe_entities',
            function (Blueprint $table) {

                $table->dropColumn('progression');
            }
        );
    }
};
