<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityVersionAttributeValue extends Model
{
    use HasFactory;


    protected $fillable = [
        'entity_version_attribute_id',

        'attribute_option_id',

        'text_value',
        'integer_value',
        'decimal_value',
        'boolean_value',
        'date_value',
        'color_value',
        'custom_value',
        'json_value',

        'sort_order',
    ];


    protected function casts(): array
    {
        return [
            'integer_value' =>
            'integer',

            'decimal_value' =>
            'decimal:4',

            'boolean_value' =>
            'boolean',

            'date_value' =>
            'date',

            'json_value' =>
            'array',

            'sort_order' =>
            'integer',
        ];
    }


    public function assignment(): BelongsTo
    {
        return $this->belongsTo(
            EntityVersionAttribute::class,
            'entity_version_attribute_id'
        );
    }


    public function option(): BelongsTo
    {
        return $this->belongsTo(
            AttributeOption::class,
            'attribute_option_id'
        );
    }


    public function displayValue(): string
    {
        if ($this->option) {
            return $this->option->name;
        }


        if (
            $this->boolean_value
            !== null
        ) {

            return $this->boolean_value
                ? 'Sí'
                : 'No';
        }


        $value =
            $this->text_value
            ?? $this->integer_value
            ?? $this->decimal_value
            ?? $this->date_value
            ?? $this->color_value
            ?? $this->custom_value;


        return (string) (
            $value
            ?? ''
        );
    }
}
