<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseSwissSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'completion_mode',
        'fixed_rounds',

        'qualification_wins',
        'elimination_losses',
        'max_rounds',

        'pairing_algorithm',
        'pairing_basis',
        'first_round_mode',
        'rematch_policy',

        'floater_policy',
        'side_balance_policy',

        'allow_draws',

        'win_points',
        'draw_points',
        'loss_points',

        'default_best_of',

        'bye_policy',
        'bye_points',
        'max_byes_per_participant',

        'initial_pairing_score_mode',

        'acceleration_mode',
        'acceleration_rounds',
        'acceleration_seed_count',
        'acceleration_virtual_points',

        'cutoff_tie_policy',
        'fallback_policy',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'fixed_rounds' => 'integer',

            'qualification_wins' => 'integer',
            'elimination_losses' => 'integer',
            'max_rounds' => 'integer',

            'allow_draws' => 'boolean',

            'win_points' => 'float',
            'draw_points' => 'float',
            'loss_points' => 'float',

            'default_best_of' => 'integer',

            'bye_points' => 'float',
            'max_byes_per_participant' => 'integer',

            'acceleration_rounds' => 'integer',
            'acceleration_seed_count' => 'integer',
            'acceleration_virtual_points' => 'float',

            'settings' => 'array',
        ];
    }

    public function phaseTemplate(): BelongsTo
    {
        return $this->belongsTo(
            PhaseTemplate::class
        );
    }

    public function getCompletionModeLabelAttribute(): string
    {
        return match ($this->completion_mode) {
            'FIXED_ROUNDS' =>
            'Rondas fijas',

            'RECORD_THRESHOLDS' =>
            'Umbrales de victorias y derrotas',

            default =>
            $this->completion_mode,
        };
    }

    public function getPairingAlgorithmLabelAttribute(): string
    {
        return match ($this->pairing_algorithm) {
            'OMNIMERGE_SCORE_GROUP' =>
            'OmniMerge Score Group',

            'ADJACENT_STANDINGS' =>
            'Clasificación adyacente',

            'RANDOM_WITHIN_SCORE' =>
            'Aleatorio dentro del score',

            default =>
            $this->pairing_algorithm,
        };
    }

    public function getPairingBasisLabelAttribute(): string
    {
        return match ($this->pairing_basis) {
            'MATCH_POINTS' =>
            'Puntos',

            'WIN_LOSS_RECORD' =>
            'Récord W/D/L',

            'PAIRING_SCORE' =>
            'Pairing Score',

            default =>
            $this->pairing_basis,
        };
    }

    public function getFirstRoundModeLabelAttribute(): string
    {
        return match ($this->first_round_mode) {
            'INPUT_ORDER' =>
            'Orden de entrada',

            'RANDOM' =>
            'Aleatorio',

            'SEEDED_HALVES' =>
            'Mitades por seed',

            'TOP_VS_BOTTOM' =>
            'Mejor seed contra peor seed',

            default =>
            $this->first_round_mode,
        };
    }

    public function getRematchPolicyLabelAttribute(): string
    {
        return match ($this->rematch_policy) {
            'STRICT_NO_REMATCH' =>
            'Prohibir rematches',

            'AVOID_IF_POSSIBLE' =>
            'Evitar si es posible',

            'ALLOW_REMATCH' =>
            'Permitir rematches',

            default =>
            $this->rematch_policy,
        };
    }

    public function getByePolicyLabelAttribute(): string
    {
        return match ($this->bye_policy) {
            'DISABLED' =>
            'Desactivado',

            'LOWEST_STANDING_WITHOUT_BYE' =>
            'Peor clasificado sin BYE previo',

            'LOWEST_SEED_WITHOUT_BYE' =>
            'Seed más bajo sin BYE previo',

            'RANDOM_ELIGIBLE' =>
            'Elegible aleatorio',

            'MANUAL' =>
            'Manual',

            default =>
            $this->bye_policy,
        };
    }

    public function getSideBalancePolicyLabelAttribute(): string
    {
        return match ($this->side_balance_policy) {
            'NONE' =>
            'Sin balance',

            'PREFER_BALANCE' =>
            'Preferir equilibrio',

            default =>
            $this->side_balance_policy,
        };
    }

    public function getAccelerationModeLabelAttribute(): string
    {
        return match ($this->acceleration_mode) {
            'NONE' =>
            'Sin aceleración',

            'GENERIC_VIRTUAL_POINTS' =>
            'Puntos virtuales por seed',

            default =>
            $this->acceleration_mode,
        };
    }

    public function getWinsRequiredAttribute(): int
    {
        return intdiv(
            $this->default_best_of,
            2
        ) + 1;
    }

    public function getRoundLimitAttribute(): int
    {
        if (
            $this->completion_mode
            ===
            'FIXED_ROUNDS'
        ) {
            return max(
                1,
                (int)
                $this->fixed_rounds
            );
        }

        return max(
            1,
            (int)
            $this->max_rounds
        );
    }
}
