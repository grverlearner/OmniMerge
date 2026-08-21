<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Campos de historial (Fase 8)
    |--------------------------------------------------------------------------
    |
    | Todo lo que se añade aquí YA existe dentro del estado del motor: el
    | tipo de terminal, el ganador, la ronda. Lo que falta es proyectarlo
    | a columnas consultables para poder responder "¿quién fue campeón?"
    | con una consulta en lugar de recorriendo JSON.
    |
    | Ver docs/md/26-Fase-8-Historial-Y-Estadisticas.md
    |
    */

    public function up(): void
    {
        Schema::table(
            'tournament_instance_participants',
            function (Blueprint $table) {

                /*
                 * Desenlace del participante en la competición.
                 * CHAMPION | ELIMINATED | QUALIFIED | UNPLACED
                 *
                 * Sale del tipo del terminal donde acabó, que hoy se
                 * pierde al proyectar (solo se guarda su nombre).
                 */
                $table
                    ->string('outcome', 30)
                    ->nullable()
                    ->after('final_location_name');

                /*
                 * Posición final. Nula a propósito cuando el grafo no
                 * produce un orden completo: mejor sin dato que con una
                 * posición inventada.
                 */
                $table
                    ->unsignedInteger('placement')
                    ->nullable()
                    ->after('outcome');

                /*
                 * Ronda más lejana alcanzada. Solo tiene sentido en
                 * motores con rondas eliminatorias.
                 */
                $table
                    ->unsignedInteger('round_reached')
                    ->nullable()
                    ->after('placement');

                $table->index(
                    [
                        'entity_id',
                        'outcome',
                    ],
                    'tipart_entity_outcome_index'
                );
            }
        );

        Schema::table(
            'tournament_instance_matches',
            function (Blueprint $table) {

                /*
                 * Desnormalización deliberada.
                 *
                 * Los encuentros guardan runtime_key ('UC-000123'), que
                 * solo significa algo dentro de SU competición. Para el
                 * head-to-head y el historial por Entidad haría falta un
                 * join a participantes en cada consulta.
                 *
                 * Esta es una tabla de proyección, no de dominio:
                 * desnormalizar es exactamente su función, y se
                 * recalcula entera en cada proyección.
                 */
                $table
                    ->foreignId('participant_a_entity_id')
                    ->nullable()
                    ->after('participant_b_name')
                    ->constrained('entities')
                    ->nullOnDelete();

                $table
                    ->foreignId('participant_b_entity_id')
                    ->nullable()
                    ->after('participant_a_entity_id')
                    ->constrained('entities')
                    ->nullOnDelete();

                $table
                    ->foreignId('winner_entity_id')
                    ->nullable()
                    ->after('participant_b_entity_id')
                    ->constrained('entities')
                    ->nullOnDelete();

                /*
                 * Grupo al que pertenece el encuentro (Group Stage).
                 */
                $table
                    ->string('group_label', 60)
                    ->nullable()
                    ->after('label');

                /*
                 * Permite ordenar encuentros cronológicamente, que es lo
                 * que hace fiables las rachas.
                 */
                $table
                    ->timestamp('completed_at')
                    ->nullable()
                    ->after('series');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'tournament_instance_participants',
            function (Blueprint $table) {

                $table->dropIndex(
                    'tipart_entity_outcome_index'
                );

                $table->dropColumn([
                    'outcome',
                    'placement',
                    'round_reached',
                ]);
            }
        );

        Schema::table(
            'tournament_instance_matches',
            function (Blueprint $table) {

                $table->dropConstrainedForeignId(
                    'participant_a_entity_id'
                );

                $table->dropConstrainedForeignId(
                    'participant_b_entity_id'
                );

                $table->dropConstrainedForeignId(
                    'winner_entity_id'
                );

                $table->dropColumn([
                    'group_label',
                    'completed_at',
                ]);
            }
        );
    }
};
