<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseSingleEliminationConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'sequence_number',
        'code',

        'label',
        'description',

        'source_type',
        'source_input_gate_id',
        'source_result_id',

        'target_type',
        'target_slot_id',
        'target_phase_exit_id',

        'allocation_mode',
        'allocation_value',

        'priority',

        'condition_type',
        'condition',

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

            'allocation_value' =>
            'float',

            'priority' =>
            'integer',

            'condition' =>
            'array',

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

    public function sourceInputGate(): BelongsTo
    {
        return $this->belongsTo(
            PhaseInputGate::class,
            'source_input_gate_id'
        );
    }

    public function sourceResult(): BelongsTo
    {
        return $this->belongsTo(
            PhaseSingleEliminationResult::class,
            'source_result_id'
        );
    }

    public function targetSlot(): BelongsTo
    {
        return $this->belongsTo(
            PhaseSingleEliminationSlot::class,
            'target_slot_id'
        );
    }

    public function targetPhaseExit(): BelongsTo
    {
        return $this->belongsTo(
            PhaseExit::class,
            'target_phase_exit_id'
        );
    }

    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'ICN%04d',
            $sequence
        );
    }

    public function getSourceLabelAttribute(): string
    {
        if (
            $this->source_type
            ===
            'INPUT_GATE'
        ) {
            return
                $this->sourceInputGate?->name
                ??
                'Puerta de entrada';
        }

        $encounter =
            $this
                ->sourceResult
                ?->encounter;

        return
            (
                $encounter?->name
                ??
                'Encuentro'
            )
            .
            ' · '
            .
            (
                $this->sourceResult?->name
                ??
                'Resultado'
            );
    }

    public function getTargetLabelAttribute(): string
    {
        if (
            $this->target_type
            ===
            'PHASE_EXIT'
        ) {
            return
                $this->targetPhaseExit?->name
                ??
                'Puerta de salida';
        }

        $encounter =
            $this
                ->targetSlot
                ?->encounter;

        return
            (
                $encounter?->name
                ??
                'Encuentro'
            )
            .
            ' · Slot '
            .
            (
                $this->targetSlot?->position
                ??
                '?'
            );
    }

    public function getAllocationLabelAttribute(): string
    {
        return match ($this->allocation_mode) {
            'ALL' =>
            'Todo',

            'TAKE_N' =>
            'Tomar '
            .
            $this->allocation_value,

            'POSITION' =>
            'Posición '
            .
            $this->allocation_value,

            'REMAINDER' =>
            'Restante',

            'CONDITIONAL' =>
            'Condicional',

            default =>
            $this->allocation_mode,
        };
    }
}