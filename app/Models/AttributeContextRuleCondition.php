<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeContextRuleCondition extends Model
{
    use HasFactory;


    protected $fillable = [
        'rule_id',

        'source_attribute_id',

        'operator',
        'source_option_id',

        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' =>
            'integer',
        ];
    }


    public function rule(): BelongsTo
    {
        return $this->belongsTo(
            AttributeContextRule::class,
            'rule_id'
        );
    }


    public function sourceAttribute(): BelongsTo
    {
        return $this->belongsTo(
            Attribute::class,
            'source_attribute_id'
        );
    }


    public function sourceOption(): BelongsTo
    {
        return $this->belongsTo(
            AttributeOption::class,
            'source_option_id'
        );
    }


    public function getOperatorLabelAttribute(): string
    {
        return match ($this->operator) {

            'EQUALS' =>
            'es',

            'NOT_EQUALS' =>
            'no es',

            'EXISTS' =>
            'tiene valor',

            'NOT_EXISTS' =>
            'no tiene valor',

            default =>
            $this->operator,
        };
    }
}
