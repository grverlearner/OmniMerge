<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StorePhaseTemplateRequest;
use App\Http\Requests\Tournaments\UpdatePhaseTemplateRequest;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\PhaseTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhaseTemplateController extends Controller
{
    public function __construct(
        private readonly
        PhaseTemplateService $service
    ) {}

    public function index(
        Request $request
    ): View {
        $this->authorize(
            'viewAny',
            PhaseTemplate::class
        );

        $user =
            $request->user();

        $search =
            trim(
                (string)
                $request->input(
                    'search'
                )
            );

        $type =
            (string)
            $request->input(
                'type',
                ''
            );

        $status =
            (string)
            $request->input(
                'status',
                ''
            );

        $visibility =
            (string)
            $request->input(
                'visibility',
                ''
            );

        $sort =
            (string)
            $request->input(
                'sort',
                'newest'
            );

        $base =
            PhaseTemplate::query()
            ->ownedBy($user);

        $stats = [
            'total' => (clone $base)->count(),

            'active' => (clone $base)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'public' => (clone $base)
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->count(),

            'with_exits' => (clone $base)
                ->whereHas('exits')
                ->count(),
        ];

        $query =
            PhaseTemplate::query()
            ->ownedBy($user)
            ->withCount('exits')

            ->when(
                $search,
                fn($query) =>
                $query->where(
                    function ($subquery) use (
                        $search
                    ) {
                        $subquery
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
                            ->orWhere(
                                'description',
                                'like',
                                "%{$search}%"
                            );
                    }
                )
            )

            ->when(
                $type,
                fn($query) =>
                $query->where(
                    'phase_type',
                    $type
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

            ->when(
                $visibility,
                fn($query) =>
                $query->where(
                    'visibility',
                    $visibility
                )
            );

        match ($sort) {
            'oldest' =>
            $query->oldest(),

            'name_asc' =>
            $query->orderBy('name'),

            'name_desc' =>
            $query->orderByDesc('name'),

            'exits_desc' =>
            $query->orderByDesc(
                'exits_count'
            ),

            default =>
            $query->latest(),
        };

        $phaseTemplates =
            $query
            ->paginate(18)
            ->withQueryString();

        return view(
            'tournaments.phase-templates.index',
            compact(
                'phaseTemplates',
                'stats',
                'search',
                'type',
                'status',
                'visibility',
                'sort'
            )
        );
    }

    public function create(
        Request $request
    ): View {
        $this->authorize(
            'create',
            PhaseTemplate::class
        );

        $previewCode =
            $this->service
            ->previewCode(
                $request->user()
            );

        return view(
            'tournaments.phase-templates.create',
            compact(
                'previewCode'
            )
        );
    }

    public function store(
        StorePhaseTemplateRequest $request
    ): RedirectResponse {
        $phaseTemplate =
            $this->service
            ->create(
                $request->user(),
                $request->validated(),
                $request->file('image')
            );

        return redirect()
            ->route(
                'tournaments.phase-templates.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Fase creada correctamente.'
            );
    }

    public function show(
        PhaseTemplate $phaseTemplate
    ): View {
        $this->authorize(
            'view',
            $phaseTemplate
        );

        $phaseTemplate
            ->load('exits')
            ->loadCount('exits');

        return view(
            'tournaments.phase-templates.show',
            compact(
                'phaseTemplate'
            )
        );
    }

    public function edit(
        PhaseTemplate $phaseTemplate
    ): View {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        return view(
            'tournaments.phase-templates.edit',
            compact(
                'phaseTemplate'
            )
        );
    }

    public function update(
        UpdatePhaseTemplateRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->service
            ->update(
                $phaseTemplate,
                $request->validated(),
                $request->file('image')
            );

        return redirect()
            ->route(
                'tournaments.phase-templates.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Fase actualizada correctamente.'
            );
    }

    public function duplicate(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->authorize(
            'view',
            $phaseTemplate
        );

        $copy =
            $this->service
            ->duplicate(
                $request->user(),
                $phaseTemplate
            );

        return redirect()
            ->route(
                'tournaments.phase-templates.edit',
                $copy
            )
            ->with(
                'success',
                'Fase duplicada. La copia comienza como borrador privado.'
            );
    }

    public function archive(
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->service
            ->archive(
                $phaseTemplate
            );

        return redirect()
            ->route(
                'tournaments.phase-templates.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Fase archivada correctamente.'
            );
    }

    public function destroy(
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $phaseTemplate
        );

        $this->service
            ->delete(
                $phaseTemplate
            );

        return redirect()
            ->route(
                'tournaments.phase-templates.index'
            )
            ->with(
                'success',
                'Fase eliminada correctamente.'
            );
    }
}
