<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'phase_swiss_round_rules',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'phase_template_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Trigger
                |--------------------------------------------------------------------------
                |
                | ROUND_NUMBER
                | QUALIFICATION_MATCH
                | ELIMINATION_MATCH
                | QUALIFICATION_OR_ELIMINATION
                | EXACT_RECORD
                |
                */

                $table
                    ->string('trigger_type', 50);

                $table
                    ->unsignedSmallInteger('round_number')
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
                | Override
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedTinyInteger('best_of');

                /*
                 * NULL = hereda configuración general.
                 */
                $table
                    ->boolean('allow_draws_override')
                    ->nullable();

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
                    'psrr_phase_order_idx'
                );

                $table
                    ->foreign(
                        'phase_template_id',
                        'psrr_phase_fk'
                    )
                    ->references('id')
                    ->on('phase_templates')
                    ->cascadeOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'phase_swiss_round_rules'
        );
    }
};
