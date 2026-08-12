<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhaseExit extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'phase_template_id',
        'sequence_number',
        'code',
        'name',
        'description',
        'selector_type',
        'selector_from',
        'selector_to',
        'priority',
        'sort_order',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'selector_from' => 'integer',
            'selector_to' => 'integer',
            'priority' => 'integer',
            'sort_order' => 'integer',
            'settings' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function phaseTemplate(): BelongsTo
    {
        return $this->belongsTo(
            PhaseTemplate::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Código
    |--------------------------------------------------------------------------
    */

    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'EXT%03d',
            $sequence
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Etiquetas
    |--------------------------------------------------------------------------
    */

    public function getSelectorLabelAttribute(): string
    {
        return match ($this->selector_type) {
            'MATCH_WINNERS' => 'Ganadores',
            'MATCH_LOSERS' => 'Perdedores',
            'TOP_N' => 'Mejores N',
            'BOTTOM_N' => 'Últimos N',
            'RANK_POSITION' => 'Posición específica',
            'RANK_RANGE' => 'Rango de posiciones',
            'ALL' => 'Todos',
            'REMAINING' => 'Restantes',
            default => $this->selector_type,
        };
    }

    public function getSelectionSummaryAttribute(): string
    {
        return match ($this->selector_type) {
            'MATCH_WINNERS' =>
            'Ganadores de los enfrentamientos',

            'MATCH_LOSERS' =>
            'Perdedores de los enfrentamientos',

            'TOP_N' =>
            'Los mejores '
                . ($this->selector_from ?: '?'),

            'BOTTOM_N' =>
            'Los últimos '
                . ($this->selector_from ?: '?'),

            'RANK_POSITION' =>
            'Posición '
                . ($this->selector_from ?: '?'),

            'RANK_RANGE' =>
            'Posiciones '
                . ($this->selector_from ?: '?')
                . '–'
                . ($this->selector_to ?: '?'),

            'ALL' =>
            'Todos los participantes',

            'REMAINING' =>
            'Todos los participantes restantes',

            default =>
            'Selector personalizado',
        };
    }
}
