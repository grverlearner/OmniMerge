<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_groups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();

            $table->string('icon', 100)->nullable();
            $table->string('color', 20)->nullable();

            $table->string('layout_type', 30)
                ->default('LIST');

            $table->boolean('collapsible')
                ->default(true);

            $table->boolean('default_expanded')
                ->default(true);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->string('status', 20)
                ->default('ACTIVE');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['user_id', 'code'],
                'attribute_groups_user_code_unique'
            );

            $table->index(['user_id', 'status']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_groups');
    }
};