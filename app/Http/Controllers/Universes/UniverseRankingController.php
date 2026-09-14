<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Models\Universe;
use App\Services\Universes\UniverseRankingService;
use App\Support\Universes\UniverseSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| UniverseRankingController
|--------------------------------------------------------------------------
|
| Clasificación del Universo. Contextual por definición: la misma Entidad
| de Biblioteca puede ser #1 aquí y #18 en otro Universo.
|
*/

class UniverseRankingController extends Controller
{
    public function __construct(
        private readonly
        UniverseRankingService $ranking
    ) {}

    public function index(
        Request $request,
        Universe $universe
    ): View {

        $this->authorize('view', $universe);

        $seasonId = (int) $request->input('season', 0);

        $gameKey = (string) $request->input('game');

        $tournamentId = (int) $request->input('tournament', 0);

        $search = trim((string) $request->input('search'));

        $sort = (string) $request->input('sort', 'points');


        /*
         * El servicio ya sabia filtrar por juego y por torneo desde que se
         * escribio, y el panel nunca se lo pedia. Son las dos preguntas que
         * distinguen una clasificacion util de una lista: quien manda EN ESTE
         * JUEGO, y quien manda EN ESTE TORNEO a lo largo de sus ediciones.
         */
        $ranking =
            $this->ranking
            ->ranking(
                $universe,
                $seasonId ?: null,
                array_filter([
                    'game_key' => $gameKey ?: null,
                    'universe_tournament_id' => $tournamentId ?: null,
                ])
            );


        /* La busqueda se hace sobre lo ya calculado: son pocas filas */
        if ($search !== '') {

            $ranking = $ranking
                ->filter(
                    fn($fila) => str_contains(
                        mb_strtolower($fila->entity?->display_label ?? ''),
                        mb_strtolower($search)
                    )
                )
                ->values();
        }


        /*
         * El orden no toca las posiciones: la posicion es la del ranking por
         * puntos y se conserva aunque se mire ordenado por otra cosa. Ordenar
         * por porcentaje de victorias y renumerar convertiria la tabla en otra
         * clasificacion distinta sin avisar.
         */
        $ranking = match ($sort) {

            'titles' => $ranking->sortByDesc('titles')->values(),

            'win_rate' => $ranking
                ->sortByDesc(fn($fila) => $fila->win_rate ?? -1)
                ->values(),

            'played' => $ranking->sortByDesc('tournaments')->values(),

            'name' => $ranking
                ->sortBy(fn($fila) => mb_strtolower($fila->entity?->display_label ?? ''))
                ->values(),

            default => $ranking,
        };


        $seasons =
            $universe->seasons()
            ->orderByDesc('number')
            ->get();

        $torneos =
            $universe->universeTournaments()
            ->orderBy('name')
            ->get();

        $juegos =
            collect(app(\App\Services\Games\GameRegistry::class)->definitions())
            ->keyBy('key');

        $settings =
            (new UniverseSettings($universe))->all();


        /*
        |--------------------------------------------------------------------------
        | Cifras
        |--------------------------------------------------------------------------
        */

        $statistics = [

            'clasificados' => $ranking->count(),

            'con_titulo' => $ranking->where('titles', '>', 0)->count(),

            'partidas' => (int) $ranking->sum('matches'),

            'sin_ganar' => $ranking->filter(
                fn($fila) => (int) $fila->wins === 0
            )->count(),
        ];


        /*
         * El podio, aparte del resto: es lo que se mira primero.
         */
        $podio = $ranking->take(3);

        /*
         * «Los ultimos en ganar» promete competiciones recientes, no ganadores
         * sueltos. Y un torneo que se queda en fase de grupos marca CHAMPION a
         * todos los que clasifican: pedir seis filas devolvia seis caras de una
         * sola competicion y escondia las demas.
         *
         * Se piden mas filas y se agrupan por competicion, quedandose con las
         * seis ultimas. Cada una lleva a todos sus ganadores.
         */
        $campeones =
            $this->ranking
            ->recentChampions($universe, 60)
            ->groupBy('tournament_instance_id')
            ->take(6);


        return view(
            'universes.ranking.index',
            compact(
                'universe',
                'ranking',
                'seasons',
                'seasonId',
                'settings',
                'gameKey',
                'tournamentId',
                'search',
                'sort',
                'torneos',
                'juegos',
                'statistics',
                'podio',
                'campeones'
            )
        );
    }

    /*
     * El sistema de puntos es lo único configurable que tiene efecto
     * real hoy, así que vive junto al ranking y no en un panel aparte.
     */
    public function updatePoints(
        Request $request,
        Universe $universe
    ): RedirectResponse {

        $this->authorize('update', $universe);

        $data = $request->validate([
            'points_champion' => ['required', 'integer', 'min:0', 'max:1000'],
            'points_win' => ['required', 'integer', 'min:0', 'max:1000'],
            'points_draw' => ['required', 'integer', 'min:0', 'max:1000'],
            'points_loss' => ['required', 'integer', 'min:0', 'max:1000'],
            'points_participation' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        (new UniverseSettings($universe))->save($data);

        return back()->with(
            'success',
            'Sistema de puntos actualizado. La clasificación se recalcula sola.'
        );
    }
}
