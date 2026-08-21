<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Universes\StoreUniverseCompetitorsRequest;
use App\Http\Requests\Universes\UpdateUniverseCompetitorRequest;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Universe;
use App\Models\UniverseCompetitor;
use App\Services\Universes\UniverseCompetitorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UniverseCompetitorController extends Controller
{
    public function __construct(
        private readonly
        UniverseCompetitorService $service
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
            trim(
                (string)
                $request->input('search')
            );

        $status =
            (string)
            $request->input(
                'status',
                ''
            );

        $base =
            $universe->competitors();

        $statistics = [

            'total' => (clone $base)
                ->count(),

            'active' => (clone $base)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'retired' => (clone $base)
                ->where(
                    'status',
                    'RETIRED'
                )
                ->count(),
        ];

        $competitors =
            $universe
            ->competitors()
            ->with([
                'entity.entityType',
            ])
            ->when(
                $search,

                fn($query) =>
                $query->where(
                    function ($subquery) use ($search) {

                        $subquery
                            ->where(
                                'display_name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhereHas(
                                'entity',

                                fn($entityQuery) =>
                                $entityQuery
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'code',
                                        'like',
                                        "%{$search}%"
                                    )
                            );
                    }
                )
            )
            ->when(
                $status,

                fn($query) =>
                $query->where(
                    'status',
                    $status
                )
            )
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view(
            'universes.competitors.index',
            compact(
                'universe',
                'competitors',
                'statistics',
                'search',
                'status'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    |
    | Selector de Entidades de la Biblioteca. Solo se ofrecen las que
    | todavía no forman parte de este Universo.
    |
    */

    public function create(
        Request $request,
        Universe $universe
    ): View {

        $this->authorize(
            'update',
            $universe
        );

        $alreadyIn =
            $universe
            ->competitors()
            ->pluck('entity_id');

        $entities =
            Entity::query()
            ->ownedBy(
                $request->user()
            )
            ->whereNotIn(
                'id',
                $alreadyIn
            )
            ->with('entityType')
            ->orderBy('name')
            ->get();

        $entityTypes =
            EntityType::query()
            ->ownedBy(
                $request->user()
            )
            ->active()
            ->orderBy('name')
            ->get();

        return view(
            'universes.competitors.create',
            compact(
                'universe',
                'entities',
                'entityTypes'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreUniverseCompetitorsRequest $request,
        Universe $universe
    ): RedirectResponse {

        $added =
            $this->service
            ->addEntities(
                $universe,

                $request->validated(
                    'entity_ids'
                )
            );

        return redirect()
            ->route(
                'universes.competitors.index',
                $universe
            )
            ->with(
                'success',

                $added === 1
                    ? 'Se incorporó 1 competidor al Universo.'
                    : "Se incorporaron {$added} competidores al Universo."
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateUniverseCompetitorRequest $request,
        Universe $universe,
        UniverseCompetitor $competitor
    ): RedirectResponse {

        $this->service
            ->update(
                $competitor,

                $request->validated()
            );

        return redirect()
            ->route(
                'universes.competitors.index',
                $universe
            )
            ->with(
                'success',
                'Competidor actualizado correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Universe $universe,
        UniverseCompetitor $competitor
    ): RedirectResponse {

        $this->authorize(
            'update',
            $universe
        );

        $this->service
            ->remove($competitor);

        return redirect()
            ->route(
                'universes.competitors.index',
                $universe
            )
            ->with(
                'success',
                'Competidor retirado del Universo. La entidad sigue en tu Biblioteca.'
            );
    }
}
