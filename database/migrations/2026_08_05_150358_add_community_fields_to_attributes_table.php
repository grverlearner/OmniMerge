<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->boolean('allow_cloning')
                ->default(true)
                ->after('scope');

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
                'scope',
                'status',
                'published_at',
            ], 'attributes_community_index');

            $table->index('views_count');
            $table->index('clones_count');
        });
    }

    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->dropIndex(
                'attributes_community_index'
            );

            $table->dropIndex([
                'views_count',
            ]);

            $table->dropIndex([
                'clones_count',
            ]);

            $table->dropColumn([
                'allow_cloning',
                'views_count',
                'clones_count',
                'published_at',
            ]);
        });
    }
};