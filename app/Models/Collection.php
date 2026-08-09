<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'slug',
        'description',
        'image',
        'icon',
        'color',
        'visibility',
        'status',
        'sort_order',
        'metadata',
        'source_collection_id',
        'sequence_number',
        'allow_cloning',
        'views_count',
        'clones_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sort_order' => 'integer',
            'allow_cloning' => 'boolean',
            'views_count' => 'integer',
            'clones_count' => 'integer',
            'published_at' => 'datetime',
            'sequence_number' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(
            Entity::class,
            'collection_entity'
        )
            ->withPivot([
                'sort_order',
                'notes',
                'added_at',
            ])
            ->orderByPivot('sort_order');
    }

    public function scopeOwnedBy(
        Builder $query,
        User $user
    ): Builder {
        return $query->where('user_id', $user->id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->image)) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($this->image);
    }

    public function sourceCollection(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'source_collection_id'
        );
    }

    public function clones(): HasMany
    {
        return $this->hasMany(
            self::class,
            'source_collection_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
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

    public static function formatCode(
        int $sequence
    ): string {
        return sprintf(
            'COL%06d',
            $sequence
        );
    }

    public function getVisibilityLabelAttribute(): string
    {
        return match ($this->visibility) {
            'PUBLIC' => 'Público',
            'PRIVATE' => 'Privado',
            'UNLISTED' => 'No listado',
            default => $this->visibility,
        };
    }
}
