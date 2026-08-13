<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'phase_swiss_advancement_rules',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'phase_template_id'
                );

                $table
                    ->unsignedBigInteger(
                        'phase_exit_id'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Rule Type
                |--------------------------------------------------------------------------
                |
                | WIN_THRESHOLD
                | LOSS_THRESHOLD
                | EXACT_RECORD
                |
                | FINAL_TOP_N
                | FINAL_BOTTOM_N
                | FINAL_RANK_POSITION
                | FINAL_RANK_RANGE
                |
                | REMAINING
                |
                */

                $table
                    ->string('rule_type', 50);

                /*
                |--------------------------------------------------------------------------
                | Thresholds
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedSmallInteger('threshold_wins')
                    ->nullable();

                $table
                    ->unsignedSmallInteger('threshold_losses')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Exact Record
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedSmallInteger('record_wins')
                    ->nullable();

                $table
                    ->unsignedSmallInteger('record_draws')
                    ->nullable();

                $table
                    ->unsignedSmallInteger('record_losses')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Final ranking
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedSmallInteger('rank_from')
                    ->nullable();

                $table
                    ->unsignedSmallInteger('rank_to')
                    ->nullable();

                $table
                    ->unsignedSmallInteger('take')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Orden / estado
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedInteger('sort_order')
                    ->default(10);

                $table
                    ->string('status', 20)
                    ->default('ACTIVE');

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'phase_template_id',
                        'status',
                        'sort_order',
                    ],
                    'psar_phase_order_idx'
                );

                $table->index(
                    'phase_exit_id',
                    'psar_exit_idx'
                );

                $table
                    ->foreign(
                        'phase_template_id',
                        'psar_phase_fk'
                    )
                    ->references('id')
                    ->on('phase_templates')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'phase_exit_id',
                        'psar_exit_fk'
                    )
                    ->references('id')
                    ->on('phase_exits')
                    ->nullOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'phase_swiss_advancement_rules'
        );
    }
};
