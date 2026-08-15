<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhaseSingleEliminationSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'encounter_id',

        'code',
        'position',

        'slot_type',
        'capacity',
        'is_required',

        'source_policy',
        'empty_behavior',
        'assignment_rule',

        'sort_order',
        'status',

        'generation_source',
        'is_locked',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'position' =>
            'integer',

            'capacity' =>
            'integer',

            'is_required' =>
            'boolean',

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

    public function incomingConnections(): HasMany
    {
        return $this->hasMany(
            PhaseSingleEliminationConnection::class,
            'target_slot_id'
        );
    }

    public static function formatCode(
        int $position
    ): string {
        return sprintf(
            'SLT%02d',
            $position
        );
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->slot_type) {
            'PARTICIPANT' =>
            'Participante',

            'BYE' =>
            'BYE',

            'OPTIONAL' =>
            'Opcional',

            'MANUAL' =>
            'Asignación manual',

            default =>
            $this->slot_type,
        };
    }

    public function getSourcePolicyLabelAttribute(): string
    {
        return match ($this->source_policy) {
            'SINGLE' =>
            'Fuente única',

            'FIRST_AVAILABLE' =>
            'Primera disponible',

            'PRIORITY' =>
            'Por prioridad',

            'CONDITIONAL' =>
            'Condicional',

            'MANUAL' =>
            'Manual',

            default =>
            $this->source_policy,
        };
    }
}