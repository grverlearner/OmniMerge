<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentStart extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_template_id',

        'sequence_number',
        'code',

        'name',
        'description',

        'source_type',

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


    public function outgoingConnections(): HasMany
    {
        return $this->hasMany(
            TournamentPhaseConnection::class,
            'source_start_id'
        );
    }


    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'STA%03d',
            $sequence
        );
    }


    public function getSourceTypeLabelAttribute(): string
    {
        return match ($this->source_type) {
            'MAIN_POOL' =>
            'Pool principal',

            'SEEDED_POOL' =>
            'Pool con seeds',

            'QUALIFIER_POOL' =>
            'Clasificados previos',

            'INVITED_POOL' =>
            'Invitados',

            'CUSTOM' =>
            'Personalizado',

            default =>
            $this->source_type,
        };
    }
}
