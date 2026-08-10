<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeRelationship extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',

        'source_attribute_id',
        'target_attribute_id',

        'relationship_type',

        'sort_order',
        'is_active',
    ];


    protected function casts(): array
    {
        return [
            'sort_order' =>
            'integer',

            'is_active' =>
            'boolean',
        ];
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }


    public function sourceAttribute(): BelongsTo
    {
        return $this->belongsTo(
            Attribute::class,
            'source_attribute_id'
        );
    }


    public function targetAttribute(): BelongsTo
    {
        return $this->belongsTo(
            Attribute::class,
            'target_attribute_id'
        );
    }


    public function scopeOwnedBy(
        Builder $query,
        User $user
    ): Builder {

        return $query->where(
            'user_id',
            $user->id
        );
    }


    public function scopeActive(
        Builder $query
    ): Builder {

        return $query->where(
            'is_active',
            true
        );
    }
}
