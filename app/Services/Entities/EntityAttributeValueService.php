<?php

namespace App\Services\Entities;

use App\Models\Attribute;
use App\Models\Entity;
use App\Models\EntityAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EntityAttributeValueService
{
    public function save(
        Entity $entity,
        Attribute $attribute,
        mixed $input
    ): EntityAttribute {
        return DB::transaction(function () use (
            $entity,
            $attribute,
            $input
        ) {
            $assignment = EntityAttribute::query()
                ->firstOrCreate(
                    [
                        'entity_id' => $entity->id,
                        'attribute_id' => $attribute->id,
                    ],
                    [
                        'sort_order' => $attribute->sort_order,
                        'is_visible' => $attribute->is_visible,
                        'is_featured' => $attribute->is_featured,
                    ]
                );

            $assignment->values()->delete();

            $values = $attribute->allows_multiple
                ? (array) $input
                : [$input];

            $values = array_values(array_filter(
                $values,
                fn ($value) =>
                    $value !== null
                    && $value !== ''
            ));

            if (
                $attribute->is_required
                && count($values) === 0
            ) {
                throw ValidationException::withMessages([
                    "attributes.{$attribute->id}" =>
                        "El atributo {$attribute->name} es obligatorio.",
                ]);
            }

            foreach ($values as $index => $value) {
                $assignment->values()->create(
                    $this->mapValue(
                        $attribute,
                        $value,
                        $index
                    )
                );
            }

            return $assignment;
        });
    }

    private function mapValue(
        Attribute $attribute,
        mixed $value,
        int $sortOrder
    ): array {
        $data = [
            'sort_order' => $sortOrder,
        ];

        switch ($attribute->data_type) {
            case 'OPTION':
                $option = $attribute->options()
                    ->whereKey($value)
                    ->where('status', 'ACTIVE')
                    ->first();

                if (! $option) {
                    throw ValidationException::withMessages([
                        "attributes.{$attribute->id}" =>
                            'La opción seleccionada no es válida.',
                    ]);
                }

                $data['attribute_option_id'] =
                    $option->id;
                break;

            case 'INTEGER':
                $data['integer_value'] = (int) $value;
                break;

            case 'DECIMAL':
                $data['decimal_value'] = $value;
                break;

            case 'BOOLEAN':
                $data['boolean_value'] = filter_var(
                    $value,
                    FILTER_VALIDATE_BOOLEAN
                );
                break;

            case 'DATE':
                $data['date_value'] = $value;
                break;

            case 'COLOR':
                $data['color_value'] = $value;
                break;

            case 'TEXT':
            case 'LONG_TEXT':
            default:
                $data['text_value'] = (string) $value;
                break;
        }

        return $data;
    }
}