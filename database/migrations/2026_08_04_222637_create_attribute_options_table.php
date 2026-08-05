<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attribute_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('parent_option_id')
                ->nullable()
                ->constrained('attribute_options')
                ->nullOnDelete();

            $table->string('code', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();

            $table->string('image')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('color', 20)->nullable();

            $table->decimal(
                'numeric_value',
                18,
                4
            )->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->json('metadata')->nullable();

            $table->string('status', 20)
                ->default('ACTIVE');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['attribute_id', 'code'],
                'attribute_options_attribute_code_unique'
            );

            $table->index(['attribute_id', 'status']);
            $table->index('parent_option_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_options');
    }
};