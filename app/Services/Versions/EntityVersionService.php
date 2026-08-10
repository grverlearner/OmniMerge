<?php

namespace App\Services\Versions;

use App\Models\Entity;
use App\Models\EntityVersion;
use App\Models\User;
use App\Models\Version;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EntityVersionService
{
    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    */

    public function create(
        User $user,
        Entity $entity,
        array $data
    ): EntityVersion {

        return DB::transaction(
            function () use (
                $user,
                $entity,
                $data
            ) {

                $this->validateOwnership(
                    $user,
                    $entity
                );


                $version =
                    $this->version(
                        $user,
                        (int) $data['version_id']
                    );


                $this->validateAssociation(
                    $entity,
                    $version
                );


                $parent =
                    $this->validateParent(
                        $entity,
                        null,
                        $data['parent_entity_version_id']
                            ?? null
                    );


                /** @var User $lockedUser */
                $lockedUser =
                    User::query()
                    ->whereKey(
                        $user->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                $sequence =
                    $this->nextSequence(
                        $lockedUser->id
                    );


                $data['user_id'] =
                    $lockedUser->id;

                $data['entity_id'] =
                    $entity->id;

                $data['parent_entity_version_id'] =
                    $parent?->id;

                $data['sequence_number'] =
                    $sequence;

                $data['code'] =
                    EntityVersion::formatCode(
                        $sequence
                    );

                $data['slug'] =
                    $this->uniqueSlug(
                        $entity,
                        $data['name']
                    );


                if (
                    $data['is_default']
                    ?? false
                ) {

                    EntityVersion::query()
                        ->where(
                            'entity_id',
                            $entity->id
                        )
                        ->update([
                            'is_default' =>
                            false,
                        ]);
                }


                return EntityVersion::query()
                    ->create(
                        $data
                    );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function update(
        User $user,
        EntityVersion $entityVersion,
        array $data
    ): EntityVersion {

        return DB::transaction(
            function () use (
                $user,
                $entityVersion,
                $data
            ) {

                $entity =
                    $entityVersion->entity;


                $this->validateOwnership(
                    $user,
                    $entity
                );


                if (
                    isset(
                        $data['version_id']
                    )
                    &&
                    (int) $data['version_id']
                    !==
                    $entityVersion->version_id
                ) {

                    $version =
                        $this->version(
                            $user,
                            (int) $data['version_id']
                        );


                    $exists =
                        EntityVersion::withTrashed()
                        ->where(
                            'entity_id',
                            $entity->id
                        )
                        ->where(
                            'version_id',
                            $version->id
                        )
                        ->whereKeyNot(
                            $entityVersion->id
                        )
                        ->exists();


                    if ($exists) {

                        throw ValidationException::withMessages([
                            'version_id' =>
                            'Esta Entidad ya tiene esta Versión.',
                        ]);
                    }


                    if (
                        $version->isExclusive()
                    ) {

                        $used =
                            EntityVersion::query()
                            ->where(
                                'version_id',
                                $version->id
                            )
                            ->whereKeyNot(
                                $entityVersion->id
                            )
                            ->exists();


                        if ($used) {

                            throw ValidationException::withMessages([
                                'version_id' =>
                                'Esta Versión es exclusiva y ya pertenece a otra Entidad.',
                            ]);
                        }
                    }
                }


                $parent =
                    $this->validateParent(
                        $entity,
                        $entityVersion,
                        $data['parent_entity_version_id']
                            ?? null
                    );


                $data['parent_entity_version_id'] =
                    $parent?->id;


                if (
                    isset(
                        $data['name']
                    )
                    &&
                    $data['name']
                    !==
                    $entityVersion->name
                ) {

                    $data['slug'] =
                        $this->uniqueSlug(
                            $entity,
                            $data['name'],
                            $entityVersion->id
                        );
                }


                if (
                    $data['is_default']
                    ?? false
                ) {

                    EntityVersion::query()
                        ->where(
                            'entity_id',
                            $entity->id
                        )
                        ->whereKeyNot(
                            $entityVersion->id
                        )
                        ->update([
                            'is_default' =>
                            false,
                        ]);
                }


                $entityVersion->update(
                    $data
                );


                return $entityVersion
                    ->refresh();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Crear muchas
    |--------------------------------------------------------------------------
    */

    public function createMany(
        User $user,
        Version $version,
        array $rows
    ): array {

        return DB::transaction(
            function () use (
                $user,
                $version,
                $rows
            ) {

                if (
                    $version->user_id
                    !==
                    $user->id
                ) {

                    throw ValidationException::withMessages([
                        'version' =>
                        'La Versión no te pertenece.',
                    ]);
                }


                if (
                    $version->isExclusive()
                    &&
                    count($rows)
                    >
                    1
                ) {

                    throw ValidationException::withMessages([
                        'entity_ids' =>
                        'Una Versión exclusiva solamente puede asociarse a una Entidad.',
                    ]);
                }


                /** @var User $lockedUser */
                $lockedUser =
                    User::query()
                    ->whereKey(
                        $user->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                $sequence =
                    $this->nextSequence(
                        $lockedUser->id
                    );


                $created = [];
                $skipped = [];


                foreach (
                    $rows
                    as $row
                ) {

                    $entity =
                        Entity::query()
                        ->ownedBy(
                            $lockedUser
                        )
                        ->findOrFail(
                            (int) $row['entity_id']
                        );


                    $exists =
                        EntityVersion::withTrashed()
                        ->where(
                            'entity_id',
                            $entity->id
                        )
                        ->where(
                            'version_id',
                            $version->id
                        )
                        ->exists();


                    if ($exists) {

                        $skipped[] =
                            $entity;

                        continue;
                    }


                    if (
                        $version->isExclusive()
                        &&
                        EntityVersion::query()
                        ->where(
                            'version_id',
                            $version->id
                        )
                        ->exists()
                    ) {

                        throw ValidationException::withMessages([
                            'entity_ids' =>
                            'La Versión exclusiva ya está asociada a una Entidad.',
                        ]);
                    }


                    $name =
                        trim(
                            (string) (
                                $row['name']
                                ?? ''
                            )
                        );


                    if ($name === '') {

                        $name =
                            $entity->name
                            . ' — '
                            . $version->name;
                    }


                    $entityVersion =
                        EntityVersion::query()
                        ->create([
                            'user_id' =>
                            $lockedUser->id,

                            'entity_id' =>
                            $entity->id,

                            'version_id' =>
                            $version->id,

                            'sequence_number' =>
                            $sequence,

                            'code' =>
                            EntityVersion::formatCode(
                                $sequence
                            ),

                            'name' =>
                            $name,

                            'slug' =>
                            $this->uniqueSlug(
                                $entity,
                                $name
                            ),

                            'description' =>
                            $row['description']
                                ?? null,

                            'image' =>
                            $row['image'],

                            'inherit_base_attributes' =>
                            true,

                            'is_default' =>
                            false,

                            'priority' =>
                            $version->priority,

                            'sort_order' =>
                            0,

                            'status' =>
                            'ACTIVE',
                        ]);


                    $created[] =
                        $entityVersion;

                    $sequence++;
                }


                return [
                    'created' =>
                    $created,

                    'skipped' =>
                    $skipped,
                ];
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Preview
    |--------------------------------------------------------------------------
    */

    public function nextCode(
        User $user
    ): string {

        return EntityVersion::formatCode(
            $this->nextSequence(
                $user->id
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    private function validateOwnership(
        User $user,
        Entity $entity
    ): void {

        if (
            $entity->user_id
            !==
            $user->id
        ) {

            throw ValidationException::withMessages([
                'entity' =>
                'La Entidad no te pertenece.',
            ]);
        }
    }


    private function version(
        User $user,
        int $versionId
    ): Version {

        return Version::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->findOrFail(
                $versionId
            );
    }


    private function validateAssociation(
        Entity $entity,
        Version $version
    ): void {

        $exists =
            EntityVersion::withTrashed()
            ->where(
                'entity_id',
                $entity->id
            )
            ->where(
                'version_id',
                $version->id
            )
            ->exists();


        if ($exists) {

            throw ValidationException::withMessages([
                'version_id' =>
                'Esta Entidad ya tiene esta Versión.',
            ]);
        }


        if (
            $version->isExclusive()
            &&
            EntityVersion::query()
            ->where(
                'version_id',
                $version->id
            )
            ->exists()
        ) {

            throw ValidationException::withMessages([
                'version_id' =>
                'Esta Versión es exclusiva y ya está asociada a otra Entidad.',
            ]);
        }
    }


    private function validateParent(
        Entity $entity,
        ?EntityVersion $entityVersion,
        mixed $parentId
    ): ?EntityVersion {

        if (
            $parentId === null
            ||
            $parentId === ''
        ) {
            return null;
        }


        $parent =
            EntityVersion::query()
            ->where(
                'entity_id',
                $entity->id
            )
            ->findOrFail(
                (int) $parentId
            );


        if (
            $entityVersion
            &&
            $parent->is(
                $entityVersion
            )
        ) {

            throw ValidationException::withMessages([
                'parent_entity_version_id' =>
                'Una Versión concreta no puede ser su propia padre.',
            ]);
        }


        if (! $entityVersion) {
            return $parent;
        }


        $visited = [];

        $current =
            $parent;


        while ($current) {

            if (
                $current->id
                ===
                $entityVersion->id
            ) {

                throw ValidationException::withMessages([
                    'parent_entity_version_id' =>
                    'La jerarquía produciría un ciclo.',
                ]);
            }


            if (
                isset(
                    $visited[$current->id]
                )
            ) {
                break;
            }


            $visited[$current->id] =
                true;


            $current =
                $current->parent;
        }


        return $parent;
    }


    private function nextSequence(
        int $userId
    ): int {

        $last =
            (int) EntityVersion::withTrashed()
                ->where(
                    'user_id',
                    $userId
                )
                ->max(
                    'sequence_number'
                );


        return $last + 1;
    }


    private function uniqueSlug(
        Entity $entity,
        string $name,
        ?int $ignoreId = null
    ): string {

        $base =
            Str::slug(
                $name
            )
            ?: 'version';


        $slug =
            $base;

        $counter =
            2;


        while (
            EntityVersion::withTrashed()
            ->where(
                'entity_id',
                $entity->id
            )
            ->when(
                $ignoreId,
                fn($query) =>
                $query->whereKeyNot(
                    $ignoreId
                )
            )
            ->where(
                'slug',
                $slug
            )
            ->exists()
        ) {

            $slug =
                $base
                . '-'
                . $counter;

            $counter++;
        }


        return $slug;
    }
}
