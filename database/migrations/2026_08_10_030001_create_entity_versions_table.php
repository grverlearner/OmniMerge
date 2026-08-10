<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'entity_versions',
            function (Blueprint $table) {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Propiedad
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Entidad + definición de Version
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('entity_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->foreignId('version_id')
                    ->constrained()
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Jerarquía concreta
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId(
                        'parent_entity_version_id'
                    )
                    ->nullable()
                    ->constrained(
                        'entity_versions'
                    )
                    ->nullOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Procedencia futura para Comunidad
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId(
                        'source_entity_version_id'
                    )
                    ->nullable()
                    ->constrained(
                        'entity_versions'
                    )
                    ->nullOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Identidad
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedBigInteger(
                        'sequence_number'
                    );

                $table
                    ->string(
                        'code',
                        30
                    );

                $table
                    ->string(
                        'name',
                        150
                    );

                $table
                    ->string(
                        'slug',
                        180
                    );

                $table
                    ->text(
                        'description'
                    )
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Imagen de ESTA Entidad en ESTA Version
                |--------------------------------------------------------------------------
                |
                | Obligatoria.
                |
                */

                $table
                    ->string(
                        'image'
                    );


                /*
                |--------------------------------------------------------------------------
                | Herencia
                |--------------------------------------------------------------------------
                */

                $table
                    ->boolean(
                        'inherit_base_attributes'
                    )
                    ->default(true);

                $table
                    ->boolean(
                        'is_default'
                    )
                    ->default(false);


                /*
                |--------------------------------------------------------------------------
                | Resolución
                |--------------------------------------------------------------------------
                */

                $table
                    ->integer(
                        'priority'
                    )
                    ->default(0);

                $table
                    ->unsignedInteger(
                        'sort_order'
                    )
                    ->default(0);


                /*
                |--------------------------------------------------------------------------
                | Estado
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'status',
                        20
                    )
                    ->default(
                        'ACTIVE'
                    );

                $table
                    ->json(
                        'metadata'
                    )
                    ->nullable();


                $table->timestamps();
                $table->softDeletes();


                /*
                |--------------------------------------------------------------------------
                | Restricciones
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'user_id',
                        'code',
                    ],
                    'entity_versions_user_code_unique'
                );

                /*
                 * Una Entidad solamente puede tener
                 * una asociación directa con cada Version.
                 */
                $table->unique(
                    [
                        'entity_id',
                        'version_id',
                    ],
                    'entity_versions_entity_version_unique'
                );

                $table->unique(
                    [
                        'entity_id',
                        'slug',
                    ],
                    'entity_versions_entity_slug_unique'
                );


                $table->index([
                    'user_id',
                    'status',
                ]);

                $table->index([
                    'entity_id',
                    'status',
                ]);

                $table->index(
                    'version_id'
                );

                $table->index(
                    'parent_entity_version_id'
                );

                $table->index(
                    'is_default'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'entity_versions'
        );
    }
};
