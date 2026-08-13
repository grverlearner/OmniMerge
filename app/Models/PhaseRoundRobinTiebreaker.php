<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseRoundRobinTiebreaker extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'criterion',
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
    | Etiquetas
    |--------------------------------------------------------------------------
    */

    public function getCriterionLabelAttribute(): string
    {
        return match ($this->criterion) {
            'WINS' =>
                'Victorias',

            'FEWEST_LOSSES' =>
                'Menos derrotas',

            'HEAD_TO_HEAD' =>
                'Enfrentamiento directo',

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

    public function getCriterionDescriptionAttribute(): string
    {
        return match ($this->criterion) {
            'WINS' =>
                'Prioriza al participante con más series ganadas.',

            'FEWEST_LOSSES' =>
                'Prioriza al participante con menos derrotas.',

            'HEAD_TO_HEAD' =>
                'Compara los resultados obtenidos entre los participantes empatados.',

            'SCORE_DIFFERENCE' =>
                'Compara score a favor menos score en contra.',

            'SCORE_FOR' =>
                'Prioriza al participante con mayor score acumulado a favor.',

            'GAME_DIFFERENCE' =>
                'Compara partidas ganadas menos partidas perdidas dentro de las series.',

            'GAME_WINS' =>
                'Prioriza al participante con más partidas internas ganadas.',

            'SEED' =>
                'Utiliza el seed inicial como último criterio de orden.',

            default =>
                'Criterio de desempate.',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Dirección
    |--------------------------------------------------------------------------
    */

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
        return match ($this->effective_direction) {
            'ASC' =>
                'Menor primero',

            'DESC' =>
                'Mayor primero',

            default =>
                $this->effective_direction,
        };
    }
}