<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * Proyección consultable de una fase (nodo del grafo) dentro de una
 * competición. node_id apunta al nodo congelado en el snapshot.
 */
class TournamentInstancePhase extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_instance_id',
        'node_id',
        'node_code',
        'node_name',
        'phase_type',
        'status',
        'participant_count',

        /*
         * La excepcion de esta fase. Nulos = lo que diga la competicion,
         * que es lo normal: "todo al mejor de 3, menos la final".
         */
        'series_format',
        'best_of',
        'fixed_games',
    ];

    protected function casts(): array
    {
        return [
            'node_id' => 'integer',
            'participant_count' => 'integer',
        ];
    }

    public function tournamentInstance(): BelongsTo
    {
        return $this->belongsTo(
            TournamentInstance::class
        );
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'LOCKED' => 'Bloqueada',
            'WAITING_INPUTS' => 'Esperando participantes',
            'READY' => 'Lista',
            'RUNNING' => 'En curso',
            'AWAITING_DECISION' => 'Esperando decisión',
            'COMPLETED' => 'Completada',
            default => $this->status,
        };
    }
}
