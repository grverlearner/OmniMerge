<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseSingleEliminationRoundRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'participants_in_round',

        'best_of',

        'sort_order',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'participants_in_round' => 'integer',

            'best_of' => 'integer',

            'sort_order' => 'integer',

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
    | Ronda
    |--------------------------------------------------------------------------
    */

    public function getRoundLabelAttribute(): string
    {
        return match ($this->participants_in_round) {
            2 =>
            'Final',

            4 =>
            'Semifinal',

            8 =>
            'Cuartos de final',

            16 =>
            'Ronda de 16',

            32 =>
            'Ronda de 32',

            64 =>
            'Ronda de 64',

            128 =>
            'Ronda de 128',

            256 =>
            'Ronda de 256',

            512 =>
            'Ronda de 512',

            default =>
            'Ronda de '
                . $this->participants_in_round,
        };
    }

    public function getWinsRequiredAttribute(): int
    {
        return intdiv(
            $this->best_of,
            2
        ) + 1;
    }
}
