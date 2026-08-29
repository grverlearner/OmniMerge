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

        /*
         * El formato de batalla lo decide la competicion, no la plantilla:
         * la misma forma de torneo se juega al mejor de 3 este ano y al
         * mejor de 5 el que viene.
         */
        'series_format',
        'best_of',
        'fixed_games',

        /*
         * El resto de lo que define como se pelea en ESTA edicion, y donde
         * se decide cada cosa. Ver la migracion que las anadio.
         */
        'battle_participants',
        'decision_mode',
        'allow_draws',
        'game_scope',
        'battle_scope',
        'start_rules',
        'copied_from_instance_id',

        'description',
        'image',

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

            'battle_participants' =>
            'integer',

            'best_of' =>
            'integer',

            'fixed_games' =>
            'integer',

            'allow_draws' =>
            'boolean',

            'start_rules' =>
            'array',

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

    /*
     * Los premios que existen solo en esta edicion. Los del torneo se
     * heredan y no se tocan desde aqui.
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(
            TournamentInstanceReward::class
        );
    }

    /*
     * Los trofeos inventados para esta edicion. Los del universo siguen
     * siendo del universo.
     */
    public function trophies(): HasMany
    {
        return $this->hasMany(
            UniverseTrophy::class,
            'tournament_instance_id'
        );
    }

    /* De que edicion se copio esta */
    public function copiedFrom(): BelongsTo
    {
        return $this->belongsTo(
            TournamentInstance::class,
            'copied_from_instance_id'
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
