<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseGroupStageTiebreaker extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'criterion',
        'normalization',
        'direction',

        'sort_order',

        'settings',
    ];

    protected function casts(): array
    {
        return [
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

    public function getCriterionLabelAttribute(): string
    {
        return match ($this->criterion) {
            'POINTS' =>
            'Puntos',

            'WINS' =>
            'Victorias',

            'SCORE_DIFFERENCE' =>
            'Diferencia de score',

            'SCORE_FOR' =>
            'Score a favor',

            'GAME_DIFFERENCE' =>
            'Diferencia de partidas',

            'GAME_WINS' =>
            'Partidas ganadas',

            'SEED' =>
            'Seed inicial',

            default =>
            $this->criterion,
        };
    }

    public function getNormalizationLabelAttribute(): string
    {
        return match ($this->normalization) {
            'DEFAULT' =>
            'Configuración general',

            'RAW' =>
            'Valor total',

            'PER_MATCH' =>
            'Por partido',

            default =>
            $this->normalization,
        };
    }

    public function getEffectiveDirectionAttribute(): string
    {
        if (
            in_array(
                $this->direction,
                [
                    'ASC',
                    'DESC',
                ],
                true
            )
        ) {
            return $this->direction;
        }

        return $this->criterion === 'SEED'
            ? 'ASC'
            : 'DESC';
    }

    public function getDirectionLabelAttribute(): string
    {
        return $this->effective_direction === 'ASC'
            ? 'Menor primero'
            : 'Mayor primero';
    }
}
