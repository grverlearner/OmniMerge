<?php

namespace App\Services\Entities;

use App\Models\Attribute;
use App\Models\Entity;
use App\Models\EntityAttribute;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EntityAttributeValueService
{
    public function __construct(
        private readonly AttributeValueMapperService $mapper
    ) {}


    /*
    |--------------------------------------------------------------------------
    | Sincronización completa
    |--------------------------------------------------------------------------
    */

    public function sync(
        Entity $entity,
        User $user,
        array $selectedAttributeIds,
        array $inputs
    ): void {

        $selectedIds =
            collect(
                $selectedAttributeIds
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->filter()
            ->unique()
            ->values();


        if ($selectedIds->isEmpty()) {

            $entity
                ->entityAttributes()
                ->delete();

            return;
        }


        $attributes =
            Attribute::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->whereIn(
                'id',
                $selectedIds
            )
            ->orderBy(
                'sort_order'
            )
            ->get();


        if (
            $attributes->count()
            !==
            $selectedIds->count()
        ) {

            throw ValidationException::withMessages([
                'selected_attribute_ids' =>
                'Uno o más atributos seleccionados no son válidos.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Quitar deseleccionados
        |--------------------------------------------------------------------------
        */

        $entity
            ->entityAttributes()
            ->whereNotIn(
                'attribute_id',
                $selectedIds
            )
            ->delete();


        /*
        |--------------------------------------------------------------------------
        | Guardar
        |--------------------------------------------------------------------------
        */

        foreach (
            $attributes
            as $attribute
        ) {

            $this->save(
                $entity,
                $attribute,
                $inputs[$attribute->id]
                    ?? null
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Guardado puntual
    |--------------------------------------------------------------------------
    */

    public function save(
        Entity $entity,
        Attribute $attribute,
        mixed $input
    ): EntityAttribute {

        return DB::transaction(
            function () use (
                $entity,
                $attribute,
                $input
            ) {

                $assignment =
                    EntityAttribute::query()
                    ->firstOrCreate(
                        [
                            'entity_id' =>
                            $entity->id,

                            'attribute_id' =>
                            $attribute->id,
                        ],
                        [
                            'sort_order' =>
                            $attribute->sort_order,

                            'is_visible' =>
                            $attribute->is_visible,

                            'is_featured' =>
                            $attribute->is_featured,
                        ]
                    );


                $mappedValues =
                    $this->mapper
                    ->mapMany(
                        $attribute,
                        $input,
                        true
                    );


                $assignment
                    ->values()
                    ->delete();


                foreach (
                    $mappedValues
                    as $value
                ) {

                    $assignment
                        ->values()
                        ->create(
                            $value
                        );
                }


                return $assignment;
            }
        );
    }
}
