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

        'entrants_per_match',
        'qualifiers_per_match',
        'encounter_profile',

        'series_format',
        'best_of',
        'fixed_games',

        'sort_order',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'participants_in_round' => 'integer',

            'entrants_per_match' => 'integer',
            'qualifiers_per_match' => 'integer',

            'best_of' => 'integer',
            'fixed_games' => 'integer',

            'sort_order' => 'integer',

            'settings' => 'array',
        ];
    }

    public function phaseTemplate(): BelongsTo
    {
        return $this->belongsTo(
            PhaseTemplate::class
        );
    }

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
                .
                $this->participants_in_round,
        };
    }

    public function getWinsRequiredAttribute(): int
    {
        return intdiv(
            $this->best_of,
            2
        ) + 1;
    }

    public function getSeriesLabelAttribute(): string
    {
        if ($this->series_format === 'FIXED_GAMES') {
            return
                $this->fixed_games
                .
                ' '
                .
                (
                    $this->fixed_games === 1
                    ? 'enfrentamiento fijo'
                    : 'enfrentamientos fijos'
                );
        }

        return
            'BO'
            .
            $this->best_of;
    }

    public function getCompetitiveFormatLabelAttribute(): ?string
    {
        if (
            $this->entrants_per_match === null
            ||
            $this->qualifiers_per_match === null
        ) {
            return null;
        }

        return
            $this->entrants_per_match
            .
            ' → '
            .
            $this->qualifiers_per_match;
    }

    public function getEncounterProfileLabelAttribute(): ?string
    {
        if ($this->encounter_profile === null) {
            return null;
        }

        return match ($this->encounter_profile) {
            'DUEL' =>
            'Duelo',

            'MULTI_COMPETITOR' =>
            'Multicompetidor',

            'CUSTOM' =>
            'Personalizado',

            default =>
            $this->encounter_profile,
        };
    }
}
