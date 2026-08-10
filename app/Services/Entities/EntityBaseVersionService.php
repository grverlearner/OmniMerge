<?php

namespace App\Services\Entities;

use App\Models\Entity;
use App\Models\EntityBaseVersion;
use App\Models\EntityVersion;
use App\Models\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EntityBaseVersionService
{
    /*
    |--------------------------------------------------------------------------
    | Establecer una EntityVersion como Base activa
    |--------------------------------------------------------------------------
    */

    public function setActiveBase(
        User $user,
        Entity $entity,
        EntityVersion $entityVersion
    ): EntityBaseVersion {

        return DB::transaction(
            function () use (
                $user,
                $entity,
                $entityVersion
            ) {

                /*
                |--------------------------------------------------------------------------
                | Bloquear Entidad
                |--------------------------------------------------------------------------
                */

                $lockedEntity =
                    Entity::query()
                    ->whereKey(
                        $entity->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | Seguridad
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $lockedEntity->user_id
                    !==
                    (int) $user->id
                ) {

                    throw ValidationException::withMessages([
                        'entity' =>
                        'La Entidad no pertenece al usuario actual.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Obtener Version válida
                |--------------------------------------------------------------------------
                |
                | Debe:
                |
                | - pertenecer al usuario;
                | - pertenecer a esta Entidad;
                | - estar activa;
                | - no estar eliminada.
                |
                */

                $lockedEntityVersion =
                    EntityVersion::query()
                    ->whereKey(
                        $entityVersion->id
                    )
                    ->where(
                        'user_id',
                        $user->id
                    )
                    ->where(
                        'entity_id',
                        $lockedEntity->id
                    )
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->lockForUpdate()
                    ->first();


                if (! $lockedEntityVersion) {

                    throw ValidationException::withMessages([
                        'entity_version_id' =>
                        'La Version seleccionada no puede utilizarse como Base activa.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Una sola Base activa
                |--------------------------------------------------------------------------
                */

                $setting =
                    EntityBaseVersion::query()
                    ->updateOrCreate(
                        [
                            'entity_id' =>
                            $lockedEntity->id,
                        ],
                        [
                            'user_id' =>
                            $user->id,

                            'entity_version_id' =>
                            $lockedEntityVersion->id,
                        ]
                    );


                return $setting->load([
                    'entityVersion.version',
                ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Restaurar Base original
    |--------------------------------------------------------------------------
    */

    public function resetToOriginal(
        User $user,
        Entity $entity
    ): void {

        DB::transaction(
            function () use (
                $user,
                $entity
            ) {

                $lockedEntity =
                    Entity::query()
                    ->whereKey(
                        $entity->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                if (
                    (int) $lockedEntity->user_id
                    !==
                    (int) $user->id
                ) {

                    throw ValidationException::withMessages([
                        'entity' =>
                        'La Entidad no pertenece al usuario actual.',
                    ]);
                }


                EntityBaseVersion::query()
                    ->where(
                        'user_id',
                        $user->id
                    )
                    ->where(
                        'entity_id',
                        $lockedEntity->id
                    )
                    ->delete();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Restaurar Original solamente si esta Version es Base
    |--------------------------------------------------------------------------
    |
    | Se utiliza antes de:
    |
    | - eliminar una EntityVersion;
    | - archivarla;
    | - desactivarla.
    |
    */

    public function resetIfUsingVersion(
        User $user,
        EntityVersion $entityVersion
    ): bool {

        return DB::transaction(
            function () use (
                $user,
                $entityVersion
            ) {

                $setting =
                    EntityBaseVersion::query()
                    ->where(
                        'user_id',
                        $user->id
                    )
                    ->where(
                        'entity_id',
                        $entityVersion->entity_id
                    )
                    ->where(
                        'entity_version_id',
                        $entityVersion->id
                    )
                    ->lockForUpdate()
                    ->first();


                if (! $setting) {
                    return false;
                }


                $setting->delete();


                return true;
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Saber si una Version es la Base activa
    |--------------------------------------------------------------------------
    */

    public function isActiveBase(
        EntityVersion $entityVersion
    ): bool {

        return EntityBaseVersion::query()
            ->where(
                'entity_id',
                $entityVersion->entity_id
            )
            ->where(
                'entity_version_id',
                $entityVersion->id
            )
            ->exists();
    }
}
