<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Retiro del sistema legacy de fases (Fase 1 — Cierre de Single Elimination)
|--------------------------------------------------------------------------
|
| `tournament_phases` fue reemplazada por PhaseTemplate + Tournament Graph
| (tournament_phase_nodes, tournament_phase_connections). Se confirmó que
| no tiene ninguna referencia activa en la interfaz, ningún test la ejercita,
| y ninguna otra tabla mantiene una foreign key hacia ella. Ver
| docs/md/16-Fase-1-Single-Elimination.md para el detalle de la auditoría.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('tournament_phases');
    }

    public function down(): void
    {
        Schema::create(
            'tournament_phases',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('tournament_template_id')
                    ->constrained('tournament_templates')
                    ->cascadeOnDelete();

                $table->unsignedInteger('sequence_number');
                $table->string('code', 30);
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->string('phase_type', 40);
                $table->unsignedInteger('sort_order')->default(10);
                $table->unsignedSmallInteger('input_participants')->nullable();
                $table->unsignedSmallInteger('qualifiers_count')->nullable();
                $table->unsignedTinyInteger('best_of')->default(1);
                $table->boolean('allow_byes')->default(false);
                $table->string('status', 20)->default('ACTIVE');
                $table->json('settings')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    ['tournament_template_id', 'sequence_number'],
                    'tp_template_sequence_unique'
                );

                $table->unique(
                    ['tournament_template_id', 'code'],
                    'tp_template_code_unique'
                );

                $table->index(
                    ['tournament_template_id', 'sort_order'],
                    'tp_template_order_index'
                );
            }
        );
    }
};
