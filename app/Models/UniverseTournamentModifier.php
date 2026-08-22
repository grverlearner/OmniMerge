<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| UniverseTournamentModifier
|--------------------------------------------------------------------------
|
| Un bonus que solo existe mientras se juega: "en la final todos reciben
| +2 fuerza", "el anfitrión tiene +1 velocidad durante todo el torneo".
|
| No modifica nada guardado. Se aplica sobre las stats ya congeladas en el
| estado, en el momento de preparar el enfrentamiento, y desaparece con el
| torneo.
|
*/

class UniverseTournamentModifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'universe_tournament_id',
        'scope',
        'scope_value',
        'target',
        'universe_entity_id',
        'game_key',
        'stat_key',
        'operation',
        'amount',
        'label',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public const SCOPES = [
        'TOURNAMENT' => 'Todo el torneo',
        'PHASE' => 'Una fase concreta',
        'ROUND' => 'Una ronda concreta',
    ];

    public const TARGETS = [
        'ALL' => 'Todos los participantes',
        'ENTITY' => 'Un competidor concreto',
    ];

    public function universeTournament(): BelongsTo
    {
        return $this->belongsTo(UniverseTournament::class);
    }

    public function universeEntity(): BelongsTo
    {
        return $this->belongsTo(UniverseEntity::class);
    }

    public function getScopeLabelAttribute(): string
    {
        return match ($this->scope) {
            'PHASE' => 'Fase: ' . ($this->scope_value ?: '—'),
            'ROUND' => 'Ronda ' . ($this->scope_value ?: '—'),
            default => 'Todo el torneo',
        };
    }

    public function getEffectLabelAttribute(): string
    {
        $amount =
            rtrim(rtrim(number_format($this->amount, 3, '.', ''), '0'), '.');

        return match ($this->operation) {
            'ADD' => '+' . $amount . ' ' . $this->stat_key,
            'SUBTRACT' => '−' . $amount . ' ' . $this->stat_key,
            'MULTIPLY' => '×' . $amount . ' ' . $this->stat_key,
            'SET' => $this->stat_key . ' = ' . $amount,
            default => $this->stat_key,
        };
    }
}
