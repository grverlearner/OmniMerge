<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'phase_group_stage_advancement_rules',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Phase Template
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'phase_template_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Puerta de salida
                |--------------------------------------------------------------------------
                |
                | La regla decide qué participantes llegan
                | a una PhaseExit.
                |
                | NO representa la siguiente Fase.
                |
                */

                $table
                    ->foreignId(
                        'phase_exit_id'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Grupo específico
                |--------------------------------------------------------------------------
                |
                | Solo se utiliza en reglas como:
                |
                | SPECIFIC_GROUP_POSITION
                | SPECIFIC_GROUP_RANGE
                |
                */

                $table
                    ->foreignId(
                        'phase_group_stage_group_id'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Tipo de regla
                |--------------------------------------------------------------------------
                |
                | EACH_GROUP_TOP_N
                | EACH_GROUP_BOTTOM_N
                | EACH_GROUP_POSITION
                | EACH_GROUP_RANGE
                |
                | CROSS_GROUP_POSITION_TOP_N
                | CROSS_GROUP_POSITION_BOTTOM_N
                |
                | BEST_REMAINING
                | WORST_REMAINING
                |
                | SPECIFIC_GROUP_POSITION
                | SPECIFIC_GROUP_RANGE
                |
                | REMAINING
                |
                */

                $table->string(
                    'rule_type',
                    50
                );

                /*
                |--------------------------------------------------------------------------
                | Posiciones
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedSmallInteger(
                        'position_from'
                    )
                    ->nullable();

                $table
                    ->unsignedSmallInteger(
                        'position_to'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Cantidad
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedSmallInteger(
                        'take'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Orden
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedInteger(
                        'sort_order'
                    )
                    ->default(10);

                /*
                |--------------------------------------------------------------------------
                | Estado
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'status',
                        20
                    )
                    ->default('ACTIVE');

                /*
                |--------------------------------------------------------------------------
                | Extensiones futuras
                |--------------------------------------------------------------------------
                */

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                |
                | IMPORTANTE:
                |
                | Escribimos nombres cortos explícitamente para evitar
                | superar el límite de identificadores de MySQL.
                |
                */

                $table
                    ->foreign(
                        'phase_template_id',
                        'pgsar_phase_fk'
                    )
                    ->references('id')
                    ->on('phase_templates')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'phase_exit_id',
                        'pgsar_exit_fk'
                    )
                    ->references('id')
                    ->on('phase_exits')
                    ->nullOnDelete();

                $table
                    ->foreign(
                        'phase_group_stage_group_id',
                        'pgsar_group_fk'
                    )
                    ->references('id')
                    ->on('phase_group_stage_groups')
                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'phase_template_id',
                        'status',
                        'sort_order',
                    ],
                    'pgsar_phase_status_order_idx'
                );

                $table->index(
                    'phase_exit_id',
                    'pgsar_exit_idx'
                );

                $table->index(
                    'phase_group_stage_group_id',
                    'pgsar_group_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'phase_group_stage_advancement_rules'
        );
    }
};
