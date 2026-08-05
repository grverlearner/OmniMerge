<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntityAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_id',
        'attribute_id',
        'custom_label',
        'is_visible',
        'is_featured',
        'sort_order',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(
            EntityAttributeValue::class
        )->orderBy('sort_order');
    }
}