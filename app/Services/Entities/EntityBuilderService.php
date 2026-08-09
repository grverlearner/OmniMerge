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
    ) {}


    /*
    |--------------------------------------------------------------------------
    | CREAR UNA ENTIDAD
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


                return $this->createWithSequence(
                    $lockedUser,
                    $data,
                    $selectedAttributeIds,
                    $attributeInputs,
                    $collectionIds,
                    $sequence
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR MUCHAS ENTIDADES
    |--------------------------------------------------------------------------
    |
    | Una sola transacción.
    | Un solo bloqueo del usuario.
    | Secuencias consecutivas.
    |
    */

    public function createMany(
        User $user,
        array $commonData,
        array $rows,
        array $selectedAttributeIds = [],
        array $commonAttributeIds = [],
        array $commonAttributeInputs = [],
        array $collectionIds = [],
        string $duplicateStrategy = 'create'
    ): array {

        $selectedAttributeIds =
            $this->normalizeIds(
                $selectedAttributeIds
            );

        $commonAttributeIds =
            $this->normalizeIds(
                $commonAttributeIds
            );


        $commonAttributeSet =
            array_fill_keys(
                $commonAttributeIds,
                true
            );


        return DB::transaction(
            function () use (
                $user,
                $commonData,
                $rows,
                $selectedAttributeIds,
                $commonAttributeSet,
                $commonAttributeInputs,
                $collectionIds,
                $duplicateStrategy
            ) {

                /*
                |--------------------------------------------------------------------------
                | Bloquear usuario una sola vez
                |--------------------------------------------------------------------------
                */

                /** @var User $lockedUser */
                $lockedUser =
                    User::query()
                    ->whereKey(
                        $user->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | Primera secuencia disponible
                |--------------------------------------------------------------------------
                */

                $sequence =
                    $this->nextSequence(
                        $lockedUser->id
                    );


                $created = [];
                $skipped = [];


                foreach (
                    $rows
                    as $rowKey => $row
                ) {

                    $name =
                        trim(
                            (string) (
                                $row['name']
                                ?? ''
                            )
                        );


                    /*
                     * Las filas completamente vacías
                     * se ignoran.
                     */
                    if ($name === '') {
                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Duplicados por nombre
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $duplicateStrategy
                        === 'skip'
                    ) {

                        $alreadyExists =
                            Entity::query()
                            ->ownedBy(
                                $lockedUser
                            )
                            ->where(
                                'name',
                                $name
                            )
                            ->exists();


                        if ($alreadyExists) {

                            $skipped[$rowKey] =
                                $name;

                            continue;
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Datos de esta fila
                    |--------------------------------------------------------------------------
                    */

                    $data =
                        $commonData;


                    $data['name'] =
                        $name;


                    $description =
                        trim(
                            (string) (
                                $row['description']
                                ?? ''
                            )
                        );


                    $data['description'] =
                        $description !== ''
                        ? $description
                        : null;


                    /*
                     * Una fila puede sobrescribir
                     * el Tipo común.
                     */
                    if (
                        ! empty($row['entity_type_id'])
                    ) {

                        $data['entity_type_id'] =
                            (int) $row['entity_type_id'];
                    }


                    /*
                     * Imagen previamente almacenada
                     * por el Controller.
                     */
                    if (
                        ! empty($row['image'])
                    ) {

                        $data['image'] =
                            $row['image'];
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Valores de Atributos
                    |--------------------------------------------------------------------------
                    */

                    $rowInputs =
                        (array) (
                            $row['attributes']
                            ?? []
                        );


                    $attributeInputs = [];


                    foreach (
                        $selectedAttributeIds
                        as $attributeId
                    ) {

                        /*
                         * Si es común:
                         * el valor común gana.
                         */
                        if (
                            isset(
                                $commonAttributeSet[$attributeId]
                            )
                        ) {

                            $attributeInputs[$attributeId] =
                                $commonAttributeInputs[$attributeId]
                                ?? null;

                            continue;
                        }


                        /*
                         * Si no es común:
                         * toma el valor de la fila.
                         */
                        $attributeInputs[$attributeId] =
                            $rowInputs[$attributeId]
                            ?? null;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Crear
                    |--------------------------------------------------------------------------
                    */

                    $entity =
                        $this->createWithSequence(
                            $lockedUser,
                            $data,
                            $selectedAttributeIds,
                            $attributeInputs,
                            $collectionIds,
                            $sequence
                        );


                    $created[$rowKey] =
                        $entity;


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
    | ACTUALIZAR
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
                 * nunca se modifican.
                 */

                if (
                    $this->shouldPublish(
                        $data
                    )
                ) {

                    $data['published_at'] =
                        $entity->published_at
                        ?? now();
                } else {

                    $data['published_at'] =
                        null;
                }


                $entity->update(
                    $data
                );


                $this
                    ->attributeValueService
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
    | CREAR CON UNA SECUENCIA YA RESERVADA
    |--------------------------------------------------------------------------
    */

    private function createWithSequence(
        User $lockedUser,
        array $data,
        array $selectedAttributeIds,
        array $attributeInputs,
        array $collectionIds,
        int $sequence
    ): Entity {

        /*
        |--------------------------------------------------------------------------
        | Identidad
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Publicación
        |--------------------------------------------------------------------------
        */

        $data['published_at'] =
            $this->shouldPublish(
                $data
            )
            ? now()
            : null;


        /*
        |--------------------------------------------------------------------------
        | Crear Entidad
        |--------------------------------------------------------------------------
        */

        $entity =
            $lockedUser
            ->entities()
            ->create(
                $data
            );


        /*
        |--------------------------------------------------------------------------
        | Características
        |--------------------------------------------------------------------------
        */

        $this
            ->attributeValueService
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


    /*
    |--------------------------------------------------------------------------
    | PREVIEW DE CÓDIGO
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
    | SIGUIENTE SECUENCIA
    |--------------------------------------------------------------------------
    */

    private function nextSequence(
        int $userId
    ): int {

        $last =
            (int) Entity::withTrashed()
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
    | SLUG ÚNICO
    |--------------------------------------------------------------------------
    */

    private function uniqueSlug(
        int $userId,
        string $name
    ): string {

        $base =
            Str::slug(
                $name
            );


        if ($base === '') {

            $base =
                'entidad';
        }


        $slug =
            $base;

        $counter =
            2;


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
                $base
                . '-'
                . $counter;

            $counter++;
        }


        return $slug;
    }


    /*
    |--------------------------------------------------------------------------
    | PUBLICACIÓN
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
    | COLECCIONES
    |--------------------------------------------------------------------------
    */

    private function syncCollections(
        Entity $entity,
        array $collectionIds
    ): void {

        $collectionIds =
            collect(
                $collectionIds
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->filter()
            ->unique()
            ->values();


        /*
         * Mantener added_at
         * si ya pertenecía.
         */

        $existing =
            $entity
            ->collections()
            ->get()
            ->keyBy(
                'id'
            );


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

                'sort_order' => ($index + 1) * 10,

                'added_at' =>
                $existingCollection
                    ?->pivot
                    ?->added_at
                    ?? now(),
            ];
        }


        $entity
            ->collections()
            ->sync(
                $syncData
            );
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZAR IDS
    |--------------------------------------------------------------------------
    */

    private function normalizeIds(
        array $ids
    ): array {

        return collect(
            $ids
        )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
