<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_attributes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entity_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('attribute_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('custom_label', 150)
                ->nullable();

            $table->boolean('is_visible')
                ->default(true);

            $table->boolean('is_featured')
                ->default(false);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['entity_id', 'attribute_id'],
                'entity_attributes_unique'
            );

            $table->index('entity_id');
            $table->index('attribute_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_attributes');
    }
};