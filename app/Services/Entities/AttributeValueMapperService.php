<?php

namespace App\Services\Entities;

use App\Models\Attribute;
use Illuminate\Validation\ValidationException;

class AttributeValueMapperService
{
    /*
    |--------------------------------------------------------------------------
    | Convertir uno o muchos valores
    |--------------------------------------------------------------------------
    */

    public function mapMany(
        Attribute $attribute,
        mixed $input,
        bool $enforceRequired = true
    ): array {

        if (
            $attribute->allows_multiple
        ) {

            $values =
                (array) $input;
        } else {

            if (is_array($input)) {

                $input =
                    collect(
                        $input
                    )
                    ->first();
            }


            $values = [
                $input,
            ];
        }


        $values =
            array_values(
                array_filter(
                    $values,
                    fn($value) =>
                    $value !== null
                        &&
                        $value !== ''
                )
            );


        if (
            $enforceRequired
            &&
            $attribute->is_required
            &&
            count($values) === 0
        ) {

            $this->invalid(
                $attribute,
                "El atributo {$attribute->name} requiere un valor."
            );
        }


        $result = [];


        foreach (
            $values
            as $index => $value
        ) {

            $result[] =
                $this->mapOne(
                    $attribute,
                    $value,
                    $index
                );
        }


        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | Valor individual
    |--------------------------------------------------------------------------
    */

    private function mapOne(
        Attribute $attribute,
        mixed $value,
        int $sortOrder
    ): array {

        $data = [
            'sort_order' =>
            $sortOrder,
        ];


        switch ($attribute->data_type) {

            /*
            |--------------------------------------------------------------------------
            | Catálogo
            |--------------------------------------------------------------------------
            */

            case 'OPTION':

                $option =
                    $attribute
                    ->options()
                    ->whereKey(
                        $value
                    )
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->first();


                if (! $option) {

                    $this->invalid(
                        $attribute,
                        'El elemento de Catálogo seleccionado no es válido.'
                    );
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

                if (
                    ! is_numeric(
                        $value
                    )
                ) {

                    $this->invalid(
                        $attribute,
                        'Debe ingresar un número entero válido.'
                    );
                }


                $numericValue =
                    (int) $value;


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

                if (
                    ! is_numeric(
                        $value
                    )
                ) {

                    $this->invalid(
                        $attribute,
                        'Debe ingresar un número válido.'
                    );
                }


                $numericValue =
                    (float) $value;


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

                $boolean =
                    filter_var(
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
                    ! is_string(
                        $value
                    )
                    ||
                    ! strtotime(
                        $value
                    )
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
                    ! is_string(
                        $value
                    )
                    ||
                    ! preg_match(
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

                $text =
                    (string) $value;


                $length =
                    mb_strlen(
                        $text
                    );


                if (
                    $attribute->min_length
                    !== null
                    &&
                    $length
                    <
                    $attribute->min_length
                ) {

                    $this->invalid(
                        $attribute,
                        "Debe contener al menos {$attribute->min_length} caracteres."
                    );
                }


                if (
                    $attribute->max_length
                    !== null
                    &&
                    $length
                    >
                    $attribute->max_length
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
            $attribute->min_numeric_value
            !== null
            &&
            $value
            <
            (float) $attribute
                ->min_numeric_value
        ) {

            $this->invalid(
                $attribute,
                "El valor mínimo permitido es {$attribute->min_numeric_value}."
            );
        }


        if (
            $attribute->max_numeric_value
            !== null
            &&
            $value
            >
            (float) $attribute
                ->max_numeric_value
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
