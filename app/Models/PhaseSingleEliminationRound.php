<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhaseSingleEliminationRound extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'sequence_number',
        'code',

        'name',
        'description',

        'stage_number',
        'branch_code',
        'round_type',

        'participants_expected',
        'qualifiers_expected',

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

            'stage_number' =>
            'integer',

            'participants_expected' =>
            'integer',

            'qualifiers_expected' =>
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

    public function encounters(): HasMany
    {
        return $this
            ->hasMany(
                PhaseSingleEliminationEncounter::class,
                'round_id'
            )
            ->orderBy('sort_order')
            ->orderBy('position')
            ->orderBy('id');
    }

    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'RND%03d',
            $sequence
        );
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->round_type) {
            'PRELIMINARY' =>
            'Preliminar',

            'MAIN' =>
            'Rama principal',

            'REPECHAGE' =>
            'Repechaje',

            'PLACEMENT' =>
            'Posicionamiento',

            'CUSTOM' =>
            'Personalizada',

            default =>
            $this->round_type,
        };
    }

    public function getBranchLabelAttribute(): string
    {
        return match ($this->branch_code) {
            'MAIN' =>
            'Principal',

            'REPECHAGE' =>
            'Repechaje',

            'SECONDARY' =>
            'Secundaria',

            default =>
            $this->branch_code,
        };
    }
}
