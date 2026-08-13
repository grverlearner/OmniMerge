<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhaseGroupStageGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_template_id',

        'sequence_number',
        'code',

        'name',
        'capacity',

        'is_active',

        'sort_order',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',

            'capacity' => 'integer',

            'is_active' => 'boolean',

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

    public function advancementRules(): HasMany
    {
        return $this->hasMany(
            PhaseGroupStageAdvancementRule::class,
            'phase_group_stage_group_id'
        );
    }

    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'GRP%03d',
            $sequence
        );
    }
}
