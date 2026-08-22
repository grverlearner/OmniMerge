<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class Universe extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [

        'user_id',

        'sequence_number',

        'code',

        'name',

        'slug',

        'description',

        'image',

        'status',

        'settings',
    ];

    protected function casts(): array
    {
        return [

            'sequence_number' =>
            'integer',

            'settings' =>
            'array',
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

    /*
     * Entidades de la Biblioteca incorporadas a este Universo.
     * La Entity NO se copia (docs/md/09-Para Futuro.md §46).
     */
    public function entities(): HasMany
    {
        return $this->hasMany(
            UniverseEntity::class
        );
    }

    /*
     * El tiempo propio del Universo.
     */
    public function seasons(): HasMany
    {
        return $this->hasMany(
            UniverseSeason::class
        )
            ->orderByDesc('number');
    }

    /*
     * Uso de plantillas de torneo dentro de este Universo.
     */
    public function universeTournaments(): HasMany
    {
        return $this->hasMany(
            UniverseTournament::class
        );
    }

    /*
     * Competiciones reales jugadas o en curso dentro de este Universo.
     */
    public function tournamentInstances(): HasMany
    {
        return $this->hasMany(
            TournamentInstance::class
        );
    }

    /*
     * Cronica del Universo (Fase 10).
     */
    public function activities(): HasMany
    {
        return $this->hasMany(
            UniverseActivity::class
        );
    }

    /*
     * Juegos del Universo (Fase 11).
     *
     * Solo la decision del usuario: el juego en si vive en GameRegistry.
     */
    public function games(): HasMany
    {
        return $this->hasMany(
            UniverseGame::class
        );
    }

    public function gameEncounters(): HasMany
    {
        return $this->hasMany(
            GameEncounter::class
        );
    }

    /*
     * Consecuencias permanentes (Fase 12).
     */
    public function trophies(): HasMany
    {
        return $this->hasMany(
            UniverseTrophy::class
        );
    }

    public function statChanges(): HasMany
    {
        return $this->hasMany(
            UniverseStatChange::class
        );
    }

    public function trophyAwards(): HasMany
    {
        return $this->hasMany(
            UniverseTrophyAward::class
        );
    }

    /*
     * Mismo conjunto que tournamentInstances, con el nombre que usa la
     * interfaz ("Competiciones").
     *
     * Ademas es el nombre que necesita scopeBindings() para resolver
     * /universes/{universe}/competitions/{competition}.
     */
    public function competitions(): HasMany
    {
        return $this->tournamentInstances();
    }

    /*
    |--------------------------------------------------------------------------
    | Temporada actual
    |--------------------------------------------------------------------------
    |
    | Se deriva de la regla "una sola temporada ACTIVE por Universo",
    | no se duplica en una columna.
    |
    */

    public function activeSeason(): ?UniverseSeason
    {
        if (
            $this->relationLoaded(
                'seasons'
            )
        ) {

            return $this
                ->getRelation('seasons')
                ->firstWhere(
                    'status',
                    'ACTIVE'
                );
        }

        return $this
            ->seasons()
            ->where(
                'status',
                'ACTIVE'
            )
            ->first();
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
    | Imagen
    |--------------------------------------------------------------------------
    */

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk =
            Storage::disk(
                'public'
            );

        if (! $disk->exists(
            $this->image
        )) {
            return null;
        }

        return $disk->url(
            $this->image
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
            'UNI%06d',
            $sequence
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
}
