<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseGroupStageSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'group_count_mode',
        'group_count',
        'target_group_size',

        'min_group_size',
        'max_group_size',

        'remainder_policy',
        'distribution_mode',
        'pot_count',

        'internal_engine_type',
        'internal_cycles',
        'internal_schedule_mode',
        'internal_allow_draws',

        'internal_win_points',
        'internal_draw_points',
        'internal_loss_points',

        'internal_best_of',

        'cross_group_normalization',
        'cutoff_tie_policy',
        'completion_mode',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'group_count' => 'integer',
            'target_group_size' => 'integer',

            'min_group_size' => 'integer',
            'max_group_size' => 'integer',

            'pot_count' => 'integer',

            'internal_cycles' => 'integer',
            'internal_allow_draws' => 'boolean',

            'internal_win_points' => 'float',
            'internal_draw_points' => 'float',
            'internal_loss_points' => 'float',

            'internal_best_of' => 'integer',

            'settings' => 'array',
        ];
    }

    public function phaseTemplate(): BelongsTo
    {
        return $this->belongsTo(
            PhaseTemplate::class
        );
    }

    public function getGroupCountModeLabelAttribute(): string
    {
        return match ($this->group_count_mode) {
            'FIXED_GROUP_COUNT' =>
            'Cantidad fija de grupos',

            'TARGET_GROUP_SIZE' =>
            'Tamaño objetivo por grupo',

            'CUSTOM_GROUPS' =>
            'Grupos personalizados',

            default =>
            $this->group_count_mode,
        };
    }

    public function getRemainderPolicyLabelAttribute(): string
    {
        return match ($this->remainder_policy) {
            'BALANCED' =>
            'Distribución equilibrada',

            'FIRST_GROUPS' =>
            'Grupos iniciales',

            'LAST_GROUPS' =>
            'Grupos finales',

            'MANUAL' =>
            'Capacidades manuales',

            default =>
            $this->remainder_policy,
        };
    }

    public function getDistributionModeLabelAttribute(): string
    {
        return match ($this->distribution_mode) {
            'INPUT_ORDER' =>
            'Orden de entrada',

            'RANDOM' =>
            'Aleatorio',

            'SNAKE_SEEDED' =>
            'Snake Seeded',

            'POT_DRAW' =>
            'Sorteo por Pots',

            'MANUAL' =>
            'Asignación manual',

            default =>
            $this->distribution_mode,
        };
    }

    public function getInternalEngineLabelAttribute(): string
    {
        return match ($this->internal_engine_type) {
            'ROUND_ROBIN' =>
            'Todos contra todos',

            default =>
            $this->internal_engine_type,
        };
    }

    public function getCrossGroupNormalizationLabelAttribute(): string
    {
        return match ($this->cross_group_normalization) {
            'RAW' =>
            'Valores totales',

            'PER_MATCH' =>
            'Normalizado por partido',

            default =>
            $this->cross_group_normalization,
        };
    }

    public function getCutoffTiePolicyLabelAttribute(): string
    {
        return match ($this->cutoff_tie_policy) {
            'USE_TIEBREAKERS' =>
            'Aplicar desempates',

            'MANUAL_RESOLUTION' =>
            'Resolución manual',

            'RANDOM_RESOLUTION' =>
            'Resolución aleatoria',

            'INCLUDE_ALL_TIED' =>
            'Incluir todos los empatados',

            'REQUIRE_PLAYOFF' =>
            'Requerir playoff',

            default =>
            $this->cutoff_tie_policy,
        };
    }

    public function getInternalWinsRequiredAttribute(): int
    {
        return intdiv(
            $this->internal_best_of,
            2
        ) + 1;
    }
}
