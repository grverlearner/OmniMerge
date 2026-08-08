<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Nuevos campos
        |--------------------------------------------------------------------------
        |
        | user_id
        |     Propietario directo del elemento.
        |
        | source_attribute_option_id
        |     Procedencia cuando el elemento fue clonado desde Comunidad.
        |
        | sequence_number
        |     Número histórico dentro de la Biblioteca del usuario.
        |
        */

        Schema::table(
            'attribute_options',
            function (Blueprint $table) {

                $table
                    ->unsignedBigInteger('user_id')
                    ->nullable()
                    ->after('id');


                $table
                    ->unsignedBigInteger(
                        'source_attribute_option_id'
                    )
                    ->nullable()
                    ->after('user_id');


                $table
                    ->unsignedInteger(
                        'sequence_number'
                    )
                    ->nullable()
                    ->after(
                        'source_attribute_option_id'
                    );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | 2. Recuperar propietario de registros existentes
        |--------------------------------------------------------------------------
        |
        | attribute_options
        |       ↓ attribute_id
        | attributes
        |       ↓ user_id
        |
        */

        DB::statement(
            '
            UPDATE attribute_options AS ao

            INNER JOIN attributes AS a
                ON a.id = ao.attribute_id

            SET ao.user_id = a.user_id
            '
        );


        /*
        |--------------------------------------------------------------------------
        | 3. Agrupar elementos existentes por propietario
        |--------------------------------------------------------------------------
        */

        $optionsByUser =
            DB::table('attribute_options')
                ->orderBy('user_id')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->groupBy('user_id');


        /*
        |--------------------------------------------------------------------------
        | 4. Códigos temporales
        |--------------------------------------------------------------------------
        |
        | Evitamos colisiones con el índice actual
        | attribute_id + code.
        |
        */

        foreach (
            $optionsByUser
            as $options
        ) {

            foreach (
                $options
                as $option
            ) {

                DB::table(
                    'attribute_options'
                )
                    ->where(
                        'id',
                        $option->id
                    )
                    ->update([
                        'code' =>
                            'TMP_CAT_'
                            .$option->id,
                    ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 5. Identidad definitiva
        |--------------------------------------------------------------------------
        |
        | Usuario 1
        |
        | CAT000001
        | CAT000002
        |
        | Usuario 2
        |
        | CAT000001
        | CAT000002
        |
        */

        foreach (
            $optionsByUser
            as $options
        ) {

            $sequence = 1;


            foreach (
                $options
                as $option
            ) {

                DB::table(
                    'attribute_options'
                )
                    ->where(
                        'id',
                        $option->id
                    )
                    ->update([

                        'sequence_number' =>
                            $sequence,

                        'code' =>
                            sprintf(
                                'CAT%06d',
                                $sequence
                            ),
                    ]);


                $sequence++;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 6. user_id ahora obligatorio
        |--------------------------------------------------------------------------
        |
        | El proyecto usa MySQL, por eso podemos asegurar el NOT NULL.
        |
        */

        DB::statement(
            '
            ALTER TABLE attribute_options
            MODIFY user_id BIGINT UNSIGNED NOT NULL
            '
        );


        /*
        |--------------------------------------------------------------------------
        | 7. Relaciones e índices
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'attribute_options',
            function (Blueprint $table) {

                $table
                    ->foreign(
                        'user_id',
                        'attribute_options_user_fk'
                    )
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();


                $table
                    ->foreign(
                        'source_attribute_option_id',
                        'attribute_options_source_fk'
                    )
                    ->references('id')
                    ->on('attribute_options')
                    ->nullOnDelete();


                $table->unique(
                    [
                        'user_id',
                        'sequence_number',
                    ],
                    'attribute_options_user_sequence_unique'
                );


                $table->unique(
                    [
                        'user_id',
                        'code',
                    ],
                    'attribute_options_user_code_unique'
                );


                $table->index(
                    [
                        'user_id',
                        'status',
                    ],
                    'attribute_options_user_status_index'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'attribute_options',
            function (Blueprint $table) {

                $table->dropForeign(
                    'attribute_options_source_fk'
                );


                $table->dropForeign(
                    'attribute_options_user_fk'
                );


                $table->dropUnique(
                    'attribute_options_user_sequence_unique'
                );


                $table->dropUnique(
                    'attribute_options_user_code_unique'
                );


                $table->dropIndex(
                    'attribute_options_user_status_index'
                );


                $table->dropColumn([
                    'sequence_number',
                    'source_attribute_option_id',
                    'user_id',
                ]);
            }
        );
    }
};