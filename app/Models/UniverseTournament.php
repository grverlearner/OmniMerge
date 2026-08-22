<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/*
|--------------------------------------------------------------------------
| UniverseTournament (UniverseTournamentDefinition)
|--------------------------------------------------------------------------
|
| Uso concreto de una TournamentTemplate dentro de un Universo
| (docs/md/09-Para Futuro.md §57).
|
| La plantilla sigue siendo un diseño reutilizable de la Biblioteca
| de Torneos: este modelo solo describe cómo la usa este Universo.
|
| No pertenece a una temporada: la definición es atemporal. Cuando
| exista TournamentInstance (Fase 6 / Sprint U6) será la instancia
| la que pertenezca a una temporada concreta.
|
*/

class UniverseTournament extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [

        'universe_id',

        'tournament_template_id',

        'name',

        'description',

        'status',
        'image',
        'context',
        'recurrence_mode',
        'recurrence_interval',
        'first_season_number',
    ];

    protected function casts(): array
    {
        return [
            'recurrence_interval' => 'integer',
            'first_season_number' => 'integer',
        ];
    }

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

    public function tournamentTemplate(): BelongsTo
    {
        return $this->belongsTo(
            TournamentTemplate::class
        );
    }

    /*
     * Ejecuciones reales de este torneo configurado.
     */
    public function instances(): HasMany
    {
        return $this->hasMany(
            TournamentInstance::class
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

    /*
    |--------------------------------------------------------------------------
    | Recurrencia
    |--------------------------------------------------------------------------
    |
    | Calculada, no agendada: no hay scheduler ni tabla de ocurrencias.
    | La definición dice cada cuánto ocurre y este método responde si
    | toca en una temporada concreta. Suficiente para lo que se pide y
    | sin estado que mantener sincronizado.
    |
    */

    public function occursInSeason(
        int $seasonNumber
    ): bool {

        $first = $this->first_season_number ?? 1;

        if ($seasonNumber < $first) {
            return false;
        }

        return match ($this->recurrence_mode) {

            'EVERY_SEASON' =>
            true,

            'EVERY_N_SEASONS' =>
            ($seasonNumber - $first)
                % max(1, (int) $this->recurrence_interval) === 0,

            'ONCE' =>
            $seasonNumber === $first,

            /*
             * MANUAL: lo decide el usuario creando la competición
             * cuando quiere. Nunca se anuncia como programado.
             */
            default =>
            false,
        };
    }

    /*
     * Próxima temporada en la que toca, a partir de la actual.
     * Null cuando no vuelve a ocurrir o cuando es manual.
     */
    public function nextSeasonNumber(
        int $currentSeasonNumber
    ): ?int {

        if ($this->recurrence_mode === 'MANUAL') {
            return null;
        }

        for (
            $season = $currentSeasonNumber;
            $season <= $currentSeasonNumber + 64;
            $season++
        ) {

            if ($this->occursInSeason($season)) {
                return $season;
            }
        }

        return null;
    }

    public function getRecurrenceLabelAttribute(): string
    {
        $first = $this->first_season_number ?? 1;

        return match ($this->recurrence_mode) {

            'EVERY_SEASON' =>
            "Cada temporada (desde la {$first})",

            'EVERY_N_SEASONS' =>
            'Cada ' . max(1, (int) $this->recurrence_interval)
                . " temporadas (desde la {$first})",

            'ONCE' =>
            "Una sola vez (temporada {$first})",

            default =>
            'Cuando yo lo decida',
        };
    }

    public static function recurrenceModes(): array
    {
        return [
            'ONCE' => 'Una sola vez',
            'EVERY_SEASON' => 'Cada temporada',
            'EVERY_N_SEASONS' => 'Cada N temporadas',
            'MANUAL' => 'Manual',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        return $disk->exists($this->image)
            ? $disk->url($this->image)
            : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {

            'DRAFT' =>
            'Borrador',

            'ACTIVE' =>
            'Activo',

            'ARCHIVED' =>
            'Archivado',

            default =>
            $this->status,
        };
    }

    public static function statuses(): array
    {
        return [

            'DRAFT' =>
            'Borrador',

            'ACTIVE' =>
            'Activo',

            'ARCHIVED' =>
            'Archivado',
        ];
    }
}
