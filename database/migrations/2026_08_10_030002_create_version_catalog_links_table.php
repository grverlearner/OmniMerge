<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'version_catalog_links',
            function (Blueprint $table) {

                $table->id();


                $table
                    ->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->foreignId('version_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->foreignId('attribute_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->foreignId(
                        'attribute_option_id'
                    )
                    ->constrained()
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Tipo
                |--------------------------------------------------------------------------
                |
                | ACTIVATES:
                | Este elemento puede activar esta Version.
                |
                | CONTEXT:
                | Esta Version ocurre dentro de ese contexto.
                |
                | RELATED:
                | Solamente existe una relación semántica.
                |
                */

                $table
                    ->string(
                        'relation_type',
                        20
                    )
                    ->default(
                        'RELATED'
                    );


                /*
                |--------------------------------------------------------------------------
                | Condiciones
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedInteger(
                        'condition_group'
                    )
                    ->default(1);

                $table
                    ->string(
                        'logical_operator',
                        5
                    )
                    ->default(
                        'AND'
                    );

                $table
                    ->boolean(
                        'is_required'
                    )
                    ->default(false);

                $table
                    ->integer(
                        'priority'
                    )
                    ->default(0);


                $table->timestamps();


                $table->unique(
                    [
                        'version_id',
                        'attribute_option_id',
                        'relation_type',
                    ],
                    'version_catalog_links_unique'
                );

                $table->index([
                    'attribute_option_id',
                    'relation_type',
                ]);

                $table->index([
                    'version_id',
                    'condition_group',
                ]);
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'version_catalog_links'
        );
    }
};
