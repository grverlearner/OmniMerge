<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| UniverseStatChange
|--------------------------------------------------------------------------
|
| Por qué cambió una stat.
|
| El valor actual sigue viviendo en universe_entity_game_stats: esto no lo
| duplica, lo explica. Y su clave única es lo que hace que procesar dos
| veces las recompensas de un torneo no las aplique dos veces.
|
*/

class UniverseStatChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'universe_id',
        'universe_entity_id',
        'universe_season_id',
        'tournament_instance_id',
        'universe_tournament_reward_id',
        'source_type',
        'game_key',
        'stat_key',
        'value_before',
        'value_after',
        'delta',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'value_before' => 'float',
            'value_after' => 'float',
            'delta' => 'float',
        ];
    }

    public function universeEntity(): BelongsTo
    {
        return $this->belongsTo(UniverseEntity::class);
    }

    public function tournamentInstance(): BelongsTo
    {
        return $this->belongsTo(TournamentInstance::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(UniverseSeason::class, 'universe_season_id');
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(
            UniverseTournamentReward::class,
            'universe_tournament_reward_id'
        );
    }

    public function getDeltaLabelAttribute(): string
    {
        $value =
            rtrim(rtrim(number_format((float) $this->delta, 3, '.', ''), '0'), '.');

        return ((float) $this->delta >= 0 ? '+' : '') . $value;
    }
}
