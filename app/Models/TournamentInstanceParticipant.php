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
| universe_entity_id y entity_id son solo enlaces opcionales.
|
*/

class TournamentInstanceParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_instance_id',
        'runtime_key',
        'universe_entity_id',
        'entity_id',
        'entity_version_id',
        'entity_version_name',
        'entity_type_name',
        'attribute_snapshot',
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
        'outcome',
        'placement',
        'round_reached',
    ];

    protected function casts(): array
    {
        return [
            'attribute_snapshot' => 'array',
            'seed' => 'integer',
            'source_start_id' => 'integer',
            'matches' => 'integer',
            'wins' => 'integer',
            'draws' => 'integer',
            'losses' => 'integer',
            'points' => 'integer',
            'placement' => 'integer',
            'round_reached' => 'integer',
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
            UniverseEntity::class
        );
    }

    public function universeEntity(): BelongsTo
    {
        return $this->belongsTo(
            UniverseEntity::class,
            'universe_entity_id'
        );
    }

    /*
     * Solo procedencia: de qué Entidad de Biblioteca se importó la del
     * Universo. Nunca se agregan estadísticas por aquí.
     */
    public function sourceEntity(): BelongsTo
    {
        return $this->belongsTo(
            Entity::class,
            'source_entity_id'
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
