<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('slug', 180);

            $table->text('description')->nullable();

            $table->string('image')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('color', 20)->nullable();

            $table->string('visibility', 20)
                ->default('PRIVATE');

            $table->string('status', 20)
                ->default('ACTIVE');

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['user_id', 'code'],
                'collections_user_code_unique'
            );

            $table->unique(
                ['user_id', 'slug'],
                'collections_user_slug_unique'
            );

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'visibility']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};