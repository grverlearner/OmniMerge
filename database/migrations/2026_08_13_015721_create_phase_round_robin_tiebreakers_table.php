<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'phase_round_robin_tiebreakers',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('phase_template_id')
                    ->constrained('phase_templates')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Criterio
                |--------------------------------------------------------------------------
                |
                | POINTS no se guarda aquí.
                |
                | Puntos siempre será el criterio principal.
                | Esta tabla contiene criterios utilizados cuando
                | dos o más participantes tienen los mismos puntos.
                |
                */

                $table
                    ->string('criterion', 40);

                /*
                |--------------------------------------------------------------------------
                | Dirección
                |--------------------------------------------------------------------------
                |
                | AUTO = la decide OmniMerge según el criterio.
                | ASC
                | DESC
                |
                */

                $table
                    ->string('direction', 10)
                    ->default('AUTO');

                /*
                |--------------------------------------------------------------------------
                | Orden
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedInteger('sort_order')
                    ->default(10);

                /*
                |--------------------------------------------------------------------------
                | Extensión
                |--------------------------------------------------------------------------
                */

                $table
                    ->json('settings')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'phase_template_id',
                        'criterion',
                    ],
                    'prrt_phase_criterion_unique'
                );

                $table->index(
                    [
                        'phase_template_id',
                        'sort_order',
                    ],
                    'prrt_phase_order_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'phase_round_robin_tiebreakers'
        );
    }
};
