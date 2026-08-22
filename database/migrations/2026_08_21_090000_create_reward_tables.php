<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Fase 12 — Consecuencias permanentes
|--------------------------------------------------------------------------
|
| La Fase 11 decide cómo se resuelve un enfrentamiento. Esta decide qué
| queda cuando la competición termina.
|
| Recompensa permanente y modificador temporal viven en tablas distintas
| a propósito: se aplican en momentos distintos, tocan cosas distintas
| (las stats guardadas contra el estado congelado del torneo) y solo una
| de las dos necesita dejar rastro auditable.
|
| Ver docs/md/30-Fase-12-Recompensas-Y-Palmares.md
|
*/

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Trofeos. Se definen en el Universo y un torneo los otorga.
         */
        Schema::create(
            'universe_trophies',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('universe_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('name', 150);
                $table->string('description', 500)->nullable();

                $table->string('icon', 16)->nullable();
                $table->string('image')->nullable();

                /* Para el color de la tarjeta */
                $table->string('tier', 20)->default('GOLD');

                $table->timestamps();
            }
        );

        /*
         * Reglas de recompensa de un torneo.
         *
         * El juego y la stat se guardan como texto porque el esquema lo
         * declara el Game Engine, no la base de datos: un juego futuro con
         * fuerza y velocidad funciona aquí sin migración.
         */
        Schema::create(
            'universe_tournament_rewards',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('universe_tournament_id')
                    ->constrained()
                    ->cascadeOnDelete();

                /*
                 * Qué hay que hacer para ganarla.
                 * POSITION | PARTICIPATION | UNBEATEN | WIN_COUNT |
                 * ENCOUNTER_WIN_COUNT
                 */
                $table->string('trigger', 30);

                /* POSITION: 1, 2, 3... · WIN_COUNT: número de victorias */
                $table->unsignedInteger('threshold')->nullable();

                /* Null = el juego de la competición */
                $table->string('game_key', 60)->nullable();

                /* Null = la recompensa es solo un trofeo */
                $table->string('stat_key', 60)->nullable();

                $table->string('operation', 20)->default('ADD');

                $table->decimal('amount', 12, 4)->default(0);

                $table->foreignId('universe_trophy_id')
                    ->nullable()
                    ->constrained('universe_trophies')
                    ->nullOnDelete();

                $table->string('label', 150)->nullable();

                $table->boolean('is_active')->default(true);

                $table->timestamps();

                $table->index(
                    ['universe_tournament_id', 'trigger'],
                    'unitrew_tournament_trigger_idx'
                );
            }
        );

        /*
         * Modificadores temporales. No tocan nada guardado: se aplican al
         * preparar el enfrentamiento, sobre las stats ya congeladas.
         */
        Schema::create(
            'universe_tournament_modifiers',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('universe_tournament_id')
                    ->constrained()
                    ->cascadeOnDelete();

                /* TOURNAMENT | PHASE | ROUND */
                $table->string('scope', 20)->default('TOURNAMENT');

                /* Nombre de la fase o número de ronda */
                $table->string('scope_value', 120)->nullable();

                /* ALL | ENTITY */
                $table->string('target', 20)->default('ALL');

                $table->foreignId('universe_entity_id')
                    ->nullable()
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('game_key', 60)->nullable();
                $table->string('stat_key', 60);

                $table->string('operation', 20)->default('ADD');
                $table->decimal('amount', 12, 4)->default(0);

                $table->string('label', 150)->nullable();

                $table->boolean('is_active')->default(true);

                $table->timestamps();

                $table->index(
                    ['universe_tournament_id', 'scope'],
                    'unitmod_tournament_scope_idx'
                );
            }
        );

        /*
         * Historial de progresión: por qué cambió una stat.
         *
         * La clave única es lo que hace idempotente todo el sistema de
         * recompensas. Reprocesar una competición no puede duplicar nada,
         * aunque el proceso anterior se hubiese interrumpido a medias.
         */
        Schema::create(
            'universe_stat_changes',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('universe_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('universe_entity_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('universe_season_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->foreignId('tournament_instance_id')
                    ->nullable()
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('universe_tournament_reward_id')
                    ->nullable()
                    ->constrained('universe_tournament_rewards')
                    ->nullOnDelete();

                /* REWARD | RANKING | MANUAL */
                $table->string('source_type', 20)->default('REWARD');

                $table->string('game_key', 60);
                $table->string('stat_key', 60);

                $table->decimal('value_before', 14, 4)->nullable();
                $table->decimal('value_after', 14, 4)->nullable();
                $table->decimal('delta', 14, 4)->nullable();

                /* "Campeón", "Invicto", "#1 del ranking" */
                $table->string('reason', 200)->nullable();

                $table->timestamps();

                /*
                 * Un mismo motivo no puede aplicarse dos veces al mismo
                 * competidor en la misma competición y la misma stat.
                 */
                $table->unique(
                    [
                        'tournament_instance_id',
                        'universe_entity_id',
                        'game_key',
                        'stat_key',
                        'universe_tournament_reward_id',
                    ],
                    'unistat_idempotency_unique'
                );

                $table->index(
                    ['universe_entity_id', 'game_key'],
                    'unistat_entity_game_idx'
                );
            }
        );

        /*
         * Trofeos ganados.
         */
        Schema::create(
            'universe_trophy_awards',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('universe_trophy_id')
                    ->constrained('universe_trophies')
                    ->cascadeOnDelete();

                $table->foreignId('universe_entity_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('universe_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('tournament_instance_id')
                    ->nullable()
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('universe_season_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->unsignedInteger('position')->nullable();

                $table->timestamp('awarded_at')->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'universe_trophy_id',
                        'universe_entity_id',
                        'tournament_instance_id',
                    ],
                    'unitroa_idempotency_unique'
                );
            }
        );

        /*
         * Marca de proceso. No es la garantía de idempotencia —esa es la
         * clave única de arriba— sino la forma barata de no repetir
         * trabajo inútil en cada acción del motor.
         */
        Schema::table(
            'tournament_instances',
            function (Blueprint $table) {

                $table->timestamp('rewards_processed_at')
                    ->nullable()
                    ->after('completed_at');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'tournament_instances',
            fn(Blueprint $table) => $table->dropColumn('rewards_processed_at')
        );

        Schema::dropIfExists('universe_trophy_awards');
        Schema::dropIfExists('universe_stat_changes');
        Schema::dropIfExists('universe_tournament_modifiers');
        Schema::dropIfExists('universe_tournament_rewards');
        Schema::dropIfExists('universe_trophies');
    }
};
