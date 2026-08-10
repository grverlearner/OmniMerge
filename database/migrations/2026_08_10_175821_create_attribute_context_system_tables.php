<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Relación estructural entre Atributos
        |--------------------------------------------------------------------------
        |
        | Anime
        |   ↓
        | Aldea Ninja
        |
        */

        Schema::create(
            'attribute_relationships',
            function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger(
                    'user_id'
                );

                $table->unsignedBigInteger(
                    'source_attribute_id'
                );

                $table->unsignedBigInteger(
                    'target_attribute_id'
                );


                $table
                    ->string(
                        'relationship_type',
                        30
                    )
                    ->default('DEPENDS_ON');


                $table
                    ->unsignedInteger(
                        'sort_order'
                    )
                    ->default(0);


                $table
                    ->boolean(
                        'is_active'
                    )
                    ->default(true);


                $table->timestamps();


                $table->unique(
                    [
                        'source_attribute_id',
                        'target_attribute_id',
                        'relationship_type',
                    ],
                    'ar_source_target_type_unique'
                );


                $table
                    ->foreign(
                        'user_id',
                        'ar_user_fk'
                    )
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();


                $table
                    ->foreign(
                        'source_attribute_id',
                        'ar_source_attr_fk'
                    )
                    ->references('id')
                    ->on('attributes')
                    ->cascadeOnDelete();


                $table
                    ->foreign(
                        'target_attribute_id',
                        'ar_target_attr_fk'
                    )
                    ->references('id')
                    ->on('attributes')
                    ->cascadeOnDelete();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Reglas contextuales
        |--------------------------------------------------------------------------
        |
        | Target:
        | Aldea Ninja
        |
        | Acción:
        | SHOW
        |
        | Condición:
        | Anime = Naruto
        |
        */

        Schema::create(
            'attribute_context_rules',
            function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger(
                    'user_id'
                );

                $table->unsignedBigInteger(
                    'target_attribute_id'
                );


                $table
                    ->string(
                        'name',
                        150
                    )
                    ->nullable();


                /*
                 * SHOW
                 * HIDE
                 * REQUIRE
                 */
                $table
                    ->string(
                        'action',
                        20
                    )
                    ->default('SHOW');


                /*
                 * ALL = AND
                 * ANY = OR
                 */
                $table
                    ->string(
                        'match_mode',
                        10
                    )
                    ->default('ALL');


                $table
                    ->integer(
                        'priority'
                    )
                    ->default(0);


                $table
                    ->boolean(
                        'is_active'
                    )
                    ->default(true);


                $table->timestamps();


                $table->index(
                    [
                        'target_attribute_id',
                        'is_active',
                    ],
                    'acr_target_active_index'
                );


                $table
                    ->foreign(
                        'user_id',
                        'acr_user_fk'
                    )
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();


                $table
                    ->foreign(
                        'target_attribute_id',
                        'acr_target_attr_fk'
                    )
                    ->references('id')
                    ->on('attributes')
                    ->cascadeOnDelete();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Condiciones
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'attribute_context_rule_conditions',
            function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger(
                    'rule_id'
                );

                $table->unsignedBigInteger(
                    'source_attribute_id'
                );

                /*
                 * EQUALS
                 * NOT_EQUALS
                 * EXISTS
                 * NOT_EXISTS
                 */
                $table
                    ->string(
                        'operator',
                        20
                    )
                    ->default('EQUALS');


                $table
                    ->unsignedBigInteger(
                        'source_option_id'
                    )
                    ->nullable();


                $table
                    ->unsignedInteger(
                        'sort_order'
                    )
                    ->default(0);


                $table->timestamps();


                $table
                    ->foreign(
                        'rule_id',
                        'acrc_rule_fk'
                    )
                    ->references('id')
                    ->on('attribute_context_rules')
                    ->cascadeOnDelete();


                $table
                    ->foreign(
                        'source_attribute_id',
                        'acrc_source_attr_fk'
                    )
                    ->references('id')
                    ->on('attributes')
                    ->cascadeOnDelete();


                $table
                    ->foreign(
                        'source_option_id',
                        'acrc_source_opt_fk'
                    )
                    ->references('id')
                    ->on('attribute_options')
                    ->nullOnDelete();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Relaciones entre elementos de Catálogo
        |--------------------------------------------------------------------------
        |
        | País = Perú
        |       ↓
        | Región permitida = Tacna
        |
        */

        Schema::create(
            'attribute_option_relationships',
            function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger(
                    'user_id'
                );

                $table->unsignedBigInteger(
                    'source_option_id'
                );

                $table->unsignedBigInteger(
                    'target_option_id'
                );


                /*
                 * ALLOWS
                 * BLOCKS
                 */
                $table
                    ->string(
                        'relationship_type',
                        20
                    )
                    ->default('ALLOWS');


                $table
                    ->integer(
                        'priority'
                    )
                    ->default(0);


                $table
                    ->boolean(
                        'is_active'
                    )
                    ->default(true);


                $table->timestamps();


                $table->unique(
                    [
                        'source_option_id',
                        'target_option_id',
                        'relationship_type',
                    ],
                    'aor_source_target_type_unique'
                );


                $table
                    ->foreign(
                        'user_id',
                        'aor_user_fk'
                    )
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();


                $table
                    ->foreign(
                        'source_option_id',
                        'aor_source_opt_fk'
                    )
                    ->references('id')
                    ->on('attribute_options')
                    ->cascadeOnDelete();


                $table
                    ->foreign(
                        'target_option_id',
                        'aor_target_opt_fk'
                    )
                    ->references('id')
                    ->on('attribute_options')
                    ->cascadeOnDelete();
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'attribute_option_relationships'
        );

        Schema::dropIfExists(
            'attribute_context_rule_conditions'
        );

        Schema::dropIfExists(
            'attribute_context_rules'
        );

        Schema::dropIfExists(
            'attribute_relationships'
        );
    }
};
