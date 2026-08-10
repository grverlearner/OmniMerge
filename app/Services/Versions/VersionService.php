<?php

namespace App\Services\Versions;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\User;
use App\Models\Version;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VersionService
{
    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    */

    public function create(
        User $user,
        array $data,
        array $catalogLinks = []
    ): Version {

        return DB::transaction(
            function () use (
                $user,
                $data,
                $catalogLinks
            ) {

                /** @var User $lockedUser */
                $lockedUser =
                    User::query()
                    ->whereKey(
                        $user->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                $this->validateParent(
                    $lockedUser,
                    null,
                    $data['parent_version_id']
                        ?? null
                );


                $sequence =
                    $this->nextSequence(
                        $lockedUser->id
                    );


                $data['sequence_number'] =
                    $sequence;

                $data['code'] =
                    Version::formatCode(
                        $sequence
                    );

                $data['slug'] =
                    $this->uniqueSlug(
                        $lockedUser->id,
                        $data['name']
                    );


                $version =
                    $lockedUser
                    ->versions()
                    ->create(
                        $data
                    );


                $this->syncCatalogLinks(
                    $lockedUser,
                    $version,
                    $catalogLinks
                );


                return $version;
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
        Version $version,
        array $data,
        array $catalogLinks = []
    ): Version {

        return DB::transaction(
            function () use (
                $user,
                $version,
                $data,
                $catalogLinks
            ) {

                $this->validateParent(
                    $user,
                    $version,
                    $data['parent_version_id']
                        ?? null
                );


                if (
                    (
                        $data['scope']
                        ?? $version->scope
                    )
                    === 'EXCLUSIVE'
                    &&
                    $version
                    ->entityVersions()
                    ->count()
                    >
                    1
                ) {

                    throw ValidationException::withMessages([
                        'scope' =>
                        'No puedes convertir esta Versión en exclusiva porque ya está asociada a varias Entidades.',
                    ]);
                }


                if (
                    isset(
                        $data['name']
                    )
                    &&
                    $data['name']
                    !==
                    $version->name
                ) {

                    $data['slug'] =
                        $this->uniqueSlug(
                            $user->id,
                            $data['name'],
                            $version->id
                        );
                }


                $version->update(
                    $data
                );


                $this->syncCatalogLinks(
                    $user,
                    $version,
                    $catalogLinks
                );


                return $version
                    ->refresh();
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

        return Version::formatCode(
            $this->nextSequence(
                $user->id
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Catálogos
    |--------------------------------------------------------------------------
    */

    private function syncCatalogLinks(
        User $user,
        Version $version,
        array $links
    ): void {

        $version
            ->catalogLinks()
            ->delete();


        foreach (
            $links
            as $link
        ) {

            if (
                empty($link['attribute_id']
                    ?? null)
                ||
                empty($link['attribute_option_id']
                    ?? null)
            ) {
                continue;
            }


            $attribute =
                Attribute::query()
                ->ownedBy(
                    $user
                )
                ->active()
                ->findOrFail(
                    (int) $link['attribute_id']
                );


            $option =
                AttributeOption::query()
                ->ownedBy(
                    $user
                )
                ->active()
                ->where(
                    'attribute_id',
                    $attribute->id
                )
                ->findOrFail(
                    (int) $link['attribute_option_id']
                );


            $version
                ->catalogLinks()
                ->create([
                    'user_id' =>
                    $user->id,

                    'attribute_id' =>
                    $attribute->id,

                    'attribute_option_id' =>
                    $option->id,

                    'relation_type' =>
                    $link['relation_type']
                        ?? 'RELATED',

                    'condition_group' =>
                    max(
                        1,
                        (int) (
                            $link['condition_group']
                            ?? 1
                        )
                    ),

                    'logical_operator' =>
                    strtoupper(
                        (string) (
                            $link['logical_operator']
                            ?? 'AND'
                        )
                    )
                        === 'OR'
                        ? 'OR'
                        : 'AND',

                    'is_required' =>
                    filter_var(
                        $link['is_required']
                            ?? false,
                        FILTER_VALIDATE_BOOLEAN
                    ),

                    'priority' =>
                    (int) (
                        $link['priority']
                        ?? 0
                    ),
                ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Parent
    |--------------------------------------------------------------------------
    */

    private function validateParent(
        User $user,
        ?Version $version,
        mixed $parentId
    ): void {

        if (
            $parentId === null
            ||
            $parentId === ''
        ) {
            return;
        }


        $parent =
            Version::query()
            ->ownedBy(
                $user
            )
            ->findOrFail(
                (int) $parentId
            );


        if (
            $version
            &&
            $parent->is(
                $version
            )
        ) {

            throw ValidationException::withMessages([
                'parent_version_id' =>
                'Una Versión no puede ser su propia padre.',
            ]);
        }


        if (! $version) {
            return;
        }


        $visited = [];

        $current =
            $parent;


        while ($current) {

            if (
                $current->id
                ===
                $version->id
            ) {

                throw ValidationException::withMessages([
                    'parent_version_id' =>
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
    }


    /*
    |--------------------------------------------------------------------------
    | Secuencia
    |--------------------------------------------------------------------------
    */

    private function nextSequence(
        int $userId
    ): int {

        $last =
            (int) Version::withTrashed()
                ->where(
                    'user_id',
                    $userId
                )
                ->max(
                    'sequence_number'
                );


        return $last + 1;
    }


    /*
    |--------------------------------------------------------------------------
    | Slug
    |--------------------------------------------------------------------------
    */

    private function uniqueSlug(
        int $userId,
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
            Version::withTrashed()
            ->where(
                'user_id',
                $userId
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
