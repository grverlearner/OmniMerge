<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\EntityType;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $statistics = [
            'entity_types' => EntityType::query()
                ->ownedBy($user)
                ->count(),

            'entities' => Entity::query()
                ->ownedBy($user)
                ->count(),

            'active_entities' => Entity::query()
                ->ownedBy($user)
                ->active()
                ->count(),

            'public_entities' => Entity::query()
                ->ownedBy($user)
                ->where('visibility', 'PUBLIC')
                ->count(),
        ];

        $recentEntities = Entity::query()
            ->ownedBy($user)
            ->with('entityType')
            ->latest()
            ->limit(5)
            ->get();

        $recentTypes = EntityType::query()
            ->ownedBy($user)
            ->withCount('entities')
            ->latest()
            ->limit(5)
            ->get();

        return view(
            'dashboard.index',
            compact(
                'statistics',
                'recentEntities',
                'recentTypes'
            )
        );
    }
}