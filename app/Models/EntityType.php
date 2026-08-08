<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntityType extends Model
{
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [
        'user_id',

        /*
        |--------------------------------------------------------------------------
        | Identificación
        |--------------------------------------------------------------------------
        */

        'sequence_number',
        'code',

        /*
        |--------------------------------------------------------------------------
        | Información
        |--------------------------------------------------------------------------
        */

        'name',
        'description',

        /*
        |--------------------------------------------------------------------------
        | Representación visual
        |--------------------------------------------------------------------------
        */

        'image',
        'icon',
        'color',

        /*
        |--------------------------------------------------------------------------
        | Organización
        |--------------------------------------------------------------------------
        */

        'status',
        'sort_order',
    ];


    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
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


    public function entities(): HasMany
    {
        return $this->hasMany(
            Entity::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            'ACTIVE'
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


    /*
    |--------------------------------------------------------------------------
    | Estado
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status
            === 'ACTIVE';
    }


    /*
    |--------------------------------------------------------------------------
    | Imagen
    |--------------------------------------------------------------------------
    */

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return asset(
            'storage/'.$this->image
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Código automático
    |--------------------------------------------------------------------------
    |
    | 1   -> TPE0001
    | 25  -> TPE0025
    | 999 -> TPE0999
    |
    */

    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'TPE%04d',
            $sequence
        );
    }
}