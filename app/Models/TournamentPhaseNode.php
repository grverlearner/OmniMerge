<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentPhaseNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_template_id',
        'phase_template_id',

        'sequence_number',
        'code',

        'name',
        'description',

        'x_position',
        'y_position',

        'status',

        'settings',
    ];


    protected function casts(): array
    {
        return [
            'sequence_number' =>
            'integer',

            'x_position' =>
            'integer',

            'y_position' =>
            'integer',

            'settings' =>
            'array',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function tournamentTemplate(): BelongsTo
    {
        return $this->belongsTo(
            TournamentTemplate::class
        );
    }


    public function phaseTemplate(): BelongsTo
    {
        return $this->belongsTo(
            PhaseTemplate::class
        );
    }


    public function entryPorts(): HasMany
    {
        return $this
            ->hasMany(
                PhaseEntryPort::class
            )
            ->orderBy('sort_order')
            ->orderBy('id');
    }


    public function outgoingConnections(): HasMany
    {
        return $this->hasMany(
            TournamentPhaseConnection::class,
            'source_node_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Código
    |--------------------------------------------------------------------------
    */

    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'NOD%03d',
            $sequence
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Etiquetas
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ACTIVE' =>
            'Activo',

            'INACTIVE' =>
            'Inactivo',

            default =>
            $this->status,
        };
    }


    public function getPhaseTypeLabelAttribute(): string
    {
        return $this
            ->phaseTemplate
            ?->type_label
            ??
            'Sin Fase';
    }


    public function getParticipantContractLabelAttribute(): string
    {
        return $this
            ->phaseTemplate
            ?->participant_contract_label
            ??
            'Sin contrato';
    }
}
