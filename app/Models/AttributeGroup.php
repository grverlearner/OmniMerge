<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeGroup extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'description',
        'icon',
        'color',
        'layout_type',
        'collapsible',
        'default_expanded',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'collapsible' => 'boolean',
            'default_expanded' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(
            Attribute::class,
            'attribute_group_attribute'
        )->withPivot([
            'custom_label',
            'sort_order',
            'is_featured',
        ])->orderByPivot('sort_order');
    }

    public function scopeOwnedBy(
        Builder $query,
        User $user
    ): Builder {
        return $query->where('user_id', $user->id);
    }
}