<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Models\Universe;
use App\Models\UniverseCompetitor;
use App\Models\UniverseTournament;
use Illuminate\Http\Request;
use Illuminate\View\View;


class UniverseDashboardController extends Controller
{
    public function __invoke(
        Request $request
    ): View {

        $this->authorize(
            'viewAny',
            Universe::class
        );

        $user =
            $request->user();

        $universes =
            Universe::query()
            ->ownedBy(
                $user
            );

        $statistics = [

            'total' => (clone $universes)
                ->count(),

            'active' => (clone $universes)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'draft' => (clone $universes)
                ->where(
                    'status',
                    'DRAFT'
                )
                ->count(),

            'archived' => (clone $universes)
                ->where(
                    'status',
                    'ARCHIVED'
                )
                ->count(),

            'competitors' =>
            UniverseCompetitor::query()
                ->whereIn(
                    'universe_id',
                    (clone $universes)->select('id')
                )
                ->count(),

            'tournaments' =>
            UniverseTournament::query()
                ->whereIn(
                    'universe_id',
                    (clone $universes)->select('id')
                )
                ->count(),
        ];

        $recentUniverses =
            Universe::query()
            ->ownedBy(
                $user
            )
            ->withCount([
                'competitors',
                'seasons',
                'universeTournaments',
            ])
            ->latest()
            ->limit(4)
            ->get();

        return view(
            'universes.dashboard',
            compact(
                'statistics',
                'recentUniverses'
            )
        );
    }
}
