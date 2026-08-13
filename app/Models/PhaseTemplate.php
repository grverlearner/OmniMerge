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
        'user_id',
        'source_phase_template_id',
        'sequence_number',
        'code',
        'name',
        'slug',
        'description',
        'image',
        'phase_type',
        'participant_mode',
        'min_participants',
        'max_participants',
        'exact_participants',
        'participant_multiple',
        'allow_byes',
        'best_of',
        'status',
        'visibility',
        'allow_cloning',
        'views_count',
        'clones_count',
        'published_at',
        'settings',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
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
