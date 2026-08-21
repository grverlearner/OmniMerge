<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | TournamentInstance
    |--------------------------------------------------------------------------
    |
    | Una competición REAL: la ejecución de un UniverseTournament.
    |
    | TournamentTemplate  → cómo está diseñado el torneo
    | UniverseTournament  → qué torneo está configurado en el Universo
    | TournamentInstance  → una ejecución concreta, con su propia historia
    |
    | Fila deliberadamente ligera: los blobs (snapshot y estado del motor)
    | viven en tablas 1:1 aparte para que listar competiciones no arrastre
    | cientos de KB y para que guardar el estado no reescriba el snapshot.
    |
    | Ver docs/md/24-Fase-6-Tournament-Runtime-Persistente.md
    |
    */

    public function up(): void
    {
        Schema::create(
            'tournament_instances',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Pertenencia
                |--------------------------------------------------------------------------
                |
                | universe_id está desnormalizado a propósito: permite listar
                | todas las competiciones de un Universo sin join y es el
                | anclaje de la autorización.
                |
                */

                $table
                    ->foreignId('universe_id')
                    ->constrained('universes')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('universe_tournament_id')
                    ->constrained('universe_tournaments')
                    ->cascadeOnDelete();

                /*
                 * Temporada opcional e informativa. Se rellena con la
                 * temporada en curso al crear la competición y no vuelve
                 * a cambiar. Sin reglas de temporada en esta fase.
                 */
                $table
                    ->foreignId('universe_season_id')
                    ->nullable()
                    ->constrained('universe_seasons')
                    ->nullOnDelete();

                /*
                 * Solo trazabilidad. La configuración real que se ejecuta
                 * vive congelada en el snapshot, no aquí.
                 */
                $table
                    ->foreignId('tournament_template_id')
                    ->nullable()
                    ->constrained('tournament_templates')
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

                /*
                |--------------------------------------------------------------------------
                | Estado
                |--------------------------------------------------------------------------
                |
                | status        → ciclo de vida de la competición
                | runtime_status→ espejo del motor, solo para mostrar
                |
                */

                $table
                    ->string('status', 20)
                    ->default('DRAFT');

                $table
                    ->string('runtime_status', 30)
                    ->nullable();

                $table
                    ->unsignedInteger('participant_count')
                    ->default(0);

                $table
                    ->timestamp('started_at')
                    ->nullable();

                $table
                    ->timestamp('completed_at')
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
                        'universe_id',
                        'code',
                    ],
                    'tinst_universe_code_unique'
                );

                $table->index(
                    [
                        'universe_id',
                        'status',
                    ],
                    'tinst_universe_status_index'
                );

                $table->index(
                    'universe_tournament_id',
                    'tinst_universe_tournament_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'tournament_instances'
        );
    }
};
