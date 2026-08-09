<?php

namespace App\Http\Controllers\Attributes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attributes\StoreAttributeGroupRequest;
use App\Http\Requests\Attributes\UpdateAttributeGroupRequest;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttributeGroupController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {
        $this->authorize(
            'viewAny',
            AttributeGroup::class
        );

        $user = $request->user();

        $search = trim(
            (string) $request->input(
                'search'
            )
        );

        $status = $request->input(
            'status'
        );

        $layout = $request->input(
            'layout'
        );

        $content = $request->input(
            'content'
        );

        $collapsible = $request->input(
            'collapsible'
        );

        $sort = (string) $request->input(
            'sort',
            'manual'
        );

        $allowedSorts = [
            'manual',

            'newest',
            'oldest',

            'name_asc',
            'name_desc',

            'code_asc',
            'code_desc',

            'attributes_desc',
            'attributes_asc',
        ];

        if (
            ! in_array(
                $sort,
                $allowedSorts,
                true
            )
        ) {
            $sort = 'manual';
        }

        $perPage = (int) $request->input(
            'per_page',
            12
        );

        if (
            ! in_array(
                $perPage,
                [
                    12,
                    24,
                    48,
                ],
                true
            )
        ) {
            $perPage = 12;
        }

        /*
        |--------------------------------------------------------------------------
        | Estadísticas
        |--------------------------------------------------------------------------
        */

        $baseQuery = AttributeGroup::query()
            ->ownedBy($user);

        $stats = [
            'total' => (clone $baseQuery)
                ->count(),

            'active' => (clone $baseQuery)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'empty' => (clone $baseQuery)
                ->whereDoesntHave(
                    'attributes'
                )
                ->count(),

            'relations' => (clone $baseQuery)
                ->withCount(
                    'attributes'
                )
                ->get()
                ->sum(
                    'attributes_count'
                ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Consulta principal
        |--------------------------------------------------------------------------
        */

        $query = AttributeGroup::query()
            ->ownedBy($user)

            ->withCount(
                'attributes'
            )

            /*
             * Necesitamos los atributos también
             * para construir el mosaico visual.
             */

            ->with([
                'attributes' => fn($query) =>
                $query
                    ->withCount([
                        'options',
                        'entityAttributes',
                    ]),
            ])

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
                $layout,
                fn($query) =>
                $query->where(
                    'layout_type',
                    $layout
                )
            )

            ->when(
                $content === 'with',
                fn($query) =>
                $query->whereHas(
                    'attributes'
                )
            )

            ->when(
                $content === 'empty',
                fn($query) =>
                $query->whereDoesntHave(
                    'attributes'
                )
            )

            ->when(
                $collapsible === 'yes',
                fn($query) =>
                $query->where(
                    'collapsible',
                    true
                )
            )

            ->when(
                $collapsible === 'no',
                fn($query) =>
                $query->where(
                    'collapsible',
                    false
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Ordenamiento
        |--------------------------------------------------------------------------
        */

        match ($sort) {
            'newest' =>
            $query
                ->orderByDesc(
                    'created_at'
                )
                ->orderByDesc(
                    'id'
                ),

            'oldest' =>
            $query
                ->orderBy(
                    'created_at'
                )
                ->orderBy(
                    'id'
                ),

            'name_asc' =>
            $query
                ->orderBy(
                    'name'
                ),

            'name_desc' =>
            $query
                ->orderByDesc(
                    'name'
                ),

            'code_asc' =>
            $query
                ->orderBy(
                    'code'
                ),

            'code_desc' =>
            $query
                ->orderByDesc(
                    'code'
                ),

            'attributes_desc' =>
            $query
                ->orderByDesc(
                    'attributes_count'
                )
                ->orderBy(
                    'name'
                ),

            'attributes_asc' =>
            $query
                ->orderBy(
                    'attributes_count'
                )
                ->orderBy(
                    'name'
                ),

            default =>
            $query
                ->orderBy(
                    'sort_order'
                )
                ->orderBy(
                    'sequence_number'
                )
                ->orderBy(
                    'name'
                ),
        };

        $groups = $query
            ->paginate(
                $perPage
            )
            ->withQueryString();

        return view(
            'attribute-groups.index',
            compact(
                'groups',
                'stats',

                'search',
                'status',
                'layout',
                'content',
                'collapsible',
                'sort',
                'perPage'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(
        Request $request
    ): View {
        $this->authorize(
            'create',
            AttributeGroup::class
        );

        $attributes = $this->availableAttributes(
            $request->user()
        );

        $previewCode =
            AttributeGroup::formatCode(
                $this->nextSequence(
                    $request->user()->id
                )
            );

        return view(
            'attribute-groups.create',
            compact(
                'attributes',
                'previewCode'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreAttributeGroupRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        $attributeIds =
            $data['attribute_ids']
            ?? [];

        $settings =
            $data['attribute_settings']
            ?? [];

        unset(
            $data['attribute_ids'],
            $data['attribute_settings']
        );

        $group = DB::transaction(
            function () use (
                $request,
                $data,
                $attributeIds,
                $settings
            ) {
                /*
                 * Bloqueamos al propietario para impedir
                 * dos GRP000001 simultáneos.
                 */

                /** @var User $user */
                $user = User::query()
                    ->whereKey(
                        $request->user()->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $sequence = $this->nextSequence(
                    $user->id
                );

                $data['sequence_number'] =
                    $sequence;

                $data['code'] =
                    AttributeGroup::formatCode(
                        $sequence
                    );

                /*
                 * Orden global automático:
                 *
                 * 10
                 * 20
                 * 30
                 */

                $data['sort_order'] =
                    (
                        (int) AttributeGroup::withTrashed()
                            ->where(
                                'user_id',
                                $user->id
                            )
                            ->max(
                                'sort_order'
                            )
                    )
                    + 10;

                $group = $user
                    ->attributeGroups()
                    ->create(
                        $data
                    );

                $this->syncAttributes(
                    $group,
                    $attributeIds,
                    $settings
                );

                return $group;
            }
        );

        return redirect()
            ->route(
                'attribute-groups.show',
                $group
            )
            ->with(
                'success',
                'Grupo creado correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(
        AttributeGroup $attributeGroup
    ): View {
        $this->authorize(
            'view',
            $attributeGroup
        );

        $attributeGroup->load([
            'attributes' => fn($query) =>
            $query
                ->withCount([
                    'options',
                    'entityAttributes',
                ]),
        ]);

        return view(
            'attribute-groups.show',
            compact(
                'attributeGroup'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        Request $request,
        AttributeGroup $attributeGroup
    ): View {
        $this->authorize(
            'update',
            $attributeGroup
        );

        $attributes = $this->availableAttributes(
            $request->user()
        );

        $attributeGroup->load(
            'attributes'
        );

        $previewCode =
            $attributeGroup->code;

        return view(
            'attribute-groups.edit',
            compact(
                'attributeGroup',
                'attributes',
                'previewCode'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateAttributeGroupRequest $request,
        AttributeGroup $attributeGroup
    ): RedirectResponse {
        $data = $request->validated();

        $attributeIds =
            $data['attribute_ids']
            ?? [];

        $settings =
            $data['attribute_settings']
            ?? [];

        unset(
            $data['attribute_ids'],
            $data['attribute_settings']
        );

        /*
         * code
         * sequence_number
         * sort_order
         *
         * no vienen del Request.
         */

        DB::transaction(
            function () use (
                $attributeGroup,
                $data,
                $attributeIds,
                $settings
            ) {
                $attributeGroup->update(
                    $data
                );

                $this->syncAttributes(
                    $attributeGroup,
                    $attributeIds,
                    $settings
                );
            }
        );

        return redirect()
            ->route(
                'attribute-groups.show',
                $attributeGroup
            )
            ->with(
                'success',
                'Grupo actualizado correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function destroy(
        AttributeGroup $attributeGroup
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $attributeGroup
        );

        DB::transaction(
            function () use (
                $attributeGroup
            ) {
                /*
                 * Quitamos únicamente la organización.
                 *
                 * Los atributos NO se eliminan.
                 */

                $attributeGroup
                    ->attributes()
                    ->detach();

                $attributeGroup->delete();
            }
        );

        return redirect()
            ->route(
                'attribute-groups.index'
            )
            ->with(
                'success',
                'Grupo eliminado. Los atributos contenidos se conservaron.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Recursos
    |--------------------------------------------------------------------------
    */

    private function availableAttributes(
        User $user
    ) {
        return Attribute::query()
            ->ownedBy($user)
            ->active()

            ->withCount([
                'options',
                'entityAttributes',
            ])

            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Sincronizar pivote
    |--------------------------------------------------------------------------
    */

    private function syncAttributes(
        AttributeGroup $group,
        array $attributeIds,
        array $settings
    ): void {
        $ids = collect(
            $attributeIds
        )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $group
                ->attributes()
                ->sync([]);

            return;
        }

        $syncData = [];

        foreach (
            $ids
            as $index => $attributeId
        ) {
            $attributeSettings =
                $settings[$attributeId]
                ?? $settings[(string) $attributeId]
                ?? [];

            $label = trim(
                (string) (
                    $attributeSettings['custom_label']
                    ?? ''
                )
            );

            $sortOrder =
                (int) (
                    $attributeSettings['sort_order']
                    ?? (($index + 1) * 10)
                );

            $featured = filter_var(
                $attributeSettings['is_featured']
                    ?? false,
                FILTER_VALIDATE_BOOLEAN
            );

            $syncData[$attributeId] = [
                'custom_label' =>
                $label !== ''
                    ? $label
                    : null,

                'sort_order' =>
                max(
                    0,
                    $sortOrder
                ),

                'is_featured' =>
                $featured,
            ];
        }

        $group
            ->attributes()
            ->sync(
                $syncData
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Secuencia
    |--------------------------------------------------------------------------
    */

    private function nextSequence(
        int $userId
    ): int {
        $current = (int) AttributeGroup::withTrashed()
            ->where(
                'user_id',
                $userId
            )
            ->max(
                'sequence_number'
            );

        return $current + 1;
    }
}
