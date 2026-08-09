<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->unsignedInteger('sequence_number')
                ->nullable()
                ->after('source_collection_id');
        });

        $collectionsByUser = DB::table('collections')
            ->orderBy('user_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');

        foreach ($collectionsByUser as $collections) {
            foreach ($collections as $collection) {
                DB::table('collections')
                    ->where('id', $collection->id)
                    ->update([
                        'code' => 'TMP_COL_' . $collection->id,
                    ]);
            }
        }

        foreach ($collectionsByUser as $collections) {
            $sequence = 1;

            foreach ($collections as $collection) {
                DB::table('collections')
                    ->where('id', $collection->id)
                    ->update([
                        'sequence_number' => $sequence,
                        'code' => sprintf(
                            'COL%06d',
                            $sequence
                        ),
                    ]);

                $sequence++;
            }
        }

        Schema::table('collections', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'sequence_number'],
                'collections_user_sequence_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropUnique(
                'collections_user_sequence_unique'
            );

            $table->dropColumn(
                'sequence_number'
            );
        });
    }
};
