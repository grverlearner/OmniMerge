<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Las estadísticas pasan a colgar de la entidad del Universo
    |--------------------------------------------------------------------------
    |
    | Hasta ahora las proyecciones de la Fase 8 guardaban entity_id, es
    | decir la entidad de BIBLIOTECA. Eso mezclaba en un solo montón lo
    | jugado en Universos distintos y daba historial competitivo a la
    | Biblioteca, que solo debería ser material reutilizable.
    |
    | Al repuntar a universe_entities, agregar estadísticas por entidad de
    | Biblioteca deja de ser posible: la separación la garantiza el
    | esquema, no la disciplina.
    |
    | No se pierde nada: los valores nuevos se deducen de los
    | participantes ya guardados.
    |
    */

    public function up(): void
    {
        /*
         * Cada bloque comprueba si ya se aplicó: MySQL no revierte DDL,
         * así que un fallo a mitad dejaría la migración sin poder
         * reintentarse.
         */

        /*
        |--------------------------------------------------------------------------
        | Participantes
        |--------------------------------------------------------------------------
        */

        if (Schema::hasColumn('tournament_instance_participants', 'universe_competitor_id')) {

            Schema::table(
                'tournament_instance_participants',
                function (Blueprint $table) {

                    $table->renameColumn(
                        'universe_competitor_id',
                        'universe_entity_id'
                    );
                }
            );
        }

        /*
         * entity_id se conserva como procedencia, nunca como clave de
         * agregación.
         */
        if (Schema::hasColumn('tournament_instance_participants', 'entity_id')) {

            Schema::table(
                'tournament_instance_participants',
                function (Blueprint $table) {

                    $table->renameColumn(
                        'entity_id',
                        'source_entity_id'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Encuentros
        |--------------------------------------------------------------------------
        */

        if (Schema::hasColumn('tournament_instance_matches', 'participant_a_entity_id')) {

            Schema::table(
                'tournament_instance_matches',
                function (Blueprint $table) {

                    $table->dropConstrainedForeignId('participant_a_entity_id');
                    $table->dropConstrainedForeignId('participant_b_entity_id');
                    $table->dropConstrainedForeignId('winner_entity_id');
                }
            );
        }

        foreach ([
            'participant_a_universe_entity_id' => 'participant_b_name',
            'participant_b_universe_entity_id' => 'participant_a_universe_entity_id',
            'winner_universe_entity_id' => 'participant_b_universe_entity_id',
        ] as $column => $after) {

            if (Schema::hasColumn('tournament_instance_matches', $column)) {
                continue;
            }

            Schema::table(
                'tournament_instance_matches',
                function (Blueprint $table) use ($column, $after) {

                    $table
                        ->foreignId($column)
                        ->nullable()
                        ->after($after);
                }
            );
        }

        /*
         * Nombres de clave foránea explícitos: los que generaría Laravel
         * con estos nombres de columna superan el límite de 64
         * caracteres de MySQL.
         */
        Schema::table(
            'tournament_instance_matches',
            function (Blueprint $table) {

                foreach ([
                    'participant_a_universe_entity_id' => 'timatch_ue_a_fk',
                    'participant_b_universe_entity_id' => 'timatch_ue_b_fk',
                    'winner_universe_entity_id' => 'timatch_ue_w_fk',
                ] as $column => $name) {

                    if ($this->constraintExists($name)) {
                        continue;
                    }

                    $table
                        ->foreign($column, $name)
                        ->references('id')
                        ->on('universe_entities')
                        ->nullOnDelete();
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Clasificación por fase
        |--------------------------------------------------------------------------
        */

        if (Schema::hasColumn('tournament_instance_phase_participants', 'entity_id')) {

            Schema::table(
                'tournament_instance_phase_participants',
                function (Blueprint $table) {

                    $table->dropForeign('tiphpart_entity_fk');
                    $table->dropIndex('tiphpart_entity_index');
                }
            );

            Schema::table(
                'tournament_instance_phase_participants',
                function (Blueprint $table) {

                    $table->renameColumn(
                        'entity_id',
                        'universe_entity_id'
                    );
                }
            );

            /*
             * La columna todavía contiene ids de Biblioteca. Hay que
             * traducirlos a entidades del Universo ANTES de crear la
             * clave foránea, o esta fallaría.
             */
            $this->backfill();

            Schema::table(
                'tournament_instance_phase_participants',
                function (Blueprint $table) {

                    $table
                        ->foreign(
                            'universe_entity_id',
                            'tiphpart_universe_entity_fk'
                        )
                        ->references('id')
                        ->on('universe_entities')
                        ->nullOnDelete();

                    $table->index(
                        'universe_entity_id',
                        'tiphpart_universe_entity_index'
                    );
                }
            );
        }

        $this->backfill();
    }


    /*
    |--------------------------------------------------------------------------
    | Relleno
    |--------------------------------------------------------------------------
    |
    | Los encuentros y la clasificación guardan la clave de runtime del
    | participante, así que basta con unirlos a la tabla de participantes
    | para saber a qué entidad del Universo corresponden.
    |
    */

    /*
     * MySQL no revierte DDL: si una migración falla a mitad, algunas
     * claves foráneas pueden existir ya. Se comprueba antes de crearlas.
     */
    private function constraintExists(
        string $name
    ): bool {

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('CONSTRAINT_NAME', $name)
            ->exists();
    }


    private function backfill(): void
    {
        foreach (
            [
                'participant_a_key' => 'participant_a_universe_entity_id',
                'participant_b_key' => 'participant_b_universe_entity_id',
                'winner_key' => 'winner_universe_entity_id',
            ]
            as $keyColumn => $targetColumn
        ) {

            DB::statement(
                "UPDATE tournament_instance_matches AS m
                 JOIN tournament_instance_participants AS p
                   ON p.tournament_instance_id = m.tournament_instance_id
                  AND p.runtime_key = m.{$keyColumn}
                 SET m.{$targetColumn} = p.universe_entity_id
                 WHERE m.{$keyColumn} IS NOT NULL"
            );
        }

        DB::statement(
            'UPDATE tournament_instance_phase_participants AS pp
             JOIN tournament_instance_participants AS p
               ON p.tournament_instance_id = pp.tournament_instance_id
              AND p.runtime_key = pp.runtime_key
             SET pp.universe_entity_id = p.universe_entity_id'
        );

        /*
         * Lo que no se pueda traducir se deja en nulo: es preferible
         * perder el enlace de una fila huérfana a bloquear la migración.
         */
        DB::statement(
            'UPDATE tournament_instance_phase_participants AS pp
             LEFT JOIN universe_entities AS ue
               ON ue.id = pp.universe_entity_id
             SET pp.universe_entity_id = NULL
             WHERE pp.universe_entity_id IS NOT NULL
               AND ue.id IS NULL'
        );

        foreach ([
            'participant_a_universe_entity_id',
            'participant_b_universe_entity_id',
            'winner_universe_entity_id',
        ] as $column) {

            DB::statement(
                "UPDATE tournament_instance_matches AS m
                 LEFT JOIN universe_entities AS ue
                   ON ue.id = m.{$column}
                 SET m.{$column} = NULL
                 WHERE m.{$column} IS NOT NULL
                   AND ue.id IS NULL"
            );
        }
    }

    public function down(): void
    {
        Schema::table(
            'tournament_instance_phase_participants',
            function (Blueprint $table) {

                $table->dropForeign('tiphpart_universe_entity_fk');
                $table->dropIndex('tiphpart_universe_entity_index');
            }
        );

        Schema::table(
            'tournament_instance_phase_participants',
            function (Blueprint $table) {

                $table->renameColumn(
                    'universe_entity_id',
                    'entity_id'
                );
            }
        );

        Schema::table(
            'tournament_instance_phase_participants',
            function (Blueprint $table) {

                $table
                    ->foreign('entity_id', 'tiphpart_entity_fk')
                    ->references('id')
                    ->on('entities')
                    ->nullOnDelete();

                $table->index('entity_id', 'tiphpart_entity_index');
            }
        );

        Schema::table(
            'tournament_instance_matches',
            function (Blueprint $table) {

                $table->dropForeign('timatch_ue_a_fk');
                $table->dropForeign('timatch_ue_b_fk');
                $table->dropForeign('timatch_ue_w_fk');

                $table->dropColumn([
                    'participant_a_universe_entity_id',
                    'participant_b_universe_entity_id',
                    'winner_universe_entity_id',
                ]);
            }
        );

        Schema::table(
            'tournament_instance_matches',
            function (Blueprint $table) {

                $table->foreignId('participant_a_entity_id')
                    ->nullable()->constrained('entities')->nullOnDelete();

                $table->foreignId('participant_b_entity_id')
                    ->nullable()->constrained('entities')->nullOnDelete();

                $table->foreignId('winner_entity_id')
                    ->nullable()->constrained('entities')->nullOnDelete();
            }
        );

        Schema::table(
            'tournament_instance_participants',
            function (Blueprint $table) {

                $table->renameColumn('universe_entity_id', 'universe_competitor_id');
                $table->renameColumn('source_entity_id', 'entity_id');
            }
        );
    }
};
