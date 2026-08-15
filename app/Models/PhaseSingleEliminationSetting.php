<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseSingleEliminationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'completion_mode',
        'target_survivors',

        'seeding_mode',
        'pairing_mode',

        'bye_assignment',
        'reseed_each_round',

        'series_format',
        'default_best_of',
        'fixed_games',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'target_survivors' => 'integer',

            'reseed_each_round' => 'boolean',

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

    public function getCompletionModeLabelAttribute(): string
    {
        return match ($this->completion_mode) {
            'WINNER' =>
            'Hasta obtener un ganador',

            'SURVIVORS' =>
            'Hasta alcanzar supervivientes',

            default =>
            $this->completion_mode,
        };
    }

    public function getSeedingModeLabelAttribute(): string
    {
        return match ($this->seeding_mode) {
            'INPUT_ORDER' =>
            'Orden de entrada',

            'RANDOM' =>
            'Aleatorio',

            'RANKING' =>
            'Ranking',

            'MANUAL' =>
            'Manual',

            default =>
            $this->seeding_mode,
        };
    }

    public function getPairingModeLabelAttribute(): string
    {
        return match ($this->pairing_mode) {
            'STANDARD_SEEDED' =>
            'Seeded estándar',

            'SEQUENTIAL' =>
            'Secuencial',

            'RANDOM' =>
            'Aleatorio',

            default =>
            $this->pairing_mode,
        };
    }

    public function getByeAssignmentLabelAttribute(): string
    {
        return match ($this->bye_assignment) {
            'TOP_SEEDS' =>
            'Mejores seeds',

            'RANDOM' =>
            'Aleatorio',

            'MANUAL' =>
            'Manual',

            default =>
            $this->bye_assignment,
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

    public function getSeriesLabelAttribute(): string
    {
        if (
            $this->series_format
            ===
            'FIXED_GAMES'
        ) {
            return
                $this->fixed_games
                .
                ' '
                .
                (
                    $this->fixed_games === 1
                    ? 'enfrentamiento fijo'
                    : 'enfrentamientos fijos'
                );
        }

        return
            'BO'
            .
            $this->default_best_of;
    }

    public function getSeriesDescriptionAttribute(): string
    {
        if (
            $this->series_format
            ===
            'FIXED_GAMES'
        ) {
            return
                'Se disputan obligatoriamente todos los enfrentamientos.';
        }

        return
            'Termina al alcanzar '
            .
            $this->wins_required
            .
            ' '
            .
            (
                $this->wins_required === 1
                ? 'victoria.'
                : 'victorias.'
            );
    }
}
