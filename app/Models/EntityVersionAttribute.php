<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntityVersionAttribute extends Model
{
    use HasFactory;


    protected $fillable = [
        'entity_version_id',
        'attribute_id',

        'behavior',

        'custom_label',
        'is_visible',
        'is_featured',

        'sort_order',
        'notes',
    ];


    protected function casts(): array
    {
        return [
            'is_visible' =>
            'boolean',

            'is_featured' =>
            'boolean',

            'sort_order' =>
            'integer',
        ];
    }


    public function entityVersion(): BelongsTo
    {
        return $this->belongsTo(
            EntityVersion::class
        );
    }


    public function attribute(): BelongsTo
    {
        return $this->belongsTo(
            Attribute::class
        );
    }


    public function values(): HasMany
    {
        return $this->hasMany(
            EntityVersionAttributeValue::class
        )
            ->orderBy(
                'sort_order'
            );
    }


    public function isHidden(): bool
    {
        return $this->behavior
            === 'HIDE';
    }


    public function isOverride(): bool
    {
        return $this->behavior
            === 'OVERRIDE';
    }
}
