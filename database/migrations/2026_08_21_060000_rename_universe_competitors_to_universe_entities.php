<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | universe_competitors → universe_entities
    |--------------------------------------------------------------------------
    |
    | Deja de ser un enlace fino a una Entity de Biblioteca y pasa a ser
    | una entidad PROPIA del Universo, con su copia de los datos.
    |
    | La Biblioteca queda como material reutilizable; el Universo tiene su
    | instancia, que evoluciona por su cuenta.
    |
    | Ver docs/md/27-Entidades-Propias-Del-Universo.md
    |
    */

    public function up(): void
    {
        Schema::rename(
            'universe_competitors',
            'universe_entities'
        );

        Schema::table(
            'universe_entities',
            function (Blueprint $table) {

                /*
                 * entity_id pasa a llamarse source_entity_id: ya no es la
                 * entidad que compite, sino de dónde se copió.
                 */
                $table->renameColumn(
                    'entity_id',
                    'source_entity_id'
                );
            }
        );

        Schema::table(
            'universe_entities',
            function (Blueprint $table) {

                /*
                |--------------------------------------------------------------------------
                | Identidad propia
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedInteger('sequence_number')
                    ->nullable()
                    ->after('universe_id');

                $table
                    ->string('code', 30)
                    ->nullable()
                    ->after('sequence_number');

                $table
                    ->string('name', 150)
                    ->nullable()
                    ->after('code');

                $table
                    ->text('description')
                    ->nullable()
                    ->after('name');

                $table
                    ->string('image')
                    ->nullable()
                    ->after('description');

                /*
                 * Texto, no clave foránea: si el tipo se renombra o se
                 * borra en Biblioteca, la entidad del Universo conserva
                 * lo que era cuando se importó.
                 */
                $table
                    ->string('entity_type_name', 120)
                    ->nullable()
                    ->after('image');

                /*
                |--------------------------------------------------------------------------
                | Copia de atributos y versiones
                |--------------------------------------------------------------------------
                |
                | JSON en lugar de replicar las 8 tablas de la Biblioteca:
                | aquí no se consultan relacionalmente, solo se muestran.
                | Es el mismo patrón de snapshot de las Fases 6 y 7.
                |
                */

                $table
                    ->json('attribute_snapshot')
                    ->nullable()
                    ->after('entity_type_name');

                $table
                    ->json('version_snapshot')
                    ->nullable()
                    ->after('attribute_snapshot');

                /*
                |--------------------------------------------------------------------------
                | Procedencia
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('source_entity_version_id')
                    ->nullable()
                    ->after('source_entity_id')
                    ->constrained('entity_versions')
                    ->nullOnDelete();

                $table
                    ->timestamp('imported_at')
                    ->nullable()
                    ->after('source_entity_version_id');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'universe_entities',
            function (Blueprint $table) {

                $table->dropConstrainedForeignId(
                    'source_entity_version_id'
                );

                $table->dropColumn([
                    'sequence_number',
                    'code',
                    'name',
                    'description',
                    'image',
                    'entity_type_name',
                    'attribute_snapshot',
                    'version_snapshot',
                    'imported_at',
                ]);
            }
        );

        Schema::table(
            'universe_entities',
            function (Blueprint $table) {

                $table->renameColumn(
                    'source_entity_id',
                    'entity_id'
                );
            }
        );

        Schema::rename(
            'universe_entities',
            'universe_competitors'
        );
    }
};
