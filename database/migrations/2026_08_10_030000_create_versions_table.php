<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'versions',
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
                | Procedencia y jerarquía
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('source_version_id')
                    ->nullable()
                    ->constrained('versions')
                    ->nullOnDelete();

                $table
                    ->foreignId('parent_version_id')
                    ->nullable()
                    ->constrained('versions')
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
                | Imagen principal OBLIGATORIA
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'image'
                    );


                /*
                |--------------------------------------------------------------------------
                | Clasificación
                |--------------------------------------------------------------------------
                |
                | ERA
                | AGE
                | FORM
                | TRANSFORMATION
                | OUTFIT
                | TIMELINE
                | OTHER
                |
                */

                $table
                    ->string(
                        'version_kind',
                        30
                    )
                    ->default(
                        'OTHER'
                    );


                /*
                 * SHARED
                 * EXCLUSIVE
                 */
                $table
                    ->string(
                        'scope',
                        20
                    )
                    ->default(
                        'SHARED'
                    );


                /*
                 * AUTO
                 * MANUAL
                 * BOTH
                 */
                $table
                    ->string(
                        'activation_mode',
                        20
                    )
                    ->default(
                        'BOTH'
                    );


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
                | Índices
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'user_id',
                        'code',
                    ],
                    'versions_user_code_unique'
                );

                $table->unique(
                    [
                        'user_id',
                        'slug',
                    ],
                    'versions_user_slug_unique'
                );

                $table->index([
                    'user_id',
                    'status',
                ]);

                $table->index([
                    'user_id',
                    'version_kind',
                ]);

                $table->index([
                    'user_id',
                    'scope',
                ]);

                $table->index(
                    'parent_version_id'
                );

                $table->index(
                    'priority'
                );

                $table->index(
                    'sort_order'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'versions'
        );
    }
};
