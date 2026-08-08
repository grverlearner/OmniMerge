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
        */

        Schema::table('entity_types', function (Blueprint $table) {
            $table->unsignedInteger('sequence_number')
                ->nullable()
                ->after('user_id');

            $table->string('image')
                ->nullable()
                ->after('description');
        });


        /*
        |--------------------------------------------------------------------------
        | 2. Obtener todos los tipos existentes
        |--------------------------------------------------------------------------
        |
        | También incluimos eliminados lógicamente porque el número histórico
        | no debe reutilizarse.
        |
        */

        $entityTypes = DB::table('entity_types')
            ->orderBy('user_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');


        /*
        |--------------------------------------------------------------------------
        | 3. Códigos temporales
        |--------------------------------------------------------------------------
        |
        | Primero reemplazamos temporalmente los códigos existentes para evitar
        | colisiones mientras los convertimos a TPE0001, TPE0002, etc.
        |
        */

        foreach ($entityTypes as $types) {
            foreach ($types as $type) {
                DB::table('entity_types')
                    ->where('id', $type->id)
                    ->update([
                        'code' => 'TMP_TPE_'.$type->id,
                    ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 4. Normalizar códigos históricos
        |--------------------------------------------------------------------------
        |
        | Cada usuario inicia su propia secuencia:
        |
        | Usuario A:
        | TPE0001
        | TPE0002
        |
        | Usuario B:
        | TPE0001
        | TPE0002
        |
        */

        foreach ($entityTypes as $types) {
            $sequence = 1;

            foreach ($types as $type) {
                DB::table('entity_types')
                    ->where('id', $type->id)
                    ->update([
                        'sequence_number' => $sequence,

                        'code' => sprintf(
                            'TPE%04d',
                            $sequence
                        ),
                    ]);

                $sequence++;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 5. Unicidad del número de creación
        |--------------------------------------------------------------------------
        */

        Schema::table('entity_types', function (Blueprint $table) {
            $table->unique(
                [
                    'user_id',
                    'sequence_number',
                ],
                'entity_types_user_sequence_unique'
            );
        });
    }


    public function down(): void
    {
        Schema::table('entity_types', function (Blueprint $table) {
            $table->dropUnique(
                'entity_types_user_sequence_unique'
            );

            $table->dropColumn([
                'sequence_number',
                'image',
            ]);
        });
    }
};