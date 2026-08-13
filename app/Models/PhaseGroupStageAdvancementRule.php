<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseGroupStageAdvancementRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'phase_exit_id',
        'phase_group_stage_group_id',

        'rule_type',

        'position_from',
        'position_to',

        'take',

        'sort_order',

        'status',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'position_from' => 'integer',
            'position_to' => 'integer',

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

    public function group(): BelongsTo
    {
        return $this->belongsTo(
            PhaseGroupStageGroup::class,
            'phase_group_stage_group_id'
        );
    }

    public function getRuleTypeLabelAttribute(): string
    {
        return match ($this->rule_type) {
            'EACH_GROUP_TOP_N' =>
            'Mejores N de cada grupo',

            'EACH_GROUP_BOTTOM_N' =>
            'Últimos N de cada grupo',

            'EACH_GROUP_POSITION' =>
            'Posición de cada grupo',

            'EACH_GROUP_RANGE' =>
            'Rango de cada grupo',

            'CROSS_GROUP_POSITION_TOP_N' =>
            'Mejores de una posición entre grupos',

            'CROSS_GROUP_POSITION_BOTTOM_N' =>
            'Peores de una posición entre grupos',

            'BEST_REMAINING' =>
            'Mejores participantes restantes',

            'WORST_REMAINING' =>
            'Peores participantes restantes',

            'SPECIFIC_GROUP_POSITION' =>
            'Posición de un grupo específico',

            'SPECIFIC_GROUP_RANGE' =>
            'Rango de un grupo específico',

            'REMAINING' =>
            'Todos los restantes',

            default =>
            $this->rule_type,
        };
    }

    public function getRuleSummaryAttribute(): string
    {
        return match ($this->rule_type) {
            'EACH_GROUP_TOP_N' =>
            'Top '
                . ($this->take ?: '?')
                . ' de cada grupo',

            'EACH_GROUP_BOTTOM_N' =>
            'Bottom '
                . ($this->take ?: '?')
                . ' de cada grupo',

            'EACH_GROUP_POSITION' =>
            'Posición '
                . ($this->position_from ?: '?')
                . ' de cada grupo',

            'EACH_GROUP_RANGE' =>
            'Posiciones '
                . ($this->position_from ?: '?')
                . '–'
                . ($this->position_to ?: '?')
                . ' de cada grupo',

            'CROSS_GROUP_POSITION_TOP_N' =>
            'Mejores '
                . ($this->take ?: '?')
                . ' de la posición '
                . ($this->position_from ?: '?'),

            'CROSS_GROUP_POSITION_BOTTOM_N' =>
            'Peores '
                . ($this->take ?: '?')
                . ' de la posición '
                . ($this->position_from ?: '?'),

            'BEST_REMAINING' =>
            'Mejores '
                . ($this->take ?: '?')
                . ' participantes restantes',

            'WORST_REMAINING' =>
            'Peores '
                . ($this->take ?: '?')
                . ' participantes restantes',

            'SPECIFIC_GROUP_POSITION' => ($this->group?->name ?: 'Grupo')
                . ' · posición '
                . ($this->position_from ?: '?'),

            'SPECIFIC_GROUP_RANGE' => ($this->group?->name ?: 'Grupo')
                . ' · posiciones '
                . ($this->position_from ?: '?')
                . '–'
                . ($this->position_to ?: '?'),

            'REMAINING' =>
            'Todos los participantes todavía no seleccionados',

            default =>
            'Regla de avance personalizada',
        };
    }
}
