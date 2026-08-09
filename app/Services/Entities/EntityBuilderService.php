<?php

namespace App\Services\Entities;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EntityBuilderService
{
    public function __construct(
        private readonly EntityAttributeValueService $attributeValueService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Crear entidad completa
    |--------------------------------------------------------------------------
    */

    public function create(
        User $user,
        array $data,
        array $selectedAttributeIds = [],
        array $attributeInputs = [],
        array $collectionIds = []
    ): Entity {
        return DB::transaction(
            function () use (
                $user,
                $data,
                $selectedAttributeIds,
                $attributeInputs,
                $collectionIds
            ) {
                /*
                 * Bloqueo para evitar dos ENT000001
                 * simultáneos.
                 */

                /** @var User $lockedUser */
                $lockedUser = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $sequence = $this->nextSequence(
                    $lockedUser->id
                );

                $data['sequence_number'] =
                    $sequence;

                $data['code'] =
                    Entity::formatCode(
                        $sequence
                    );

                $data['slug'] =
                    $this->uniqueSlug(
                        $lockedUser->id,
                        $data['name']
                    );

                $data['published_at'] =
                    $this->shouldPublish($data)
                        ? now()
                        : null;

                $entity = $lockedUser
                    ->entities()
                    ->create($data);

                /*
                |--------------------------------------------------------------------------
                | Características
                |--------------------------------------------------------------------------
                */

                $this->attributeValueService
                    ->sync(
                        $entity,
                        $lockedUser,
                        $selectedAttributeIds,
                        $attributeInputs
                    );

                /*
                |--------------------------------------------------------------------------
                | Colecciones
                |--------------------------------------------------------------------------
                */

                $this->syncCollections(
                    $entity,
                    $collectionIds
                );

                return $entity;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar entidad completa
    |--------------------------------------------------------------------------
    */

    public function update(
        User $user,
        Entity $entity,
        array $data,
        array $selectedAttributeIds = [],
        array $attributeInputs = [],
        array $collectionIds = []
    ): Entity {
        return DB::transaction(
            function () use (
                $user,
                $entity,
                $data,
                $selectedAttributeIds,
                $attributeInputs,
                $collectionIds
            ) {
                /*
                 * Código, slug y sequence_number
                 * nunca vienen desde el request.
                 */

                if ($this->shouldPublish($data)) {
                    $data['published_at'] =
                        $entity->published_at
                        ?? now();
                } else {
                    $data['published_at'] =
                        null;
                }

                $entity->update($data);

                $this->attributeValueService
                    ->sync(
                        $entity,
                        $user,
                        $selectedAttributeIds,
                        $attributeInputs
                    );

                $this->syncCollections(
                    $entity,
                    $collectionIds
                );

                return $entity;
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
        return Entity::formatCode(
            $this->nextSequence(
                $user->id
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Siguiente secuencia
    |--------------------------------------------------------------------------
    */

    private function nextSequence(
        int $userId
    ): int {
        $last = (int) Entity::withTrashed()
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
        string $name
    ): string {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'entidad';
        }

        $slug = $base;
        $counter = 2;

        while (
            Entity::withTrashed()
                ->where(
                    'user_id',
                    $userId
                )
                ->where(
                    'slug',
                    $slug
                )
                ->exists()
        ) {
            $slug =
                $base.'-'.$counter;

            $counter++;
        }

        return $slug;
    }

    /*
    |--------------------------------------------------------------------------
    | Publicación
    |--------------------------------------------------------------------------
    */

    private function shouldPublish(
        array $data
    ): bool {
        return (
            $data['visibility']
            ?? null
        ) === 'PUBLIC'
            &&
        (
            $data['status']
            ?? null
        ) === 'ACTIVE';
    }

    /*
    |--------------------------------------------------------------------------
    | Colecciones
    |--------------------------------------------------------------------------
    */

    private function syncCollections(
        Entity $entity,
        array $collectionIds
    ): void {
        $collectionIds = collect(
            $collectionIds
        )
            ->map(
                fn ($id) => (int) $id
            )
            ->filter()
            ->unique()
            ->values();

        /*
         * Mantener added_at si ya pertenecía.
         */

        $existing = $entity
            ->collections()
            ->get()
            ->keyBy('id');

        $syncData = [];

        foreach (
            $collectionIds
            as $index => $collectionId
        ) {
            $existingCollection =
                $existing->get(
                    $collectionId
                );

            $syncData[$collectionId] = [
                'sort_order' =>
                    ($index + 1) * 10,

                'added_at' =>
                    $existingCollection?->pivot?->added_at
                    ?? now(),
            ];
        }

        $entity
            ->collections()
            ->sync(
                $syncData
            );
    }
}