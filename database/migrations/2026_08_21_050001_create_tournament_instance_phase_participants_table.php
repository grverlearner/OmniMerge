<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Rendimiento de un participante DENTRO de una fase
    |--------------------------------------------------------------------------
    |
    | La única tabla nueva de la Fase 8.
    |
    | Los motores ya calculan esta clasificación: Round Robin la deja en
    | runtime['standings'] y Group Stage en runtime['groups'][].standings.
    | Hoy solo vive dentro del JSON del estado, así que no se puede
    | consultar ni ordenar.
    |
    | Sin esta tabla no hay "posición en el grupo", ni tabla de
    | clasificación consultable, ni rendimiento por motor.
    |
    | Ojo: es distinta de tournament_instance_participants, que agrega el
    | rendimiento de TODA la competición. Un participante puede jugar
    | varias fases y rendir distinto en cada una.
    |
    */

    public function up(): void
    {
        Schema::create(
            'tournament_instance_phase_participants',
            function (Blueprint $table) {

                $table->id();

                /*
                 * Las claves foráneas se nombran a mano: el nombre que
                 * generaría Laravel con este nombre de tabla supera el
                 * límite de 64 caracteres de MySQL.
                 */
                $table->foreignId(
                    'tournament_instance_id'
                );

                $table->foreignId(
                    'tournament_instance_phase_id'
                );

                $table->string(
                    'runtime_key',
                    60
                );

                /*
                 * Desnormalizado para poder agregar por Entidad sin join.
                 */
                $table
                    ->foreignId('entity_id')
                    ->nullable();

                $table
                    ->foreign(
                        'tournament_instance_id',
                        'tiphpart_instance_fk'
                    )
                    ->references('id')
                    ->on('tournament_instances')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'tournament_instance_phase_id',
                        'tiphpart_phase_fk'
                    )
                    ->references('id')
                    ->on('tournament_instance_phases')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'entity_id',
                        'tiphpart_entity_fk'
                    )
                    ->references('id')
                    ->on('entities')
                    ->nullOnDelete();

                $table->string(
                    'participant_name',
                    150
                );

                /*
                 * Solo Group Stage lo rellena.
                 */
                $table
                    ->string('group_label', 60)
                    ->nullable();

                $table
                    ->unsignedInteger('position')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Rendimiento dentro de la fase
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger('matches')->default(0);
                $table->unsignedInteger('wins')->default(0);
                $table->unsignedInteger('draws')->default(0);
                $table->unsignedInteger('losses')->default(0);
                $table->integer('points')->default(0);

                $table->integer('score_for')->default(0);
                $table->integer('score_against')->default(0);
                $table->integer('score_difference')->default(0);

                /*
                 * ADVANCED | ELIMINATED | PLAYED
                 */
                $table
                    ->string('status', 20)
                    ->default('PLAYED');

                $table->timestamps();

                $table->unique(
                    [
                        'tournament_instance_phase_id',
                        'runtime_key',
                    ],
                    'tiphpart_phase_key_unique'
                );

                $table->index(
                    [
                        'entity_id',
                    ],
                    'tiphpart_entity_index'
                );

                $table->index(
                    [
                        'tournament_instance_id',
                    ],
                    'tiphpart_instance_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'tournament_instance_phase_participants'
        );
    }
};
