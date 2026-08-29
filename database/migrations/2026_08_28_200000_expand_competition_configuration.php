<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Lo que decide una EDICION, y lo que decide cada fase suya
|--------------------------------------------------------------------------
|
| El torneo es la marca -"la Copa"-. La competicion es la edicion que se
| juega este ano, y no todas las ediciones son iguales: cambia el juego,
| cambia cuantos juegos dura un enfrentamiento, y a veces cambia hasta
| dentro de la misma edicion -grupos a un juego, final al mejor de cinco-.
|
| Tres niveles, de mas debil a mas fuerte:
|
|   el torneo        lo que heredan todas las ediciones
|   la competicion   lo que decide ESTA edicion
|   la fase          la excepcion dentro de esta edicion
|
| Un nivel solo puede abrir lo que el de arriba permitio: si el torneo dice
| que el juego es siempre el mismo, la edicion no lo cambia. Eso es lo que
| hacen las dos banderas nuevas del torneo.
|
*/
return new class extends Migration
{
    public function up(): void
    {
        /*
         * El torneo decide si sus ediciones pueden bajar la decision a las
         * fases. Antes solo decia si el JUEGO podia variar entre ediciones
         * (game_mode); no habia forma de decir "y ademas puede variar entre
         * fases", que es una pregunta distinta.
         */
        Schema::table('universe_tournaments', function (Blueprint $table) {

            $table->boolean('allow_phase_game')
                ->default(false)
                ->after('game_mode');

            $table->boolean('allow_phase_battle')
                ->default(false)
                ->after('allow_phase_game');
        });


        Schema::table('tournament_instances', function (Blueprint $table) {

            /* Lo basico de una edicion: tiene cartel propio */
            $table->text('description')
                ->nullable()
                ->after('name');

            $table->string('image')
                ->nullable()
                ->after('description');

            /*
             * La batalla de ESTA edicion. series_format, best_of y
             * fixed_games ya existian; faltaba el resto de lo que define
             * como se pelea.
             */
            $table->unsignedTinyInteger('battle_participants')
                ->nullable()
                ->after('fixed_games');

            $table->string('decision_mode', 24)
                ->default('SERIES_THEN_POINTS')
                ->after('battle_participants');

            $table->boolean('allow_draws')
                ->default(false)
                ->after('decision_mode');

            /*
             * Donde se decide cada cosa dentro de esta edicion.
             *
             * COMPETITION: una sola configuracion para todas las fases.
             * PHASE:       cada fase puede tener la suya.
             *
             * Solo se puede poner en PHASE si el torneo lo permitio.
             */
            $table->string('game_scope', 16)
                ->default('COMPETITION')
                ->after('allow_draws');

            $table->string('battle_scope', 16)
                ->default('COMPETITION')
                ->after('game_scope');

            /*
             * Con que criterio se repartieron los competidores entre los
             * puntos de entrada del grafo.
             *
             * Se guarda -y no solo el resultado- porque la edicion
             * siguiente se hace copiando esta: sin la regla habria que
             * volver a marcar competidor por competidor.
             *
             * Su forma la define CompetitionStartRouting.
             */
            $table->json('start_rules')
                ->nullable()
                ->after('battle_scope');

            /* De que edicion se copio esta, si se copio de alguna */
            $table->foreignId('copied_from_instance_id')
                ->nullable()
                ->after('start_rules')
                ->constrained('tournament_instances')
                ->nullOnDelete();
        });


        /*
         * La excepcion de una fase concreta. Nulo = lo que diga la
         * competicion, que es lo normal.
         *
         * series_format, best_of y fixed_games ya estaban aqui.
         */
        Schema::table('tournament_instance_phases', function (Blueprint $table) {

            $table->string('game_key', 60)
                ->nullable()
                ->after('phase_type');

            $table->unsignedTinyInteger('battle_participants')
                ->nullable()
                ->after('fixed_games');

            $table->string('decision_mode', 24)
                ->nullable()
                ->after('battle_participants');

            $table->boolean('allow_draws')
                ->nullable()
                ->after('decision_mode');
        });


        /*
         * Un trofeo puede nacer para una edicion concreta.
         *
         * Los del torneo -sin competicion- son la vitrina permanente de la
         * marca y una edicion no los toca. Los de una edicion son suyos: se
         * crean, se corrigen y se borran ahi mismo.
         */
        Schema::table('universe_trophies', function (Blueprint $table) {

            $table->foreignId('tournament_instance_id')
                ->nullable()
                ->after('universe_id')
                ->constrained('tournament_instances')
                ->cascadeOnDelete();
        });


        /*
         * Los premios de una edicion.
         *
         * Tabla aparte de universe_tournament_rewards y no una columna
         * nullable en ella: los del torneo los heredan TODAS las ediciones
         * y editarlos desde una sola las cambiaria todas, que es
         * justamente lo que no debe pasar.
         */
        Schema::create('tournament_instance_rewards', function (Blueprint $table) {

            $table->id();

            $table->foreignId('tournament_instance_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * En que fase se gana.
             *
             * NULL = al terminar la competicion, que es lo habitual.
             * Un nodo = al terminar esa fase, y entonces "puesto 1"
             * significa primero de esa fase, no del torneo.
             */
            $table->unsignedBigInteger('node_id')
                ->nullable();

            $table->string('trigger', 32)
                ->default('POSITION');

            $table->unsignedSmallInteger('threshold')
                ->nullable();

            $table->string('game_key', 60)
                ->nullable();

            $table->string('stat_key', 60)
                ->nullable();

            $table->string('operation', 16)
                ->default('ADD');

            $table->decimal('amount', 10, 2)
                ->default(0);

            $table->foreignId('universe_trophy_id')
                ->nullable()
                ->constrained('universe_trophies')
                ->nullOnDelete();

            $table->string('label', 150)
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            /*
             * Si la edicion siguiente debe ofrecerlo ya marcado al
             * copiarse. Un premio inventado para un aniversario no deberia
             * arrastrarse solo; uno que se quiere fijo, si.
             */
            $table->boolean('carry_forward')
                ->default(true);

            $table->timestamps();

            $table->index(['tournament_instance_id', 'node_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_instance_rewards');

        Schema::table('universe_trophies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tournament_instance_id');
        });

        Schema::table('tournament_instance_phases', function (Blueprint $table) {
            $table->dropColumn([
                'game_key',
                'battle_participants',
                'decision_mode',
                'allow_draws',
            ]);
        });

        Schema::table('tournament_instances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('copied_from_instance_id');
            $table->dropColumn([
                'description',
                'image',
                'battle_participants',
                'decision_mode',
                'allow_draws',
                'game_scope',
                'battle_scope',
                'start_rules',
            ]);
        });

        Schema::table('universe_tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'allow_phase_game',
                'allow_phase_battle',
            ]);
        });
    }
};
