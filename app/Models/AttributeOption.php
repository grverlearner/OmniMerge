<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AttributeOption extends Model
{
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Propiedad y procedencia
        |--------------------------------------------------------------------------
        */

        'user_id',
        'source_attribute_option_id',


        /*
        |--------------------------------------------------------------------------
        | Catálogo y jerarquía
        |--------------------------------------------------------------------------
        */

        'attribute_id',
        'parent_option_id',


        /*
        |--------------------------------------------------------------------------
        | Identidad
        |--------------------------------------------------------------------------
        */

        'sequence_number',
        'code',
        'name',
        'description',


        /*
        |--------------------------------------------------------------------------
        | Visual
        |--------------------------------------------------------------------------
        */

        'image',
        'icon',
        'color',


        /*
        |--------------------------------------------------------------------------
        | Datos
        |--------------------------------------------------------------------------
        */

        'numeric_value',
        'sort_order',
        'metadata',
        'status',
    ];


    protected function casts(): array
    {
        return [

            'sequence_number' =>
            'integer',

            'numeric_value' =>
            'decimal:4',

            'metadata' =>
            'array',

            'sort_order' =>
            'integer',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Propietario
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Catálogo propietario
    |--------------------------------------------------------------------------
    */

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(
            Attribute::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Procedencia comunitaria
    |--------------------------------------------------------------------------
    */

    public function sourceOption(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'source_attribute_option_id'
        );
    }


    public function clones(): HasMany
    {
        return $this->hasMany(
            self::class,
            'source_attribute_option_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Jerarquía
    |--------------------------------------------------------------------------
    */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_option_id'
        );
    }


    public function children(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_option_id'
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
    | Valores utilizados por entidades
    |--------------------------------------------------------------------------
    */

    public function values(): HasMany
    {
        return $this->hasMany(
            EntityAttributeValue::class,
            'attribute_option_id'
        );
    }

    public function versionCatalogLinks(): HasMany
    {
        return $this->hasMany(
            VersionCatalogLink::class,
            'attribute_option_id'
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
            'CAT%06d',
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


        if (
            ! $disk->exists(
                $this->image
            )
        ) {
            return null;
        }


        return $disk->url(
            $this->image
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Estado
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status
            === 'ACTIVE';
    }


    public function isArchived(): bool
    {
        return $this->status
            === 'ARCHIVED';
    }


    /*
    |--------------------------------------------------------------------------
    | Jerarquía
    |--------------------------------------------------------------------------
    */

    public function isRoot(): bool
    {
        return $this->parent_option_id
            === null;
    }


    public function hasChildren(): bool
    {
        return $this
            ->children()
            ->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | Cadena de antecesores
    |--------------------------------------------------------------------------
    |
    | Ejemplo:
    |
    | Perú
    |   └── Tacna
    |       └── Pocollay
    |
    | Para Pocollay devuelve:
    |
    | [Perú, Tacna]
    |
    */

    public function ancestorChain(): Collection
    {
        $ancestors =
            collect();


        $visited = [];


        $current =
            $this->parent;


        while ($current) {

            /*
             * Protección adicional ante datos cíclicos históricos.
             */

            if (
                isset(
                    $visited[$current->id]
                )
            ) {
                break;
            }


            $visited[$current->id] = true;


            $ancestors->prepend(
                $current
            );


            $current =
                $current->parent;
        }


        return $ancestors;
    }


    /*
    |--------------------------------------------------------------------------
    | Profundidad
    |--------------------------------------------------------------------------
    |
    | Raíz   = 0
    | Hijo   = 1
    | Nieto  = 2
    |
    */

    public function getHierarchyDepthAttribute(): int
    {
        return $this
            ->ancestorChain()
            ->count();
    }


    /*
    |--------------------------------------------------------------------------
    | Detectar descendencia
    |--------------------------------------------------------------------------
    |
    | Sirve para impedir:
    |
    | A
    | └── B
    |     └── C
    |
    | y después intentar:
    |
    | A.parent = C
    |
    */

    public function isDescendantOf(
        AttributeOption $possibleAncestor
    ): bool {

        $visited = [];


        $current =
            $this->parent;


        while ($current) {

            if (
                $current->is(
                    $possibleAncestor
                )
            ) {
                return true;
            }


            if (
                isset(
                    $visited[$current->id]
                )
            ) {
                break;
            }


            $visited[$current->id] = true;


            $current =
                $current->parent;
        }


        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Nombre del nivel
    |--------------------------------------------------------------------------
    */

    public function getHierarchyLabelAttribute(): string
    {
        if ($this->isRoot()) {
            return 'Nivel principal';
        }


        return 'Nivel '
            . $this->hierarchy_depth;
    }
}
