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

        'exit_timing',

        'selector_from',
        'selector_to',

        'selector_round_size',

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

            'selector_round_size' => 'integer',

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
    | Selector
    |--------------------------------------------------------------------------
    */

    public function getSelectorLabelAttribute(): string
    {
        return match ($this->selector_type) {
            /*
             * SINGLE ELIMINATION
             */
            'SURVIVORS' =>
            'Supervivientes',

            'ELIMINATED' =>
            'Eliminados',

            'ELIMINATED_IN_ROUND' =>
            'Eliminados en una ronda',

            'ENGINE_RULES' =>
            'Reglas del Engine',

            /*
             * GENÉRICOS
             */
            'MATCH_WINNERS' =>
            'Ganadores',

            'MATCH_LOSERS' =>
            'Perdedores',

            'TOP_N' =>
            'Mejores N',

            'BOTTOM_N' =>
            'Últimos N',

            'RANK_POSITION' =>
            'Posición específica',

            'RANK_RANGE' =>
            'Rango de posiciones',

            'ALL' =>
            'Todos',

            'REMAINING' =>
            'Restantes',

            default =>
            $this->selector_type,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Resumen
    |--------------------------------------------------------------------------
    */

    public function getSelectionSummaryAttribute(): string
    {
        return match ($this->selector_type) {
            /*
             * SINGLE ELIMINATION
             */
            'SURVIVORS' =>
            'Competidores que permanecen al finalizar la Fase',

            'ELIMINATED' =>
            'Todos los competidores eliminados durante la Fase',

            'ELIMINATED_IN_ROUND' =>
            'Competidores eliminados en '
                . $this->roundLabel(
                    $this->selector_round_size
                ),

            /*
             * GENÉRICOS
             */
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

    /*
    |--------------------------------------------------------------------------
    | Timing
    |--------------------------------------------------------------------------
    */

    public function getTimingLabelAttribute(): string
    {
        return match ($this->exit_timing) {
            'PHASE_END' =>
            'Al finalizar la Fase',

            'ON_ELIMINATION' =>
            'Al producirse la eliminación',

            default =>
            $this->exit_timing,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function roundLabel(
        ?int $roundSize
    ): string {
        return match ($roundSize) {
            2 =>
            'la Final',

            4 =>
            'la Semifinal',

            8 =>
            'los Cuartos de final',

            16 =>
            'la Ronda de 16',

            32 =>
            'la Ronda de 32',

            64 =>
            'la Ronda de 64',

            128 =>
            'la Ronda de 128',

            256 =>
            'la Ronda de 256',

            512 =>
            'la Ronda de 512',

            null =>
            'una ronda específica',

            default =>
            'la ronda de '
                . $roundSize,
        };
    }
}
