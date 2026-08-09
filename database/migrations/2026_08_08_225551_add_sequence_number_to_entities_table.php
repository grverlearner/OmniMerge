<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entities', function (Blueprint $table) {
            $table->unsignedInteger('sequence_number')
                ->nullable()
                ->after('source_entity_id');
        });

        /*
        |--------------------------------------------------------------------------
        | Reordenar entidades existentes por usuario
        |--------------------------------------------------------------------------
        */

        $entitiesByUser = DB::table('entities')
            ->orderBy('user_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');

        /*
        |--------------------------------------------------------------------------
        | Código temporal
        |--------------------------------------------------------------------------
        |
        | Evita colisiones con el índice único actual.
        |
        */

        foreach ($entitiesByUser as $entities) {
            foreach ($entities as $entity) {
                DB::table('entities')
                    ->where('id', $entity->id)
                    ->update([
                        'code' => 'TMP_ENT_' . $entity->id,
                    ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Código definitivo
        |--------------------------------------------------------------------------
        */

        foreach ($entitiesByUser as $entities) {
            $sequence = 1;

            foreach ($entities as $entity) {
                DB::table('entities')
                    ->where('id', $entity->id)
                    ->update([
                        'sequence_number' => $sequence,
                        'code' => sprintf('ENT%06d', $sequence),
                    ]);

                $sequence++;
            }
        }

        Schema::table('entities', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'sequence_number'],
                'entities_user_sequence_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('entities', function (Blueprint $table) {
            $table->dropUnique(
                'entities_user_sequence_unique'
            );

            $table->dropColumn(
                'sequence_number'
            );
        });
    }
};
