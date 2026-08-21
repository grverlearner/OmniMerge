<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Contexto de Entidad en el participante (Fase 7)
    |--------------------------------------------------------------------------
    |
    | La Fase 6 dejó el participante con entity_id pero sin versión ni
    | atributos. Esto rellena ese hueco.
    |
    | Los nombres se CONGELAN en lugar de leerse por join: renombrar la
    | Entidad o su versión no debe alterar un torneo ya jugado. El FK se
    | conserva solo para poder enlazar a la ficha mientras exista.
    |
    | Ver docs/md/25-Fase-7-Entidades-Reales-En-Torneos.md
    |
    */

    public function up(): void
    {
        Schema::table(
            'tournament_instance_participants',
            function (Blueprint $table) {

                $table
                    ->foreignId('entity_version_id')
                    ->nullable()
                    ->after('entity_id')
                    ->constrained('entity_versions')
                    ->nullOnDelete();

                $table
                    ->string('entity_version_name', 150)
                    ->nullable()
                    ->after('entity_version_id');

                $table
                    ->string('entity_type_name', 120)
                    ->nullable()
                    ->after('entity_version_name');

                /*
                 * Atributos efectivos congelados. Solo el par
                 * nombre/valor mostrable: no se duplican opciones,
                 * grupos ni catálogos, que siguen viviendo en la
                 * Biblioteca.
                 */
                $table
                    ->json('attribute_snapshot')
                    ->nullable()
                    ->after('entity_type_name');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'tournament_instance_participants',
            function (Blueprint $table) {

                $table->dropConstrainedForeignId(
                    'entity_version_id'
                );

                $table->dropColumn([
                    'entity_version_name',
                    'entity_type_name',
                    'attribute_snapshot',
                ]);
            }
        );
    }
};
