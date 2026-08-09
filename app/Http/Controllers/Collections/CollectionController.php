<?php

namespace App\Http\Controllers\Collections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collections\StoreCollectionRequest;
use App\Http\Requests\Collections\UpdateCollectionRequest;
use App\Models\Collection;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CollectionController extends Controller
{
    public function index(
        Request $request
    ): View {
        $this->authorize(
            'viewAny',
            Collection::class
        );

        $search = trim(
            (string) $request->input(
                'search'
            )
        );

        $status = $request->input(
            'status'
        );

        $visibility = $request->input(
            'visibility'
        );

        $image = $request->input(
            'image'
        );

        $sort = (string) $request->input(
            'sort',
            'newest'
        );

        $perPage = (int) $request->input(
            'per_page',
            24
        );

        if (
            ! in_array(
                $perPage,
                [12, 24, 48, 96],
                true
            )
        ) {
            $perPage = 24;
        }

        $base = Collection::query()
            ->ownedBy(
                $request->user()
            );

        $stats = [
            'total' => (clone $base)->count(),

            'public' => (clone $base)
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->count(),

            'active' => (clone $base)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'with_entities' => (clone $base)
                ->whereHas(
                    'entities'
                )
                ->count(),
        ];

        $query = Collection::query()
            ->ownedBy(
                $request->user()
            )
            ->withCount('entities')

            ->when(
                $search,
                fn($query) =>
                $query->where(
                    fn($subquery) =>
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
                        )
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
            )

            ->when(
                $image === 'yes',
                fn($query) =>
                $query->whereNotNull(
                    'image'
                )
            )

            ->when(
                $image === 'no',
                fn($query) =>
                $query->whereNull(
                    'image'
                )
            );

        match ($sort) {
            'oldest' =>
            $query->orderBy(
                'created_at'
            ),

            'name_asc' =>
            $query->orderBy(
                'name'
            ),

            'name_desc' =>
            $query->orderByDesc(
                'name'
            ),

            'code_asc' =>
            $query->orderBy(
                'code'
            ),

            'code_desc' =>
            $query->orderByDesc(
                'code'
            ),

            'entities_desc' =>
            $query->orderByDesc(
                'entities_count'
            ),

            'entities_asc' =>
            $query->orderBy(
                'entities_count'
            ),

            'views_desc' =>
            $query->orderByDesc(
                'views_count'
            ),

            'clones_desc' =>
            $query->orderByDesc(
                'clones_count'
            ),

            default =>
            $query->orderByDesc(
                'created_at'
            ),
        };

        $collections = $query
            ->paginate($perPage)
            ->withQueryString();

        return view(
            'collections.index',
            compact(
                'collections',
                'stats',
                'search',
                'status',
                'visibility',
                'image',
                'sort',
                'perPage'
            )
        );
    }

    public function create(
        Request $request
    ): View {
        $this->authorize(
            'create',
            Collection::class
        );

        $entities = Entity::query()
            ->ownedBy(
                $request->user()
            )
            ->with('entityType')
            ->orderBy('name')
            ->get();

        $entityTypes = EntityType::query()
            ->ownedBy(
                $request->user()
            )
            ->active()
            ->orderBy('name')
            ->get();

        $previewCode =
            Collection::formatCode(
                $this->nextSequence(
                    $request->user()->id
                )
            );

        return view(
            'collections.create',
            compact(
                'entities',
                'entityTypes',
                'previewCode'
            )
        );
    }

    public function store(
        StoreCollectionRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        $entityIds =
            $data['entity_ids']
            ?? [];

        unset(
            $data['entity_ids']
        );

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request
                ->file('image')
                ->store(
                    'collections',
                    'public'
                );

            $data['image'] =
                $imagePath;
        }

        try {
            $collection = DB::transaction(
                function () use (
                    $request,
                    $data,
                    $entityIds
                ) {
                    /** @var User $user */
                    $user = User::query()
                        ->whereKey(
                            $request->user()->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    $sequence =
                        $this->nextSequence(
                            $user->id
                        );

                    $data['sequence_number'] =
                        $sequence;

                    $data['code'] =
                        Collection::formatCode(
                            $sequence
                        );

                    $data['slug'] =
                        $this->uniqueSlug(
                            $user->id,
                            $data['name']
                        );

                    $data['sort_order'] =
                        (int) Collection::withTrashed()
                            ->where(
                                'user_id',
                                $user->id
                            )
                            ->max(
                                'sort_order'
                            )
                        + 10;

                    $data['published_at'] =
                        $this->shouldPublish(
                            $data
                        )
                        ? now()
                        : null;

                    $collection = $user
                        ->collections()
                        ->create($data);

                    $this->syncEntities(
                        $collection,
                        $entityIds
                    );

                    return $collection;
                }
            );
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')
                    ->delete(
                        $imagePath
                    );
            }

            throw $exception;
        }

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

        $collection->loadCount(
            'entities'
        );

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

        $collection->load(
            'entities'
        );

        $entities = Entity::query()
            ->ownedBy(
                $request->user()
            )
            ->with('entityType')
            ->orderBy('name')
            ->get();

        $entityTypes = EntityType::query()
            ->ownedBy(
                $request->user()
            )
            ->active()
            ->orderBy('name')
            ->get();

        $previewCode =
            $collection->code;

        return view(
            'collections.edit',
            compact(
                'collection',
                'entities',
                'entityTypes',
                'previewCode'
            )
        );
    }

    public function update(
        UpdateCollectionRequest $request,
        Collection $collection
    ): RedirectResponse {
        $data =
            $request->validated();

        $entityIds =
            $data['entity_ids']
            ?? [];

        unset(
            $data['entity_ids']
        );

        $oldImage =
            $collection->image;

        $newImage =
            null;

        if (
            $request->hasFile('image')
        ) {
            $newImage = $request
                ->file('image')
                ->store(
                    'collections',
                    'public'
                );

            $data['image'] =
                $newImage;
        } elseif (
            $request->boolean(
                'remove_image'
            )
        ) {
            $data['image'] =
                null;
        }

        unset(
            $data['remove_image']
        );

        if ($this->shouldPublish($data)) {
            $data['published_at'] =
                $collection->published_at
                ?? now();
        } else {
            $data['published_at'] =
                null;
        }

        try {
            DB::transaction(
                function () use (
                    $collection,
                    $data,
                    $entityIds
                ) {
                    $collection->update(
                        $data
                    );

                    $this->syncEntities(
                        $collection,
                        $entityIds
                    );
                }
            );
        } catch (Throwable $exception) {
            if ($newImage) {
                Storage::disk('public')
                    ->delete(
                        $newImage
                    );
            }

            throw $exception;
        }

        if (
            $oldImage
            &&
            (
                $newImage
                ||
                $request->boolean(
                    'remove_image'
                )
            )
        ) {
            Storage::disk('public')
                ->delete(
                    $oldImage
                );
        }

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

        /*
         * Conservamos portada durante SoftDelete.
         */

        $collection->delete();

        return redirect()
            ->route(
                'collections.index'
            )
            ->with(
                'success',
                'Colección eliminada correctamente.'
            );
    }

    private function nextSequence(
        int $userId
    ): int {
        return (
            (int) Collection::withTrashed()
                ->where(
                    'user_id',
                    $userId
                )
                ->max(
                    'sequence_number'
                )
        ) + 1;
    }

    private function uniqueSlug(
        int $userId,
        string $name
    ): string {
        $base =
            Str::slug($name)
            ?: 'coleccion';

        $slug = $base;
        $counter = 2;

        while (
            Collection::withTrashed()
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'slug',
                $slug
            )
            ->exists()
        ) {
            $slug =
                $base . '-' . $counter;

            $counter++;
        }

        return $slug;
    }

    private function shouldPublish(
        array $data
    ): bool {
        return (
            $data['visibility']
            ?? null
        ) === 'PUBLIC'
            &&
            (
                $data['status']
                ?? null
            ) === 'ACTIVE';
    }

    private function syncEntities(
        Collection $collection,
        array $entityIds
    ): void {
        $sync = [];

        foreach (
            array_values(
                array_unique(
                    array_map(
                        'intval',
                        $entityIds
                    )
                )
            )
            as $index => $entityId
        ) {
            $sync[$entityId] = [
                'sort_order' => ($index + 1) * 10,

                'added_at' =>
                now(),
            ];
        }

        $collection
            ->entities()
            ->sync($sync);
    }
}
