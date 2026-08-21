<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| TournamentInstancePhaseParticipant
|--------------------------------------------------------------------------
|
| Rendimiento de un participante DENTRO de una fase concreta.
|
| Distinto de TournamentInstanceParticipant, que agrega toda la
| competición: aquí un mismo competidor puede tener una fila por cada
| fase que disputó, con su posición, su grupo y sus cifras propias.
|
| Ver docs/md/26-Fase-8-Historial-Y-Estadisticas.md §3.2
|
*/

class TournamentInstancePhaseParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_instance_id',
        'tournament_instance_phase_id',
        'runtime_key',
        'universe_entity_id',
        'participant_name',
        'group_label',
        'position',
        'matches',
        'wins',
        'draws',
        'losses',
        'points',
        'score_for',
        'score_against',
        'score_difference',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'matches' => 'integer',
            'wins' => 'integer',
            'draws' => 'integer',
            'losses' => 'integer',
            'points' => 'integer',
            'score_for' => 'integer',
            'score_against' => 'integer',
            'score_difference' => 'integer',
        ];
    }

    public function tournamentInstance(): BelongsTo
    {
        return $this->belongsTo(
            TournamentInstance::class
        );
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(
            TournamentInstancePhase::class,
            'tournament_instance_phase_id'
        );
    }

    public function universeEntity(): BelongsTo
    {
        return $this->belongsTo(
            UniverseEntity::class,
            'universe_entity_id'
        );
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {

            'ADVANCED' =>
            'Clasificado',

            'ELIMINATED' =>
            'Eliminado',

            default =>
            'Disputada',
        };
    }
}
