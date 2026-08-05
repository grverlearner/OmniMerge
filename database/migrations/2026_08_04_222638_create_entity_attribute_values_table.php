<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'entity_attribute_values',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('entity_attribute_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('attribute_option_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->text('text_value')->nullable();
                $table->bigInteger('integer_value')->nullable();

                $table->decimal(
                    'decimal_value',
                    18,
                    4
                )->nullable();

                $table->boolean('boolean_value')
                    ->nullable();

                $table->date('date_value')->nullable();

                $table->string('color_value', 20)
                    ->nullable();

                $table->string('custom_value')
                    ->nullable();

                $table->json('json_value')->nullable();

                $table->unsignedInteger('sort_order')
                    ->default(0);

                $table->timestamps();

                $table->index('entity_attribute_id');
                $table->index('attribute_option_id');
                $table->index('integer_value');
                $table->index('decimal_value');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'entity_attribute_values'
        );
    }
};