<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attribute_groups', function (Blueprint $table) {
            $table
                ->unsignedInteger('sequence_number')
                ->nullable()
                ->after('user_id');
        });

        /*
        |--------------------------------------------------------------------------
        | Recuperar grupos existentes por usuario
        |--------------------------------------------------------------------------
        */

        $groupsByUser = DB::table('attribute_groups')
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
        | Evitamos conflictos con:
        |
        | UNIQUE(user_id, code)
        |
        */

        foreach ($groupsByUser as $groups) {
            foreach ($groups as $group) {
                DB::table('attribute_groups')
                    ->where('id', $group->id)
                    ->update([
                        'code' => 'TMP_GRP_' . $group->id,
                    ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Secuencia y código definitivo
        |--------------------------------------------------------------------------
        */

        foreach ($groupsByUser as $groups) {
            $sequence = 1;

            foreach ($groups as $group) {
                DB::table('attribute_groups')
                    ->where('id', $group->id)
                    ->update([
                        'sequence_number' => $sequence,

                        'code' => sprintf(
                            'GRP%06d',
                            $sequence
                        ),
                    ]);

                $sequence++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Índice
        |--------------------------------------------------------------------------
        */

        Schema::table('attribute_groups', function (Blueprint $table) {
            $table->unique(
                [
                    'user_id',
                    'sequence_number',
                ],
                'attribute_groups_user_sequence_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('attribute_groups', function (Blueprint $table) {
            $table->dropUnique(
                'attribute_groups_user_sequence_unique'
            );

            $table->dropColumn(
                'sequence_number'
            );
        });
    }
};
