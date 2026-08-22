<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
| Un trofeo concreto en manos de un competidor concreto.
*/

class UniverseTrophyAward extends Model
{
    use HasFactory;

    protected $fillable = [
        'universe_trophy_id',
        'universe_entity_id',
        'universe_id',
        'tournament_instance_id',
        'universe_season_id',
        'position',
        'awarded_at',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'awarded_at' => 'datetime',
        ];
    }

    public function trophy(): BelongsTo
    {
        return $this->belongsTo(UniverseTrophy::class, 'universe_trophy_id');
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
}
