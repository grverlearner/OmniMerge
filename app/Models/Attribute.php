<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Attribute extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'source_attribute_id',
        'code',
        'name',
        'image',
        'icon',
        'color',
        'slug',
        'description',
        'help_text',
        'placeholder',
        'data_type',
        'value_source',
        'display_style',
        'allows_multiple',
        'allows_custom_values',
        'is_required',
        'is_filterable',
        'is_comparable',
        'is_searchable',
        'is_visible',
        'is_featured',
        'min_numeric_value',
        'max_numeric_value',
        'min_length',
        'max_length',
        'unit',
        'sort_order',
        'hierarchy_level',
        'scope',
        'default_value',
        'validation_rules',
        'configuration',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'allows_multiple' => 'boolean',
            'allows_custom_values' => 'boolean',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'is_comparable' => 'boolean',
            'is_searchable' => 'boolean',
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
            'min_numeric_value' => 'decimal:4',
            'max_numeric_value' => 'decimal:4',
            'default_value' => 'array',
            'validation_rules' => 'array',
            'configuration' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceAttribute(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'source_attribute_id'
        );
    }

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function entityAttributes(): HasMany
    {
        return $this->hasMany(EntityAttribute::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeGroup::class,
            'attribute_group_attribute'
        )->withPivot([
            'custom_label',
            'sort_order',
            'is_featured',
        ]);
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

    public function usesCatalog(): bool
    {
        return in_array(
            $this->value_source,
            ['CATALOG', 'MIXED'],
            true
        );
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

    public function isSelectable(): bool
    {
        return $this->data_type === 'OPTION'
            || in_array(
                $this->value_source,
                ['CATALOG', 'MIXED'],
                true
            );
    }

    public function isMultiSelectable(): bool
    {
        return $this->isSelectable()
            && $this->allows_multiple;
    }
}
