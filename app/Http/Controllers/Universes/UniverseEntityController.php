<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Universes\StoreUniverseEntitiesRequest;
use App\Http\Requests\Universes\UpdateUniverseEntityRequest;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\TournamentInstanceParticipant;
use App\Models\Universe;
use App\Models\UniverseEntity;
use App\Services\Tournaments\History\EntityCompetitionStatsService;
use App\Services\Universes\UniverseEntityImporter;
use App\Services\Universes\UniverseRankingService;
use App\Services\Universes\UniverseEntityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| UniverseEntityController
|--------------------------------------------------------------------------
|
| Entidades propias del Universo.
|
| Se importan desde la Biblioteca una sola vez y a partir de ahí son
| independientes: su historial y sus estadísticas viven aquí, no en la
| Biblioteca.
|
| Ver docs/md/27-Entidades-Propias-Del-Universo.md
|
*/

class UniverseEntityController extends Controller
{
    public function __construct(
        private readonly \App\Services\Universes\UniverseEntityBrowser $browser,
        private readonly \App\Services\Universes\UniverseEntitySync $sync,
        private readonly \App\Services\Universes\UniverseEntityVersionResolver $versions,
        private readonly
        UniverseEntityService $service,

        private readonly
        UniverseEntityImporter $importer,

        private readonly
        EntityCompetitionStatsService $stats,

        private readonly
        UniverseRankingService $ranking,

        /* Fase 11 */
        private readonly
        \App\Services\Games\GameStatsService $gameStats,

        /* Fase 12 */
        private readonly
        \App\Services\Rewards\PalmaresService $palmares
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request,
        Universe $universe
    ): View {

        $this->authorize('view', $universe);

        /*
         * Todo el trabajo vive en UniverseEntityBrowser: buscar, filtrar
         * por atributo, ordenar y armar cada ficha con su record, sus
         * trofeos y sus versiones.
         *
         * Filtrar por atributo no cabe en SQL -viven en un JSON copiado- y
         * mezclarlo aqui con la consulta habria dejado media logica en el
         * controlador y media en el servicio.
         */
        return view(
            'universes.entities.index',
            [
                'universe' => $universe,
                ...$this->browser->browse($universe, $request),
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Traer de la Biblioteca lo que cambio
    |--------------------------------------------------------------------------
    |
    | Dos pasos a proposito: primero se ve que cambiaria, despues se aplica.
    | Una actualizacion que puede retirar atributos no deberia ocurrir de un
    | clic a ciegas.
    |
    */

    public function syncPreview(
        Universe $universe,
        UniverseEntity $entity
    ) {
        $this->authorize('update', $universe);

        abort_unless((int) $entity->universe_id === (int) $universe->id, 404);

        return response()->json(
            $this->sync->diff($entity)
        );
    }


    public function syncApply(
        Request $request,
        Universe $universe,
        UniverseEntity $entity
    ): RedirectResponse {

        $this->authorize('update', $universe);

        abort_unless((int) $entity->universe_id === (int) $universe->id, 404);

        $resultado = $this->sync->apply(
            $entity,
            $request->boolean('with_identity')
        );

        return back()->with(
            $resultado['applied'] ? 'success' : 'error',
            $resultado['summary']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Importar desde Biblioteca
    |--------------------------------------------------------------------------
    */

    public function create(
        Request $request,
        Universe $universe
    ): View {

        $this->authorize(
            'update',
            $universe
        );

        /*
         * Solo se ofrece lo que todavía no está importado: evitar
         * duplicados antes de que ocurran es mejor que rechazarlos
         * después.
         */
        $already =
            $universe
            ->entities()
            ->pluck('source_entity_id')
            ->filter();

        $entities =
            Entity::query()
            ->ownedBy($request->user())
            ->whereNotIn('id', $already)
            ->with('entityType')
            ->orderBy('name')
            ->get();

        $entityTypes =
            EntityType::query()
            ->ownedBy($request->user())
            ->active()
            ->orderBy('name')
            ->get();

        return view(
            'universes.entities.create',
            compact(
                'universe',
                'entities',
                'entityTypes'
            )
        );
    }

    public function store(
        StoreUniverseEntitiesRequest $request,
        Universe $universe
    ): RedirectResponse {

        $imported =
            $this->importer
            ->import(
                $universe,
                $request->validated('entity_ids')
            );

        return redirect()
            ->route(
                'universes.entities.index',
                $universe
            )
            ->with(
                'success',

                $imported === 1
                    ? 'Se importó 1 entidad al Universo. Es una copia independiente: '
                        . 'editarla en la Biblioteca ya no la afecta.'
                    : "Se importaron {$imported} entidades al Universo. Son copias "
                        . 'independientes: editarlas en la Biblioteca ya no las afecta.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Ficha de la entidad del Universo
    |--------------------------------------------------------------------------
    |
    | Aquí vive todo: sus datos copiados y su vida competitiva dentro de
    | este Universo. La Biblioteca no muestra nada de esto.
    |
    */

    public function show(
        Universe $universe,
        UniverseEntity $entity
    ): View {

        $this->authorize(
            'view',
            $universe
        );

        $summary = $this->stats->summary($entity);
        $byEngine = $this->stats->byEngine($entity);
        $history = $this->stats->history($entity);
        $rivals = $this->stats->rivals($entity);
        $streaks = $this->stats->streaks($entity);

        /*
         * Posicion en la clasificacion del Universo.
         */
        $rank = $this->ranking->positionOf($universe, $entity);

        /*
         * Juegos (Fase 11): sus capacidades configuradas y su record
         * derivado, uno por juego.
         */
        $gameProfile = $this->gameStats->profile($entity);

        /*
         * Palmares y progresion (Fase 12). Todo derivado: los titulos
         * salen de las posiciones resueltas y los trofeos de lo concedido.
         */
        $palmares = $this->palmares->summary($entity);
        $podiums = $this->palmares->podiums($entity);
        $trophyAwards = $this->palmares->trophies($entity);
        $statHistory = $this->palmares->statHistory($entity);

        /*
         * Historial agrupado por temporada: convierte una lista plana en
         * la cronica del participante dentro del mundo.
         */
        $historyBySeason = $history->groupBy(
            fn($participation) =>
            $participation->tournamentInstance?->season?->number ?? 0
        )->sortKeysDesc();

        return view(
            'universes.entities.show',
            compact(
                'universe',
                'entity',
                'summary',
                'byEngine',
                'history',
                'historyBySeason',
                'rivals',
                'streaks',
                'rank',
                'gameProfile',
                'palmares',
                'podiums',
                'trophyAwards',
                'statHistory'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Head-to-head dentro del Universo
    |--------------------------------------------------------------------------
    */

    public function headToHead(
        Request $request,
        Universe $universe,
        UniverseEntity $entity
    ): View {

        $this->authorize('view', $universe);

        /*
         * El rival es OPCIONAL.
         *
         * Antes se buscaba con findOrFail(query('rival')), asi que entrar
         * sin elegir a nadie buscaba el rival numero 0 y devolvia 404. Y
         * entrar sin elegir es lo normal: se viene aqui justamente a elegir.
         */
        $rival = null;

        if ($rivalId = (int) $request->query('rival')) {

            $rival = $universe->entities()->find($rivalId);
        }

        $comparison = $rival
            ? $this->stats->headToHead($entity, $rival)
            : null;

        /*
         * Con quien se puede comparar: todos los demas, con su cara, sus
         * atributos y cuantas veces se han cruzado ya. Se arma con el
         * mismo servicio que el panel para que las fichas se lean igual en
         * los dos sitios.
         */
        $candidatos = collect(
            $this->browser->browse($universe, new Request())['entities']
        )
            ->reject(fn ($e) => (int) $e['id'] === (int) $entity->id)
            ->values()
            ->all();

        /* Cuantas veces se ha cruzado con cada uno, para poder ordenarlos */
        $cruces = collect($this->stats->rivals($entity))
            ->keyBy('entity_id');

        $candidatos = collect($candidatos)
            ->map(function (array $c) use ($cruces) {

                $cruce = $cruces->get($c['id']);

                $c['h2h'] = $cruce ? [
                    'matches' => (int) ($cruce['matches'] ?? 0),
                    'wins' => (int) ($cruce['wins'] ?? 0),
                    'losses' => (int) ($cruce['losses'] ?? 0),
                    'draws' => (int) ($cruce['draws'] ?? 0),
                ] : null;

                return $c;
            })
            ->values()
            ->all();

        return view(
            'universes.entities.head-to-head',
            [
                'universe' => $universe,
                'entity' => $entity,
                'rival' => $rival,
                'comparison' => $comparison,
                'candidates' => $candidatos,

                /* Las cifras de los dos, para poder enfrentarlas */
                'leftStats' => $this->stats->summary($entity),
                'rightStats' => $rival ? $this->stats->summary($rival) : null,
                'leftGames' => $this->gameStats->profile($entity),
                'rightGames' => $rival ? $this->gameStats->profile($rival) : null,
            ]
        );
    }


    public function update(
        UpdateUniverseEntityRequest $request,
        Universe $universe,
        UniverseEntity $entity
    ): RedirectResponse {

        $this->service
            ->update($entity, $request->validated());

        return back()->with(
            'success',
            'Entidad actualizada correctamente.'
        );
    }

    public function destroy(
        Universe $universe,
        UniverseEntity $entity
    ): RedirectResponse {

        $this->authorize(
            'update',
            $universe
        );

        $this->service
            ->remove($entity);

        return redirect()
            ->route(
                'universes.entities.index',
                $universe
            )
            ->with(
                'success',
                'Entidad retirada del Universo. La de tu Biblioteca no se ha tocado.'
            );
    }
}
