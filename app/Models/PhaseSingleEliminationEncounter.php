<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhaseSingleEliminationEncounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',
        'round_id',

        'sequence_number',
        'code',

        'name',
        'description',

        'position',

        'entrants_count',
        'qualifiers_count',
        'min_entrants_to_start',

        'encounter_profile',
        'activation_policy',
        'allows_incomplete',

        'series_format',
        'best_of',
        'fixed_games',

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

            'position' =>
            'integer',

            'entrants_count' =>
            'integer',

            'qualifiers_count' =>
            'integer',

            'min_entrants_to_start' =>
            'integer',

            'allows_incomplete' =>
            'boolean',

            'best_of' =>
            'integer',

            'fixed_games' =>
            'integer',

            'sort_order' =>
            'integer',

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

    public function round(): BelongsTo
    {
        return $this->belongsTo(
            PhaseSingleEliminationRound::class,
            'round_id'
        );
    }

    public function slots(): HasMany
    {
        return $this
            ->hasMany(
                PhaseSingleEliminationSlot::class,
                'encounter_id'
            )
            ->orderBy('sort_order')
            ->orderBy('position')
            ->orderBy('id');
    }

    public function results(): HasMany
    {
        return $this
            ->hasMany(
                PhaseSingleEliminationResult::class,
                'encounter_id'
            )
            ->orderBy('sort_order')
            ->orderBy('priority')
            ->orderBy('id');
    }

    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'ENC%04d',
            $sequence
        );
    }

    public function getCompetitiveFormatLabelAttribute(): string
    {
        return
            $this->entrants_count
            .
            ' → '
            .
            $this->qualifiers_count;
    }

    public function getProfileLabelAttribute(): string
    {
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

    public function getSeriesLabelAttribute(): string
    {
        if (
            $this->series_format
            ===
            'FIXED_GAMES'
        ) {
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

        if (
            $this->series_format
            ===
            'NONE'
        ) {
            return 'Sin serie';
        }

        return
            'BO'
            .
            ($this->best_of ?? 1);
    }

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
}