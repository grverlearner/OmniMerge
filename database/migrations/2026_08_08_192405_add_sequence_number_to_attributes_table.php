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
        | Número histórico
        |--------------------------------------------------------------------------
        |
        | Este número indica el orden en que el atributo fue creado por
        | cada usuario.
        |
        | sequence_number = 1
        | code            = ATR000001
        |
        */

        Schema::table('attributes', function (Blueprint $table) {
            $table->unsignedInteger('sequence_number')
                ->nullable()
                ->after('source_attribute_id');
        });


        /*
        |--------------------------------------------------------------------------
        | Obtener atributos existentes
        |--------------------------------------------------------------------------
        |
        | Incluimos también los eliminados lógicamente.
        |
        */

        $attributesByUser = DB::table('attributes')
            ->orderBy('user_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');


        /*
        |--------------------------------------------------------------------------
        | Códigos temporales
        |--------------------------------------------------------------------------
        |
        | Evita colisiones mientras convertimos los códigos históricos.
        |
        */

        foreach ($attributesByUser as $attributes) {

            foreach ($attributes as $attribute) {

                DB::table('attributes')
                    ->where('id', $attribute->id)
                    ->update([
                        'code' => 'TMP_ATR_' . $attribute->id,
                    ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Numeración definitiva
        |--------------------------------------------------------------------------
        */

        foreach ($attributesByUser as $attributes) {

            $sequence = 1;


            foreach ($attributes as $attribute) {

                DB::table('attributes')
                    ->where('id', $attribute->id)
                    ->update([
                        'sequence_number' => $sequence,

                        'code' => sprintf(
                            'ATR%06d',
                            $sequence
                        ),
                    ]);


                $sequence++;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Unicidad por usuario
        |--------------------------------------------------------------------------
        */

        Schema::table('attributes', function (Blueprint $table) {

            $table->unique(
                [
                    'user_id',
                    'sequence_number',
                ],
                'attributes_user_sequence_unique'
            );
        });
    }


    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {

            $table->dropUnique(
                'attributes_user_sequence_unique'
            );


            $table->dropColumn(
                'sequence_number'
            );
        });
    }
};
