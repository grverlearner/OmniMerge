<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entity_types', function (Blueprint $table) {

            $table
                ->foreignId('source_entity_type_id')
                ->nullable()
                ->after('user_id')
                ->constrained('entity_types')
                ->nullOnDelete();

            $table->index(
                [
                    'user_id',
                    'source_entity_type_id',
                ],
                'entity_types_user_source_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('entity_types', function (Blueprint $table) {

            $table->dropIndex(
                'entity_types_user_source_index'
            );

            $table->dropConstrainedForeignId(
                'source_entity_type_id'
            );
        });
    }
};
