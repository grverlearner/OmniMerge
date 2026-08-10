<?php

namespace App\Http\Controllers\Versions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Versions\EntityVersionRequest;
use App\Models\Entity;
use App\Models\EntityVersion;
use App\Models\EntityVersionImage;
use App\Models\Version;
use App\Services\Versions\EntityVersionService;
use App\Services\Versions\VersionResolverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class EntityVersionController extends Controller
{
    public function index(
        Entity $entity
    ): View {

        $this->authorize(
            'update',
            $entity
        );


        $entity->load([
            'entityVersions.version.parent',
            'entityVersions.children',
        ]);


        return view(
            'entity-versions.index',
            compact(
                'entity'
            )
        );
    }


    public function create(
        Request $request,
        Entity $entity,
        EntityVersionService $service
    ): View {

        $this->authorize(
            'update',
            $entity
        );


        return view(
            'entity-versions.form',
            array_merge(
                $this->formResources(
                    $request,
                    $entity
                ),
                [
                    'entityVersion' =>
                    null,

                    'previewCode' =>
                    $service->nextCode(
                        $request->user()
                    ),
                ]
            )
        );
    }


    public function store(
        EntityVersionRequest $request,
        Entity $entity,
        EntityVersionService $service
    ): RedirectResponse {

        $this->authorize(
            'update',
            $entity
        );


        $data =
            $request->validated();


        unset(
            $data['image']
        );


        $imagePath =
            $request
            ->file(
                'image'
            )
            ->store(
                'entity-versions',
                'public'
            );


        $data['image'] =
            $imagePath;


        try {

            $entityVersion =
                $service->create(
                    $request->user(),
                    $entity,
                    $data
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
                'entity-versions.show',
                [
                    $entity,
                    $entityVersion,
                ]
            )
            ->with(
                'success',
                'Versión de la Entidad creada correctamente.'
            );
    }


    public function show(
        Entity $entity,
        EntityVersion $entityVersion,
        VersionResolverService $resolver
    ): View {

        $this->ensureEntity(
            $entity,
            $entityVersion
        );


        $this->authorize(
            'view',
            $entityVersion
        );


        $entityVersion->load([
            'entity',
            'version.parent',

            'parent.version',
            'children.version',

            'versionAttributes.attribute.groups',
            'versionAttributes.values.option',

            'images',
        ]);


        $effectiveAttributes =
            $resolver
            ->effectiveAttributes(
                $entityVersion
            );


        return view(
            'entity-versions.show',
            compact(
                'entity',
                'entityVersion',
                'effectiveAttributes'
            )
        );
    }


    public function edit(
        Request $request,
        Entity $entity,
        EntityVersion $entityVersion
    ): View {

        $this->ensureEntity(
            $entity,
            $entityVersion
        );


        $this->authorize(
            'update',
            $entityVersion
        );


        return view(
            'entity-versions.form',
            array_merge(
                $this->formResources(
                    $request,
                    $entity,
                    $entityVersion
                ),
                [
                    'entityVersion' =>
                    $entityVersion,

                    'previewCode' =>
                    $entityVersion->code,
                ]
            )
        );
    }


    public function update(
        EntityVersionRequest $request,
        Entity $entity,
        EntityVersion $entityVersion,
        EntityVersionService $service
    ): RedirectResponse {

        $this->ensureEntity(
            $entity,
            $entityVersion
        );


        $this->authorize(
            'update',
            $entityVersion
        );


        $data =
            $request->validated();


        unset(
            $data['image']
        );


        $oldImage =
            $entityVersion->image;

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
                    'entity-versions',
                    'public'
                );


            $data['image'] =
                $newImage;
        }


        try {

            $service->update(
                $request->user(),
                $entityVersion,
                $data
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
                'entity-versions.show',
                [
                    $entity,
                    $entityVersion,
                ]
            )
            ->with(
                'success',
                'Versión actualizada correctamente.'
            );
    }


    public function destroy(
        Entity $entity,
        EntityVersion $entityVersion
    ): RedirectResponse {

        $this->ensureEntity(
            $entity,
            $entityVersion
        );


        $this->authorize(
            'delete',
            $entityVersion
        );


        $entityVersion->delete();


        return redirect()
            ->route(
                'entity-versions.index',
                $entity
            )
            ->with(
                'success',
                'Versión eliminada.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Galería
    |--------------------------------------------------------------------------
    */

    public function storeImages(
        Request $request,
        Entity $entity,
        EntityVersion $entityVersion
    ): RedirectResponse {

        $this->ensureEntity(
            $entity,
            $entityVersion
        );


        $this->authorize(
            'update',
            $entityVersion
        );


        $request->validate([
            'gallery_images' => [
                'required',
                'array',
                'min:1',
                'max:20',
            ],

            'gallery_images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);


        $max =
            (int) $entityVersion
                ->images()
                ->max(
                    'sort_order'
                );


        foreach (
            $request->file(
                'gallery_images',
                []
            )
            as $file
        ) {

            $max +=
                10;


            $path =
                $file->store(
                    'entity-versions/gallery',
                    'public'
                );


            $entityVersion
                ->images()
                ->create([
                    'image' =>
                    $path,

                    'sort_order' =>
                    $max,
                ]);
        }


        return back()
            ->with(
                'success',
                'Imágenes añadidas a la galería.'
            );
    }


    public function destroyImage(
        Entity $entity,
        EntityVersion $entityVersion,
        EntityVersionImage $image
    ): RedirectResponse {

        $this->ensureEntity(
            $entity,
            $entityVersion
        );


        $this->authorize(
            'update',
            $entityVersion
        );


        abort_unless(
            $image->entity_version_id
                ===
                $entityVersion->id,
            404
        );


        Storage::disk(
            'public'
        )
            ->delete(
                $image->image
            );


        $image->delete();


        return back()
            ->with(
                'success',
                'Imagen eliminada.'
            );
    }


    private function formResources(
        Request $request,
        Entity $entity,
        ?EntityVersion $editing = null
    ): array {

        $user =
            $request->user();


        $versions =
            Version::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->get();


        $parentEntityVersions =
            EntityVersion::query()
            ->where(
                'entity_id',
                $entity->id
            )
            ->when(
                $editing,
                fn($query) =>
                $query->whereKeyNot(
                    $editing->id
                )
            )
            ->with(
                'version'
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->get();


        return compact(
            'entity',
            'versions',
            'parentEntityVersions'
        );
    }


    private function ensureEntity(
        Entity $entity,
        EntityVersion $entityVersion
    ): void {

        abort_unless(
            $entityVersion->entity_id
                ===
                $entity->id,
            404
        );
    }
}
