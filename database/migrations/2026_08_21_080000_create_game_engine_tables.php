<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Fase 11 — Motor de Juegos
|--------------------------------------------------------------------------
|
| No se crean tablas de Battle ni de Encounter: la Battle ya es un match
| con su serie (MatchSeriesRuntime) y el Encounter ya es un juego de esa
| serie. Aquí solo vive lo que de verdad falta.
|
| Los juegos NO tienen tabla: se declaran en código (GameRegistry). Lo que
| sí se guarda es qué juegos usa cada Universo y con qué configuración.
|
| Ver docs/md/29-Fase-11-Motor-De-Juegos.md
|
*/

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Juegos habilitados por Universo.
         */
        Schema::create(
            'universe_games',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('universe_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('game_key', 60);

                $table->boolean('is_enabled')->default(true);
                $table->boolean('is_default')->default(false);

                /*
                 * Configuración propia del juego dentro de este Universo.
                 * Su forma la define cada engine, no esta tabla.
                 */
                $table->json('configuration')->nullable();

                $table->timestamps();

                $table->unique(
                    ['universe_id', 'game_key'],
                    'unigame_universe_key_unique'
                );
            }
        );

        /*
         * Game Stats del competidor. Pertenecen al UniverseEntity, jamás a
         * la Entity canónica de la Biblioteca.
         *
         * El JSON es lo que permite que Highest Number use min/max y otro
         * juego use strength/speed/defense sin migración nueva.
         */
        Schema::create(
            'universe_entity_game_stats',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('universe_entity_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('game_key', 60);

                $table->json('stats')->nullable();

                $table->timestamps();

                $table->unique(
                    ['universe_entity_id', 'game_key'],
                    'unient_game_stats_unique'
                );
            }
        );

        /*
         * Registro estructurado de cada Encounter resuelto. Es la materia
         * prima del historial y de las recompensas futuras: quién, con qué
         * juego, en qué torneo y en qué temporada.
         */
        Schema::create(
            'game_encounters',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('universe_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('tournament_instance_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->foreignId('universe_season_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->string('game_key', 60);

                /* Identidad de la Battle dentro del runtime */
                $table->string('battle_key', 120);
                $table->string('node_id', 120)->nullable();
                $table->string('phase_name', 150)->nullable();

                $table->unsignedInteger('encounter_number')->default(1);
                $table->unsignedInteger('participant_count')->default(0);

                $table->boolean('is_draw')->default(false);

                $table->foreignId('winner_universe_entity_id')
                    ->nullable()
                    ->constrained('universe_entities')
                    ->nullOnDelete();

                /* Resultado completo tal como lo devolvió el engine */
                $table->json('payload')->nullable();

                $table->timestamps();

                $table->index(
                    ['universe_id', 'game_key'],
                    'gameenc_universe_game_idx'
                );

                $table->unique(
                    ['tournament_instance_id', 'battle_key', 'encounter_number'],
                    'gameenc_battle_number_unique'
                );
            }
        );

        /*
         * Una fila por participante y Encounter. Es lo que permite derivar
         * victorias, derrotas y win rate sin almacenar contadores que se
         * puedan desincronizar.
         */
        Schema::create(
            'game_encounter_participants',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('game_encounter_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('universe_entity_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->string('participant_key', 120);
                $table->string('name', 200)->nullable();

                $table->decimal('value', 14, 4)->nullable();
                $table->string('display_value', 60)->nullable();

                $table->unsignedInteger('position')->default(1);

                $table->boolean('is_winner')->default(false);

                /* Stats que se usaron para generar el valor */
                $table->json('stats_used')->nullable();

                $table->timestamps();

                $table->index(
                    ['universe_entity_id'],
                    'gameencp_entity_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('game_encounter_participants');
        Schema::dropIfExists('game_encounters');
        Schema::dropIfExists('universe_entity_game_stats');
        Schema::dropIfExists('universe_games');
    }
};
