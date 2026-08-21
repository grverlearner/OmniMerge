<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | La copia del Universo sobrevive a su origen
    |--------------------------------------------------------------------------
    |
    | source_entity_id heredó ON DELETE CASCADE del enlace que existía
    | cuando el Universo apuntaba directamente a la Biblioteca. Con esa
    | regla, borrar una Entidad de la Biblioteca borraba también la
    | entidad del Universo y, con ella, todo su historial.
    |
    | Eso contradice el principio de esta separación: la copia es
    | independiente. El origen es solo trazabilidad, así que al
    | desaparecer debe quedar en nulo, no arrastrar la copia.
    |
    | Se escribe con SQL directo y de forma idempotente porque el nombre
    | de la constraint depende de por dónde haya pasado la tabla: la
    | heredó del renombrado de universe_competitors.
    |
    | Ver docs/md/27-Entidades-Propias-Del-Universo.md
    |
    */

    public function up(): void
    {
        if (! Schema::hasTable('universe_entities')) {
            return;
        }

        /*
         * Se limpian primero las referencias colgantes: si el origen ya
         * no existe, la copia se queda sin enlace, que es justo lo que
         * la nueva regla hará de ahora en adelante.
         */
        DB::statement(
            'UPDATE universe_entities ue
             LEFT JOIN entities e ON e.id = ue.source_entity_id
             SET ue.source_entity_id = NULL
             WHERE ue.source_entity_id IS NOT NULL
               AND e.id IS NULL'
        );

        foreach ($this->foreignKeysOn('source_entity_id') as $name) {

            DB::statement(
                "ALTER TABLE universe_entities DROP FOREIGN KEY `{$name}`"
            );
        }

        /*
         * La columna era obligatoria porque antes representaba un
         * enlace vivo a la Biblioteca. Ahora es solo procedencia, y
         * puede quedar vacía: ON DELETE SET NULL además lo exige.
         */
        DB::statement(
            'ALTER TABLE universe_entities
             MODIFY source_entity_id BIGINT UNSIGNED NULL'
        );

        DB::statement(
            'ALTER TABLE universe_entities
             ADD CONSTRAINT unient_source_entity_fk
             FOREIGN KEY (source_entity_id)
             REFERENCES entities (id)
             ON DELETE SET NULL'
        );
    }

    public function down(): void
    {
        foreach ($this->foreignKeysOn('source_entity_id') as $name) {

            DB::statement(
                "ALTER TABLE universe_entities DROP FOREIGN KEY `{$name}`"
            );
        }

        DB::statement(
            'ALTER TABLE universe_entities
             ADD CONSTRAINT universe_entities_source_entity_id_foreign
             FOREIGN KEY (source_entity_id)
             REFERENCES entities (id)
             ON DELETE CASCADE'
        );
    }

    private function foreignKeysOn(
        string $column
    ): array {

        return collect(
            DB::select(
                "SELECT CONSTRAINT_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'universe_entities'
                   AND COLUMN_NAME = ?
                   AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$column]
            )
        )
            ->pluck('CONSTRAINT_NAME')
            ->all();
    }
};
