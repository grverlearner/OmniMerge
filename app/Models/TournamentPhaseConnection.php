<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPhaseConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_template_id',

        'sequence_number',
        'code',

        'label',
        'description',

        'source_type',

        'source_start_id',
        'source_node_id',
        'source_phase_exit_id',

        'target_type',

        'target_entry_port_id',
        'target_terminal_id',

        'allocation_mode',
        'allocation_value',

        'priority',

        'status',

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

            'settings' =>
            'array',
        ];
    }


    public function tournamentTemplate(): BelongsTo
    {
        return $this->belongsTo(
            TournamentTemplate::class
        );
    }


    public function sourceStart(): BelongsTo
    {
        return $this->belongsTo(
            TournamentStart::class,
            'source_start_id'
        );
    }


    public function sourceNode(): BelongsTo
    {
        return $this->belongsTo(
            TournamentPhaseNode::class,
            'source_node_id'
        );
    }


    public function sourcePhaseExit(): BelongsTo
    {
        return $this->belongsTo(
            PhaseExit::class,
            'source_phase_exit_id'
        );
    }


    public function targetEntryPort(): BelongsTo
    {
        return $this->belongsTo(
            PhaseEntryPort::class,
            'target_entry_port_id'
        );
    }


    public function targetTerminal(): BelongsTo
    {
        return $this->belongsTo(
            TournamentTerminal::class,
            'target_terminal_id'
        );
    }


    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'CON%03d',
            $sequence
        );
    }


    public function getSourceLabelAttribute(): string
    {
        if (
            $this->source_type
            ===
            'START'
        ) {
            return
                $this->sourceStart?->name
                ??
                'Inicio';
        }

        return (
                $this->sourceNode?->name
                ??
                'Fase'
            )
            .
            ' · '
            .
            (
                $this->sourcePhaseExit?->name
                ??
                'Salida'
            );
    }


    public function getTargetLabelAttribute(): string
    {
        if (
            $this->target_type
            ===
            'TERMINAL'
        ) {
            return
                $this->targetTerminal?->name
                ??
                'Terminal';
        }

        return (
                $this
                ->targetEntryPort
                ?->node
                ?->name
                ??
                'Fase'
            )
            .
            ' · '
            .
            (
                $this->targetEntryPort?->name
                ??
                'Entrada'
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

            'PERCENTAGE' =>
            $this->allocation_value
                .
                '%',

            'REMAINDER' =>
            'Restante',

            default =>
            $this->allocation_mode,
        };
    }
}
