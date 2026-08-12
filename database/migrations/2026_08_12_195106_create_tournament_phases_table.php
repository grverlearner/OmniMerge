<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'tournament_phases',
            function (Blueprint $table) {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Plantilla
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId(
                        'tournament_template_id'
                    )
                    ->constrained(
                        'tournament_templates'
                    )
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Identidad
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'sequence_number'
                );

                $table->string(
                    'code',
                    30
                );

                $table->string(
                    'name',
                    150
                );

                $table
                    ->text('description')
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Tipo de fase
                |--------------------------------------------------------------------------
                |
                | En esta primera versión solamente activaremos
                | SINGLE_ELIMINATION.
                |
                */

                $table->string(
                    'phase_type',
                    40
                );


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
                | Participantes
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedSmallInteger(
                        'input_participants'
                    )
                    ->nullable();

                $table
                    ->unsignedSmallInteger(
                        'qualifiers_count'
                    )
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Enfrentamiento
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedTinyInteger(
                        'best_of'
                    )
                    ->default(1);

                $table
                    ->boolean(
                        'allow_byes'
                    )
                    ->default(false);


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
                | Extensión futura
                |--------------------------------------------------------------------------
                */

                $table
                    ->json('settings')
                    ->nullable();


                $table->timestamps();

                $table->softDeletes();


                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'tournament_template_id',
                        'sequence_number',
                    ],
                    'tp_template_sequence_unique'
                );

                $table->unique(
                    [
                        'tournament_template_id',
                        'code',
                    ],
                    'tp_template_code_unique'
                );

                $table->index(
                    [
                        'tournament_template_id',
                        'sort_order',
                    ],
                    'tp_template_order_index'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'tournament_phases'
        );
    }
};