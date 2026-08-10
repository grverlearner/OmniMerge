<?php

namespace App\Http\Controllers\Versions;

use App\Http\Controllers\Controller;

use App\Models\Attribute;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\EntityVersion;
use App\Models\Version;

use App\Services\Versions\VersionResolverService;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class VersionWorkspaceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | TODAS LAS ENTITY VERSIONS
    |--------------------------------------------------------------------------
    */

    public function entities(
        Request $request
    ): View {

        $this->authorize(
            'viewAny',
            EntityVersion::class
        );


        $user =
            $request->user();


        $search =
            trim(
                (string) $request->input(
                    'search'
                )
            );


        $versionId =
            $request->integer(
                'version'
            )
            ?: null;


        $typeId =
            $request->integer(
                'type'
            )
            ?: null;


        $status =
            strtoupper(
                (string) $request->input(
                    'status'
                )
            );


        $default =
            (string) $request->input(
                'default'
            );


        $overrides =
            (string) $request->input(
                'overrides'
            );


        $media =
            (string) $request->input(
                'media'
            );


        $base =
            EntityVersion::query()
            ->ownedBy(
                $user
            );


        $stats = [
            'total' => (clone $base)
                ->count(),

            'active' => (clone $base)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'default' => (clone $base)
                ->where(
                    'is_default',
                    true
                )
                ->count(),

            'with_overrides' => (clone $base)
                ->whereHas(
                    'versionAttributes'
                )
                ->count(),

            'with_media' => (clone $base)
                ->whereHas(
                    'images'
                )
                ->count(),
        ];


        $entityVersions =
            EntityVersion::query()
            ->ownedBy(
                $user
            )
            ->with([
                'entity.entityType',
                'version.parent',
            ])
            ->withCount([
                'versionAttributes',
                'images',
                'children',
            ])
            ->when(
                $search,
                function ($query) use (
                    $search
                ) {

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
                                ->orWhereHas(
                                    'entity',
                                    fn($entityQuery) =>
                                    $entityQuery
                                        ->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                )
                                ->orWhereHas(
                                    'version',
                                    fn($versionQuery) =>
                                    $versionQuery
                                        ->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                );
                        }
                    );
                }
            )
            ->when(
                $versionId,
                fn($query) =>
                $query->where(
                    'version_id',
                    $versionId
                )
            )
            ->when(
                $typeId,
                fn($query) =>
                $query->whereHas(
                    'entity',
                    fn($entityQuery) =>
                    $entityQuery
                        ->where(
                            'entity_type_id',
                            $typeId
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
                $default
                    ===
                    'YES',
                fn($query) =>
                $query->where(
                    'is_default',
                    true
                )
            )
            ->when(
                $default
                    ===
                    'NO',
                fn($query) =>
                $query->where(
                    'is_default',
                    false
                )
            )
            ->when(
                $overrides
                    ===
                    'YES',
                fn($query) =>
                $query->whereHas(
                    'versionAttributes'
                )
            )
            ->when(
                $overrides
                    ===
                    'NO',
                fn($query) =>
                $query->whereDoesntHave(
                    'versionAttributes'
                )
            )
            ->when(
                $media
                    ===
                    'YES',
                fn($query) =>
                $query->whereHas(
                    'images'
                )
            )
            ->when(
                $media
                    ===
                    'NO',
                fn($query) =>
                $query->whereDoesntHave(
                    'images'
                )
            )
            ->orderByDesc(
                'is_default'
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->paginate(24)
            ->withQueryString();


        $versions =
            Version::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->orderBy(
                'name'
            )
            ->get();


        $entityTypes =
            EntityType::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->orderBy(
                'name'
            )
            ->get();


        return view(
            'versions.workspace.entities',
            compact(
                'entityVersions',
                'versions',
                'entityTypes',
                'stats',

                'search',
                'versionId',
                'typeId',
                'status',
                'default',
                'overrides',
                'media'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COBERTURA
    |--------------------------------------------------------------------------
    */

    public function coverage(
        Request $request
    ): View {

        $this->authorize(
            'viewAny',
            Version::class
        );


        $user =
            $request->user();


        $versions =
            Version::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->with([
                'catalogLinks',
            ])
            ->withCount(
                'entityVersions'
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->paginate(20);


        $coverageRows =
            collect(
                $versions->items()
            )
            ->map(
                function (
                    Version $version
                ) use (
                    $user
                ) {

                    $optionIds =
                        $version
                        ->catalogLinks
                        ->where(
                            'relation_type',
                            'ACTIVATES'
                        )
                        ->pluck(
                            'attribute_option_id'
                        )
                        ->unique()
                        ->values();


                    if (
                        $optionIds->isEmpty()
                    ) {

                        return [
                            'version' =>
                            $version,

                            'eligible' =>
                            null,

                            'covered' =>
                            $version
                                ->entity_versions_count,

                            'missing' =>
                            null,

                            'percentage' =>
                            null,
                        ];
                    }


                    $eligibleIds =
                        Entity::query()
                        ->ownedBy(
                            $user
                        )
                        ->whereHas(
                            'entityAttributes.values',
                            fn($query) =>
                            $query
                                ->whereIn(
                                    'attribute_option_id',
                                    $optionIds
                                )
                        )
                        ->pluck(
                            'id'
                        );


                    $eligible =
                        $eligibleIds->count();


                    $covered =
                        EntityVersion::query()
                        ->where(
                            'version_id',
                            $version->id
                        )
                        ->whereIn(
                            'entity_id',
                            $eligibleIds
                        )
                        ->count();


                    $missing =
                        max(
                            0,
                            $eligible
                                -
                                $covered
                        );


                    $percentage =
                        $eligible > 0
                        ? round(
                            (
                                $covered
                                /
                                $eligible
                            )
                                *
                                100
                        )
                        : 0;


                    return [
                        'version' =>
                        $version,

                        'eligible' =>
                        $eligible,

                        'covered' =>
                        $covered,

                        'missing' =>
                        $missing,

                        'percentage' =>
                        $percentage,
                    ];
                }
            );


        return view(
            'versions.workspace.coverage',
            compact(
                'versions',
                'coverageRows'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | MULTIMEDIA GLOBAL
    |--------------------------------------------------------------------------
    */

    public function media(
        Request $request
    ): View {

        $this->authorize(
            'viewAny',
            EntityVersion::class
        );


        $search =
            trim(
                (string) $request->input(
                    'search'
                )
            );


        $entityVersions =
            EntityVersion::query()
            ->ownedBy(
                $request->user()
            )
            ->with([
                'entity',
                'version',
                'images',
            ])
            ->withCount(
                'images'
            )
            ->when(
                $search,
                function ($query) use (
                    $search
                ) {

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
                                ->orWhereHas(
                                    'entity',
                                    fn($entityQuery) =>
                                    $entityQuery
                                        ->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                )
                                ->orWhereHas(
                                    'version',
                                    fn($versionQuery) =>
                                    $versionQuery
                                        ->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                );
                        }
                    );
                }
            )
            ->orderBy(
                'name'
            )
            ->paginate(18)
            ->withQueryString();


        return view(
            'versions.workspace.media',
            compact(
                'entityVersions',
                'search'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PROBADOR RESOLVER
    |--------------------------------------------------------------------------
    */

    public function resolver(
        Request $request,
        VersionResolverService $resolver
    ): View {

        $this->authorize(
            'viewAny',
            EntityVersion::class
        );


        $user =
            $request->user();


        $entities =
            Entity::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->orderBy(
                'name'
            )
            ->get();


        $catalogAttributes =
            Attribute::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->where(
                'data_type',
                'OPTION'
            )
            ->with([
                'options' =>
                fn($query) =>
                $query
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy(
                        'name'
                    ),
            ])
            ->orderBy(
                'name'
            )
            ->get();


        $entity =
            null;

        $result =
            null;

        $candidateVersions =
            collect();


        $optionIds =
            collect(
                (array) $request->input(
                    'option_ids',
                    []
                )
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->filter()
            ->unique()
            ->values();


        if (
            $request->filled(
                'entity_id'
            )
        ) {

            $entity =
                Entity::query()
                ->ownedBy(
                    $user
                )
                ->findOrFail(
                    $request->integer(
                        'entity_id'
                    )
                );


            /*
             * Evitar IDs ajenos manipulados por URL.
             */
            $allowedOptionIds =
                Attribute::query()
                ->ownedBy(
                    $user
                )
                ->whereHas(
                    'options',
                    fn($query) =>
                    $query->whereIn(
                        'id',
                        $optionIds
                    )
                )
                ->with([
                    'options' =>
                    fn($query) =>
                    $query->whereIn(
                        'id',
                        $optionIds
                    ),
                ])
                ->get()
                ->pluck(
                    'options'
                )
                ->flatten()
                ->pluck(
                    'id'
                )
                ->map(
                    fn($id) =>
                    (int) $id
                );


            $optionIds =
                $optionIds
                ->intersect(
                    $allowedOptionIds
                )
                ->values();


            $result =
                $resolver->resolve(
                    $entity,
                    $optionIds->all(),
                    null,
                    false
                );


            /*
             * Candidatas visibles para diagnóstico.
             * El resultado definitivo sigue siendo el Resolver.
             */

            $candidateVersions =
                $entity
                ->entityVersions()
                ->active()
                ->with([
                    'version.catalogLinks.option',
                ])
                ->get()
                ->filter(
                    function (
                        EntityVersion $item
                    ) use (
                        $optionIds
                    ) {

                        return $item
                            ->version
                            ->catalogLinks
                            ->where(
                                'relation_type',
                                'ACTIVATES'
                            )
                            ->pluck(
                                'attribute_option_id'
                            )
                            ->intersect(
                                $optionIds
                            )
                            ->isNotEmpty();
                    }
                )
                ->values();
        }


        return view(
            'versions.workspace.resolver',
            compact(
                'entities',
                'catalogAttributes',

                'entity',
                'result',
                'candidateVersions',
                'optionIds'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COMPARAR VERSIONES DE UNA ENTIDAD
    |--------------------------------------------------------------------------
    */

    public function compare(
        Request $request,
        Entity $entity,
        VersionResolverService $resolver
    ): View {

        $this->authorize(
            'update',
            $entity
        );


        $entity->load([
            'entityAttributes.attribute',
            'entityAttributes.values.option',

            'entityVersions.version',
        ]);


        $selectedIds =
            collect(
                (array) $request->input(
                    'versions',
                    []
                )
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->filter()
            ->unique()
            ->take(4)
            ->values();


        if (
            $selectedIds->isEmpty()
        ) {

            $selectedIds =
                $entity
                ->entityVersions
                ->take(2)
                ->pluck(
                    'id'
                );
        }


        $selectedVersions =
            EntityVersion::query()
            ->where(
                'entity_id',
                $entity->id
            )
            ->whereIn(
                'id',
                $selectedIds
            )
            ->with([
                'version',
            ])
            ->get()
            ->sortBy(
                fn($item) =>
                $selectedIds
                    ->search(
                        $item->id
                    )
            )
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Columna BASE
        |--------------------------------------------------------------------------
        */

        $baseMap =
            $entity
            ->entityAttributes
            ->mapWithKeys(
                function (
                    $assignment
                ) {

                    if (
                        ! $assignment
                            ->attribute
                    ) {
                        return [];
                    }


                    return [
                        $assignment
                            ->attribute_id
                        => [
                            'attribute' =>
                            $assignment->attribute,

                            'display' =>
                            $assignment
                                ->values
                                ->map(
                                    fn($value) =>
                                    $value
                                        ->displayValue()
                                )
                                ->filter()
                                ->implode(
                                    ', '
                                ),
                        ],
                    ];
                }
            );


        $columns =
            collect([
                [
                    'key' =>
                    'base',

                    'label' =>
                    'Entidad base',

                    'name' =>
                    $entity->name,

                    'image_url' =>
                    $entity->image_url,

                    'map' =>
                    $baseMap,
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | Columnas Versiones
        |--------------------------------------------------------------------------
        */

        foreach (
            $selectedVersions
            as $entityVersion
        ) {

            $effective =
                $resolver
                ->effectiveAttributes(
                    $entityVersion
                );


            $map =
                $effective
                ->mapWithKeys(
                    fn($item) => [
                        $item['attribute']->id
                        => [
                            'attribute' =>
                            $item['attribute'],

                            'display' =>
                            $item['display'],

                            'source' =>
                            $item['source_name'],
                        ],
                    ]
                );


            $columns->push([
                'key' =>
                'version-'
                    . $entityVersion->id,

                'label' =>
                $entityVersion
                    ->version
                    ->name,

                'name' =>
                $entityVersion->name,

                'image_url' =>
                $entityVersion
                    ->image_url,

                'map' =>
                $map,
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Filas
        |--------------------------------------------------------------------------
        */

        $attributeMap =
            collect();


        foreach (
            $columns
            as $column
        ) {

            foreach (
                $column['map']
                as $attributeId => $value
            ) {

                $attributeMap->put(
                    $attributeId,
                    $value['attribute']
                );
            }
        }


        $rows =
            $attributeMap
            ->sortBy(
                fn($attribute) =>
                $attribute
                    ->sort_order
                    ?? 0
            )
            ->map(
                function (
                    $attribute
                ) use (
                    $columns
                ) {

                    return [
                        'attribute' =>
                        $attribute,

                        'values' =>
                        $columns
                            ->mapWithKeys(
                                fn($column) => [
                                    $column['key']
                                    =>
                                    $column['map'][$attribute->id]['display']
                                        ?? null,
                                ]
                            ),
                    ];
                }
            )
            ->values();


        return view(
            'entity-versions.compare',
            compact(
                'entity',
                'selectedVersions',
                'columns',
                'rows'
            )
        );
    }
}
