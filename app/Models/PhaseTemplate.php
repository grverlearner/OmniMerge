<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class PhaseTemplate extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'phase_template_id',

        'configuration_mode',
        'input_mode',
        'routing_mode',

        'entrants_per_match',
        'qualifiers_per_match',
        'encounter_profile',
        'remainder_policy',

        'completion_mode',
        'target_survivors',

        'seeding_mode',
        'pairing_mode',

        'bye_assignment',
        'reseed_each_round',

        'series_format',
        'default_best_of',
        'fixed_games',

        'structure_mode',
        'structure_status',
        'structure_version',
        'structure_fingerprint',
        'structure_generated_at',
        'structure_validated_at',

        'settings',
    ];

    protected function casts(): array
    {
        return [
            'entrants_per_match' =>
            'integer',

            'qualifiers_per_match' =>
            'integer',

            'target_survivors' =>
            'integer',

            'reseed_each_round' =>
            'boolean',

            'default_best_of' =>
            'integer',

            'fixed_games' =>
            'integer',

            'sequence_number' => 'integer',
            'min_participants' => 'integer',
            'max_participants' => 'integer',
            'exact_participants' => 'integer',
            'participant_multiple' => 'integer',
            'allow_byes' => 'boolean',
            'best_of' => 'integer',
            'allow_cloning' => 'boolean',
            'views_count' => 'integer',
            'clones_count' => 'integer',
            'published_at' => 'datetime',
            'settings' => 'array',
            'metadata' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sourceTemplate(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'source_phase_template_id'
        );
    }

    public function clones(): HasMany
    {
        return $this->hasMany(
            self::class,
            'source_phase_template_id'
        );
    }

    public function exits(): HasMany
    {
        return $this->hasMany(PhaseExit::class)
            ->orderBy('sort_order')
            ->orderBy('priority')
            ->orderBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Contrato de entrada reutilizable
    |--------------------------------------------------------------------------
    */

    public function inputGates(): HasMany
    {
        return $this
            ->hasMany(
                PhaseInputGate::class
            )
            ->orderBy('sort_order')
            ->orderBy('priority')
            ->orderBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Estructura interna de Eliminación Simple
    |--------------------------------------------------------------------------
    */

    public function singleEliminationRounds(): HasMany
    {
        return $this
            ->hasMany(
                PhaseSingleEliminationRound::class
            )
            ->orderBy('sort_order')
            ->orderBy('stage_number')
            ->orderBy('id');
    }

    public function singleEliminationEncounters(): HasMany
    {
        return $this
            ->hasMany(
                PhaseSingleEliminationEncounter::class
            )
            ->orderBy('sort_order')
            ->orderBy('sequence_number')
            ->orderBy('id');
    }

    public function singleEliminationConnections(): HasMany
    {
        return $this
            ->hasMany(
                PhaseSingleEliminationConnection::class
            )
            ->orderBy('priority')
            ->orderBy('sequence_number')
            ->orderBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Usos contextuales en Tournament Graph
    |--------------------------------------------------------------------------
    */

    public function tournamentPhaseNodes(): HasMany
    {
        return $this->hasMany(
            TournamentPhaseNode::class
        );
    }

    public function singleEliminationSetting(): HasOne
    {
        return $this->hasOne(
            PhaseSingleEliminationSetting::class
        );
    }

    public function singleEliminationRoundRules(): HasMany
    {
        return $this
            ->hasMany(
                PhaseSingleEliminationRoundRule::class
            )
            ->orderByDesc(
                'participants_in_round'
            );
    }

    public function roundRobinSetting(): HasOne
    {
        return $this->hasOne(
            PhaseRoundRobinSetting::class
        );
    }

    public function roundRobinTiebreakers(): HasMany
    {
        return $this
            ->hasMany(
                PhaseRoundRobinTiebreaker::class
            )
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function groupStageSetting(): HasOne
    {
        return $this->hasOne(
            PhaseGroupStageSetting::class
        );
    }

    public function groupStageGroups(): HasMany
    {
        return $this
            ->hasMany(
                PhaseGroupStageGroup::class
            )
            ->orderBy('sort_order')
            ->orderBy('sequence_number')
            ->orderBy('id');
    }

    public function groupStageAdvancementRules(): HasMany
    {
        return $this
            ->hasMany(
                PhaseGroupStageAdvancementRule::class
            )
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function groupStageTiebreakers(): HasMany
    {
        return $this
            ->hasMany(
                PhaseGroupStageTiebreaker::class
            )
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function swissSetting(): HasOne
    {
        return $this->hasOne(
            PhaseSwissSetting::class
        );
    }

    public function swissTiebreakers(): HasMany
    {
        return $this
            ->hasMany(
                PhaseSwissTiebreaker::class
            )
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function swissRoundRules(): HasMany
    {
        return $this
            ->hasMany(
                PhaseSwissRoundRule::class
            )
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function swissAdvancementRules(): HasMany
    {
        return $this
            ->hasMany(
                PhaseSwissAdvancementRule::class
            )
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOwnedBy(
        Builder $query,
        User $user
    ): Builder {
        return $query->where(
            'user_id',
            $user->id
        );
    }

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            'ACTIVE'
        );
    }

    public function scopePublished(
        Builder $query
    ): Builder {
        return $query
            ->where('visibility', 'PUBLIC')
            ->where('status', 'ACTIVE')
            ->whereNotNull('published_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Imagen
    |--------------------------------------------------------------------------
    */

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (! $disk->exists($this->image)) {
            return null;
        }

        return $disk->url($this->image);
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
            'PHS%06d',
            $sequence
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Estado público
    |--------------------------------------------------------------------------
    */

    public function isPublished(): bool
    {
        return $this->visibility === 'PUBLIC'
            && $this->status === 'ACTIVE'
            && $this->published_at !== null;
    }

    public function canBeCloned(): bool
    {
        return $this->isPublished()
            && $this->allow_cloning;
    }

    /*
    |--------------------------------------------------------------------------
    | Etiquetas
    |--------------------------------------------------------------------------
    */

    public function getTypeLabelAttribute(): string
    {
        return match ($this->phase_type) {
            'SINGLE_ELIMINATION' => 'Eliminación directa',
            'ROUND_ROBIN' => 'Todos contra todos',
            'GROUP_STAGE' => 'Fase de grupos',
            'LEAGUE' => 'Liga / División',
            'SWISS' => 'Sistema suizo',
            'CUSTOM' => 'Personalizada',
            default => $this->phase_type,
        };
    }

    public function getParticipantModeLabelAttribute(): string
    {
        return match ($this->participant_mode) {
            'INDIVIDUAL' => 'Individual',
            'TEAM' => 'Equipos',
            'FLEXIBLE' => 'Flexible',
            default => $this->participant_mode,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'DRAFT' => 'Borrador',
            'ACTIVE' => 'Activa',
            'ARCHIVED' => 'Archivada',
            default => $this->status,
        };
    }

    public function getVisibilityLabelAttribute(): string
    {
        return match ($this->visibility) {
            'PRIVATE' => 'Privada',
            'PUBLIC' => 'Pública',
            'UNLISTED' => 'No listada',
            default => $this->visibility,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Contrato de entrada
    |--------------------------------------------------------------------------
    */

    public function getParticipantContractLabelAttribute(): string
    {
        if ($this->exact_participants !== null) {
            return $this->exact_participants
                . ' participantes exactos';
        }

        if ($this->max_participants === null) {
            $label = $this->min_participants
                . '+ participantes';
        } elseif (
            $this->min_participants
            ===
            $this->max_participants
        ) {
            $label = $this->min_participants
                . ' participantes';
        } else {
            $label = $this->min_participants
                . '–'
                . $this->max_participants
                . ' participantes';
        }

        if (
            $this->participant_multiple
            &&
            $this->participant_multiple > 1
        ) {
            $label .= ' · múltiplo de '
                . $this->participant_multiple;
        }

        return $label;
    }
}
