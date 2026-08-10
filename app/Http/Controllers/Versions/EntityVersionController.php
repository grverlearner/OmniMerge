<?php

namespace App\Http\Controllers\Versions;

use App\Http\Controllers\Controller;

use App\Http\Requests\Versions\EntityVersionRequest;
use App\Http\Requests\Versions\StoreEntityVersionRequest;

use App\Models\Attribute;
use App\Models\Entity;
use App\Models\EntityVersion;
use App\Models\EntityVersionImage;
use App\Models\Version;

use App\Services\Versions\EntityVersionService;
use App\Services\Versions\UnifiedEntityVersionCreationService;
use App\Services\Versions\VersionResolverService;
use App\Services\Entities\EntityBaseVersionService;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

use Throwable;

class EntityVersionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX DE UNA ENTIDAD
    |--------------------------------------------------------------------------
    */

    public function index(
        Entity $entity
    ): View {

        $this->authorize(
            'update',
            $entity
        );


        $entity->load([
            /*
        |--------------------------------------------------------------------------
        | Base activa
        |--------------------------------------------------------------------------
        */

            'baseVersionSetting.entityVersion.version',


            /*
        |--------------------------------------------------------------------------
        | Versiones
        |--------------------------------------------------------------------------
        */

            'entityVersions' =>
            fn($query) =>
            $query
                ->with([
                    'version.parent',
                    'baseSetting',
                ])
                ->withCount([
                    'images',
                    'versionAttributes',
                ])
                ->orderBy(
                    'sort_order'
                )
                ->orderBy(
                    'name'
                ),
        ]);


        return view(
            'entity-versions.index',
            compact(
                'entity'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | STORE UNIFICADO
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreEntityVersionRequest $request,
        Entity $entity,
        UnifiedEntityVersionCreationService $creationService
    ): RedirectResponse {

        $this->authorize(
            'update',
            $entity
        );


        $data =
            $request->validated();


        /*
        |--------------------------------------------------------------------------
        | Archivos creados
        |--------------------------------------------------------------------------
        |
        | Si falla la transacción eliminamos únicamente
        | los archivos creados durante este request.
        |
        */

        $createdFiles = [];


        try {

            /*
            |--------------------------------------------------------------------------
            | Imagen concreta de la EntityVersion
            |--------------------------------------------------------------------------
            */

            $entityImagePath =
                $this->resolveEntityVersionImage(
                    $request,
                    $entity,
                    $createdFiles
                );


            /*
            |--------------------------------------------------------------------------
            | Nueva definición
            |--------------------------------------------------------------------------
            */

            $creatingDefinition =
                in_array(
                    $data['definition_mode'],
                    [
                        'NEW_SHARED',
                        'NEW_EXCLUSIVE',
                    ],
                    true
                );


            $versionData = [];

            $catalogLinks = [];


            if ($creatingDefinition) {

                /*
                |--------------------------------------------------------------------------
                | Imagen de Version general
                |--------------------------------------------------------------------------
                */

                if (
                    $data['definition_image_mode']
                    ===
                    'UPLOAD'
                ) {

                    $versionImagePath =
                        $request
                        ->file(
                            'new_version_image'
                        )
                        ->store(
                            'versions',
                            'public'
                        );


                    $createdFiles[] =
                        $versionImagePath;
                } else {

                    /*
                     * Copiamos físicamente el archivo.
                     *
                     * NO hacemos que Version y EntityVersion
                     * apunten al mismo path porque posteriormente
                     * cualquiera de las dos imágenes podría cambiar.
                     */
                    $versionImagePath =
                        $this->copyPublicFile(
                            $entityImagePath,
                            'versions'
                        );


                    $createdFiles[] =
                        $versionImagePath;
                }


                $versionData = [
                    'parent_version_id' =>
                    $data['new_version_parent_id']
                        ?? null,

                    'name' =>
                    $data['new_version_name'],

                    'description' =>
                    $data['new_version_description']
                        ?? null,

                    'image' =>
                    $versionImagePath,

                    'version_kind' =>
                    $data['new_version_kind'],

                    'activation_mode' =>
                    $data['new_version_activation_mode'],

                    'priority' =>
                    (int) (
                        $data['priority']
                        ?? 0
                    ),

                    'sort_order' =>
                    0,

                    'status' =>
                    'ACTIVE',
                ];


                /*
                |--------------------------------------------------------------------------
                | Contexto opcional inicial
                |--------------------------------------------------------------------------
                */

                if (
                    ! empty($data['new_catalog_attribute_id'])
                    &&
                    ! empty($data['new_catalog_attribute_option_id'])
                ) {

                    $catalogLinks[] = [
                        'attribute_id' =>
                        (int) $data['new_catalog_attribute_id'],

                        'attribute_option_id' =>
                        (int) $data['new_catalog_attribute_option_id'],

                        'relation_type' =>
                        $data['new_relation_type']
                            ?? 'ACTIVATES',

                        'condition_group' =>
                        1,

                        'logical_operator' =>
                        'AND',

                        'is_required' =>
                        false,

                        'priority' =>
                        0,
                    ];
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Datos EntityVersion
            |--------------------------------------------------------------------------
            */

            $entityVersionData = [
                'parent_entity_version_id' =>
                $data['parent_entity_version_id']
                    ?? null,

                'name' =>
                $data['name']
                    ?? '',

                'description' =>
                $data['description']
                    ?? null,

                'image' =>
                $entityImagePath,

                'inherit_base_attributes' =>
                (bool) (
                    $data['inherit_base_attributes']
                    ?? true
                ),

                'is_default' =>
                (bool) (
                    $data['is_default']
                    ?? false
                ),

                'priority' =>
                (int) (
                    $data['priority']
                    ?? 0
                ),

                'sort_order' =>
                (int) (
                    $data['sort_order']
                    ?? 0
                ),

                'status' =>
                $data['status']
                    ?? 'ACTIVE',
            ];


            /*
            |--------------------------------------------------------------------------
            | Crear
            |--------------------------------------------------------------------------
            */

            $result =
                $creationService
                ->create(
                    $request->user(),
                    $entity,
                    [
                        'definition_mode' =>
                        $data['definition_mode'],

                        'version_id' =>
                        $data['version_id']
                            ?? null,

                        'version_data' =>
                        $versionData,

                        'catalog_links' =>
                        $catalogLinks,

                        'entity_version_data' =>
                        $entityVersionData,

                        'auto_parent' =>
                        (bool) (
                            $data['auto_parent']
                            ?? true
                        ),
                    ]
                );


            /** @var EntityVersion $entityVersion */
            $entityVersion =
                $result['entity_version'];


            $message =
                $result['created_definition']
                ? 'Versión general y versión de la Entidad creadas correctamente.'
                : 'Versión de la Entidad creada correctamente.';


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
                    $message
                )
                ->with(
                    'entity_version_just_created',
                    true
                )
                ->with(
                    'definition_just_created',
                    $result['created_definition']
                );
        } catch (Throwable $exception) {

            foreach (
                array_unique(
                    $createdFiles
                )
                as $path
            ) {

                Storage::disk(
                    'public'
                )
                    ->delete(
                        $path
                    );
            }


            throw $exception;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

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
            'entity.baseVersionSetting.entityVersion',

            'baseSetting',

            'version.parent',
            'version.children',

            'parent.version',
            'children.version',

            'versionAttributes.attribute.groups',
            'versionAttributes.values.option',

            'images',
            'entity.presentation.entityVersion',
            'entity.presentation.mediaImage',
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


    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        EntityVersionRequest $request,
        Entity $entity,
        EntityVersion $entityVersion,
        EntityVersionService $service,
        EntityBaseVersionService $baseVersionService
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

            $updatedEntityVersion =
                $service->update(
                    $request->user(),
                    $entityVersion,
                    $data
                );


            $baseWasReset =
                false;


            /*
            |--------------------------------------------------------------------------
            | Una Base activa debe continuar activa
            |--------------------------------------------------------------------------
            */

            if (
                $updatedEntityVersion->status
                !==
                'ACTIVE'
            ) {

                $baseWasReset =
                    $baseVersionService
                    ->resetIfUsingVersion(
                        $request->user(),
                        $updatedEntityVersion
                    );
            }
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
        $message =
            $baseWasReset
            ? 'Versión actualizada. Como dejó de estar activa, la Entidad volvió automáticamente a su Base original.'
            : 'Versión actualizada correctamente.';


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
                $message
            );
    }

    /*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/

    public function destroy(
        Request $request,
        Entity $entity,
        EntityVersion $entityVersion,
        EntityBaseVersionService $baseVersionService
    ): RedirectResponse {

        $this->ensureEntity(
            $entity,
            $entityVersion
        );


        $this->authorize(
            'delete',
            $entityVersion
        );


        /*
    |--------------------------------------------------------------------------
    | Si era Base activa, restaurar Base original
    |--------------------------------------------------------------------------
    */

        $baseWasReset =
            $baseVersionService
            ->resetIfUsingVersion(
                $request->user(),
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
                $baseWasReset
                    ? 'Versión eliminada. La Entidad volvió automáticamente a su Base original.'
                    : 'Versión eliminada.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | MULTIMEDIA — SUBIR
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


        $data =
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

                'media_type' => [
                    'required',
                    'in:PORTRAIT,FULL_BODY,COMBAT,OUTFIT,REFERENCE,ALTERNATIVE,OTHER',
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

            $max += 10;


            $path =
                $file->store(
                    'entity-versions/gallery',
                    'public'
                );


            $caption =
                Str::headline(
                    pathinfo(
                        $file
                            ->getClientOriginalName(),
                        PATHINFO_FILENAME
                    )
                );


            $entityVersion
                ->images()
                ->create([
                    'image' =>
                    $path,

                    'caption' =>
                    $caption,

                    'alt_text' =>
                    $entityVersion->name
                        . ' — '
                        . $caption,

                    'media_type' =>
                    $data['media_type'],

                    'sort_order' =>
                    $max,
                ]);
        }


        return back()
            ->with(
                'success',
                'Multimedia añadida correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | MULTIMEDIA — EDITAR
    |--------------------------------------------------------------------------
    */

    public function updateImage(
        Request $request,
        Entity $entity,
        EntityVersion $entityVersion,
        EntityVersionImage $image
    ): RedirectResponse {

        $this->ensureImage(
            $entity,
            $entityVersion,
            $image
        );


        $this->authorize(
            'update',
            $entityVersion
        );


        $data =
            $request->validate([
                'caption' => [
                    'nullable',
                    'string',
                    'max:200',
                ],

                'alt_text' => [
                    'nullable',
                    'string',
                    'max:200',
                ],

                'media_type' => [
                    'required',
                    'in:PORTRAIT,FULL_BODY,COMBAT,OUTFIT,REFERENCE,ALTERNATIVE,OTHER',
                ],
            ]);


        $image->update(
            $data
        );


        return back()
            ->with(
                'success',
                'Información de la imagen actualizada.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | MULTIMEDIA — USAR COMO PRINCIPAL
    |--------------------------------------------------------------------------
    */

    public function makeImagePrimary(
        Entity $entity,
        EntityVersion $entityVersion,
        EntityVersionImage $image
    ): RedirectResponse {

        $this->ensureImage(
            $entity,
            $entityVersion,
            $image
        );


        $this->authorize(
            'update',
            $entityVersion
        );


        DB::transaction(
            function () use (
                $entityVersion,
                $image
            ) {

                $oldPrimary =
                    $entityVersion->image;


                $newPrimary =
                    $image->image;


                $entityVersion->update([
                    'image' =>
                    $newPrimary,
                ]);


                /*
                 * En vez de destruir la portada anterior,
                 * hacemos intercambio.
                 */

                if (
                    $oldPrimary
                    &&
                    Storage::disk(
                        'public'
                    )
                    ->exists(
                        $oldPrimary
                    )
                ) {

                    $image->update([
                        'image' =>
                        $oldPrimary,

                        'caption' =>
                        'Portada anterior',

                        'alt_text' =>
                        $entityVersion->name
                            . ' — portada anterior',

                        'media_type' =>
                        'ALTERNATIVE',
                    ]);
                } else {

                    $image->delete();
                }
            }
        );


        return back()
            ->with(
                'success',
                'La imagen ahora es la portada principal.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | MULTIMEDIA — REORDENAR
    |--------------------------------------------------------------------------
    */

    public function reorderImages(
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


        $data =
            $request->validate([
                'ordered_ids' => [
                    'required',
                    'array',
                ],

                'ordered_ids.*' => [
                    'integer',
                    'distinct',
                ],
            ]);


        $ids =
            collect(
                $data['ordered_ids']
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->values();


        $validCount =
            $entityVersion
            ->images()
            ->whereIn(
                'id',
                $ids
            )
            ->count();


        if (
            $validCount
            !==
            $ids->count()
        ) {

            throw ValidationException::withMessages([
                'ordered_ids' =>
                'El orden contiene imágenes que no pertenecen a esta Versión.',
            ]);
        }


        DB::transaction(
            function () use (
                $entityVersion,
                $ids
            ) {

                foreach (
                    $ids
                    as $index => $id
                ) {

                    $entityVersion
                        ->images()
                        ->whereKey(
                            $id
                        )
                        ->update([
                            'sort_order' => (
                                $index
                                +
                                1
                            )
                                *
                                10,
                        ]);
                }
            }
        );


        return back()
            ->with(
                'success',
                'Orden multimedia actualizado.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | MULTIMEDIA — ELIMINAR
    |--------------------------------------------------------------------------
    */

    public function destroyImage(
        Entity $entity,
        EntityVersion $entityVersion,
        EntityVersionImage $image
    ): RedirectResponse {

        $this->ensureImage(
            $entity,
            $entityVersion,
            $image
        );


        $this->authorize(
            'update',
            $entityVersion
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


    /*
    |--------------------------------------------------------------------------
    | RECURSOS DE FORMULARIO
    |--------------------------------------------------------------------------
    */

    private function formResources(
        Request $request,
        Entity $entity,
        ?EntityVersion $editing = null
    ): array {

        $user =
            $request->user();


        /*
        |--------------------------------------------------------------------------
        | Definiciones
        |--------------------------------------------------------------------------
        */

        $versions =
            Version::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->with([
                'parent',
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


        /*
        |--------------------------------------------------------------------------
        | EntityVersions existentes
        |--------------------------------------------------------------------------
        */

        $parentEntityVersions =
            EntityVersion::query()
            ->where(
                'entity_id',
                $entity->id
            )
            ->when(
                $editing,
                fn($query) =>
                $query->where(
                    'id',
                    '<>',
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


        /*
        |--------------------------------------------------------------------------
        | Catálogos
        |--------------------------------------------------------------------------
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


        /*
        |--------------------------------------------------------------------------
        | Payloads Alpine
        |--------------------------------------------------------------------------
        */

        $versionPayload =
            $versions
            ->map(
                fn($version) => [
                    'id' =>
                    (string) $version->id,

                    'name' =>
                    $version->name,

                    'code' =>
                    $version->code,

                    'kind' =>
                    $version->kind_label,

                    'scope' =>
                    $version->scope_label,

                    'scope_code' =>
                    $version->scope,

                    'image_url' =>
                    $version->image_url,

                    'parent_version_id' =>
                    $version->parent_version_id
                        ? (string) $version->parent_version_id
                        : '',

                    'parent_name' =>
                    $version->parent?->name,

                    'usage_count' =>
                    (int) $version
                        ->entity_versions_count,
                ]
            )
            ->values()
            ->all();


        $entityVersionPayload =
            $parentEntityVersions
            ->map(
                fn($item) => [
                    'id' =>
                    (string) $item->id,

                    'version_id' =>
                    (string) $item->version_id,

                    'name' =>
                    $item->name,

                    'version_name' =>
                    $item->version?->name,

                    'image_url' =>
                    $item->image_url,
                ]
            )
            ->values()
            ->all();


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
                            ]
                        )
                        ->values()
                        ->all(),
                ]
            )
            ->values()
            ->all();


        /*
        |--------------------------------------------------------------------------
        | Atajos por query string
        |--------------------------------------------------------------------------
        */

        $creationDefaults = [
            'definition_mode' =>
            strtoupper(
                (string) $request->query(
                    'definition_mode',
                    'EXISTING'
                )
            ),

            'parent_entity_version_id' =>
            (string) $request->query(
                'parent_entity_version_id',
                ''
            ),

            'new_version_parent_id' =>
            (string) $request->query(
                'new_version_parent_id',
                ''
            ),
        ];


        return compact(
            'entity',
            'versions',
            'parentEntityVersions',
            'catalogAttributes',

            'versionPayload',
            'entityVersionPayload',
            'catalogPayload',

            'creationDefaults'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Resolver imagen EntityVersion
    |--------------------------------------------------------------------------
    */

    private function resolveEntityVersionImage(
        StoreEntityVersionRequest $request,
        Entity $entity,
        array &$createdFiles
    ): string {

        $source =
            $request->validated()['image_source'];


        /*
        |--------------------------------------------------------------------------
        | Nueva subida
        |--------------------------------------------------------------------------
        */

        if (
            $source
            ===
            'UPLOAD'
        ) {

            $path =
                $request
                ->file(
                    'image'
                )
                ->store(
                    'entity-versions',
                    'public'
                );


            $createdFiles[] =
                $path;


            return $path;
        }


        /*
        |--------------------------------------------------------------------------
        | Entidad base
        |--------------------------------------------------------------------------
        */

        if (
            $source
            ===
            'ENTITY'
        ) {

            if (! $entity->image) {

                throw ValidationException::withMessages([
                    'image_source' =>
                    'La Entidad base no tiene imagen.',
                ]);
            }


            $path =
                $this->copyPublicFile(
                    $entity->image,
                    'entity-versions'
                );


            $createdFiles[] =
                $path;


            return $path;
        }


        /*
        |--------------------------------------------------------------------------
        | Otra Version de esta misma Entidad
        |--------------------------------------------------------------------------
        */

        $sourceVersion =
            EntityVersion::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->where(
                'entity_id',
                $entity->id
            )
            ->findOrFail(
                (int) $request->validated()['source_entity_version_id']
            );


        $path =
            $this->copyPublicFile(
                $sourceVersion->image,
                'entity-versions'
            );


        $createdFiles[] =
            $path;


        return $path;
    }


    /*
    |--------------------------------------------------------------------------
    | Copiar archivo público
    |--------------------------------------------------------------------------
    */

    private function copyPublicFile(
        string $sourcePath,
        string $directory
    ): string {

        $disk =
            Storage::disk(
                'public'
            );


        if (
            ! $disk->exists(
                $sourcePath
            )
        ) {

            throw ValidationException::withMessages([
                'image_source' =>
                'La imagen seleccionada ya no existe físicamente.',
            ]);
        }


        $extension =
            pathinfo(
                $sourcePath,
                PATHINFO_EXTENSION
            );


        $target =
            $directory
            . '/'
            . Str::uuid()
            . (
                $extension
                ? '.' . $extension
                : ''
            );


        if (
            ! $disk->copy(
                $sourcePath,
                $target
            )
        ) {

            throw ValidationException::withMessages([
                'image_source' =>
                'No se pudo copiar la imagen seleccionada.',
            ]);
        }


        return $target;
    }


    /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    */

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


    private function ensureImage(
        Entity $entity,
        EntityVersion $entityVersion,
        EntityVersionImage $image
    ): void {

        $this->ensureEntity(
            $entity,
            $entityVersion
        );


        abort_unless(
            $image->entity_version_id
                ===
                $entityVersion->id,
            404
        );
    }
}
