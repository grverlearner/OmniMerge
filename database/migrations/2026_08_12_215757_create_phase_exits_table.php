<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phase_exits', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Fase propietaria
            |--------------------------------------------------------------------------
            */

            $table->foreignId('phase_template_id')
                ->constrained('phase_templates')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Identidad
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('sequence_number');

            $table->string('code', 30);
            $table->string('name', 120);

            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Selector
            |--------------------------------------------------------------------------
            |
            | Ejemplos:
            |
            | MATCH_WINNERS
            | MATCH_LOSERS
            | TOP_N
            | BOTTOM_N
            | RANK_POSITION
            | RANK_RANGE
            | ALL
            | REMAINING
            |
            */

            $table->string('selector_type', 40);

            $table->unsignedSmallInteger('selector_from')->nullable();
            $table->unsignedSmallInteger('selector_to')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Evaluación
            |--------------------------------------------------------------------------
            */

            $table->unsignedSmallInteger('priority')->default(10);
            $table->unsignedInteger('sort_order')->default(10);

            $table->string('status', 20)->default('ACTIVE');

            /*
            |--------------------------------------------------------------------------
            | Extensión
            |--------------------------------------------------------------------------
            */

            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->unique(
                ['phase_template_id', 'sequence_number'],
                'pe_phase_sequence_unique'
            );

            $table->unique(
                ['phase_template_id', 'code'],
                'pe_phase_code_unique'
            );

            $table->index(
                ['phase_template_id', 'priority'],
                'pe_phase_priority_index'
            );

            $table->index(
                ['phase_template_id', 'sort_order'],
                'pe_phase_order_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phase_exits');
    }
};
