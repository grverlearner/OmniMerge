<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class Entity extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'source_entity_id',

        'sequence_number',
        'entity_type_id',

        'code',
        'name',
        'slug',

        'description',
        'image',

        'status',
        'visibility',
        'metadata',

        'allow_cloning',
        'views_count',
        'clones_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'metadata' => 'array',
            'allow_cloning' => 'boolean',
            'views_count' => 'integer',
            'clones_count' => 'integer',
            'published_at' => 'datetime',
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
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function entityType(): BelongsTo
    {
        return $this->belongsTo(
            EntityType::class
        );
    }

    public function sourceEntity(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'source_entity_id'
        );
    }

    public function clones(): HasMany
    {
        return $this->hasMany(
            self::class,
            'source_entity_id'
        );
    }

    public function entityAttributes(): HasMany
    {
        return $this->hasMany(
            EntityAttribute::class
        )
            ->orderBy('sort_order');
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

    /*
|--------------------------------------------------------------------------
| Presentación pública
|--------------------------------------------------------------------------
*/

    public function presentation(): HasOne
    {
        return $this->hasOne(
            EntityPresentation::class
        );
    }


    /*
|--------------------------------------------------------------------------
| EntityVersion utilizada públicamente
|--------------------------------------------------------------------------
*/

    public function getPublicEntityVersionAttribute(): ?EntityVersion
    {
        $presentation =
            $this->resolvedPresentation();


        if (
            ! $presentation
            ||
            $presentation->mode === 'BASE'
        ) {
            return null;
        }


        return $presentation
            ->entityVersion;
    }


    /*
|--------------------------------------------------------------------------
| Nombre público
|--------------------------------------------------------------------------
*/

    public function getPublicDisplayNameAttribute(): string
    {
        $presentation =
            $this->resolvedPresentation();


        if (
            ! $presentation
            ||
            $presentation->mode === 'BASE'
            ||
            ! $presentation->use_version_name
            ||
            ! $presentation->entityVersion
        ) {
            return $this->name;
        }


        return $presentation
            ->entityVersion
            ->name
            ?: $this->name;
    }


    /*
|--------------------------------------------------------------------------
| Descripción pública
|--------------------------------------------------------------------------
*/

    public function getPublicDescriptionAttribute(): ?string
    {
        $presentation =
            $this->resolvedPresentation();


        if (
            ! $presentation
            ||
            $presentation->mode === 'BASE'
            ||
            ! $presentation->use_version_description
            ||
            ! $presentation->entityVersion
        ) {
            return $this->description;
        }


        return $presentation
            ->entityVersion
            ->description
            ?: $this->description;
    }


    /*
|--------------------------------------------------------------------------
| Imagen pública
|--------------------------------------------------------------------------
*/

    public function getPublicImageUrlAttribute(): ?string
    {
        $presentation =
            $this->resolvedPresentation();


        if (
            ! $presentation
            ||
            $presentation->mode === 'BASE'
        ) {
            return $this->image_url;
        }


        /*
     * Imagen concreta de Multimedia.
     */
        if (
            $presentation->mode === 'VERSION_MEDIA'
            &&
            $presentation->mediaImage
        ) {
            return $presentation
                ->mediaImage
                ->image_url
                ?: $presentation
                ->entityVersion
                ?->image_url
                ?: $this->image_url;
        }


        /*
     * Portada de EntityVersion.
     */
        return $presentation
            ->entityVersion
            ?->image_url
            ?: $this->image_url;
    }


    /*
|--------------------------------------------------------------------------
| Resolver presentación sin provocar consultas innecesarias
|--------------------------------------------------------------------------
*/

    private function resolvedPresentation(): ?EntityPresentation
    {
        if (
            $this->relationLoaded(
                'presentation'
            )
        ) {
            return $this->getRelation(
                'presentation'
            );
        }


        return $this
            ->presentation()
            ->with([
                'entityVersion.version',
                'mediaImage',
            ])
            ->first();
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(
            Collection::class,
            'collection_entity'
        )
            ->withPivot([
                'sort_order',
                'notes',
                'added_at',
            ])
            ->orderByPivot('sort_order');
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
            'ENT%06d',
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
        $disk = Storage::disk('public');

        if (! $disk->exists($this->image)) {
            return null;
        }

        return $disk->url(
            $this->image
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Etiquetas
    |--------------------------------------------------------------------------
    */

    public function getVisibilityLabelAttribute(): string
    {
        return match ($this->visibility) {
            'PUBLIC' => 'Público',
            'PRIVATE' => 'Privado',
            'UNLISTED' => 'No listado',
            default => $this->visibility,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ACTIVE' => 'Activo',
            'INACTIVE' => 'Inactivo',
            'ARCHIVED' => 'Archivado',
            default => $this->status,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Comunidad
    |--------------------------------------------------------------------------
    */

    public function isPublic(): bool
    {
        return $this->visibility === 'PUBLIC';
    }

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
}
