<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| GameEncounterParticipant
|--------------------------------------------------------------------------
|
| Qué hizo un competidor en un enfrentamiento concreto.
|
| Es la fila desde la que se derivan victorias, derrotas y win rate. No se
| almacenan contadores: mismo criterio que la clasificación de la Fase 10,
| lo derivado nunca se desincroniza.
|
*/

class GameEncounterParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_encounter_id',
        'universe_entity_id',
        'participant_key',
        'name',
        'value',
        'display_value',
        'position',
        'is_winner',
        'stats_used',
        'detail',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'position' => 'integer',
            'is_winner' => 'boolean',
            'stats_used' => 'array',
            'detail' => 'array',
        ];
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(GameEncounter::class, 'game_encounter_id');
    }

    public function universeEntity(): BelongsTo
    {
        return $this->belongsTo(UniverseEntity::class);
    }
}
