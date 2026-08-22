<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/*
|--------------------------------------------------------------------------
| TournamentInstance
|--------------------------------------------------------------------------
|
| Una competición REAL. Es la ejecución de un UniverseTournament con
| participantes concretos, y sobrevive a la sesión del usuario.
|
| Ver docs/md/24-Fase-6-Tournament-Runtime-Persistente.md
|
*/

class TournamentInstance extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [

        'universe_id',

        'universe_tournament_id',
        'game_key',

        'universe_season_id',

        'tournament_template_id',

        'sequence_number',

        'code',

        'name',

        'status',

        'runtime_status',

        'participant_count',

        'started_at',

        'completed_at',

        /* Fase 12 */
        'rewards_processed_at',
    ];

    protected function casts(): array
    {
        return [

            'sequence_number' =>
            'integer',

            'participant_count' =>
            'integer',

            'started_at' =>
            'datetime',

            'completed_at' =>
            'datetime',

            'rewards_processed_at' =>
            'datetime',
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

    public function universeTournament(): BelongsTo
    {
        return $this->belongsTo(
            UniverseTournament::class
        );
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(
            UniverseSeason::class,
            'universe_season_id'
        );
    }

    public function tournamentTemplate(): BelongsTo
    {
        return $this->belongsTo(
            TournamentTemplate::class
        );
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(
            TournamentInstanceSnapshot::class
        );
    }

    public function state(): HasOne
    {
        return $this->hasOne(
            TournamentInstanceState::class
        );
    }

    public function participants(): HasMany
    {
        return $this->hasMany(
            TournamentInstanceParticipant::class
        )
            ->orderBy('seed');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(
            TournamentInstancePhase::class
        );
    }

    public function matches(): HasMany
    {
        return $this->hasMany(
            TournamentInstanceMatch::class
        );
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            TournamentInstanceEvent::class
        )
            ->orderBy('sequence');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeInUniverse(
        Builder $query,
        Universe $universe
    ): Builder {

        return $query->where(
            'universe_id',
            $universe->id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Ciclo de vida
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === 'DRAFT';
    }

    public function isRunning(): bool
    {
        return $this->status === 'RUNNING';
    }

    /*
     * Una competición terminada o cancelada no admite más acciones.
     */
    public function isClosed(): bool
    {
        return in_array(
            $this->status,
            [
                'COMPLETED',
                'CANCELLED',
            ],
            true
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
            'CMP%06d',
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
            'Preparada',

            'RUNNING' =>
            'En curso',

            'PAUSED' =>
            'Pausada',

            'COMPLETED' =>
            'Finalizada',

            'CANCELLED' =>
            'Cancelada',

            default =>
            $this->status,
        };
    }

    public function getRuntimeStatusLabelAttribute(): ?string
    {
        return match ($this->runtime_status) {

            'READY' =>
            'Lista para comenzar',

            'RUNNING' =>
            'Ejecutándose',

            'COMPLETED' =>
            'Recorrido completado',

            'BLOCKED' =>
            'Bloqueada',

            'AWAITING_DECISION' =>
            'Esperando una decisión',

            default =>
            $this->runtime_status,
        };
    }

    public static function statuses(): array
    {
        return [

            'DRAFT' =>
            'Preparada',

            'RUNNING' =>
            'En curso',

            'PAUSED' =>
            'Pausada',

            'COMPLETED' =>
            'Finalizada',

            'CANCELLED' =>
            'Cancelada',
        ];
    }
}
