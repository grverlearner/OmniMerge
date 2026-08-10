<?php

namespace App\Http\Controllers\Versions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Versions\VersionRequest;
use App\Models\Attribute;
use App\Models\Entity;
use App\Models\Version;
use App\Services\Versions\VersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class VersionController extends Controller
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
            Version::class
        );


        $user =
            $request->user();


        $search =
            trim(
                (string) $request->input(
                    'search'
                )
            );

        $kind =
            (string) $request->input(
                'kind'
            );

        $scope =
            (string) $request->input(
                'scope'
            );

        $status =
            (string) $request->input(
                'status'
            );

        $activation =
            (string) $request->input(
                'activation'
            );


        $base =
            Version::query()
            ->ownedBy(
                $user
            );


        $stats = [
            'total' => (clone $base)
                ->count(),

            'shared' => (clone $base)
                ->where(
                    'scope',
                    'SHARED'
                )
                ->count(),

            'exclusive' => (clone $base)
                ->where(
                    'scope',
                    'EXCLUSIVE'
                )
                ->count(),

            'automatic' => (clone $base)
                ->whereIn(
                    'activation_mode',
                    [
                        'AUTO',
                        'BOTH',
                    ]
                )
                ->count(),

            'active' => (clone $base)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),
        ];


        $versions =
            Version::query()
            ->ownedBy(
                $user
            )
            ->with([
                'parent',
                'catalogLinks.option.attribute',
            ])
            ->withCount([
                'entityVersions',
                'children',
                'catalogLinks',
            ])
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
                $kind,
                fn($query) =>
                $query->where(
                    'version_kind',
                    $kind
                )
            )
            ->when(
                $scope,
                fn($query) =>
                $query->where(
                    'scope',
                    $scope
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
                $activation,
                fn($query) =>
                $query->where(
                    'activation_mode',
                    $activation
                )
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->paginate(24)
            ->withQueryString();


        $treeVersions =
            Version::query()
            ->ownedBy(
                $user
            )
            ->whereNull(
                'parent_version_id'
            )
            ->with([
                'children.children',
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
            ->get();


        return view(
            'versions.index',
            compact(
                'versions',
                'treeVersions',
                'stats',

                'search',
                'kind',
                'scope',
                'status',
                'activation'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(
        Request $request,
        VersionService $service
    ): View {

        $this->authorize(
            'create',
            Version::class
        );


        return view(
            'versions.form',
            array_merge(
                $this->formResources(
                    $request
                ),
                [
                    'version' =>
                    null,

                    'previewCode' =>
                    $service->nextCode(
                        $request->user()
                    ),
                ]
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        VersionRequest $request,
        VersionService $service
    ): RedirectResponse {

        $this->authorize(
            'create',
            Version::class
        );


        $data =
            $request->validated();


        $catalogLinks =
            $data['catalog_links']
            ?? [];


        unset(
            $data['catalog_links'],
            $data['image']
        );


        $imagePath =
            $request
            ->file(
                'image'
            )
            ->store(
                'versions',
                'public'
            );


        $data['image'] =
            $imagePath;


        try {

            $version =
                $service->create(
                    $request->user(),
                    $data,
                    $catalogLinks
                );
        } catch (Throwable $exception) {

            Storage::disk(
                'public'
            )
                ->delete(
                    $imagePath
                );


            throw $exception;
        }


        return redirect()
            ->route(
                'versions.show',
                $version
            )
            ->with(
                'success',
                'Versión creada correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Version $version
    ): View {

        $this->authorize(
            'view',
            $version
        );


        $version->load([
            'parent',
            'children',

            'catalogLinks.attribute',
            'catalogLinks.option',

            'entityVersions.entity',
        ]);


        $version->loadCount([
            'entityVersions',
            'children',
            'catalogLinks',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Cobertura
        |--------------------------------------------------------------------------
        */

        $activationOptionIds =
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


        $eligibleEntities =
            collect();


        if (
            $activationOptionIds
            ->isNotEmpty()
        ) {

            $eligibleEntities =
                Entity::query()
                ->ownedBy(
                    $request->user()
                )
                ->whereHas(
                    'entityAttributes.values',

                    fn($query) =>
                    $query->whereIn(
                        'attribute_option_id',
                        $activationOptionIds
                    )
                )
                ->orderBy(
                    'name'
                )
                ->get();
        }


        $assignedIds =
            $version
            ->entityVersions
            ->pluck(
                'entity_id'
            );


        $missingEntities =
            $eligibleEntities
            ->whereNotIn(
                'id',
                $assignedIds
            )
            ->values();


        return view(
            'versions.show',
            compact(
                'version',
                'eligibleEntities',
                'missingEntities'
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
        Version $version,
        VersionService $service
    ): View {

        $this->authorize(
            'update',
            $version
        );


        $version->load([
            'catalogLinks',
        ]);


        return view(
            'versions.form',
            array_merge(
                $this->formResources(
                    $request,
                    $version
                ),
                [
                    'version' =>
                    $version,

                    'previewCode' =>
                    $version->code,
                ]
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        VersionRequest $request,
        Version $version,
        VersionService $service
    ): RedirectResponse {

        $this->authorize(
            'update',
            $version
        );


        $data =
            $request->validated();


        $catalogLinks =
            $data['catalog_links']
            ?? [];


        unset(
            $data['catalog_links'],
            $data['image']
        );


        $oldImage =
            $version->image;

        $newImage =
            null;


        if (
            $request->hasFile(
                'image'
            )
        ) {

            $newImage =
                $request
                ->file(
                    'image'
                )
                ->store(
                    'versions',
                    'public'
                );


            $data['image'] =
                $newImage;
        }


        try {

            $service->update(
                $request->user(),
                $version,
                $data,
                $catalogLinks
            );
        } catch (Throwable $exception) {

            if ($newImage) {

                Storage::disk(
                    'public'
                )
                    ->delete(
                        $newImage
                    );
            }


            throw $exception;
        }


        if (
            $newImage
            &&
            $oldImage
        ) {

            Storage::disk(
                'public'
            )
                ->delete(
                    $oldImage
                );
        }


        return redirect()
            ->route(
                'versions.show',
                $version
            )
            ->with(
                'success',
                'Versión actualizada correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Version $version
    ): RedirectResponse {

        $this->authorize(
            'delete',
            $version
        );


        /*
         * Soft Delete:
         * mantenemos archivos para futura restauración/historial.
         */
        $version->delete();


        return redirect()
            ->route(
                'versions.index'
            )
            ->with(
                'success',
                'Versión eliminada.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Recursos formulario
    |--------------------------------------------------------------------------
    */

    private function formResources(
        Request $request,
        ?Version $editing = null
    ): array {

        $user =
            $request->user();


        $parentVersions =
            Version::query()
            ->ownedBy(
                $user
            )
            ->when(
                $editing,
                fn($query) =>
                $query->whereKeyNot(
                    $editing->id
                )
            )
            ->orderBy(
                'name'
            )
            ->get();


        /*
         * Solo Catálogos.
         */
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


        $catalogPayload =
            $catalogAttributes
            ->map(
                fn($attribute) => [
                    'id' =>
                    (string) $attribute->id,

                    'name' =>
                    $attribute->name,

                    'options' =>
                    $attribute
                        ->options
                        ->map(
                            fn($option) => [
                                'id' =>
                                (string) $option->id,

                                'name' =>
                                $option->name,

                                'image_url' =>
                                $option->image_url,
                            ]
                        )
                        ->values()
                        ->all(),
                ]
            )
            ->values()
            ->all();


        $linkPayload =
            $editing
            ? $editing
            ->catalogLinks
            ->map(
                fn($link) => [
                    'attribute_id' =>
                    (string) $link->attribute_id,

                    'attribute_option_id' =>
                    (string) $link->attribute_option_id,

                    'relation_type' =>
                    $link->relation_type,

                    'condition_group' =>
                    (int) $link->condition_group,

                    'logical_operator' =>
                    $link->logical_operator,

                    'is_required' =>
                    (bool) $link->is_required,

                    'priority' =>
                    (int) $link->priority,
                ]
            )
            ->values()
            ->all()

            : [];


        return compact(
            'parentVersions',
            'catalogAttributes',
            'catalogPayload',
            'linkPayload'
        );
    }
}
