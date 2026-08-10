<?php

namespace App\Services\Versions;

use App\Models\Entity;
use App\Models\EntityVersion;
use App\Models\User;
use App\Models\Version;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnifiedEntityVersionCreationService
{
    public function __construct(
        private readonly VersionService $versionService,
        private readonly EntityVersionService $entityVersionService
    ) {}


    /*
    |--------------------------------------------------------------------------
    | Crear flujo unificado
    |--------------------------------------------------------------------------
    |
    | Puede:
    |
    | EXISTING
    |   Usar una Version ya existente.
    |
    | NEW_SHARED
    |   Crear Version compartida + EntityVersion.
    |
    | NEW_EXCLUSIVE
    |   Crear Version exclusiva + EntityVersion.
    |
    */

    public function create(
        User $user,
        Entity $entity,
        array $payload
    ): array {

        return DB::transaction(
            function () use (
                $user,
                $entity,
                $payload
            ) {

                $mode =
                    $payload['definition_mode'];


                $createdDefinition =
                    false;


                /*
                |--------------------------------------------------------------------------
                | Resolver definición
                |--------------------------------------------------------------------------
                */

                if (
                    $mode
                    ===
                    'EXISTING'
                ) {

                    $version =
                        Version::query()
                        ->ownedBy(
                            $user
                        )
                        ->active()
                        ->findOrFail(
                            (int) $payload['version_id']
                        );
                } else {

                    $versionData =
                        $payload['version_data'];


                    $versionData['scope'] =
                        $mode
                        ===
                        'NEW_EXCLUSIVE'
                        ? 'EXCLUSIVE'
                        : 'SHARED';


                    /*
                     * Si la estás creando desde una Entidad
                     * debe quedar utilizable inmediatamente.
                     */
                    $versionData['status'] =
                        'ACTIVE';


                    $version =
                        $this
                        ->versionService
                        ->create(
                            $user,
                            $versionData,
                            $payload['catalog_links']
                                ?? []
                        );


                    $createdDefinition =
                        true;
                }


                /*
                |--------------------------------------------------------------------------
                | EntityVersion
                |--------------------------------------------------------------------------
                */

                $entityVersionData =
                    $payload['entity_version_data'];


                $entityVersionData['version_id'] =
                    $version->id;


                /*
                |--------------------------------------------------------------------------
                | Nombre automático
                |--------------------------------------------------------------------------
                */

                if (
                    trim(
                        (string) (
                            $entityVersionData['name']
                            ?? ''
                        )
                    )
                    ===
                    ''
                ) {

                    $entityVersionData['name'] =
                        $entity->name
                        . ' — '
                        . $version->name;
                }


                /*
                |--------------------------------------------------------------------------
                | Padre automático
                |--------------------------------------------------------------------------
                |
                | Ejemplo:
                |
                | Version padre:
                | Boruto
                |
                | Nueva Version:
                | Baryon
                |
                | Si Naruto ya tiene:
                | Naruto Boruto
                |
                | entonces:
                | Naruto Baryon.parent = Naruto Boruto
                |
                */

                if (
                    (
                        $payload['auto_parent']
                        ?? true
                    )
                    &&
                    empty($entityVersionData['parent_entity_version_id'])
                    &&
                    $version
                    ->parent_version_id
                ) {

                    $automaticParent =
                        EntityVersion::query()
                        ->where(
                            'entity_id',
                            $entity->id
                        )
                        ->where(
                            'version_id',
                            $version
                                ->parent_version_id
                        )
                        ->active()
                        ->orderByDesc(
                            'is_default'
                        )
                        ->orderByDesc(
                            'priority'
                        )
                        ->first();


                    if ($automaticParent) {

                        $entityVersionData['parent_entity_version_id'] =
                            $automaticParent->id;
                    }
                }


                $entityVersion =
                    $this
                    ->entityVersionService
                    ->create(
                        $user,
                        $entity,
                        $entityVersionData
                    );


                return [
                    'version' =>
                    $version,

                    'entity_version' =>
                    $entityVersion,

                    'created_definition' =>
                    $createdDefinition,
                ];
            }
        );
    }
}
