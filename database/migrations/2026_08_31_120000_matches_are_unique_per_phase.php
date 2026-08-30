<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Dos fases a la vez pueden llamar igual a sus batallas
|--------------------------------------------------------------------------
|
| Cada motor numera sus enfrentamientos con su propia cuenta: la primera
| jornada de una liga es «RR-R1-M1», venga de la fase que venga. Mientras un
| torneo fuese una fila eso bastaba, porque no había dos ligas a la vez.
|
| Con dos fases arrancando en paralelo hay 45 batallas en una y 45 en la
| otra, y las 45 se llaman igual. Como la fila se consideraba única por
| competición, las de la segunda fase PISABAN a las de la primera: quedaban
| 45 filas en vez de 90, todas apuntando a un solo nodo.
|
| El síntoma no parecía eso. Parecía que solo se podía jugar una fase: la
| otra se veía vacía, sin un solo enfrentamiento, porque literalmente no
| existía en la proyección.
|
| El identificador sigue siendo el del motor —local a su fase, que es lo que
| el motor entiende—; lo que cambia es que la fase forma parte de la
| identidad. Ninguna fila existente se toca: añadir una columna a un índice
| único solo lo hace más permisivo.
|
*/
return new class extends Migration
{
    private const VIEJO = 'tmatch_instance_match_unique';

    private const NUEVO = 'tmatch_instance_node_match_unique';

    public function up(): void
    {
        Schema::table('tournament_instance_matches', function (Blueprint $table) {

            $table->unique(
                [
                    'tournament_instance_id',
                    'node_id',
                    'runtime_match_id',
                ],
                self::NUEVO
            );
        });

        Schema::table('tournament_instance_matches', function (Blueprint $table) {
            $table->dropUnique(self::VIEJO);
        });
    }

    public function down(): void
    {
        /*
         * Volver atrás solo es posible si no hay ya dos fases compartiendo
         * identificador. Si las hay, se avisa en vez de fallar con un error
         * de índice duplicado que no explica nada.
         */
        $duplicados = \Illuminate\Support\Facades\DB::table('tournament_instance_matches')
            ->selectRaw('tournament_instance_id, runtime_match_id')
            ->groupBy('tournament_instance_id', 'runtime_match_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicados) {
            throw new RuntimeException(
                'Hay competiciones con fases en paralelo que comparten '
                . 'identificador de batalla. Restaurar el índice antiguo '
                . 'borraría enfrentamientos jugados.'
            );
        }

        Schema::table('tournament_instance_matches', function (Blueprint $table) {

            $table->unique(
                [
                    'tournament_instance_id',
                    'runtime_match_id',
                ],
                self::VIEJO
            );
        });

        Schema::table('tournament_instance_matches', function (Blueprint $table) {
            $table->dropUnique(self::NUEVO);
        });
    }
};
