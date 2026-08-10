<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'entity_version_images',
            function (Blueprint $table) {

                /*
                |--------------------------------------------------------------------------
                | Tipo de imagen
                |--------------------------------------------------------------------------
                |
                | PORTRAIT
                | FULL_BODY
                | COMBAT
                | OUTFIT
                | REFERENCE
                | ALTERNATIVE
                | OTHER
                |
                */

                $table
                    ->string(
                        'media_type',
                        30
                    )
                    ->default('ALTERNATIVE')
                    ->after('caption');


                /*
                |--------------------------------------------------------------------------
                | Texto alternativo
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'alt_text',
                        200
                    )
                    ->nullable()
                    ->after('media_type');


                $table->index(
                    [
                        'entity_version_id',
                        'media_type',
                    ],
                    'evi_entity_type_index'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'entity_version_images',
            function (Blueprint $table) {

                $table->dropIndex(
                    'evi_entity_type_index'
                );

                $table->dropColumn([
                    'media_type',
                    'alt_text',
                ]);
            }
        );
    }
};
