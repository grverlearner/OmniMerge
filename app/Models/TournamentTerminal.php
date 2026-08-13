<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentTerminal extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_template_id',

        'sequence_number',
        'code',

        'name',
        'description',

        'terminal_type',

        'expected_participants',

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

            'expected_participants' =>
            'integer',

            'x_position' =>
            'integer',

            'y_position' =>
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


    public function incomingConnections(): HasMany
    {
        return $this->hasMany(
            TournamentPhaseConnection::class,
            'target_terminal_id'
        );
    }


    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'END%03d',
            $sequence
        );
    }


    public function getTerminalTypeLabelAttribute(): string
    {
        return match ($this->terminal_type) {
            'CHAMPION' =>
            'Campeón',

            'QUALIFIED' =>
            'Clasificados',

            'ELIMINATED' =>
            'Eliminados',

            'SECONDARY' =>
            'Ruta secundaria',

            'PLACEMENT' =>
            'Posición final',

            'CUSTOM' =>
            'Personalizado',

            default =>
            $this->terminal_type,
        };
    }
}
