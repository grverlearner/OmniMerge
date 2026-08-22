<?php

namespace App\Models;

use App\Services\Games\GameRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
|--------------------------------------------------------------------------
| GameEncounter
|--------------------------------------------------------------------------
|
| Un enfrentamiento resuelto.
|
| La Battle no tiene tabla propia: ya es el match con su serie en el
| runtime, proyectado en tournament_instance_matches desde la Fase 6. Lo
| que faltaba era el detalle de CADA enfrentamiento de esa serie, con los
| valores que generó cada participante.
|
| Guarda deliberadamente más de lo que hoy se muestra: es la materia prima
| de las recompensas de la Fase 12, que necesitarán saber ganador,
| posición, participación, juego, torneo, temporada y Universo.
|
*/

class GameEncounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'universe_id',
        'tournament_instance_id',
        'universe_season_id',
        'game_key',
        'battle_key',
        'node_id',
        'phase_name',
        'encounter_number',
        'participant_count',
        'is_draw',
        'winner_universe_entity_id',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'encounter_number' => 'integer',
            'participant_count' => 'integer',
            'is_draw' => 'boolean',
            'payload' => 'array',
        ];
    }

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function tournamentInstance(): BelongsTo
    {
        return $this->belongsTo(TournamentInstance::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(
            UniverseSeason::class,
            'universe_season_id'
        );
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(
            UniverseEntity::class,
            'winner_universe_entity_id'
        );
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GameEncounterParticipant::class)
            ->orderBy('position');
    }

    public function getGameNameAttribute(): string
    {
        return app(GameRegistry::class)
            ->definition($this->game_key)['name']
            ?? $this->game_key;
    }
}
