<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Models\TournamentPhase;
use App\Models\TournamentTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;


class TournamentDashboardController extends Controller
{
    public function __invoke(
        Request $request
    ): View {

        $this->authorize(
            'viewAny',
            TournamentTemplate::class
        );


        $user =
            $request->user();


        $base =
            TournamentTemplate::query()
            ->ownedBy(
                $user
            );


        $statistics = [

            'total' => (clone $base)
                ->count(),

            'active' => (clone $base)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'draft' => (clone $base)
                ->where(
                    'status',
                    'DRAFT'
                )
                ->count(),

            'public' => (clone $base)
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->count(),

            'phases' =>
            TournamentPhase::query()
                ->whereHas(
                    'tournamentTemplate',
                    fn($query) =>
                    $query->where(
                        'user_id',
                        $user->id
                    )
                )
                ->count(),
        ];


        $recentTemplates =
            TournamentTemplate::query()
            ->ownedBy(
                $user
            )
            ->withCount(
                'phases'
            )
            ->latest()
            ->limit(6)
            ->get();


        return view(
            'tournaments.dashboard',
            compact(
                'statistics',
                'recentTemplates'
            )
        );
    }
}
