<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| TournamentInstanceParticipant
|--------------------------------------------------------------------------
|
| Participante congelado de una ejecución concreta.
|
| El nombre y el seed son copias del momento en que arrancó la
| competición: si después cambia el Universo, el histórico no se altera.
| universe_competitor_id y entity_id son solo enlaces opcionales.
|
*/

class TournamentInstanceParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_instance_id',
        'runtime_key',
        'universe_competitor_id',
        'entity_id',
        'name',
        'seed',
        'source_start_id',
        'status',
        'matches',
        'wins',
        'draws',
        'losses',
        'points',
        'final_location_type',
        'final_location_name',
    ];

    protected function casts(): array
    {
        return [
            'seed' => 'integer',
            'source_start_id' => 'integer',
            'matches' => 'integer',
            'wins' => 'integer',
            'draws' => 'integer',
            'losses' => 'integer',
            'points' => 'integer',
        ];
    }

    public function tournamentInstance(): BelongsTo
    {
        return $this->belongsTo(
            TournamentInstance::class
        );
    }

    public function universeCompetitor(): BelongsTo
    {
        return $this->belongsTo(
            UniverseCompetitor::class
        );
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(
            Entity::class
        );
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'WAITING' => 'En espera',
            'ACTIVE' => 'Activo',
            'ELIMINATED' => 'Eliminado',
            'FINISHED' => 'Finalizado',
            'STRANDED' => 'Sin ruta',
            default => $this->status,
        };
    }
}
