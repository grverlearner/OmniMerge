<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Actividad del Universo
    |--------------------------------------------------------------------------
    |
    | Podría derivarse de tournament_instance_events, pero esos eventos
    | son del MOTOR (DISPATCH_START, NODE_COMPLETED...), no del mundo.
    | Una tabla propia y pequeña, escrita solo en los momentos que
    | importan, es más honesta y mucho más barata de leer.
    |
    */

    public function up(): void
    {
        Schema::create(
            'universe_activities',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('universe_id');
                $table->foreignId('universe_season_id')->nullable();
                $table->foreignId('universe_entity_id')->nullable();
                $table->foreignId('tournament_instance_id')->nullable();

                /*
                 * COMPETITION_STARTED | COMPETITION_COMPLETED |
                 * CHAMPION_CROWNED | SEASON_STARTED | ENTITIES_IMPORTED
                 */
                $table->string('type', 40);

                $table->string('icon', 8)->nullable();
                $table->string('message', 255);
                $table->json('context')->nullable();

                $table->timestamp('occurred_at');
                $table->timestamps();

                /*
                 * Nombres explícitos: los que generaría Laravel superan
                 * el límite de 64 caracteres de MySQL.
                 */
                $table->foreign('universe_id', 'uniact_universe_fk')
                    ->references('id')->on('universes')->cascadeOnDelete();

                $table->foreign('universe_season_id', 'uniact_season_fk')
                    ->references('id')->on('universe_seasons')->nullOnDelete();

                $table->foreign('universe_entity_id', 'uniact_entity_fk')
                    ->references('id')->on('universe_entities')->nullOnDelete();

                $table->foreign('tournament_instance_id', 'uniact_instance_fk')
                    ->references('id')->on('tournament_instances')->nullOnDelete();

                $table->index(
                    ['universe_id', 'occurred_at'],
                    'uniact_universe_time_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('universe_activities');
    }
};
