<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'entity_presentations',
            function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger(
                    'user_id'
                );

                $table->unsignedBigInteger(
                    'entity_id'
                );

                $table
                    ->unsignedBigInteger(
                        'entity_version_id'
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'entity_version_image_id'
                    )
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | BASE
                | VERSION_PRIMARY
                | VERSION_MEDIA
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'mode',
                        30
                    )
                    ->default('BASE');


                /*
                 * Si se muestra una Version,
                 * también podemos decidir si su
                 * nombre y descripción reemplazan
                 * a los datos de la Entidad base.
                 */
                $table
                    ->boolean(
                        'use_version_name'
                    )
                    ->default(true);

                $table
                    ->boolean(
                        'use_version_description'
                    )
                    ->default(true);


                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Una configuración por Entidad
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    'entity_id',
                    'ep_entity_unique'
                );


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys cortas
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreign(
                        'user_id',
                        'ep_user_fk'
                    )
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();


                $table
                    ->foreign(
                        'entity_id',
                        'ep_entity_fk'
                    )
                    ->references('id')
                    ->on('entities')
                    ->cascadeOnDelete();


                $table
                    ->foreign(
                        'entity_version_id',
                        'ep_version_fk'
                    )
                    ->references('id')
                    ->on('entity_versions')
                    ->nullOnDelete();


                $table
                    ->foreign(
                        'entity_version_image_id',
                        'ep_media_fk'
                    )
                    ->references('id')
                    ->on('entity_version_images')
                    ->nullOnDelete();
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'entity_presentations'
        );
    }
};
