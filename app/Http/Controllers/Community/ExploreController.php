<?php

namespace App\Http\Controllers\Community;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Collection;
use App\Models\Entity;
use App\Models\EntityType;
use App\Services\Community\CommunityCloneService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExploreController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->input(
            'tab',
            'entities'
        );

        $search = trim(
            (string) $request->input('search')
        );

        $sort = $request->input(
            'sort',
            'popular'
        );

        $entityTypeId =
            $request->integer('entity_type')
            ?: null;

        $dataType =
            $request->input('data_type');

        $entities = null;
        $collections = null;
        $attributes = null;

        if ($tab === 'entities') {
            $entities = $this->entityQuery(
                $search,
                $sort,
                $entityTypeId
            )
                ->paginate(18)
                ->withQueryString();
        }

        if ($tab === 'collections') {
            $collections =
                $this->collectionQuery(
                    $search,
                    $sort
                )
                ->paginate(18)
                ->withQueryString();
        }

        if ($tab === 'attributes') {
            $attributes =
                $this->attributeQuery(
                    $search,
                    $sort,
                    $dataType
                )
                ->paginate(18)
                ->withQueryString();
        }

        $entityTypes = EntityType::query()
            ->whereHas(
                'entities',
                fn(Builder $query) =>
                $query
                    ->where(
                        'visibility',
                        'PUBLIC'
                    )
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->whereNotNull(
                        'published_at'
                    )
            )
            ->orderBy('name')
            ->get();

        $statistics = [
            'entities' =>
            Entity::query()
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->whereNotNull(
                    'published_at'
                )
                ->count(),

            'collections' =>
            Collection::query()
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->whereNotNull(
                    'published_at'
                )
                ->count(),

            'attributes' =>
            Attribute::query()
                ->where(
                    'scope',
                    'PUBLIC'
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->whereNotNull(
                    'published_at'
                )
                ->count(),

            'creators' =>
            DB::table('users')
                ->whereExists(
                    fn($query) =>
                    $query
                        ->selectRaw('1')
                        ->from('entities')
                        ->whereColumn(
                            'entities.user_id',
                            'users.id'
                        )
                        ->where(
                            'entities.visibility',
                            'PUBLIC'
                        )
                        ->where(
                            'entities.status',
                            'ACTIVE'
                        )
                )
                ->count(),
        ];

        return view(
            'community.index',
            compact(
                'tab',
                'search',
                'sort',
                'entityTypeId',
                'dataType',
                'entities',
                'collections',
                'attributes',
                'entityTypes',
                'statistics'
            )
        );
    }

    public function entity(
        Request $request,
        Entity $entity
    ): View {
        $this->ensurePublicEntity($entity);

        $entity->load([
            'creator',
            'entityType',
            'entityAttributes.attribute',
            'entityAttributes.values.option',
            'collections' => fn($query) =>
            $query->where(
                'collections.visibility',
                'PUBLIC'
            ),
        ]);

        $this->recordView(
            $request,
            'ENTITY',
            $entity->id
        );

        $entity->increment('views_count');

        $relatedEntities = Entity::query()
            ->where('id', '<>', $entity->id)
            ->where('visibility', 'PUBLIC')
            ->where('status', 'ACTIVE')
            ->whereNotNull('published_at')
            ->when(
                $entity->entity_type_id,
                fn($query) =>
                $query->where(
                    'entity_type_id',
                    $entity->entity_type_id
                )
            )
            ->with([
                'creator',
                'entityType',
            ])
            ->orderByDesc('clones_count')
            ->limit(6)
            ->get();

        return view(
            'community.entity',
            compact(
                'entity',
                'relatedEntities'
            )
        );
    }

    public function collection(
        Request $request,
        Collection $collection
    ): View {
        $this->ensurePublicCollection(
            $collection
        );

        $collection->load([
            'creator',
            'entities' => fn($query) =>
            $query
                ->where(
                    'entities.visibility',
                    'PUBLIC'
                )
                ->where(
                    'entities.status',
                    'ACTIVE'
                )
                ->with('entityType'),
        ]);

        $this->recordView(
            $request,
            'COLLECTION',
            $collection->id
        );

        $collection->increment(
            'views_count'
        );

        return view(
            'community.collection',
            compact('collection')
        );
    }

    public function attribute(
        Request $request,
        Attribute $attribute
    ): View {
        $this->ensurePublicAttribute(
            $attribute
        );

        $attribute->load([
            'creator',
            'options' => fn($query) =>
            $query->where(
                'status',
                'ACTIVE'
            ),
            'groups',
        ])->loadCount(
            'entityAttributes'
        );

        $this->recordView(
            $request,
            'ATTRIBUTE',
            $attribute->id
        );

        $attribute->increment(
            'views_count'
        );

        return view(
            'community.attribute',
            compact('attribute')
        );
    }

    public function cloneEntity(
        Request $request,
        Entity $entity,
        CommunityCloneService $service
    ): RedirectResponse {
        $this->ensurePublicEntity($entity);

        /** @var User $user */
        $user = $request->user();

        abort_if(
            $entity->user_id === $user->id,
            422,
            'No necesitas clonar tu propia entidad.'
        );

        $clone = $service->cloneEntity(
            $entity,
            $user
        );

        return redirect()
            ->route('entities.show', $clone)
            ->with(
                'success',
                'Entidad copiada a tu biblioteca.'
            );
    }

    public function cloneCollection(
        Request $request,
        Collection $collection,
        CommunityCloneService $service
    ): RedirectResponse {
        $this->ensurePublicCollection($collection);

        /** @var User $user */
        $user = $request->user();

        abort_if(
            $collection->user_id === $user->id,
            422,
            'No necesitas clonar tu propia colección.'
        );

        $clone = $service->cloneCollection(
            $collection,
            $user
        );

        return redirect()
            ->route('collections.show', $clone)
            ->with(
                'success',
                'Colección copiada a tu biblioteca.'
            );
    }

    public function cloneAttribute(
        Request $request,
        Attribute $attribute,
        CommunityCloneService $service
    ): RedirectResponse {
        $this->ensurePublicAttribute($attribute);

        /** @var User $user */
        $user = $request->user();

        abort_if(
            $attribute->user_id === $user->id,
            422,
            'No necesitas clonar tu propio atributo.'
        );

        $clone = $service->cloneAttribute(
            $attribute,
            $user
        );

        return redirect()
            ->route('attributes.show', $clone)
            ->with(
                'success',
                'Atributo copiado a tu biblioteca.'
            );
    }

    private function entityQuery(
        string $search,
        string $sort,
        ?int $entityTypeId
    ): Builder {
        return Entity::query()
            ->where(
                'visibility',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
            )
            ->with([
                'creator',
                'entityType',
            ])
            ->when(
                $search,
                fn(Builder $query) =>
                $query->where(
                    fn(Builder $subquery) =>
                    $subquery
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'description',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'creator',
                            fn(Builder $creator) =>
                            $creator->where(
                                'username',
                                'like',
                                "%{$search}%"
                            )
                        )
                )
            )
            ->when(
                $entityTypeId,
                fn(Builder $query) =>
                $query->where(
                    'entity_type_id',
                    $entityTypeId
                )
            )
            ->tap(
                fn(Builder $query) =>
                $this->applySort(
                    $query,
                    $sort
                )
            );
    }

    private function collectionQuery(
        string $search,
        string $sort
    ): Builder {
        return Collection::query()
            ->where(
                'visibility',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
            )
            ->with('creator')
            ->withCount('entities')
            ->when(
                $search,
                fn(Builder $query) =>
                $query->where(
                    fn(Builder $subquery) =>
                    $subquery
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'description',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'creator',
                            fn(Builder $creator) =>
                            $creator->where(
                                'username',
                                'like',
                                "%{$search}%"
                            )
                        )
                )
            )
            ->tap(
                fn(Builder $query) =>
                $this->applySort(
                    $query,
                    $sort
                )
            );
    }

    private function attributeQuery(
        string $search,
        string $sort,
        ?string $dataType
    ): Builder {
        return Attribute::query()
            ->where(
                'scope',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
            )
            ->with('creator')
            ->withCount([
                'options',
                'entityAttributes',
            ])
            ->when(
                $search,
                fn(Builder $query) =>
                $query->where(
                    fn(Builder $subquery) =>
                    $subquery
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'description',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'creator',
                            fn(Builder $creator) =>
                            $creator->where(
                                'username',
                                'like',
                                "%{$search}%"
                            )
                        )
                )
            )
            ->when(
                $dataType,
                fn(Builder $query) =>
                $query->where(
                    'data_type',
                    $dataType
                )
            )
            ->tap(
                fn(Builder $query) =>
                $this->applySort(
                    $query,
                    $sort
                )
            );
    }

    private function applySort(
        Builder $query,
        string $sort
    ): void {
        match ($sort) {
            'newest' =>
            $query->orderByDesc(
                'published_at'
            ),

            'oldest' =>
            $query->orderBy(
                'published_at'
            ),

            'name' =>
            $query->orderBy('name'),

            'cloned' =>
            $query->orderByDesc(
                'clones_count'
            ),

            'viewed' =>
            $query->orderByDesc(
                'views_count'
            ),

            default =>
            $query
                ->orderByDesc(
                    'clones_count'
                )
                ->orderByDesc(
                    'views_count'
                )
                ->orderByDesc(
                    'published_at'
                ),
        };
    }

    private function ensurePublicEntity(
        Entity $entity
    ): void {
        abort_unless(
            $entity->visibility === 'PUBLIC'
                && $entity->status === 'ACTIVE'
                && $entity->published_at,
            404
        );
    }

    private function ensurePublicCollection(
        Collection $collection
    ): void {
        abort_unless(
            $collection->visibility
                === 'PUBLIC'
                && $collection->status
                === 'ACTIVE'
                && $collection->published_at,
            404
        );
    }

    private function ensurePublicAttribute(
        Attribute $attribute
    ): void {
        abort_unless(
            $attribute->scope === 'PUBLIC'
                && $attribute->status === 'ACTIVE'
                && $attribute->published_at,
            404
        );
    }

    private function recordView(
        Request $request,
        string $contentType,
        int $contentId
    ): void {
        DB::table(
            'community_interactions'
        )->insert([
            'user_id' =>
            $request->user()?->id,

            'content_type' =>
            $contentType,

            'content_id' =>
            $contentId,

            'interaction_type' =>
            'VIEW',

            'metadata' =>
            json_encode([
                'ip' =>
                $request->ip(),
            ]),

            'created_at' =>
            now(),

            'updated_at' =>
            now(),
        ]);
    }
}
