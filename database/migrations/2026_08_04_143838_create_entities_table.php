<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('entity_type_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('slug', 180);

            $table->text('description')->nullable();
            $table->string('image')->nullable();

            $table->string('status', 20)
                ->default('ACTIVE');

            $table->string('visibility', 20)
                ->default('PRIVATE');

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['user_id', 'code'],
                'entities_user_code_unique'
            );

            $table->unique(
                ['user_id', 'slug'],
                'entities_user_slug_unique'
            );

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'visibility']);
            $table->index('entity_type_id');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};