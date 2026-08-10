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
use Illuminate\Database\Eloquent\Relations\HasOne;

class EntityVersion extends Model
{
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [
        'user_id',

        'entity_id',
        'version_id',

        'parent_entity_version_id',
        'source_entity_version_id',

        'sequence_number',
        'code',
        'name',
        'slug',

        'description',
        'image',

        'inherit_base_attributes',
        'is_default',

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

            'inherit_base_attributes' =>
            'boolean',

            'is_default' =>
            'boolean',

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


    public function entity(): BelongsTo
    {
        return $this->belongsTo(
            Entity::class
        );
    }


    public function version(): BelongsTo
    {
        return $this->belongsTo(
            Version::class
        );
    }


    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_entity_version_id'
        );
    }


    public function children(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_entity_version_id'
        )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            );
    }


    public function sourceEntityVersion(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'source_entity_version_id'
        );
    }


    public function clones(): HasMany
    {
        return $this->hasMany(
            self::class,
            'source_entity_version_id'
        );
    }


    public function versionAttributes(): HasMany
    {
        return $this->hasMany(
            EntityVersionAttribute::class
        )
            ->orderBy(
                'sort_order'
            );
    }


    public function images(): HasMany
    {
        return $this->hasMany(
            EntityVersionImage::class
        )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'id'
            );
    }

    /*
|--------------------------------------------------------------------------
| Base activa
|--------------------------------------------------------------------------
*/

    public function baseSetting(): HasOne
    {
        return $this->hasOne(
            EntityBaseVersion::class,
            'entity_version_id'
        );
    }


    /*
|--------------------------------------------------------------------------
| Saber si esta EntityVersion es Base activa
|--------------------------------------------------------------------------
*/

    public function getIsActiveBaseAttribute(): bool
    {
        if (
            $this->relationLoaded(
                'baseSetting'
            )
        ) {

            return $this->baseSetting
                !==
                null;
        }


        return $this
            ->baseSetting()
            ->exists();
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
            'EVR%06d',
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
