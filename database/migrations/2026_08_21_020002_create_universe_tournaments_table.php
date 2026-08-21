<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | UniverseTournament (UniverseTournamentDefinition)
    |--------------------------------------------------------------------------
    |
    | Uso concreto de una TournamentTemplate dentro de un Universo
    | (docs/md/09-Para Futuro.md §57).
    |
    | Sustituye a la FK directa tournament_templates.universe_id, que
    | impedía reutilizar una misma plantilla en varios Universos y
    | contradecía el principio "crear una vez y reutilizar muchas
    | veces" (§906-907).
    |
    | Sin índice único (universe_id, tournament_template_id): un mismo
    | Universo puede adoptar la misma plantilla varias veces con
    | nombres distintos.
    |
    | Sin season_id de forma deliberada: la definición es atemporal.
    | Cuando exista TournamentInstance (Fase 6 / Sprint U6), será la
    | instancia la que pertenezca a una temporada concreta.
    |
    */

    public function up(): void
    {
        Schema::create(
            'universe_tournaments',
            function (Blueprint $table) {

                $table->id();

                $table
                    ->foreignId('universe_id')
                    ->constrained('universes')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('tournament_template_id')
                    ->constrained('tournament_templates')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Contexto dentro del Universo
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'name',
                    150
                );

                $table
                    ->text('description')
                    ->nullable();

                $table
                    ->string('status', 20)
                    ->default('DRAFT');

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

                $table->index(
                    [
                        'universe_id',
                        'status',
                    ],
                    'unitour_universe_status_index'
                );

                $table->index(
                    'tournament_template_id',
                    'unitour_template_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'universe_tournaments'
        );
    }
};
