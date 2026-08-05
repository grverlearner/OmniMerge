<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeOption extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'attribute_id',
        'parent_option_id',
        'code',
        'name',
        'description',
        'image',
        'icon',
        'color',
        'numeric_value',
        'sort_order',
        'metadata',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'numeric_value' => 'decimal:4',
            'metadata' => 'array',
        ];
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_option_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_option_id'
        )->orderBy('sort_order');
    }
}