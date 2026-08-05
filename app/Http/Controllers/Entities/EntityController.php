<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\StoreEntityRequest;
use App\Http\Requests\Entities\UpdateEntityRequest;
use App\Models\Entity;
use App\Models\EntityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EntityController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Entity::class);

        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $type = $request->input('type');

        $entities = Entity::query()
            ->ownedBy($request->user())
            ->with('entityType')
            ->when(
                $search,
                fn($query) => $query->where(
                    fn($subquery) => $subquery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere(
                            'description',
                            'like',
                            "%{$search}%"
                        )
                )
            )
            ->when(
                $status,
                fn($query) => $query->where(
                    'status',
                    $status
                )
            )
            ->when(
                $type,
                fn($query) => $query->where(
                    'entity_type_id',
                    $type
                )
            )
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $entityTypes = EntityType::query()
            ->ownedBy($request->user())
            ->active()
            ->orderBy('name')
            ->get();

        return view(
            'entities.index',
            compact(
                'entities',
                'entityTypes',
                'search',
                'status',
                'type'
            )
        );
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Entity::class);

        $entityTypes = EntityType::query()
            ->ownedBy($request->user())
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'entities.create',
            compact('entityTypes')
        );
    }

    public function store(
        StoreEntityRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request
                ->file('image')
                ->store('entities', 'public');
        }

        unset($data['remove_image']);

        $entity = $request->user()
            ->entities()
            ->create($data);

        return redirect()
            ->route('entities.show', $entity)
            ->with(
                'success',
                'Entidad creada correctamente.'
            );
    }

    public function show(Entity $entity): View
    {
        $this->authorize('view', $entity);

        $entity->load([
            'entityType',
            'entityAttributes.attribute.groups',
            'entityAttributes.values.option',
        ]);

        return view(
            'entities.show',
            compact('entity')
        );
    }

    public function edit(
        Request $request,
        Entity $entity
    ): View {
        $this->authorize('update', $entity);

        $entityTypes = EntityType::query()
            ->ownedBy($request->user())
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'entities.edit',
            compact('entity', 'entityTypes')
        );
    }

    public function update(
        UpdateEntityRequest $request,
        Entity $entity
    ): RedirectResponse {
        $data = $request->validated();

        if ($request->boolean('remove_image')) {
            if ($entity->image) {
                Storage::disk('public')
                    ->delete($entity->image);
            }

            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            if ($entity->image) {
                Storage::disk('public')
                    ->delete($entity->image);
            }

            $data['image'] = $request
                ->file('image')
                ->store('entities', 'public');
        }

        unset($data['remove_image']);

        $entity->update($data);

        return redirect()
            ->route('entities.show', $entity)
            ->with(
                'success',
                'Entidad actualizada correctamente.'
            );
    }

    public function destroy(Entity $entity): RedirectResponse
    {
        $this->authorize('delete', $entity);

        $entity->delete();

        return redirect()
            ->route('entities.index')
            ->with(
                'success',
                'Entidad eliminada correctamente.'
            );
    }
}
