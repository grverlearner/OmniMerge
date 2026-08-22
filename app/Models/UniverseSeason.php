<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/*
|--------------------------------------------------------------------------
| UniverseSeason
|--------------------------------------------------------------------------
|
| El tiempo propio de un Universo (docs/md/09-Para Futuro.md §54-56).
|
| Estados documentados: PLANNED | ACTIVE | COMPLETED | ARCHIVED
|
*/

class UniverseSeason extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [

        'universe_id',

        'number',

        'name',

        'description',

        'status',

        'starts_at',

        'ends_at',
    ];

    protected function casts(): array
    {
        return [

            'number' =>
            'integer',

            'starts_at' =>
            'date',

            'ends_at' =>
            'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /*
     * Competiciones jugadas dentro de esta temporada.
     */
    public function competitions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            TournamentInstance::class,
            'universe_season_id'
        );
    }

    public function universe(): BelongsTo
    {
        return $this->belongsTo(
            Universe::class
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
    | Etiquetas
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {

            'PLANNED' =>
            'Planificada',

            'ACTIVE' =>
            'En curso',

            'COMPLETED' =>
            'Finalizada',

            'ARCHIVED' =>
            'Archivada',

            default =>
            $this->status,
        };
    }

    public function getPeriodLabelAttribute(): string
    {
        if (
            ! $this->starts_at
            &&
            ! $this->ends_at
        ) {
            return 'Sin fechas definidas';
        }

        $start =
            $this->starts_at
            ?->format('d/m/Y')
            ?? '—';

        $end =
            $this->ends_at
            ?->format('d/m/Y')
            ?? '—';

        return $start
            . ' → '
            . $end;
    }

    public static function statuses(): array
    {
        return [

            'PLANNED' =>
            'Planificada',

            'ACTIVE' =>
            'En curso',

            'COMPLETED' =>
            'Finalizada',

            'ARCHIVED' =>
            'Archivada',
        ];
    }
}
