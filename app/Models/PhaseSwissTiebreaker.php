<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseSwissTiebreaker extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'criterion',
        'parameter_int',
        'direction',

        'sort_order',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'parameter_int' => 'integer',
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
            'WINS' =>
            'Victorias',

            'FEWEST_LOSSES' =>
            'Menos derrotas',

            'OPPONENT_SCORE_SUM' =>
            'Opponent Score Sum',

            'OPPONENT_SCORE_CUT_LOWEST' =>
            'Opponent Score Sum descartando los más bajos',

            'SONNEBORN_BERGER' =>
            'Sonneborn-Berger',

            'CUMULATIVE_SCORE' =>
            'Score acumulativo',

            'SCORE_DIFFERENCE' =>
            'Diferencia de score',

            'SCORE_FOR' =>
            'Score a favor',

            'GAME_DIFFERENCE' =>
            'Diferencia de partidas',

            'GAME_WINS' =>
            'Partidas ganadas',

            'HEAD_TO_HEAD' =>
            'Enfrentamiento directo',

            'SEED' =>
            'Seed inicial',

            default =>
            $this->criterion,
        };
    }

    public function getCriterionDescriptionAttribute(): string
    {
        return match ($this->criterion) {
            'WINS' =>
            'Prioriza al participante con más victorias.',

            'FEWEST_LOSSES' =>
            'Prioriza al participante con menos derrotas.',

            'OPPONENT_SCORE_SUM' =>
            'Suma el score acumulado por los rivales enfrentados.',

            'OPPONENT_SCORE_CUT_LOWEST' =>
            'Suma el score de los rivales ignorando los resultados de oposición más bajos configurados.',

            'SONNEBORN_BERGER' =>
            'Pondera los resultados obtenidos según la fuerza de los rivales.',

            'CUMULATIVE_SCORE' =>
            'Suma el score que el participante llevaba después de cada ronda.',

            'SCORE_DIFFERENCE' =>
            'Compara score a favor menos score en contra.',

            'SCORE_FOR' =>
            'Compara el score total conseguido a favor.',

            'GAME_DIFFERENCE' =>
            'Compara partidas internas ganadas menos partidas perdidas.',

            'GAME_WINS' =>
            'Compara la cantidad de partidas internas ganadas.',

            'HEAD_TO_HEAD' =>
            'Utiliza el resultado directo si los participantes empatados se enfrentaron.',

            'SEED' =>
            'Utiliza el seed inicial como criterio final.',

            default =>
            'Criterio de desempate Swiss.',
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

        return match ($this->criterion) {
            'FEWEST_LOSSES',
            'SEED' =>
            'ASC',

            default =>
            'DESC',
        };
    }

    public function getDirectionLabelAttribute(): string
    {
        return $this->effective_direction
            ===
            'ASC'
            ? 'Menor primero'
            : 'Mayor primero';
    }

    public function getSummaryAttribute(): string
    {
        if (
            $this->criterion
            ===
            'OPPONENT_SCORE_CUT_LOWEST'
        ) {
            return $this->criterion_label
                .
                ' · descarta '
                .
                ($this->parameter_int ?: 1);
        }

        return $this->criterion_label;
    }
}
