<?php

namespace App\Http\Controllers\EntityTypes;

use App\Http\Controllers\Controller;
use App\Http\Requests\EntityTypes\StoreEntityTypeRequest;
use App\Http\Requests\EntityTypes\UpdateEntityTypeRequest;
use App\Models\EntityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntityTypeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', EntityType::class);

        $search = trim((string) $request->input('search'));
        $status = $request->input('status');

        $entityTypes = EntityType::query()
            ->ownedBy($request->user())
            ->withCount('entities')
            ->when(
                $search,
                fn ($query) => $query->where(
                    fn ($subquery) => $subquery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                )
            )
            ->when(
                $status,
                fn ($query) => $query->where(
                    'status',
                    $status
                )
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view(
            'entity-types.index',
            compact('entityTypes', 'search', 'status')
        );
    }

    public function create(): View
    {
        $this->authorize('create', EntityType::class);

        return view('entity-types.create');
    }

    public function store(
        StoreEntityTypeRequest $request
    ): RedirectResponse {
        $entityType = $request->user()
            ->entityTypes()
            ->create($request->validated());

        return redirect()
            ->route('entity-types.show', $entityType)
            ->with(
                'success',
                'Tipo de entidad creado correctamente.'
            );
    }

    public function show(EntityType $entityType): View
    {
        $this->authorize('view', $entityType);

        $entityType->loadCount('entities');

        $entities = $entityType->entities()
            ->latest()
            ->limit(8)
            ->get();

        return view(
            'entity-types.show',
            compact('entityType', 'entities')
        );
    }

    public function edit(EntityType $entityType): View
    {
        $this->authorize('update', $entityType);

        return view(
            'entity-types.edit',
            compact('entityType')
        );
    }

    public function update(
        UpdateEntityTypeRequest $request,
        EntityType $entityType
    ): RedirectResponse {
        $entityType->update($request->validated());

        return redirect()
            ->route('entity-types.show', $entityType)
            ->with(
                'success',
                'Tipo de entidad actualizado correctamente.'
            );
    }

    public function destroy(
        EntityType $entityType
    ): RedirectResponse {
        $this->authorize('delete', $entityType);

        if ($entityType->entities()->exists()) {
            return back()->with(
                'error',
                'No puedes eliminar el tipo porque tiene entidades asociadas.'
            );
        }

        $entityType->delete();

        return redirect()
            ->route('entity-types.index')
            ->with(
                'success',
                'Tipo de entidad eliminado correctamente.'
            );
    }
}