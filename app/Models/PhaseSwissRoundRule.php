<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseSwissRoundRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'trigger_type',

        'round_number',

        'record_wins',
        'record_draws',
        'record_losses',

        'best_of',
        'allow_draws_override',

        'sort_order',
        'status',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'round_number' => 'integer',

            'record_wins' => 'integer',
            'record_draws' => 'integer',
            'record_losses' => 'integer',

            'best_of' => 'integer',

            'allow_draws_override' => 'boolean',

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

    public function getTriggerLabelAttribute(): string
    {
        return match ($this->trigger_type) {
            'ROUND_NUMBER' =>
            'Ronda específica',

            'QUALIFICATION_MATCH' =>
            'Partido de clasificación',

            'ELIMINATION_MATCH' =>
            'Partido de eliminación',

            'QUALIFICATION_OR_ELIMINATION' =>
            'Clasificación o eliminación',

            'EXACT_RECORD' =>
            'Récord específico',

            default =>
            $this->trigger_type,
        };
    }

    public function getTriggerSummaryAttribute(): string
    {
        return match ($this->trigger_type) {
            'ROUND_NUMBER' =>
            'Ronda '
                .
                ($this->round_number ?: '?'),

            'QUALIFICATION_MATCH' =>
            'Una victoria puede activar una salida de clasificación',

            'ELIMINATION_MATCH' =>
            'Una derrota puede activar una salida de eliminación',

            'QUALIFICATION_OR_ELIMINATION' =>
            'La serie puede decidir clasificación o eliminación',

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

            default =>
            'Regla Swiss',
        };
    }

    public function getBestOfLabelAttribute(): string
    {
        return 'BO'
            .
            $this->best_of;
    }

    public function getDrawOverrideLabelAttribute(): string
    {
        if (
            $this->allow_draws_override
            ===
            null
        ) {
            return 'Hereda empates';
        }

        return $this->allow_draws_override
            ? 'Permite empates'
            : 'Requiere ganador';
    }
}
