<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeContextRule extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',

        'target_attribute_id',

        'name',

        'action',
        'match_mode',

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


    public function targetAttribute(): BelongsTo
    {
        return $this->belongsTo(
            Attribute::class,
            'target_attribute_id'
        );
    }


    public function conditions(): HasMany
    {
        return $this
            ->hasMany(
                AttributeContextRuleCondition::class,
                'rule_id'
            )
            ->orderBy(
                'sort_order'
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


    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {

            'SHOW' =>
            'Mostrar',

            'HIDE' =>
            'Ocultar',

            'REQUIRE' =>
            'Requerir',

            default =>
            $this->action,
        };
    }


    public function getMatchModeLabelAttribute(): string
    {
        return $this->match_mode === 'ANY'
            ? 'Cualquier condición'
            : 'Todas las condiciones';
    }
}
