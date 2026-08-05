<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'attribute_group_attribute',
            function (Blueprint $table) {
                $table->foreignId('attribute_group_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('attribute_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'custom_label',
                    150
                )->nullable();

                $table->unsignedInteger('sort_order')
                    ->default(0);

                $table->boolean('is_featured')
                    ->default(false);

                $table->primary([
                    'attribute_group_id',
                    'attribute_id',
                ]);

                $table->index('attribute_id');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'attribute_group_attribute'
        );
    }
};