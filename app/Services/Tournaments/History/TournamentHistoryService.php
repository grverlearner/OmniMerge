<?php

namespace App\Services\Tournaments\History;

use App\Models\TournamentInstance;
use App\Models\TournamentInstanceMatch;
use App\Models\TournamentInstancePhaseParticipant;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| TournamentHistoryService
|--------------------------------------------------------------------------
|
| Responde "¿qué pasó en este torneo?".
|
| Solo LEE de las proyecciones: no calcula resultados ni interpreta el
| estado del motor. Si un dato no está proyectado, no se inventa.
|
| Ver docs/md/26-Fase-8-Historial-Y-Estadisticas.md
|
*/

class TournamentHistoryService
{
    /*
    |--------------------------------------------------------------------------
    | Resumen de la competición
    |--------------------------------------------------------------------------
    */

    public function summary(
        TournamentInstance $instance
    ): array {

        $matches =
            $instance->matches();

        $played =
            (clone $matches)
            ->where('status', 'COMPLETED')
            ->count();

        return [

            'participants' =>
            $instance->participant_count,

            'phases' =>
            $instance->phases()->count(),

            'matches' =>
            (clone $matches)->count(),

            'matches_played' =>
            $played,

            'duration' =>
            $this->duration($instance),

            'champion' =>
            $this->champion($instance),
        ];
    }

    /*
     * Duración legible. Nula mientras la competición no haya terminado:
     * mejor sin dato que un número que no significa nada.
     */
    private function duration(
        TournamentInstance $instance
    ): ?string {

        if (
            ! $instance->started_at
            ||
            ! $instance->completed_at
        ) {
            return null;
        }

        $minutes =
            $instance->started_at
            ->diffInMinutes(
                $instance->completed_at
            );

        if ($minutes < 60) {
            return max(1, (int) $minutes) . ' min';
        }

        if ($minutes < 1440) {
            return round($minutes / 60, 1) . ' h';
        }

        return round($minutes / 1440) . ' días';
    }

    public function champion(
        TournamentInstance $instance
    ) {

        /*
         * Un grafo bien formado deja un solo campeón. Si por diseño del
         * grafo llegaran varios al terminal de campeón, se desempata por
         * rendimiento en lugar de devolver uno al azar.
         */
        return $instance
            ->participants()
            ->with('universeEntity')
            ->where(
                'outcome',
                'CHAMPION'
            )
            ->orderByDesc('points')
            ->orderByDesc('wins')
            ->orderBy('seed')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Clasificación final
    |--------------------------------------------------------------------------
    |
    | Orden: campeón primero, después por puntos y victorias. No se
    | inventa una posición numérica cuando el grafo no la produce.
    |
    */

    public function standings(
        TournamentInstance $instance
    ): Collection {

        return $instance
            ->participants()
            ->with('universeEntity')
            ->get()
            ->sortBy([
                fn($a, $b) =>
                ($a->outcome === 'CHAMPION' ? 0 : 1)
                    <=> ($b->outcome === 'CHAMPION' ? 0 : 1),

                fn($a, $b) =>
                $b->points <=> $a->points,

                fn($a, $b) =>
                $b->wins <=> $a->wins,

                fn($a, $b) =>
                $a->seed <=> $b->seed,
            ])
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Fases con su visualización
    |--------------------------------------------------------------------------
    |
    | Cada motor devuelve la forma que le corresponde. No se fuerza una
    | representación común: un bracket y una liguilla no son lo mismo.
    |
    */

    public function phases(
        TournamentInstance $instance
    ): Collection {

        $matches =
            $instance
            ->matches()
            /*
             * Las imágenes de las tarjetas salen de aquí; sin eager
             * loading serían dos consultas por encuentro.
             */
            ->with([
                'participantAEntity',
                'participantBEntity',
            ])
            ->orderBy('round_number')
            ->orderBy('id')
            ->get();

        $standings =
            TournamentInstancePhaseParticipant::query()
            ->where(
                'tournament_instance_id',
                $instance->id
            )
            ->with('universeEntity')
            ->orderBy('position')
            ->get()
            ->groupBy('tournament_instance_phase_id');

        return $instance
            ->phases()
            ->orderBy('id')
            ->get()
            ->map(
                function ($phase) use ($matches, $standings) {

                    $phaseMatches =
                        $matches->where(
                            'node_id',
                            $phase->node_id
                        )
                        ->values();

                    $phaseStandings =
                        $standings->get(
                            $phase->id,
                            collect()
                        );

                    return [

                        'phase' =>
                        $phase,

                        'matches' =>
                        $phaseMatches,

                        'standings' =>
                        $phaseStandings,

                        /*
                         * La vista elige la representación según esto.
                         */
                        'view' =>
                        match ($phase->phase_type) {

                            'SINGLE_ELIMINATION' =>
                            'bracket',

                            'GROUP_STAGE' =>
                            'groups',

                            'ROUND_ROBIN' =>
                            'table',

                            default =>
                            'matches',
                        },

                        'rounds' =>
                        $phaseMatches
                            ->groupBy('round_number'),

                        'groups' =>
                        $phaseStandings
                            ->groupBy('group_label'),
                    ];
                }
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Matriz de enfrentamientos (Round Robin)
    |--------------------------------------------------------------------------
    |
    | Devuelve [claveA][claveB] => encuentro, para pintar la tabla
    | cruzada de todos contra todos.
    |
    */

    public function crossTable(
        Collection $matches
    ): array {

        $table = [];

        foreach ($matches as $match) {

            if (
                ! $match->participant_a_key
                ||
                ! $match->participant_b_key
            ) {
                continue;
            }

            $table[$match->participant_a_key][$match->participant_b_key] =
                $match;

            $table[$match->participant_b_key][$match->participant_a_key] =
                $match;
        }

        return $table;
    }

    /*
    |--------------------------------------------------------------------------
    | Traspaso entre fases
    |--------------------------------------------------------------------------
    |
    | "3 clasificados de Fase de grupos pasaron a Eliminatoria".
    | Se deriva de quién quedó ADVANCED en cada fase.
    |
    */

    public function handovers(
        TournamentInstance $instance
    ): array {

        return TournamentInstancePhaseParticipant::query()
            ->where(
                'tournament_instance_id',
                $instance->id
            )
            ->where(
                'status',
                'ADVANCED'
            )
            ->selectRaw(
                'tournament_instance_phase_id, count(*) as total'
            )
            ->groupBy(
                'tournament_instance_phase_id'
            )
            ->pluck(
                'total',
                'tournament_instance_phase_id'
            )
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Encuentros con filtros
    |--------------------------------------------------------------------------
    */

    public function matches(
        TournamentInstance $instance,
        ?int $nodeId = null,
        ?int $round = null
    ) {

        return TournamentInstanceMatch::query()
            ->where(
                'tournament_instance_id',
                $instance->id
            )
            ->when(
                $nodeId,

                fn($query) =>
                $query->where('node_id', $nodeId)
            )
            ->when(
                $round,

                fn($query) =>
                $query->where('round_number', $round)
            )
            ->orderBy('node_id')
            ->orderBy('round_number')
            ->orderBy('id')
            ->get();
    }
}
