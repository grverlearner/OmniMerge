<?php

namespace App\Services\Versions;

use App\Models\Attribute;
use App\Models\Entity;
use App\Models\EntityAttributeValue;
use App\Models\EntityVersion;
use App\Models\EntityVersionAttributeValue;
use App\Models\Version;
use Illuminate\Support\Collection;

class VersionResolverService
{
    /*
    |--------------------------------------------------------------------------
    | Resolver por elementos de Catálogo
    |--------------------------------------------------------------------------
    |
    | Ejemplo:
    |
    | Entity = Naruto
    |
    | optionIds = [
    |     ID de "Naruto Shippuden"
    | ]
    |
    | Resultado:
    |
    | Naruto — Shippuden
    |
    */

    public function resolve(
        Entity $entity,
        array $optionIds = [],
        ?Version $explicitVersion = null,
        bool $fallbackToDefault = true
    ): ?EntityVersion {

        $optionIds =
            collect(
                $optionIds
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->filter()
            ->unique()
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Selección explícita
        |--------------------------------------------------------------------------
        */

        if ($explicitVersion) {

            return EntityVersion::query()
                ->where(
                    'entity_id',
                    $entity->id
                )
                ->where(
                    'version_id',
                    $explicitVersion->id
                )
                ->active()
                ->first();
        }


        /*
        |--------------------------------------------------------------------------
        | Candidatas
        |--------------------------------------------------------------------------
        */

        if ($optionIds->isNotEmpty()) {

            $candidates =
                EntityVersion::query()
                ->where(
                    'entity_id',
                    $entity->id
                )
                ->active()
                ->whereHas(
                    'version',
                    fn($query) =>
                    $query
                        ->active()
                        ->whereIn(
                            'activation_mode',
                            [
                                'AUTO',
                                'BOTH',
                            ]
                        )
                )
                ->with([
                    'version.catalogLinks',
                ])
                ->get()
                ->filter(
                    fn(
                        EntityVersion $entityVersion
                    ) =>
                    $this->matchesActivation(
                        $entityVersion->version,
                        $optionIds
                    )
                )
                ->sortByDesc(
                    fn(
                        EntityVersion $entityVersion
                    ) => (
                        (int) $entityVersion
                            ->priority
                    )
                        +
                        (
                            (int) $entityVersion
                                ->version
                                ->priority
                        )
                );


            $resolved =
                $candidates->first();


            if ($resolved) {
                return $resolved;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Fallback
        |--------------------------------------------------------------------------
        */

        if ($fallbackToDefault) {

            return EntityVersion::query()
                ->where(
                    'entity_id',
                    $entity->id
                )
                ->active()
                ->where(
                    'is_default',
                    true
                )
                ->first();
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Características efectivas
    |--------------------------------------------------------------------------
    |
    | 1. Base.
    | 2. Padres.
    | 3. Actual.
    |
    */

    public function effectiveAttributes(
        EntityVersion $entityVersion
    ): Collection {

        $entityVersion->loadMissing([
            'entity.entityAttributes.attribute.groups',
            'entity.entityAttributes.values.option',

            'versionAttributes.attribute.groups',
            'versionAttributes.values.option',

            'parent.versionAttributes.attribute.groups',
            'parent.versionAttributes.values.option',
        ]);


        $effective =
            collect();


        /*
        |--------------------------------------------------------------------------
        | BASE
        |--------------------------------------------------------------------------
        */

        if (
            $entityVersion
            ->inherit_base_attributes
        ) {

            foreach (
                $entityVersion
                    ->entity
                    ->entityAttributes
                as $assignment
            ) {

                $attribute =
                    $assignment->attribute;


                if (! $attribute) {
                    continue;
                }


                $effective->put(
                    $attribute->id,
                    [
                        'attribute' =>
                        $attribute,

                        'values' =>
                        $this->entityRawValues(
                            $assignment->values,
                            $attribute
                        ),

                        'display' =>
                        $assignment
                            ->values
                            ->map(
                                fn(
                                    EntityAttributeValue $value
                                ) =>
                                $value->displayValue()
                            )
                            ->filter()
                            ->implode(
                                ', '
                            ),

                        'source' =>
                        'BASE',

                        'source_name' =>
                        'Entidad base',

                        'sort_order' =>
                        $assignment->sort_order,

                        'custom_label' =>
                        $assignment->custom_label,

                        'is_visible' =>
                        $assignment->is_visible,

                        'is_featured' =>
                        $assignment->is_featured,
                    ]
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Cadena de Versiones
        |--------------------------------------------------------------------------
        */

        foreach (
            $this->versionChain(
                $entityVersion
            )
            as $versionNode
        ) {

            $versionNode->loadMissing([
                'versionAttributes.attribute.groups',
                'versionAttributes.values.option',
            ]);


            foreach (
                $versionNode
                    ->versionAttributes
                as $assignment
            ) {

                $attribute =
                    $assignment->attribute;


                if (! $attribute) {
                    continue;
                }


                /*
                 * HIDE elimina el Atributo del resultado.
                 */
                if (
                    $assignment->behavior
                    === 'HIDE'
                ) {

                    $effective->forget(
                        $attribute->id
                    );

                    continue;
                }


                $effective->put(
                    $attribute->id,
                    [
                        'attribute' =>
                        $attribute,

                        'values' =>
                        $this->versionRawValues(
                            $assignment->values,
                            $attribute
                        ),

                        'display' =>
                        $assignment
                            ->values
                            ->map(
                                fn(
                                    EntityVersionAttributeValue $value
                                ) =>
                                $value->displayValue()
                            )
                            ->filter()
                            ->implode(
                                ', '
                            ),

                        'source' =>
                        'VERSION',

                        'source_name' =>
                        $versionNode->name,

                        'sort_order' =>
                        $assignment->sort_order,

                        'custom_label' =>
                        $assignment->custom_label,

                        'is_visible' =>
                        $assignment->is_visible,

                        'is_featured' =>
                        $assignment->is_featured,
                    ]
                );
            }
        }


        return $effective
            ->sortBy(
                fn($item) =>
                $item['sort_order']
                    ?? 0
            )
            ->values();
    }


    /*
    |--------------------------------------------------------------------------
    | Activación
    |--------------------------------------------------------------------------
    */

    private function matchesActivation(
        Version $version,
        Collection $optionIds
    ): bool {

        $links =
            $version
            ->catalogLinks
            ->where(
                'relation_type',
                'ACTIVATES'
            );


        if ($links->isEmpty()) {
            return false;
        }


        $groups =
            $links->groupBy(
                'condition_group'
            );


        /*
         * Los grupos funcionan como alternativas OR.
         * Dentro de cada grupo se evalúa AND/OR.
         */
        foreach (
            $groups
            as $group
        ) {

            $result =
                null;


            foreach (
                $group
                as $link
            ) {

                $matches =
                    $optionIds
                    ->contains(
                        (int) $link
                            ->attribute_option_id
                    );


                if ($result === null) {

                    $result =
                        $matches;

                    continue;
                }


                if (
                    $link->logical_operator
                    === 'OR'
                ) {

                    $result =
                        $result
                        ||
                        $matches;
                } else {

                    $result =
                        $result
                        &&
                        $matches;
                }
            }


            if ($result === true) {
                return true;
            }
        }


        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Cadena padre → hijo
    |--------------------------------------------------------------------------
    */

    private function versionChain(
        EntityVersion $entityVersion
    ): Collection {

        $chain =
            collect();

        $visited = [];

        $current =
            $entityVersion;


        while ($current) {

            if (
                isset(
                    $visited[$current->id]
                )
            ) {
                break;
            }


            $visited[$current->id] =
                true;


            $chain->prepend(
                $current
            );


            $current =
                $current->parent;
        }


        return $chain;
    }


    private function entityRawValues(
        Collection $values,
        Attribute $attribute
    ): array {

        return $values
            ->map(
                fn(
                    EntityAttributeValue $value
                ) =>
                $this->rawValue(
                    $attribute,
                    $value
                )
            )
            ->filter(
                fn($value) =>
                $value !== null
                    &&
                    $value !== ''
            )
            ->values()
            ->all();
    }


    private function versionRawValues(
        Collection $values,
        Attribute $attribute
    ): array {

        return $values
            ->map(
                fn(
                    EntityVersionAttributeValue $value
                ) =>
                $this->rawValue(
                    $attribute,
                    $value
                )
            )
            ->filter(
                fn($value) =>
                $value !== null
                    &&
                    $value !== ''
            )
            ->values()
            ->all();
    }


    private function rawValue(
        Attribute $attribute,
        mixed $value
    ): mixed {

        return match ($attribute->data_type) {

            'OPTION' =>
            $value->attribute_option_id,

            'INTEGER' =>
            $value->integer_value,

            'DECIMAL' =>
            $value->decimal_value,

            'BOOLEAN' =>
            $value->boolean_value === null
                ? null
                : (
                    $value->boolean_value
                    ? '1'
                    : '0'
                ),

            'DATE' =>
            $value
                ->date_value
                ?->format(
                    'Y-m-d'
                ),

            'COLOR' =>
            $value->color_value,

            default =>
            $value->text_value
                ??
                $value->custom_value,
        };
    }
}
