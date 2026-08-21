<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Poblar la copia de las entidades ya importadas
    |--------------------------------------------------------------------------
    |
    | Las entidades que ya estaban en un Universo solo guardaban el enlace
    | a Biblioteca. Aquí se les copia la identidad para que dejen de
    | depender de ella.
    |
    | Se usan consultas planas a propósito: una migración no debe depender
    | de servicios de la aplicación, que pueden cambiar de firma y romper
    | un migrate:fresh meses después.
    |
    | Los atributos y las versiones los rellena el comando
    | `universes:import-snapshots`, que sí puede usar el resolutor de
    | versiones y es re-ejecutable.
    |
    */

    public function up(): void
    {
        /*
         * Identidad: se copia el alias del Universo si lo había, y si no
         * el nombre canónico de la Entidad.
         */
        DB::statement(
            "UPDATE universe_entities AS ue
             JOIN entities AS e
               ON e.id = ue.source_entity_id
             LEFT JOIN entity_types AS et
               ON et.id = e.entity_type_id
             SET ue.name = COALESCE(NULLIF(ue.display_name, ''), e.name),
                 ue.description = e.description,
                 ue.image = e.image,
                 ue.entity_type_name = et.name,
                 ue.imported_at = COALESCE(ue.imported_at, ue.created_at)
             WHERE ue.name IS NULL"
        );

        /*
         * Las entidades cuya Entity de origen ya no existe conservan al
         * menos su alias: no se quedan sin nombre.
         */
        DB::statement(
            "UPDATE universe_entities
             SET name = COALESCE(NULLIF(display_name, ''), 'Entidad importada'),
                 imported_at = COALESCE(imported_at, created_at)
             WHERE name IS NULL"
        );

        /*
        |--------------------------------------------------------------------------
        | Código correlativo por Universo
        |--------------------------------------------------------------------------
        */

        $universeIds =
            DB::table('universe_entities')
            ->distinct()
            ->pluck('universe_id');

        foreach ($universeIds as $universeId) {

            $sequence = 0;

            DB::table('universe_entities')
                ->where('universe_id', $universeId)
                ->orderBy('id')
                ->get(['id'])
                ->each(
                    function ($row) use (&$sequence) {

                        $sequence++;

                        DB::table('universe_entities')
                            ->where('id', $row->id)
                            ->update([
                                'sequence_number' => $sequence,
                                'code' => sprintf('UEN%06d', $sequence),
                            ]);
                    }
                );
        }
    }

    public function down(): void
    {
        DB::table('universe_entities')
            ->update([
                'name' => null,
                'description' => null,
                'image' => null,
                'entity_type_name' => null,
                'sequence_number' => null,
                'code' => null,
                'imported_at' => null,
            ]);
    }
};
