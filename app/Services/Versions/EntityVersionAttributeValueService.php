<?php

namespace App\Services\Versions;

use App\Models\Attribute;
use App\Models\EntityVersion;
use App\Models\EntityVersionAttribute;
use App\Services\Entities\AttributeValueMapperService;
use Illuminate\Support\Facades\DB;

class EntityVersionAttributeValueService
{
    public function __construct(
        private readonly AttributeValueMapperService $mapper
    ) {}


    /*
    |--------------------------------------------------------------------------
    | Heredar
    |--------------------------------------------------------------------------
    |
    | INHERIT = no existe registro de sobrescritura.
    |
    */

    public function inherit(
        EntityVersion $entityVersion,
        Attribute $attribute
    ): void {

        $assignment =
            EntityVersionAttribute::query()
            ->where(
                'entity_version_id',
                $entityVersion->id
            )
            ->where(
                'attribute_id',
                $attribute->id
            )
            ->first();


        if (! $assignment) {
            return;
        }


        $assignment
            ->values()
            ->delete();

        $assignment->delete();
    }


    /*
    |--------------------------------------------------------------------------
    | Ocultar
    |--------------------------------------------------------------------------
    */

    public function hide(
        EntityVersion $entityVersion,
        Attribute $attribute
    ): EntityVersionAttribute {

        return DB::transaction(
            function () use (
                $entityVersion,
                $attribute
            ) {

                $assignment =
                    EntityVersionAttribute::query()
                    ->updateOrCreate(
                        [
                            'entity_version_id' =>
                            $entityVersion->id,

                            'attribute_id' =>
                            $attribute->id,
                        ],
                        [
                            'behavior' =>
                            'HIDE',

                            'sort_order' =>
                            $attribute->sort_order,

                            'is_visible' =>
                            false,

                            'is_featured' =>
                            false,
                        ]
                    );


                $assignment
                    ->values()
                    ->delete();


                return $assignment;
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Sobrescribir
    |--------------------------------------------------------------------------
    */

    public function override(
        EntityVersion $entityVersion,
        Attribute $attribute,
        mixed $input
    ): EntityVersionAttribute {

        return DB::transaction(
            function () use (
                $entityVersion,
                $attribute,
                $input
            ) {

                $mappedValues =
                    $this->mapper
                    ->mapMany(
                        $attribute,
                        $input,
                        true
                    );


                $assignment =
                    EntityVersionAttribute::query()
                    ->updateOrCreate(
                        [
                            'entity_version_id' =>
                            $entityVersion->id,

                            'attribute_id' =>
                            $attribute->id,
                        ],
                        [
                            'behavior' =>
                            'OVERRIDE',

                            'sort_order' =>
                            $attribute->sort_order,

                            'is_visible' =>
                            $attribute->is_visible,

                            'is_featured' =>
                            $attribute->is_featured,
                        ]
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
