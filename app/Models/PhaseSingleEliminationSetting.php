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

        'configuration_mode',
        'input_mode',
        'routing_mode',

        'entrants_per_match',
        'qualifiers_per_match',
        'encounter_profile',
        'remainder_policy',

        'completion_mode',
        'target_survivors',

        'seeding_mode',
        'pairing_mode',

        'bye_assignment',
        'reseed_each_round',

        'series_format',
        'default_best_of',
        'fixed_games',

        'structure_mode',
        'structure_status',
        'structure_version',
        'structure_fingerprint',
        'structure_generated_at',
        'structure_validated_at',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'entrants_per_match' =>
            'integer',

            'qualifiers_per_match' =>
            'integer',

            'target_survivors' =>
            'integer',

            'reseed_each_round' =>
            'boolean',

            'default_best_of' =>
            'integer',

            'fixed_games' =>
            'integer',

            'structure_version' =>
            'integer',

            'structure_generated_at' =>
            'datetime',

            'structure_validated_at' =>
            'datetime',

            'settings' =>
            'array',
        ];
    }

    public function phaseTemplate(): BelongsTo
    {
        return $this->belongsTo(
            PhaseTemplate::class
        );
    }

    public function getConfigurationModeLabelAttribute(): string
    {
        return match ($this->configuration_mode) {
            'BASIC' =>
            'Básico',

            'ADVANCED' =>
            'Avanzado',

            default =>
            $this->configuration_mode,
        };
    }

    public function getInputModeLabelAttribute(): string
    {
        return match ($this->input_mode) {
            'POOL' =>
            'Bolsa común',

            'PER_SEED' =>
            'Una entrada por seed',

            'GROUPED' =>
            'Entradas agrupadas',

            'HYBRID' =>
            'Entrada híbrida',

            'CUSTOM' =>
            'Entrada personalizada',

            default =>
            $this->input_mode,
        };
    }

    public function getRoutingModeLabelAttribute(): string
    {
        return match ($this->routing_mode) {
            'AUTOMATIC' =>
            'Automático',

            'POSITIONAL' =>
            'Por posición',

            'MANUAL' =>
            'Manual',

            'CUSTOM' =>
            'Personalizado',

            default =>
            $this->routing_mode,
        };
    }

    public function getEncounterProfileLabelAttribute(): string
    {
        return match ($this->encounter_profile) {
            'DUEL' =>
            'Duelo',

            'MULTI_COMPETITOR' =>
            'Multicompetidor',

            'CUSTOM' =>
            'Personalizado',

            default =>
            $this->encounter_profile,
        };
    }

    public function getRemainderPolicyLabelAttribute(): string
    {
        return match ($this->remainder_policy) {
            'BYE' =>
            'Avance libre por BYE',

            'PRELIMINARY' =>
            'Ronda preliminar',

            'BALANCED' =>
            'Distribución balanceada',

            'INCOMPLETE_MATCH' =>
            'Encuentro incompleto',

            'MANUAL' =>
            'Resolución manual',

            'REJECT' =>
            'Rechazar cantidad incompatible',

            default =>
            $this->remainder_policy,
        };
    }

    public function getCompetitiveFormatLabelAttribute(): string
    {
        return
            $this->entrants_per_match
            .
            ' → '
            .
            $this->qualifiers_per_match;
    }

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

    public function getWinsRequiredAttribute(): int
    {
        return intdiv(
            $this->default_best_of,
            2
        ) + 1;
    }

    public function getSeriesLabelAttribute(): string
    {
        if ($this->series_format === 'FIXED_GAMES') {
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
        if ($this->series_format === 'FIXED_GAMES') {
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
    public function getStructureStatusLabelAttribute(): string
    {
        return match ($this->structure_status) {
            'NOT_GENERATED' =>
            'Sin estructura',

            'GENERATED' =>
            'Pendiente de validación',

            'VALID' =>
            'Lista para probar',

            'INVALID' =>
            'Requiere correcciones',

            'STALE' =>
            'Estructura desactualizada',

            default =>
            $this->structure_status
                ??
                'Sin estructura',
        };
    }
}
