<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code', 30);
            $table->string('name', 100);
            $table->text('description')->nullable();

            $table->string('icon', 100)->nullable();
            $table->string('color', 20)->nullable();

            $table->string('status', 20)
                ->default('ACTIVE');

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['user_id', 'code'],
                'entity_types_user_code_unique'
            );

            $table->index(['user_id', 'status']);
            $table->index('name');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_types');
    }
};