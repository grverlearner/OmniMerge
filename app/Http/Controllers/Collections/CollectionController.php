<?php

namespace App\Http\Controllers\Collections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collections\StoreCollectionRequest;
use App\Http\Requests\Collections\UpdateCollectionRequest;
use App\Models\Collection;
use App\Models\Entity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize(
            'viewAny',
            Collection::class
        );

        $search = trim(
            (string) $request->input('search')
        );

        $collections = Collection::query()
            ->ownedBy($request->user())
            ->withCount('entities')
            ->when(
                $search,
                fn($query) => $query->where(
                    fn($subquery) => $subquery
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
                )
            )
            ->orderBy('sort_order')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view(
            'collections.index',
            compact(
                'collections',
                'search'
            )
        );
    }

    public function create(Request $request): View
    {
        $this->authorize(
            'create',
            Collection::class
        );

        $entities = Entity::query()
            ->ownedBy($request->user())
            ->with('entityType')
            ->orderBy('name')
            ->get();

        return view(
            'collections.create',
            compact('entities')
        );
    }

    public function store(
        StoreCollectionRequest $request
    ): RedirectResponse {
        if (($data['visibility'] ?? null) === 'PUBLIC') {
            $data['published_at'] = now();
        }

        $data = $request->validated();
        $entityIds = $data['entity_ids'] ?? [];

        unset($data['entity_ids']);

        if ($request->hasFile('image')) {
            $data['image'] = $request
                ->file('image')
                ->store(
                    'collections',
                    'public'
                );
        }

        $collection = $request
            ->user()
            ->collections()
            ->create($data);

        $syncData = [];

        foreach (
            array_values($entityIds)
            as $index => $entityId
        ) {
            $syncData[$entityId] = [
                'sort_order' => $index,
                'added_at' => now(),
            ];
        }

        $collection
            ->entities()
            ->sync($syncData);

        return redirect()
            ->route(
                'collections.show',
                $collection
            )
            ->with(
                'success',
                'Colección creada correctamente.'
            );
    }

    public function show(
        Collection $collection
    ): View {
        $this->authorize(
            'view',
            $collection
        );

        $collection->load([
            'entities.entityType',
        ]);

        return view(
            'collections.show',
            compact('collection')
        );
    }

    public function edit(
        Request $request,
        Collection $collection
    ): View {
        $this->authorize(
            'update',
            $collection
        );

        $entities = Entity::query()
            ->ownedBy($request->user())
            ->with('entityType')
            ->orderBy('name')
            ->get();

        $collection->load('entities');

        return view(
            'collections.edit',
            compact(
                'collection',
                'entities'
            )
        );
    }

    public function update(
        UpdateCollectionRequest $request,
        Collection $collection
    ): RedirectResponse {
        $data = $request->validated();
        $entityIds = $data['entity_ids'] ?? [];

        unset($data['entity_ids']);

        if ($request->boolean('remove_image')) {
            if ($collection->image) {
                Storage::disk('public')
                    ->delete($collection->image);
            }

            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            if ($collection->image) {
                Storage::disk('public')
                    ->delete($collection->image);
            }

            $data['image'] = $request
                ->file('image')
                ->store(
                    'collections',
                    'public'
                );
        }

        unset($data['remove_image']);

        if (
            ($data['visibility'] ?? null) === 'PUBLIC'
            && ! $collection->published_at
        ) {
            $data['published_at'] = now();
        }

        if (($data['visibility'] ?? null) !== 'PUBLIC') {
            $data['published_at'] = null;
        }
        $collection->update($data);

        $syncData = [];

        foreach (
            array_values($entityIds)
            as $index => $entityId
        ) {
            $syncData[$entityId] = [
                'sort_order' => $index,
                'added_at' => now(),
            ];
        }

        $collection
            ->entities()
            ->sync($syncData);

        return redirect()
            ->route(
                'collections.show',
                $collection
            )
            ->with(
                'success',
                'Colección actualizada correctamente.'
            );
    }

    public function destroy(
        Collection $collection
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $collection
        );

        if ($collection->image) {
            Storage::disk('public')
                ->delete($collection->image);
        }

        $collection
            ->entities()
            ->detach();

        $collection->delete();

        return redirect()
            ->route('collections.index')
            ->with(
                'success',
                'Colección eliminada correctamente.'
            );
    }
}
