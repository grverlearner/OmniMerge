<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'entity_version_attribute_values',
            function (Blueprint $table) {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Entity Version Attribute
                |--------------------------------------------------------------------------
                |
                | La FK se crea manualmente porque el nombre automático
                | generado por Laravel supera el límite de MySQL.
                |
                */

                $table
                    ->foreignId(
                        'entity_version_attribute_id'
                    );


                $table
                    ->foreign(
                        'entity_version_attribute_id',
                        'eva_values_assignment_fk'
                    )
                    ->references(
                        'id'
                    )
                    ->on(
                        'entity_version_attributes'
                    )
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Attribute Option
                |--------------------------------------------------------------------------
                |
                | También le damos un nombre corto manualmente para evitar
                | el mismo problema con MySQL.
                |
                */

                $table
                    ->foreignId(
                        'attribute_option_id'
                    )
                    ->nullable();


                $table
                    ->foreign(
                        'attribute_option_id',
                        'eva_values_option_fk'
                    )
                    ->references(
                        'id'
                    )
                    ->on(
                        'attribute_options'
                    )
                    ->nullOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Valores tipados
                |--------------------------------------------------------------------------
                */

                $table
                    ->text(
                        'text_value'
                    )
                    ->nullable();


                $table
                    ->bigInteger(
                        'integer_value'
                    )
                    ->nullable();


                $table
                    ->decimal(
                        'decimal_value',
                        18,
                        4
                    )
                    ->nullable();


                $table
                    ->boolean(
                        'boolean_value'
                    )
                    ->nullable();


                $table
                    ->date(
                        'date_value'
                    )
                    ->nullable();


                $table
                    ->string(
                        'color_value',
                        20
                    )
                    ->nullable();


                $table
                    ->string(
                        'custom_value'
                    )
                    ->nullable();


                $table
                    ->json(
                        'json_value'
                    )
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Orden
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedInteger(
                        'sort_order'
                    )
                    ->default(0);


                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'entity_version_attribute_id',
                    'eva_values_assignment_index'
                );


                $table->index(
                    'attribute_option_id',
                    'eva_values_option_index'
                );


                $table->index(
                    'integer_value',
                    'eva_values_integer_index'
                );


                $table->index(
                    'decimal_value',
                    'eva_values_decimal_index'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'entity_version_attribute_values'
        );
    }
};
