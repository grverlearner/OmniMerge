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

        'sequence_number',
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
            'sequence_number' => 'integer',

            'collapsible' => 'boolean',
            'default_expanded' => 'boolean',

            'sort_order' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(
            Attribute::class,
            'attribute_group_attribute'
        )
            ->withPivot([
                'custom_label',
                'sort_order',
                'is_featured',
            ])
            ->orderByPivot(
                'sort_order'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

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
            'status',
            'ACTIVE'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Código
    |--------------------------------------------------------------------------
    */

    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'GRP%06d',
            $sequence
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status
            === 'ACTIVE';
    }

    public function isArchived(): bool
    {
        return $this->status
            === 'ARCHIVED';
    }

    /*
    |--------------------------------------------------------------------------
    | Etiqueta de presentación
    |--------------------------------------------------------------------------
    */

    public function getLayoutLabelAttribute(): string
    {
        return match ($this->layout_type) {
            'LIST' => 'Lista',
            'GRID' => 'Cuadrícula',
            'CARDS' => 'Tarjetas',
            'TABLE' => 'Tabla',
            'COMPACT' => 'Compacto',

            default => $this->layout_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ACTIVE' => 'Activo',
            'INACTIVE' => 'Inactivo',
            'ARCHIVED' => 'Archivado',

            default => $this->status,
        };
    }
}
