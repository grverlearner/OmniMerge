<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhaseEntryPort extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_phase_node_id',

        'sequence_number',
        'code',

        'name',
        'description',

        'merge_policy',

        'is_required',
        'accepts_multiple_connections',

        'min_participants',
        'max_participants',
        'exact_participants',

        'sort_order',
        'status',

        'settings',
    ];


    protected function casts(): array
    {
        return [
            'sequence_number' =>
            'integer',

            'is_required' =>
            'boolean',

            'accepts_multiple_connections' =>
            'boolean',

            'min_participants' =>
            'integer',

            'max_participants' =>
            'integer',

            'exact_participants' =>
            'integer',

            'sort_order' =>
            'integer',

            'settings' =>
            'array',
        ];
    }


    public function node(): BelongsTo
    {
        return $this->belongsTo(
            TournamentPhaseNode::class,
            'tournament_phase_node_id'
        );
    }


    public function incomingConnections(): HasMany
    {
        return $this->hasMany(
            TournamentPhaseConnection::class,
            'target_entry_port_id'
        );
    }


    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'IN%03d',
            $sequence
        );
    }


    public function getMergePolicyLabelAttribute(): string
    {
        return match ($this->merge_policy) {
            'APPEND' =>
            'Combinar participantes',

            'WAIT_ALL' =>
            'Esperar todas las rutas',

            'FIRST_AVAILABLE' =>
            'Primera ruta disponible',

            'PRIORITY' =>
            'Por prioridad',

            default =>
            $this->merge_policy,
        };
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
            return
                $this->min_participants
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
