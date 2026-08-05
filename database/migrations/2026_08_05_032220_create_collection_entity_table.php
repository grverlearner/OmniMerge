<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_entity', function (Blueprint $table) {
            $table->foreignId('collection_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('entity_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->text('notes')->nullable();

            $table->timestamp('added_at')
                ->nullable()
                ->useCurrent();

            $table->primary([
                'collection_id',
                'entity_id',
            ]);

            $table->index('entity_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_entity');
    }
};