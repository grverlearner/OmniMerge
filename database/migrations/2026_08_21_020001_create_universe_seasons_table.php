<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | UniverseSeason
    |--------------------------------------------------------------------------
    |
    | El tiempo propio de un Universo
    | (docs/md/09-Para Futuro.md §54-56).
    |
    | Los cuatro estados son los documentados:
    | PLANNED | ACTIVE | COMPLETED | ARCHIVED
    |
    | Regla de negocio (aplicada en UniverseSeasonService):
    | solo una temporada ACTIVE por Universo. La "temporada actual"
    | del Universo se deriva de esa regla, no se duplica en una
    | columna de la tabla universes.
    |
    */

    public function up(): void
    {
        Schema::create(
            'universe_seasons',
            function (Blueprint $table) {

                $table->id();

                $table
                    ->foreignId('universe_id')
                    ->constrained('universes')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Identidad
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'number'
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
                | Estado y calendario
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('status', 20)
                    ->default('PLANNED');

                $table
                    ->date('starts_at')
                    ->nullable();

                $table
                    ->date('ends_at')
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
                        'universe_id',
                        'number',
                    ],
                    'uniseason_universe_number_unique'
                );

                $table->index(
                    [
                        'universe_id',
                        'status',
                    ],
                    'uniseason_universe_status_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'universe_seasons'
        );
    }
};
