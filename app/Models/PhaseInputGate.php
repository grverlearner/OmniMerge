<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhaseInputGate extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'sequence_number',
        'code',

        'name',
        'description',

        'input_type',
        'merge_policy',
        'distribution_mode',
        'empty_behavior',

        'min_participants',
        'max_participants',
        'exact_participants',

        'is_required',
        'accepts_batch',
        'accepts_multiple_connections',

        'priority',
        'sort_order',
        'status',

        'generation_source',
        'is_locked',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' =>
            'integer',

            'min_participants' =>
            'integer',

            'max_participants' =>
            'integer',

            'exact_participants' =>
            'integer',

            'is_required' =>
            'boolean',

            'accepts_batch' =>
            'boolean',

            'accepts_multiple_connections' =>
            'boolean',

            'priority' =>
            'integer',

            'sort_order' =>
            'integer',

            'is_locked' =>
            'boolean',

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

    public function contextualEntryPorts(): HasMany
    {
        return $this->hasMany(
            PhaseEntryPort::class,
            'phase_input_gate_id'
        );
    }
    public function outgoingConnections(): HasMany
    {
        return $this->hasMany(
            PhaseSingleEliminationConnection::class,
            'source_input_gate_id'
        );
    }

    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'GIN%03d',
            $sequence
        );
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->input_type) {
            'POOL' =>
            'Bolsa general',

            'PER_SEED' =>
            'Entrada por seed',

            'GROUPED' =>
            'Entrada agrupada',

            'HYBRID' =>
            'Entrada híbrida',

            'CUSTOM' =>
            'Entrada personalizada',

            default =>
            $this->input_type,
        };
    }

    public function getDistributionLabelAttribute(): string
    {
        return match ($this->distribution_mode) {
            'INPUT_ORDER' =>
            'Orden de entrada',

            'RANKING' =>
            'Por ranking',

            'RANDOM' =>
            'Aleatoria',

            'BALANCED' =>
            'Balanceada',

            'EXTREMES' =>
            'Entre extremos',

            'MANUAL' =>
            'Manual',

            'CUSTOM' =>
            'Personalizada',

            default =>
            $this->distribution_mode,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ACTIVE' =>
            'Activa',

            'INACTIVE' =>
            'Inactiva',

            default =>
            $this->status
                ?:
                'Sin estado',
        };
    }

    public function getCoverageLabelAttribute(): string
    {
        $connections =
            $this->relationLoaded(
                'outgoingConnections'
            )
                ? $this->outgoingConnections
                : $this
                    ->outgoingConnections()
                    ->get();

        $activeConnections =
            $connections
            ->where(
                'status',
                'ACTIVE'
            )
            ->values();

        $positionConnections =
            $activeConnections
            ->where(
                'allocation_mode',
                'POSITION'
            );

        if (
            $this->exact_participants !== null
            &&
            $positionConnections->isNotEmpty()
        ) {
            $coveredPositions =
                $positionConnections
                ->pluck(
                    'allocation_value'
                )
                ->filter(
                    fn($value) =>
                    $value !== null
                )
                ->map(
                    fn($value) =>
                    (int) $value
                )
                ->filter(
                    fn(int $position) =>
                    $position > 0
                )
                ->unique()
                ->count();

            return $coveredPositions
                .
                ' / '
                .
                $this->exact_participants
                .
                ' posiciones cubiertas';
        }

        $routes =
            $activeConnections
            ->count();

        return $routes
            .
            ($routes === 1
                ? ' ruta activa'
                : ' rutas activas');
    }

    public function getContractLabelAttribute(): string
    {
        if (
            $this->exact_participants
            !==
            null
        ) {
            return
                $this->exact_participants
                .
                ' exactos';
        }

        if (
            $this->min_participants === null
            &&
            $this->max_participants === null
        ) {
            return 'Flexible';
        }

        if (
            $this->max_participants
            ===
            null
        ) {
            return ($this->min_participants ?? 0)
                .
                '+';
        }

        return ($this->min_participants ?? 0)
            .
            '–'
            .
            $this->max_participants;
    }
}
