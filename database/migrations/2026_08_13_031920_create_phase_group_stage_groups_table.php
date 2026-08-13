<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'phase_group_stage_groups',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('phase_template_id')
                    ->constrained('phase_templates')
                    ->cascadeOnDelete();

                $table
                    ->unsignedInteger('sequence_number');

                $table
                    ->string('code', 30);

                $table
                    ->string('name', 120);

                /*
                |--------------------------------------------------------------------------
                | Capacidad
                |--------------------------------------------------------------------------
                |
                | Principalmente útil para:
                |
                | CUSTOM_GROUPS
                | remainder_policy = MANUAL
                |
                */

                $table
                    ->unsignedSmallInteger('capacity')
                    ->nullable();

                $table
                    ->boolean('is_active')
                    ->default(true);

                $table
                    ->unsignedInteger('sort_order')
                    ->default(10);

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'phase_template_id',
                        'sequence_number',
                    ],
                    'pgsg_phase_sequence_unique'
                );

                $table->unique(
                    [
                        'phase_template_id',
                        'code',
                    ],
                    'pgsg_phase_code_unique'
                );

                $table->index(
                    [
                        'phase_template_id',
                        'is_active',
                        'sort_order',
                    ],
                    'pgsg_phase_active_order_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'phase_group_stage_groups'
        );
    }
};
