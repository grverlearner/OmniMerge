<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeOptionRelationship extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',

        'source_option_id',
        'target_option_id',

        'relationship_type',

        'priority',
        'is_active',
    ];


    protected function casts(): array
    {
        return [
            'priority' =>
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


    public function sourceOption(): BelongsTo
    {
        return $this->belongsTo(
            AttributeOption::class,
            'source_option_id'
        );
    }


    public function targetOption(): BelongsTo
    {
        return $this->belongsTo(
            AttributeOption::class,
            'target_option_id'
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
