<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | UniverseCompetitor
    |--------------------------------------------------------------------------
    |
    | Contexto de una Entity dentro de un Universo concreto.
    |
    | La Entity de la Biblioteca NO se copia ni se modifica
    | (docs/md/09-Para Futuro.md §46). Esta tabla solo guarda
    | la situación de esa Entity dentro de este Universo.
    |
    | Sin Soft Delete de forma deliberada: quitar un competidor
    | deshace una asociación, no destruye información propia.
    |
    */

    public function up(): void
    {
        Schema::create(
            'universe_competitors',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Vínculo
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('universe_id')
                    ->constrained('universes')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('entity_id')
                    ->constrained('entities')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Contexto dentro del Universo
                |--------------------------------------------------------------------------
                |
                | display_name permite que una misma Entity se llame
                | distinto dentro de este Universo. Si es nulo se usa
                | el nombre canónico de la Entity.
                |
                */

                $table
                    ->string('display_name', 150)
                    ->nullable();

                $table
                    ->string('status', 20)
                    ->default('ACTIVE');

                $table
                    ->text('notes')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Laravel
                |--------------------------------------------------------------------------
                */

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'universe_id',
                        'entity_id',
                    ],
                    'unicomp_universe_entity_unique'
                );

                $table->index(
                    [
                        'universe_id',
                        'status',
                    ],
                    'unicomp_universe_status_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'universe_competitors'
        );
    }
};
