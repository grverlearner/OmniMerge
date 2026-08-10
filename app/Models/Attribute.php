<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class Attribute extends Model
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
        'source_attribute_id',


        /*
        |--------------------------------------------------------------------------
        | Identificación
        |--------------------------------------------------------------------------
        */

        'sequence_number',
        'code',
        'name',
        'slug',


        /*
        |--------------------------------------------------------------------------
        | Información
        |--------------------------------------------------------------------------
        */

        'description',
        'help_text',
        'placeholder',


        /*
        |--------------------------------------------------------------------------
        | Representación visual
        |--------------------------------------------------------------------------
        */

        'image',
        'icon',
        'color',


        /*
        |--------------------------------------------------------------------------
        | Tipo y comportamiento
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Restricciones
        |--------------------------------------------------------------------------
        */

        'min_numeric_value',
        'max_numeric_value',

        'min_length',
        'max_length',

        'unit',


        /*
        |--------------------------------------------------------------------------
        | Organización
        |--------------------------------------------------------------------------
        */

        'sort_order',
        'hierarchy_level',


        /*
        |--------------------------------------------------------------------------
        | Publicación
        |--------------------------------------------------------------------------
        */

        'scope',
        'status',
        'allow_cloning',

        'published_at',


        /*
        |--------------------------------------------------------------------------
        | Configuración
        |--------------------------------------------------------------------------
        */

        'default_value',
        'validation_rules',
        'configuration',


        /*
        |--------------------------------------------------------------------------
        | Comunidad
        |--------------------------------------------------------------------------
        */

        'views_count',
        'clones_count',
    ];


    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',

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

            'sort_order' => 'integer',
            'hierarchy_level' => 'integer',

            'default_value' => 'array',
            'validation_rules' => 'array',
            'configuration' => 'array',

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
        return $this->belongsTo(
            User::class
        );
    }


    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }


    public function sourceAttribute(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'source_attribute_id'
        );
    }


    public function clones(): HasMany
    {
        return $this->hasMany(
            self::class,
            'source_attribute_id'
        );
    }


    public function options(): HasMany
    {
        return $this->hasMany(
            AttributeOption::class
        )
            ->orderBy('sort_order')
            ->orderBy('name');
    }


    public function entityAttributes(): HasMany
    {
        return $this->hasMany(
            EntityAttribute::class
        );
    }

    public function entityVersionAttributes(): HasMany
    {
        return $this->hasMany(
            EntityVersionAttribute::class
        );
    }


    public function versionCatalogLinks(): HasMany
    {
        return $this->hasMany(
            VersionCatalogLink::class
        );
    }


    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeGroup::class,
            'attribute_group_attribute'
        )
            ->withPivot([
                'custom_label',
                'sort_order',
                'is_featured',
            ]);
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
    | Código automático
    |--------------------------------------------------------------------------
    |
    | 1   -> ATR000001
    | 25  -> ATR000025
    | 999 -> ATR000999
    |
    */

    public static function formatCode(
        int $sequence
    ): string {

        return sprintf(
            'ATR%06d',
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
    | Etiqueta amigable del tipo
    |--------------------------------------------------------------------------
    */

    public function getDataTypeLabelAttribute(): string
    {
        return match ($this->data_type) {

            'OPTION' =>
            'Catálogo',

            'BOOLEAN' =>
            'Sí / No',

            'TEXT' =>
            'Texto corto',

            'LONG_TEXT' =>
            'Texto largo',

            'INTEGER' =>
            'Número entero',

            'DECIMAL' =>
            'Número decimal',

            'DATE' =>
            'Fecha',

            'COLOR' =>
            'Color',

            default =>
            $this->data_type,
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Icono por tipo
    |--------------------------------------------------------------------------
    */

    public function getDataTypeIconAttribute(): string
    {
        return match ($this->data_type) {

            'OPTION' =>
            '◆',

            'BOOLEAN' =>
            '✓',

            'INTEGER',
            'DECIMAL' =>
            '#',

            'DATE' =>
            '◫',

            'COLOR' =>
            '◉',

            'LONG_TEXT' =>
            '¶',

            default =>
            'T',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Visibilidad amigable
    |--------------------------------------------------------------------------
    */

    public function getScopeLabelAttribute(): string
    {
        return match ($this->scope) {

            'PUBLIC' =>
            'Público',

            'PRIVATE' =>
            'Privado',

            'UNLISTED' =>
            'No listado',

            default =>
            $this->scope,
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Presentación amigable
    |--------------------------------------------------------------------------
    */

    public function getDisplayStyleLabelAttribute(): string
    {
        return match ($this->display_style) {

            'TEXTBOX' =>
            'Caja de texto',

            'TEXTAREA' =>
            'Área de texto',

            'NUMBER' =>
            'Campo numérico',

            'SELECT' =>
            'Selección única',

            'MULTISELECT' =>
            'Selección múltiple',

            'RADIO' =>
            'Sí / No',

            'CHECKBOX' =>
            'Casilla',

            'TAGS' =>
            'Etiquetas',

            'SLIDER' =>
            'Deslizador',

            'COLOR_PICKER' =>
            'Selector de color',

            'DATE_PICKER' =>
            'Selector de fecha',

            default =>
            $this->display_style,
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Modo de selección
    |--------------------------------------------------------------------------
    */

    public function getSelectionModeLabelAttribute(): string
    {
        if ($this->data_type === 'OPTION') {

            return $this->allows_multiple
                ? 'Múltiple'
                : 'Única';
        }


        if ($this->data_type === 'BOOLEAN') {
            return 'Sí / No';
        }


        return 'Valor único';
    }


    /*
    |--------------------------------------------------------------------------
    | Catálogo
    |--------------------------------------------------------------------------
    */

    public function usesCatalog(): bool
    {
        /*
         * OPTION será el estándar nuevo.
         *
         * Conservamos CATALOG/MIXED para compatibilidad con atributos
         * antiguos.
         */

        return $this->data_type === 'OPTION'
            || in_array(
                $this->value_source,
                [
                    'CATALOG',
                    'MIXED',
                ],
                true
            );
    }


    public function isSelectable(): bool
    {
        return $this->usesCatalog();
    }


    public function isMultiSelectable(): bool
    {
        return $this->usesCatalog()
            && $this->allows_multiple;
    }


    /*
    |--------------------------------------------------------------------------
    | Comunidad
    |--------------------------------------------------------------------------
    */

    public function isPublished(): bool
    {
        return $this->scope === 'PUBLIC'
            && $this->status === 'ACTIVE'
            && $this->published_at !== null;
    }


    public function canBeCloned(): bool
    {
        return $this->isPublished()
            && $this->allow_cloning;
    }
}
