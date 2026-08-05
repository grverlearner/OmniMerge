<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->foreignId('source_collection_id')
                ->nullable()
                ->after('user_id')
                ->constrained('collections')
                ->nullOnDelete();

            $table->boolean('allow_cloning')
                ->default(true)
                ->after('visibility');

            $table->unsignedBigInteger('views_count')
                ->default(0)
                ->after('allow_cloning');

            $table->unsignedBigInteger('clones_count')
                ->default(0)
                ->after('views_count');

            $table->timestamp('published_at')
                ->nullable()
                ->after('clones_count');

            $table->index([
                'visibility',
                'status',
                'published_at',
            ], 'collections_community_index');

            $table->index('source_collection_id');
            $table->index('views_count');
            $table->index('clones_count');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropForeign([
                'source_collection_id',
            ]);

            $table->dropIndex(
                'collections_community_index'
            );

            $table->dropIndex([
                'source_collection_id',
            ]);

            $table->dropIndex([
                'views_count',
            ]);

            $table->dropIndex([
                'clones_count',
            ]);

            $table->dropColumn([
                'source_collection_id',
                'allow_cloning',
                'views_count',
                'clones_count',
                'published_at',
            ]);
        });
    }
};