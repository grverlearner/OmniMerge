<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| TournamentInstanceMatch
|--------------------------------------------------------------------------
|
| Proyección consultable de un encuentro. Guarda los nombres además de
| las claves, para que un encuentro histórico siga siendo legible
| aunque el competidor desaparezca del Universo.
|
*/

class TournamentInstanceMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_instance_id',
        'node_id',
        'runtime_match_id',
        'round_number',
        'label',
        'status',
        'participant_a_key',
        'participant_b_key',
        'participant_a_name',
        'participant_b_name',
        'score_a',
        'score_b',
        'winner_key',
        'loser_key',
        'is_draw',
        'series',
        'participant_a_universe_entity_id',
        'participant_b_universe_entity_id',
        'winner_universe_entity_id',
        'group_label',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'node_id' => 'integer',
            'completed_at' => 'datetime',
            'round_number' => 'integer',
            'score_a' => 'integer',
            'score_b' => 'integer',
            'is_draw' => 'boolean',
            'series' => 'array',
        ];
    }

    /*
     * Entidades desnormalizadas: permiten pintar imágenes en el
     * historial sin pasar por la tabla de participantes.
     */
    public function participantAEntity(): BelongsTo
    {
        return $this->belongsTo(
            UniverseEntity::class,
            'participant_a_universe_entity_id'
        );
    }

    public function participantBEntity(): BelongsTo
    {
        return $this->belongsTo(
            UniverseEntity::class,
            'participant_b_universe_entity_id'
        );
    }

    public function winnerEntity(): BelongsTo
    {
        return $this->belongsTo(
            UniverseEntity::class,
            'winner_universe_entity_id'
        );
    }

    public function tournamentInstance(): BelongsTo
    {
        return $this->belongsTo(
            TournamentInstance::class
        );
    }

    public function isPlayed(): bool
    {
        return $this->status === 'COMPLETED';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'PENDING' => 'Pendiente',
            'RUNNING' => 'En juego',
            'COMPLETED' => 'Jugado',
            'BYE' => 'BYE',
            default => $this->status,
        };
    }
}
