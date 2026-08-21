<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| UniverseCompetitor
|--------------------------------------------------------------------------
|
| Situación de una Entity dentro de un Universo concreto.
|
| La Entity sigue siendo canónica y vive en la Biblioteca; aquí solo
| se guarda su contexto competitivo dentro de este Universo
| (docs/md/09-Para Futuro.md §46-47).
|
| Sin SoftDeletes de forma deliberada: quitar un competidor deshace
| una asociación, no destruye información propia.
|
*/

class UniverseCompetitor extends Model
{
    use HasFactory;

    protected $fillable = [

        'universe_id',

        'entity_id',

        'display_name',

        'status',

        'notes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function universe(): BelongsTo
    {
        return $this->belongsTo(
            Universe::class
        );
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(
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

    /*
    |--------------------------------------------------------------------------
    | Presentación
    |--------------------------------------------------------------------------
    |
    | El alias del Universo tiene prioridad sobre el nombre canónico
    | de la Entity, pero nunca lo modifica.
    |
    */

    public function getDisplayLabelAttribute(): string
    {
        return $this->display_name
            ?: ($this->entity?->name ?? 'Competidor');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {

            'ACTIVE' =>
            'Activo',

            'INACTIVE' =>
            'Inactivo',

            'RETIRED' =>
            'Retirado',

            default =>
            $this->status,
        };
    }

    public static function statuses(): array
    {
        return [

            'ACTIVE' =>
            'Activo',

            'INACTIVE' =>
            'Inactivo',

            'RETIRED' =>
            'Retirado',
        ];
    }
}
