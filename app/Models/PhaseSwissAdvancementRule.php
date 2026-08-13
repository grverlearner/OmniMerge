<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseSwissAdvancementRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',
        'phase_exit_id',

        'rule_type',

        'threshold_wins',
        'threshold_losses',

        'record_wins',
        'record_draws',
        'record_losses',

        'rank_from',
        'rank_to',

        'take',

        'sort_order',
        'status',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'threshold_wins' => 'integer',
            'threshold_losses' => 'integer',

            'record_wins' => 'integer',
            'record_draws' => 'integer',
            'record_losses' => 'integer',

            'rank_from' => 'integer',
            'rank_to' => 'integer',

            'take' => 'integer',

            'sort_order' => 'integer',

            'settings' => 'array',
        ];
    }

    public function phaseTemplate(): BelongsTo
    {
        return $this->belongsTo(
            PhaseTemplate::class
        );
    }

    public function phaseExit(): BelongsTo
    {
        return $this->belongsTo(
            PhaseExit::class
        );
    }

    public function getRuleTypeLabelAttribute(): string
    {
        return match ($this->rule_type) {
            'WIN_THRESHOLD' =>
            'Umbral de victorias',

            'LOSS_THRESHOLD' =>
            'Umbral de derrotas',

            'EXACT_RECORD' =>
            'Récord exacto',

            'FINAL_TOP_N' =>
            'Top N final',

            'FINAL_BOTTOM_N' =>
            'Bottom N final',

            'FINAL_RANK_POSITION' =>
            'Posición final',

            'FINAL_RANK_RANGE' =>
            'Rango final',

            'REMAINING' =>
            'Participantes restantes',

            default =>
            $this->rule_type,
        };
    }

    public function getRuleSummaryAttribute(): string
    {
        return match ($this->rule_type) {
            'WIN_THRESHOLD' => ($this->threshold_wins ?: '?')
                .
                ' victorias',

            'LOSS_THRESHOLD' => ($this->threshold_losses ?: '?')
                .
                ' derrotas',

            'EXACT_RECORD' => ($this->record_wins ?? 0)
                .
                'W · '
                .
                ($this->record_draws ?? 0)
                .
                'D · '
                .
                ($this->record_losses ?? 0)
                .
                'L',

            'FINAL_TOP_N' =>
            'Top '
                .
                ($this->take ?: '?')
                .
                ' de la clasificación final',

            'FINAL_BOTTOM_N' =>
            'Bottom '
                .
                ($this->take ?: '?')
                .
                ' de la clasificación final',

            'FINAL_RANK_POSITION' =>
            'Posición final '
                .
                ($this->rank_from ?: '?'),

            'FINAL_RANK_RANGE' =>
            'Posiciones '
                .
                ($this->rank_from ?: '?')
                .
                '–'
                .
                ($this->rank_to ?: '?'),

            'REMAINING' =>
            'Todo participante todavía activo/no seleccionado',

            default =>
            'Regla Swiss',
        };
    }

    public function isDynamic(): bool
    {
        return in_array(
            $this->rule_type,
            [
                'WIN_THRESHOLD',
                'LOSS_THRESHOLD',
                'EXACT_RECORD',
            ],
            true
        );
    }

    public function getTimingLabelAttribute(): string
    {
        return $this->isDynamic()
            ? 'Durante la Fase'
            : 'Al finalizar la Fase';
    }
}
