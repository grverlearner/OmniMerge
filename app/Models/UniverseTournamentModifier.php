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
        'award_phase',
        'selector_type',
        'selector_from',
        'selector_to',
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
            'selector_from' => 'integer',
            'selector_to' => 'integer',
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

        /*
         * El unico que no se sabe de antemano: se resuelve cuando la fase
         * termina y se concede a quien haya quedado arriba.
         */
        'PHASE_PODIUM' => 'El podio de una fase',
    ];

    /*
     * Que parte de la clasificacion se lo lleva. Mismo vocabulario que
     * las puertas de salida: es el mismo corte sobre la misma tabla.
     */
    public const SELECTORS = [
        'TOP_N' => 'Los N primeros',
        'RANK_POSITION' => 'Un puesto exacto',
        'RANK_RANGE' => 'Un rango de puestos',
        'BOTTOM_N' => 'Los N últimos',
    ];

    /* Un bonus que hay que ganarselo jugando */
    public function isEarned(): bool
    {
        return $this->target === 'PHASE_PODIUM';
    }

    /*
     * Como se lee el corte. "del 3º al 4º" en vez de
     * "RANK_RANGE 3 4".
     */
    public function getSelectorLabelAttribute(): string
    {
        $from = (int) $this->selector_from;
        $to = (int) $this->selector_to;

        return match ($this->selector_type) {

            'RANK_POSITION' =>
            'el ' . $from . 'º',

            'RANK_RANGE' =>
            'del ' . $from . 'º al ' . $to . 'º',

            'BOTTOM_N' =>
            $from === 1
                ? 'el último'
                : 'los ' . $from . ' últimos',

            default =>
            $from === 1
                ? 'el 1º'
                : 'los ' . $from . ' primeros',
        };
    }

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
