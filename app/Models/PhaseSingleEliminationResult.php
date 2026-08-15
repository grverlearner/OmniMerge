<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhaseSingleEliminationResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'encounter_id',

        'sequence_number',
        'code',

        'name',
        'description',

        'result_type',

        'position_from',
        'position_to',

        'quantity',

        'flow_mode',
        'participant_status',

        'is_required',
        'is_splittable',
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

            'position_from' =>
            'integer',

            'position_to' =>
            'integer',

            'quantity' =>
            'integer',

            'is_required' =>
            'boolean',

            'is_splittable' =>
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

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(
            PhaseSingleEliminationEncounter::class,
            'encounter_id'
        );
    }

    public function outgoingConnections(): HasMany
    {
        return $this->hasMany(
            PhaseSingleEliminationConnection::class,
            'source_result_id'
        );
    }

    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'RES%02d',
            $sequence
        );
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->result_type) {
            'WINNER' =>
            'Ganador',

            'LOSER' =>
            'Perdedor',

            'POSITION' =>
            'Posición',

            'TOP_N' =>
            'Mejores N',

            'QUALIFIED' =>
            'Clasificados',

            'ELIMINATED' =>
            'Eliminados',

            'SURVIVOR' =>
            'Supervivientes',

            'SCORE_THRESHOLD' =>
            'Puntuación mínima',

            'MANUAL' =>
            'Selección manual',

            'CUSTOM' =>
            'Personalizado',

            default =>
            $this->result_type,
        };
    }

    public function getQuantityLabelAttribute(): string
    {
        return
            $this->quantity
            .
            ' '
            .
            (
                $this->quantity === 1
                ? 'participante'
                : 'participantes'
            );
    }

    public function getPositionLabelAttribute(): ?string
    {
        if (
            $this->position_from
            ===
            null
        ) {
            return null;
        }

        if (
            $this->position_to === null
            ||
            $this->position_to
            ===
            $this->position_from
        ) {
            return
                'Posición '
                .
                $this->position_from;
        }

        return
            'Posiciones '
            .
            $this->position_from
            .
            '–'
            .
            $this->position_to;
    }
}