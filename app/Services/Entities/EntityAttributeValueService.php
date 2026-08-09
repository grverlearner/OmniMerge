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
    /*
    |--------------------------------------------------------------------------
    | Sincronizar características de una entidad
    |--------------------------------------------------------------------------
    */

    public function sync(
        Entity $entity,
        User $user,
        array $selectedAttributeIds,
        array $inputs
    ): void {
        $selectedIds = collect(
            $selectedAttributeIds
        )
            ->map(
                fn ($id) => (int) $id
            )
            ->filter()
            ->unique()
            ->values();

        /*
         * Si no seleccionó ninguno,
         * se eliminan todas las asignaciones.
         */

        if ($selectedIds->isEmpty()) {
            $entity
                ->entityAttributes()
                ->delete();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Solo atributos del propietario
        |--------------------------------------------------------------------------
        */

        $attributes = Attribute::query()
            ->ownedBy($user)
            ->active()
            ->whereIn(
                'id',
                $selectedIds
            )
            ->orderBy('sort_order')
            ->get();

        if (
            $attributes->count()
            !== $selectedIds->count()
        ) {
            throw ValidationException::withMessages([
                'selected_attribute_ids' =>
                    'Uno o más atributos seleccionados no son válidos.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Quitar atributos deseleccionados
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
        | Guardar seleccionados
        |--------------------------------------------------------------------------
        */

        foreach ($attributes as $attribute) {
            $this->save(
                $entity,
                $attribute,
                $inputs[$attribute->id] ?? null
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar un atributo
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

                /*
                 * Si cambió el atributo,
                 * reconstruimos sus valores.
                 */

                $assignment
                    ->values()
                    ->delete();

                /*
                 * Normalizar valores.
                 */

                if ($attribute->allows_multiple) {
                    $values = (array) $input;
                } else {
                    if (is_array($input)) {
                        $input = collect($input)
                            ->first();
                    }

                    $values = [$input];
                }

                $values = array_values(
                    array_filter(
                        $values,
                        fn ($value) =>
                            $value !== null
                            && $value !== ''
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | Requerido solamente si el atributo fue asignado
                |--------------------------------------------------------------------------
                */

                if (
                    $attribute->is_required
                    && count($values) === 0
                ) {
                    throw ValidationException::withMessages([
                        "attributes.{$attribute->id}" =>
                            "El atributo {$attribute->name} requiere un valor.",
                    ]);
                }

                foreach (
                    $values
                    as $index => $value
                ) {
                    $assignment
                        ->values()
                        ->create(
                            $this->mapValue(
                                $attribute,
                                $value,
                                $index
                            )
                        );
                }

                return $assignment;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Traducir valor
    |--------------------------------------------------------------------------
    */

    private function mapValue(
        Attribute $attribute,
        mixed $value,
        int $sortOrder
    ): array {
        $data = [
            'sort_order' => $sortOrder,
        ];

        switch ($attribute->data_type) {
            /*
            |--------------------------------------------------------------------------
            | Catálogo
            |--------------------------------------------------------------------------
            */

            case 'OPTION':
                $option = $attribute
                    ->options()
                    ->whereKey($value)
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->first();

                if (! $option) {
                    throw ValidationException::withMessages([
                        "attributes.{$attribute->id}" =>
                            'El elemento de Catálogo seleccionado no es válido.',
                    ]);
                }

                $data['attribute_option_id'] =
                    $option->id;

                break;

            /*
            |--------------------------------------------------------------------------
            | Entero
            |--------------------------------------------------------------------------
            */

            case 'INTEGER':
                if (! is_numeric($value)) {
                    $this->invalid(
                        $attribute,
                        'Debe ingresar un número entero válido.'
                    );
                }

                $numericValue = (int) $value;

                $this->validateNumericRange(
                    $attribute,
                    $numericValue
                );

                $data['integer_value'] =
                    $numericValue;

                break;

            /*
            |--------------------------------------------------------------------------
            | Decimal
            |--------------------------------------------------------------------------
            */

            case 'DECIMAL':
                if (! is_numeric($value)) {
                    $this->invalid(
                        $attribute,
                        'Debe ingresar un número válido.'
                    );
                }

                $numericValue = (float) $value;

                $this->validateNumericRange(
                    $attribute,
                    $numericValue
                );

                $data['decimal_value'] =
                    $numericValue;

                break;

            /*
            |--------------------------------------------------------------------------
            | Boolean
            |--------------------------------------------------------------------------
            */

            case 'BOOLEAN':
                $boolean = filter_var(
                    $value,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );

                if ($boolean === null) {
                    $this->invalid(
                        $attribute,
                        'Selecciona Sí o No.'
                    );
                }

                $data['boolean_value'] =
                    $boolean;

                break;

            /*
            |--------------------------------------------------------------------------
            | Fecha
            |--------------------------------------------------------------------------
            */

            case 'DATE':
                if (
                    ! is_string($value)
                    || ! strtotime($value)
                ) {
                    $this->invalid(
                        $attribute,
                        'La fecha ingresada no es válida.'
                    );
                }

                $data['date_value'] =
                    $value;

                break;

            /*
            |--------------------------------------------------------------------------
            | Color
            |--------------------------------------------------------------------------
            */

            case 'COLOR':
                if (
                    ! is_string($value)
                    || ! preg_match(
                        '/^#[0-9A-Fa-f]{6}$/',
                        $value
                    )
                ) {
                    $this->invalid(
                        $attribute,
                        'El color seleccionado no es válido.'
                    );
                }

                $data['color_value'] =
                    $value;

                break;

            /*
            |--------------------------------------------------------------------------
            | Texto
            |--------------------------------------------------------------------------
            */

            case 'TEXT':
            case 'LONG_TEXT':
            default:
                $text = (string) $value;

                $length = mb_strlen($text);

                if (
                    $attribute->min_length !== null
                    && $length < $attribute->min_length
                ) {
                    $this->invalid(
                        $attribute,
                        "Debe contener al menos {$attribute->min_length} caracteres."
                    );
                }

                if (
                    $attribute->max_length !== null
                    && $length > $attribute->max_length
                ) {
                    $this->invalid(
                        $attribute,
                        "No puede superar {$attribute->max_length} caracteres."
                    );
                }

                $data['text_value'] =
                    $text;

                break;
        }

        return $data;
    }

    private function validateNumericRange(
        Attribute $attribute,
        int|float $value
    ): void {
        if (
            $attribute->min_numeric_value !== null
            && $value < (float) $attribute->min_numeric_value
        ) {
            $this->invalid(
                $attribute,
                "El valor mínimo permitido es {$attribute->min_numeric_value}."
            );
        }

        if (
            $attribute->max_numeric_value !== null
            && $value > (float) $attribute->max_numeric_value
        ) {
            $this->invalid(
                $attribute,
                "El valor máximo permitido es {$attribute->max_numeric_value}."
            );
        }
    }

    private function invalid(
        Attribute $attribute,
        string $message
    ): never {
        throw ValidationException::withMessages([
            "attributes.{$attribute->id}" =>
                $message,
        ]);
    }
}