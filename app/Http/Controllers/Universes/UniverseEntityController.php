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
        private readonly
        UniverseEntityService $service,

        private readonly
        UniverseEntityImporter $importer,

        private readonly
        EntityCompetitionStatsService $stats,

        private readonly
        UniverseRankingService $ranking
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

        $this->authorize(
            'view',
            $universe
        );

        $search =
            trim((string) $request->input('search'));

        $status =
            (string) $request->input('status', '');

        $base =
            $universe->entities();

        $statistics = [

            'total' => (clone $base)->count(),

            'active' => (clone $base)
                ->where('status', 'ACTIVE')
                ->count(),

            'retired' => (clone $base)
                ->where('status', 'RETIRED')
                ->count(),
        ];

        $universeEntities =
            $universe
            ->entities()
            ->when(
                $search,

                fn($query) =>
                $query->where(
                    function ($subquery) use ($search) {

                        $subquery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('display_name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('entity_type_name', 'like', "%{$search}%");
                    }
                )
            )
            ->when(
                $status,

                fn($query) =>
                $query->where('status', $status)
            )
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        /*
         * Cifras competitivas de la página actual, en una sola consulta
         * en lugar de una por entidad.
         */
        $records =
            TournamentInstanceParticipant::query()
            ->whereIn(
                'universe_entity_id',
                $universeEntities->pluck('id')
            )
            ->selectRaw(
                'universe_entity_id,
                 count(*) as tournaments,
                 sum(wins) as wins,
                 sum(losses) as losses,
                 sum(case when outcome = \'CHAMPION\' then 1 else 0 end) as titles'
            )
            ->groupBy('universe_entity_id')
            ->get()
            ->keyBy('universe_entity_id');

        return view(
            'universes.entities.index',
            compact(
                'universe',
                'universeEntities',
                'statistics',
                'records',
                'search',
                'status'
            )
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
                'rank'
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

        $this->authorize(
            'view',
            $universe
        );

        $rival =
            $universe
            ->entities()
            ->findOrFail(
                (int) $request->query('rival')
            );

        $comparison =
            $this->stats
            ->headToHead($entity, $rival);

        return view(
            'universes.entities.head-to-head',
            compact(
                'universe',
                'entity',
                'rival',
                'comparison'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar / quitar
    |--------------------------------------------------------------------------
    */

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
