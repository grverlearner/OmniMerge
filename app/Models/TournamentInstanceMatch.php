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
    ];

    protected function casts(): array
    {
        return [
            'node_id' => 'integer',
            'round_number' => 'integer',
            'score_a' => 'integer',
            'score_b' => 'integer',
            'is_draw' => 'boolean',
            'series' => 'array',
        ];
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
