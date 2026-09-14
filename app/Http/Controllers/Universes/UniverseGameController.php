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

                    /*
                     * Y cuantas competiciones lo han elegido. Enfrentamientos
                     * altos con cero competiciones significa que todo se ha
                     * jugado en el simulador, que es otra cosa.
                     */
                    'competitions' =>
                    $universe->tournamentInstances()
                        ->where('game_key', $game->game_key)
                        ->count(),

                    /*
                     * Con que valores entra un competidor nuevo en este
                     * Universo. La tarjeta del juego lo ensena sin obligar a
                     * abrir su ficha.
                     */
                    'configuration' =>
                    $this->games->configuration(
                        $universe,
                        $game->game_key
                    ),
                ]
            );


        /*
         * Cuantos competidores hay en el mundo. Sin esto, «0 partidas» no se
         * distingue de «todavia no hay con quien jugar».
         */
        $competidores =
            $universe->entities()->count();

        return view(
            'universes.games.index',
            compact('universe', 'games', 'competidores')
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
                'participants.universeEntity',
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

        /*
         * Como entra un competidor nuevo en ESTE Universo. El motor dice
         * que estadisticas existen; el Universo, con que valores empiezan.
         */
        $configuration =
            $this->games->configuration($universe, $key);

        /*
         * Cuantos competidores tienen ya estadisticas de este juego. Es a
         * quienes afectaria reajustar la configuracion, asi que el nombre
         * dice eso y no otra cosa.
         */
        $conEstadisticas =
            $universe->entities()
            ->whereHas(
                'gameStats',
                fn($query) => $query->where('game_key', $key)
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Los competidores, con sus numeros de ESTE juego
        |--------------------------------------------------------------------------
        |
        | Era lo que le faltaba a esta ficha. Un juego se entiende leyendo sus
        | reglas, pero se entiende de verdad viendo con que numeros lo juega
        | cada uno: quien es fiable, quien es una loteria, y quien todavia
        | esta con los valores de partida.
        |
        */

        $iniciales =
            $configuration->initialStats();

        $competidores =
            $universe->entities()
            ->with([
                'gameStats' => fn($query) => $query->where('game_key', $key),
            ])
            ->withCount([
                'encounterResults as jugados' =>
                fn($query) =>
                $query->whereHas(
                    'encounter',
                    fn($sub) => $sub->where('game_key', $key)
                ),

                'encounterResults as ganados' =>
                fn($query) =>
                $query
                    ->where('is_winner', true)
                    ->whereHas(
                        'encounter',
                        fn($sub) => $sub->where('game_key', $key)
                    ),
            ])
            ->orderBy('name')
            ->get()
            ->map(
                function ($entidad) use ($iniciales, $configuration) {

                    $suyas = $entidad->gameStats->first()?->stats;

                    return [
                        'entidad' => $entidad,

                        /* Sin fila propia, juega con los valores de partida */
                        'stats' =>
                        $configuration->clampStats(
                            is_array($suyas) && $suyas !== []
                                ? $suyas
                                : $iniciales
                        ),

                        'propias' =>
                        is_array($suyas) && $suyas !== [],

                        'jugados' => (int) $entidad->jugados,
                        'ganados' => (int) $entidad->ganados,
                    ];
                }
            );


        /*
         * El techo de todos, para poder dibujar los rangos a la misma escala.
         * Sin esto cada barra usaria su propia regla y no se podrian comparar.
         */
        $techo =
            max(
                1.0,
                $competidores
                    ->flatMap(fn($fila) => array_values($fila['stats']))
                    ->max() ?? 1.0
            );


        return view(
            'universes.games.show',
            compact(
                'universe',
                'definition',
                'record',
                'recentEncounters',
                'leaders',
                'configuration',
                'conEstadisticas',
                'competidores',
                'techo'
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
    | Configuracion del juego en este Universo
    |--------------------------------------------------------------------------
    |
    | Decide con que valores entra un competidor nuevo y hasta donde puede
    | llegar en este mundo. Un Universo de recien llegados puede repartir
    | 0-3; uno de veteranos, 5-20. Mismo juego, mismo motor.
    |
    */

    public function updateConfiguration(
        Request $request,
        Universe $universe,
        string $game
    ): RedirectResponse {

        $this->authorize('update', $universe);

        if (! $this->registry->has($game)) {
            abort(404);
        }

        $definition = $this->registry->definition($game);
        $key = $definition['key'];

        $stats = [];

        foreach ($definition['stats'] ?? [] as $schema) {

            $statKey = $schema['key'];

            $stats[$statKey] = [
                'default' => (float) $request->input("stats.{$statKey}.default", 0),
                'min' => (float) $request->input("stats.{$statKey}.min", 0),
                'max' => (float) $request->input("stats.{$statKey}.max", 0),
            ];
        }

        $configuration = $this->games->saveConfiguration(
            $universe,
            $key,
            $stats
        );

        /*
         * Aplicar a los que ya estaban.
         *
         * Es opcional a proposito: cambiar el punto de partida NO deberia
         * borrar de golpe el progreso que un competidor ya se gano. Solo
         * si el usuario lo pide.
         */
        $reajustados = 0;

        if ($request->boolean('apply_to_existing')) {

            $inicial = $configuration->initialStats();

            foreach ($universe->entities()->get() as $entity) {
                $this->stats->update($entity, $key, $inicial);
                $reajustados++;
            }
        }

        $mensaje = 'Configuracion de ' . $definition['name'] . ' guardada. '
            . 'Los competidores nuevos entraran con estos valores.';

        if ($reajustados > 0) {
            $mensaje .= " Se reajustaron {$reajustados} competidores existentes.";
        }

        return back()->with('success', $mensaje);
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
