<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Los premios de una edición también dejan rastro
|--------------------------------------------------------------------------
|
| Un premio puede venir del TORNEO —lo heredan todas sus ediciones— o de la
| EDICIÓN, que solo existe en esta. Los segundos se podían configurar desde
| hace tiempo y no se repartían nunca: el reparto solo leía los del torneo.
|
| Lo que lo impedía estaba aquí. El registro de cada cambio de stat apunta a
| la regla que lo causó, y esa columna solo sabía apuntar a un premio de
| torneo. Escribir ahí el id de un premio de edición habría señalado a otra
| fila —o a ninguna—, y el índice de idempotencia habría confundido dos
| premios distintos que casualmente compartieran número.
|
| Se añade la columna hermana, y el índice pasa a mirar las dos. Ninguna
| fila existente cambia: las de siempre siguen con su premio de torneo y la
| nueva columna a null.
|
| ---------------------------------------------------------------
|
| El índice nuevo se crea ANTES de tirar el viejo, y por eso se llama
| distinto. MySQL usa ese índice para sostener una clave foránea y se niega
| a soltarlo mientras sea el único que sirve: «cannot drop index, needed in
| a foreign key constraint». Con el nuevo ya puesto —mismas columnas de
| cabecera— la restricción tiene dónde apoyarse y el viejo se va sin drama.
|
*/
return new class extends Migration
{
    private const VIEJO = 'unistat_idempotency_unique';

    private const NUEVO = 'unistat_idempotency_unique_v2';

    public function up(): void
    {
        if (! Schema::hasColumn('universe_stat_changes', 'tournament_instance_reward_id')) {

            Schema::table('universe_stat_changes', function (Blueprint $table) {

                $table->foreignId('tournament_instance_reward_id')
                    ->nullable()
                    ->after('universe_tournament_reward_id')
                    ->constrained('tournament_instance_rewards')
                    ->nullOnDelete();
            });
        }

        Schema::table('universe_stat_changes', function (Blueprint $table) {

            $table->unique(
                [
                    'tournament_instance_id',
                    'universe_entity_id',
                    'game_key',
                    'stat_key',
                    'universe_tournament_reward_id',
                    'tournament_instance_reward_id',
                ],
                self::NUEVO
            );
        });

        Schema::table('universe_stat_changes', function (Blueprint $table) {
            $table->dropUnique(self::VIEJO);
        });
    }

    public function down(): void
    {
        Schema::table('universe_stat_changes', function (Blueprint $table) {

            $table->unique(
                [
                    'tournament_instance_id',
                    'universe_entity_id',
                    'game_key',
                    'stat_key',
                    'universe_tournament_reward_id',
                ],
                self::VIEJO
            );
        });

        Schema::table('universe_stat_changes', function (Blueprint $table) {
            $table->dropUnique(self::NUEVO);
        });

        Schema::table('universe_stat_changes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tournament_instance_reward_id');
        });
    }
};
