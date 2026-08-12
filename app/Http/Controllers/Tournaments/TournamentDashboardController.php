<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Models\PhaseExit;
use App\Models\PhaseTemplate;
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

        $this->authorize(
            'viewAny',
            PhaseTemplate::class
        );

        $user =
            $request->user();

        $tournaments =
            TournamentTemplate::query()
            ->ownedBy($user);

        $phases =
            PhaseTemplate::query()
            ->ownedBy($user);

        $statistics = [
            'tournaments' => (clone $tournaments)
                ->count(),

            'active_tournaments' => (clone $tournaments)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'phases' => (clone $phases)
                ->count(),

            'active_phases' => (clone $phases)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'public_phases' => (clone $phases)
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->count(),

            'phase_exits' =>
            PhaseExit::query()
                ->whereHas(
                    'phaseTemplate',
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
            ->ownedBy($user)
            ->latest()
            ->limit(3)
            ->get();

        $recentPhases =
            PhaseTemplate::query()
            ->ownedBy($user)
            ->withCount('exits')
            ->latest()
            ->limit(3)
            ->get();

        return view(
            'tournaments.dashboard',
            compact(
                'statistics',
                'recentTemplates',
                'recentPhases'
            )
        );
    }
}
