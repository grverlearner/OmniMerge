<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Models\TournamentInstance;
use App\Models\TournamentInstanceParticipant;
use App\Models\Universe;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| UniverseHistoryController
|--------------------------------------------------------------------------
|
| "¿Qué se ha jugado en este Universo?"
|
| Solo lectura sobre proyecciones. No toca el runtime ni el estado.
|
*/

class UniverseHistoryController extends Controller
{
    public function index(
        Request $request,
        Universe $universe
    ): View {

        $this->authorize(
            'view',
            $universe
        );

        $engine =
            (string) $request->input('engine', '');

        $seasonId =
            (int) $request->input('season', 0);

        $sort =
            (string) $request->input('sort', 'newest');

        $query =
            TournamentInstance::query()
            ->inUniverse($universe)
            ->with([
                'universeTournament',
                'season',
            ])
            /*
             * El historial es lo que YA se jugó: una competición que
             * nunca arrancó no cuenta como historia.
             */
            ->where(
                'status',
                '!=',
                'DRAFT'
            )
            ->when(
                $engine,

                fn($builder) =>
                $builder->whereHas(
                    'phases',

                    fn($phases) =>
                    $phases->where('phase_type', $engine)
                )
            )
            ->when(
                $seasonId,

                fn($builder) =>
                $builder->where('universe_season_id', $seasonId)
            );

        match ($sort) {

            'oldest' =>
            $query->orderBy('started_at'),

            default =>
            $query->orderByDesc('started_at')
                ->orderByDesc('id'),
        };

        $competitions =
            $query
            ->paginate(12)
            ->withQueryString();

        /*
         * Los campeones se traen en una sola consulta para las tarjetas,
         * en lugar de una por competición.
         */
        $champions =
            TournamentInstanceParticipant::query()
            ->whereIn(
                'tournament_instance_id',
                $competitions->pluck('id')
            )
            ->where(
                'outcome',
                'CHAMPION'
            )
            ->with('universeEntity')
            ->get()
            ->keyBy('tournament_instance_id');

        $statistics = [

            'played' =>
            TournamentInstance::query()
                ->inUniverse($universe)
                ->where('status', '!=', 'DRAFT')
                ->count(),

            'completed' =>
            TournamentInstance::query()
                ->inUniverse($universe)
                ->where('status', 'COMPLETED')
                ->count(),

            'matches' =>
            \App\Models\TournamentInstanceMatch::query()
                ->whereIn(
                    'tournament_instance_id',

                    TournamentInstance::query()
                        ->inUniverse($universe)
                        ->select('id')
                )
                ->where('status', 'COMPLETED')
                ->count(),
        ];

        $seasons =
            $universe
            ->seasons()
            ->get();

        return view(
            'universes.history.index',
            compact(
                'universe',
                'competitions',
                'champions',
                'statistics',
                'seasons',
                'engine',
                'seasonId',
                'sort'
            )
        );
    }
}
