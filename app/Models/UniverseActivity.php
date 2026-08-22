<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| UniverseActivity
|--------------------------------------------------------------------------
|
| Lo que ha ido pasando en el Universo, contado en lenguaje del mundo y
| no del motor: "Naruto ganó la Copa Shinobi", no "NODE_COMPLETED".
|
*/

class UniverseActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'universe_id',
        'universe_season_id',
        'universe_entity_id',
        'tournament_instance_id',
        'type',
        'icon',
        'message',
        'context',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(
            UniverseSeason::class,
            'universe_season_id'
        );
    }

    public function universeEntity(): BelongsTo
    {
        return $this->belongsTo(
            UniverseEntity::class,
            'universe_entity_id'
        );
    }

    public function tournamentInstance(): BelongsTo
    {
        return $this->belongsTo(TournamentInstance::class);
    }
}
