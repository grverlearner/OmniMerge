<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class Version extends Model
{
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [
        'user_id',
        'source_version_id',
        'parent_version_id',

        'sequence_number',
        'code',
        'name',
        'slug',

        'description',
        'image',

        'version_kind',
        'scope',
        'activation_mode',

        'priority',
        'sort_order',

        'status',
        'metadata',
    ];


    protected function casts(): array
    {
        return [
            'sequence_number' =>
            'integer',

            'priority' =>
            'integer',

            'sort_order' =>
            'integer',

            'metadata' =>
            'array',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }


    public function sourceVersion(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'source_version_id'
        );
    }


    public function clones(): HasMany
    {
        return $this->hasMany(
            self::class,
            'source_version_id'
        );
    }


    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_version_id'
        );
    }


    public function children(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_version_id'
        )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            );
    }


    public function entityVersions(): HasMany
    {
        return $this->hasMany(
            EntityVersion::class
        )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            );
    }


    public function catalogLinks(): HasMany
    {
        return $this->hasMany(
            VersionCatalogLink::class
        )
            ->orderBy(
                'condition_group'
            )
            ->orderByDesc(
                'priority'
            );
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


    /*
    |--------------------------------------------------------------------------
    | Código
    |--------------------------------------------------------------------------
    */

    public static function formatCode(
        int $sequence
    ): string {

        return sprintf(
            'VER%06d',
            $sequence
        );
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
        $disk =
            Storage::disk(
                'public'
            );


        if (! $disk->exists($this->image)) {
            return null;
        }


        return $disk->url(
            $this->image
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isExclusive(): bool
    {
        return $this->scope
            === 'EXCLUSIVE';
    }


    public function isShared(): bool
    {
        return $this->scope
            === 'SHARED';
    }


    public function canAutoActivate(): bool
    {
        return in_array(
            $this->activation_mode,
            [
                'AUTO',
                'BOTH',
            ],
            true
        );
    }


    public function getKindLabelAttribute(): string
    {
        return match ($this->version_kind) {

            'ERA' =>
            'Era',

            'AGE' =>
            'Edad',

            'FORM' =>
            'Forma',

            'TRANSFORMATION' =>
            'Transformación',

            'OUTFIT' =>
            'Apariencia',

            'TIMELINE' =>
            'Línea temporal',

            default =>
            'Otra',
        };
    }


    public function getScopeLabelAttribute(): string
    {
        return $this->scope
            === 'EXCLUSIVE'
            ? 'Exclusiva'
            : 'Compartida';
    }


    public function getActivationLabelAttribute(): string
    {
        return match ($this->activation_mode) {

            'AUTO' =>
            'Automática',

            'MANUAL' =>
            'Manual',

            default =>
            'Automática y manual',
        };
    }


    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {

            'ACTIVE' =>
            'Activa',

            'INACTIVE' =>
            'Inactiva',

            'ARCHIVED' =>
            'Archivada',

            default =>
            $this->status,
        };
    }
}
