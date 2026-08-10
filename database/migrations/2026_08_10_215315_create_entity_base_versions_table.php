<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'entity_base_versions',
            function (Blueprint $table) {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Propietario
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'user_id'
                );


                /*
                |--------------------------------------------------------------------------
                | Entidad
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'entity_id'
                );


                /*
                |--------------------------------------------------------------------------
                | Version concreta utilizada como Base activa
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'entity_version_id'
                );


                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Una sola Base activa por Entidad
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    'entity_id',
                    'ebv_entity_unique'
                );


                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'user_id',
                    'ebv_user_index'
                );


                $table->index(
                    'entity_version_id',
                    'ebv_version_index'
                );


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                |
                | Nombres cortos para no superar el límite de MySQL.
                |
                */

                $table
                    ->foreign(
                        'user_id',
                        'ebv_user_fk'
                    )
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();


                $table
                    ->foreign(
                        'entity_id',
                        'ebv_entity_fk'
                    )
                    ->references('id')
                    ->on('entities')
                    ->cascadeOnDelete();


                /*
                 * Si en algún momento una EntityVersion
                 * fuera eliminada físicamente, la selección
                 * de Base también desaparecería y la Entidad
                 * volvería naturalmente a su Base original.
                 */
                $table
                    ->foreign(
                        'entity_version_id',
                        'ebv_version_fk'
                    )
                    ->references('id')
                    ->on('entity_versions')
                    ->cascadeOnDelete();
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'entity_base_versions'
        );
    }
};
