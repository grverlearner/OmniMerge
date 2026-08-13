<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'phase_exits',
            function (Blueprint $table) {

                /*
                |--------------------------------------------------------------------------
                | Momento de salida
                |--------------------------------------------------------------------------
                |
                | PHASE_END
                | ON_ELIMINATION
                |
                */

                $table
                    ->string('exit_timing', 30)
                    ->default('PHASE_END')
                    ->after('selector_type');

                /*
                |--------------------------------------------------------------------------
                | Ronda de eliminación
                |--------------------------------------------------------------------------
                |
                | Solo se usa principalmente con:
                |
                | ELIMINATED_IN_ROUND
                |
                | 2  = Final
                | 4  = Semifinal
                | 8  = Cuartos
                | ...
                |
                */

                $table
                    ->unsignedSmallInteger(
                        'selector_round_size'
                    )
                    ->nullable()
                    ->after('selector_to');

                $table->index(
                    [
                        'phase_template_id',
                        'selector_type',
                    ],
                    'pe_phase_selector_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'phase_exits',
            function (Blueprint $table) {

                $table->dropIndex(
                    'pe_phase_selector_index'
                );

                $table->dropColumn([
                    'exit_timing',
                    'selector_round_size',
                ]);
            }
        );
    }
};
