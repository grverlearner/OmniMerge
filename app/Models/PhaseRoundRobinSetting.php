<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseRoundRobinSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'cycles',

        'initial_order_mode',
        'schedule_mode',

        'allow_draws',

        'win_points',
        'draw_points',
        'loss_points',

        'default_best_of',
        'series_format',
        'fixed_games',

        'cutoff_tie_policy',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'cycles' => 'integer',

            'allow_draws' => 'boolean',

            'win_points' => 'float',
            'draw_points' => 'float',
            'loss_points' => 'float',

            'default_best_of' => 'integer',
            'fixed_games' => 'integer',

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

    public function getInitialOrderModeLabelAttribute(): string
    {
        return match ($this->initial_order_mode) {
            'INPUT_ORDER' =>
            'Orden de entrada',

            'RANDOM' =>
            'Aleatorio',

            'RANKING' =>
            'Ranking',

            'MANUAL' =>
            'Manual',

            default =>
            $this->initial_order_mode,
        };
    }

    public function getScheduleModeLabelAttribute(): string
    {
        return match ($this->schedule_mode) {
            'BALANCED' =>
            'Calendario equilibrado',

            default =>
            $this->schedule_mode,
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

    /*
    |--------------------------------------------------------------------------
    | Best Of
    |--------------------------------------------------------------------------
    */

    public function getWinsRequiredAttribute(): int
    {
        return intdiv(
            $this->default_best_of,
            2
        ) + 1;
    }

    /*
    |--------------------------------------------------------------------------
    | Ciclos
    |--------------------------------------------------------------------------
    */

    public function getCyclesLabelAttribute(): string
    {
        return match ($this->cycles) {
            1 =>
            'Single Round Robin',

            2 =>
            'Double Round Robin',

            3 =>
            'Triple Round Robin',

            default =>
            $this->cycles
                . ' ciclos',
        };
    }
}
