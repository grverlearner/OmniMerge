<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'community_interactions',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                /*
                 * ENTITY
                 * COLLECTION
                 * ATTRIBUTE
                 */
                $table->string(
                    'content_type',
                    30
                );

                $table->unsignedBigInteger(
                    'content_id'
                );

                /*
                 * VIEW
                 * CLONE
                 * FAVORITE
                 */
                $table->string(
                    'interaction_type',
                    30
                );

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'content_type',
                    'content_id',
                ]);

                $table->index([
                    'user_id',
                    'interaction_type',
                ]);

                $table->index('created_at');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'community_interactions'
        );
    }
};