<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'tournament_templates',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Propietario
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Procedencia
                |--------------------------------------------------------------------------
                |
                | Más adelante será útil para Comunidad y clonación.
                |
                */

                $table
                    ->foreignId(
                        'source_tournament_template_id'
                    )
                    ->nullable()
                    ->constrained(
                        'tournament_templates'
                    )
                    ->nullOnDelete();


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

                $table->string(
                    'slug',
                    180
                );

                $table
                    ->text('description')
                    ->nullable();

                $table
                    ->string('image')
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Participantes
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedSmallInteger(
                        'min_participants'
                    )
                    ->default(2);

                $table
                    ->unsignedSmallInteger(
                        'max_participants'
                    )
                    ->nullable();

                $table
                    ->boolean('allow_byes')
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
                    ->default('DRAFT');

                $table
                    ->string(
                        'visibility',
                        20
                    )
                    ->default('PRIVATE');


                /*
                |--------------------------------------------------------------------------
                | Comunidad
                |--------------------------------------------------------------------------
                */

                $table
                    ->boolean(
                        'allow_cloning'
                    )
                    ->default(true);

                $table
                    ->unsignedBigInteger(
                        'views_count'
                    )
                    ->default(0);

                $table
                    ->unsignedBigInteger(
                        'clones_count'
                    )
                    ->default(0);

                $table
                    ->timestamp(
                        'published_at'
                    )
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Configuración extensible
                |--------------------------------------------------------------------------
                */

                $table
                    ->json('settings')
                    ->nullable();

                $table
                    ->json('metadata')
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Laravel
                |--------------------------------------------------------------------------
                */

                $table->timestamps();

                $table->softDeletes();


                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'user_id',
                        'sequence_number',
                    ],
                    'tt_user_sequence_unique'
                );

                $table->unique(
                    [
                        'user_id',
                        'code',
                    ],
                    'tt_user_code_unique'
                );

                $table->unique(
                    [
                        'user_id',
                        'slug',
                    ],
                    'tt_user_slug_unique'
                );

                $table->index(
                    [
                        'user_id',
                        'status',
                    ],
                    'tt_user_status_index'
                );

                $table->index(
                    [
                        'visibility',
                        'status',
                        'published_at',
                    ],
                    'tt_public_index'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'tournament_templates'
        );
    }
};
