<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Models\GameEncounter;
use App\Models\Universe;
use App\Models\UniverseEntity;
use App\Services\Games\GameRegistry;
use App\Services\Games\GameStatsService;
use App\Services\Games\UniverseGameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| UniverseGameController
|--------------------------------------------------------------------------
|
| El catálogo de juegos del Universo.
|
| Los juegos no se crean desde aquí: viven en código (GameRegistry). Esta
| sección sirve para entenderlos, elegir cuál usa el mundo por defecto, y
| ver quién destaca en cada uno.
|
| Ver docs/md/29-Fase-11-Motor-De-Juegos.md
|
*/

class UniverseGameController extends Controller
{
    public function __construct(
        private readonly
        GameRegistry $registry,

        private readonly
        UniverseGameService $games,

        private readonly
        GameStatsService $stats
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Catálogo
    |--------------------------------------------------------------------------
    */

    public function index(
        Universe $universe
    ): View {

        $this->authorize('view', $universe);

        $games =
            $this->games
            ->sync($universe)
            ->map(
                fn($game) => [

                    'record' => $game,

                    'definition' =>
                    $this->registry->definition($game->game_key),

                    /*
                     * Cuánto se ha jugado de verdad. Un catálogo que no
                     * dice esto es una lista de folletos.
                     */
                    'encounters' =>
                    $universe->gameEncounters()
                        ->where('game_key', $game->game_key)
                        ->count(),
                ]
            );

        return view(
            'universes.games.index',
            compact('universe', 'games')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Ficha del juego
    |--------------------------------------------------------------------------
    */

    public function show(
        Universe $universe,
        string $game
    ): View {

        $this->authorize('view', $universe);

        if (! $this->registry->has($game)) {
            abort(404);
        }

        $definition =
            $this->registry->definition($game);

        $key =
            $definition['key'];

        $record =
            $this->games
            ->sync($universe)
            ->firstWhere('game_key', $key);

        /*
         * Últimos enfrentamientos de este juego en el Universo: es lo que
         * convierte la ficha en algo vivo y no en documentación.
         */
        $recentEncounters =
            GameEncounter::query()
            ->where('universe_id', $universe->id)
            ->where('game_key', $key)
            ->with([
                'participants',
                'tournamentInstance:id,name,code',
            ])
            ->latest('id')
            ->limit(10)
            ->get();

        /*
         * Quién destaca. Se deriva de los enfrentamientos jugados, igual
         * que la clasificación del Universo.
         */
        $leaders =
            $universe->entities()
            ->withCount([
                'encounterResults as encounters_played' =>
                fn($query) =>
                $query->whereHas(
                    'encounter',
                    fn($sub) => $sub->where('game_key', $key)
                ),

                'encounterResults as encounters_won' =>
                fn($query) =>
                $query
                    ->where('is_winner', true)
                    ->whereHas(
                        'encounter',
                        fn($sub) => $sub->where('game_key', $key)
                    ),
            ])
            ->having('encounters_played', '>', 0)
            ->orderByDesc('encounters_won')
            ->orderByDesc('encounters_played')
            ->limit(8)
            ->get();

        return view(
            'universes.games.show',
            compact(
                'universe',
                'definition',
                'record',
                'recentEncounters',
                'leaders'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Juego por defecto del Universo
    |--------------------------------------------------------------------------
    */

    public function setDefault(
        Request $request,
        Universe $universe
    ): RedirectResponse {

        $this->authorize('update', $universe);

        $key =
            (string) $request->input('game_key');

        if (! $this->registry->has($key)) {

            throw ValidationException::withMessages([
                'game_key' => 'Ese juego no existe.',
            ]);
        }

        $this->games
            ->setDefault($universe, $key);

        return back()->with(
            'success',
            $this->registry->definition($key)['name']
                . ' es ahora el juego por defecto del Universo. '
                . 'Los torneos nuevos lo propondrán primero.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Game Stats de un competidor
    |--------------------------------------------------------------------------
    |
    | Se guardan en el UniverseEntity. La Entity de la Biblioteca no se
    | toca ni se entera.
    |
    */

    public function updateStats(
        Request $request,
        Universe $universe,
        UniverseEntity $entity,
        string $game
    ): RedirectResponse {

        $this->authorize('update', $universe);

        if (! $this->registry->has($game)) {
            abort(404);
        }

        $definition =
            $this->registry->definition($game);

        $submitted = [];

        foreach ($definition['stats'] ?? [] as $schema) {

            $submitted[$schema['key']] =
                $request->input(
                    'stats.' . $schema['key'],
                    $schema['default'] ?? null
                );
        }

        /*
         * No se valida contra un esquema rígido: el engine ya sanea, y
         * rechazar un rango invertido cuando se puede enderezar sería
         * fricción sin ganancia.
         */
        $this->stats
            ->update(
                $entity,
                $definition['key'],
                $submitted
            );

        return back()->with(
            'success',
            'Estadísticas de '
                . $definition['name']
                . ' actualizadas para '
                . $entity->display_label
                . '.'
        );
    }
}
