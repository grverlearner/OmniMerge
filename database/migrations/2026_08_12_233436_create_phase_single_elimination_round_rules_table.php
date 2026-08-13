<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'phase_single_elimination_round_rules',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('phase_template_id')
                    ->constrained('phase_templates')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Tamaño lógico de ronda
                |--------------------------------------------------------------------------
                |
                | 2   = Final
                | 4   = Semifinal
                | 8   = Cuartos
                | 16  = Round of 16
                | 32  = Round of 32
                | etc.
                |
                */

                $table
                    ->unsignedSmallInteger(
                        'participants_in_round'
                    );

                /*
                |--------------------------------------------------------------------------
                | Override Best Of
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedTinyInteger('best_of');

                $table
                    ->unsignedInteger('sort_order')
                    ->default(10);

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Una regla por tamaño de ronda
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'phase_template_id',
                        'participants_in_round',
                    ],
                    'pser_phase_round_unique'
                );

                $table->index(
                    [
                        'phase_template_id',
                        'sort_order',
                    ],
                    'pser_phase_order_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'phase_single_elimination_round_rules'
        );
    }
};
