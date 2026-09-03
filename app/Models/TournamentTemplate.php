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


class TournamentTemplate extends Model
{
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [

        'user_id',

        'source_tournament_template_id',

        'sequence_number',

        'code',

        'name',

        'slug',

        'description',

        'image',

        'min_participants',

        'max_participants',

        'allow_byes',

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

            'sequence_number' =>
            'integer',

            'min_participants' =>
            'integer',

            'max_participants' =>
            'integer',

            'allow_byes' =>
            'boolean',

            'allow_cloning' =>
            'boolean',

            'views_count' =>
            'integer',

            'clones_count' =>
            'integer',

            'published_at' =>
            'datetime',

            'settings' =>
            'array',

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


    /*
     * Usos de esta plantilla dentro de Universos.
     *
     * Una plantilla es un diseño reutilizable: puede ser adoptada
     * por varios Universos a la vez (docs/md/09-Para Futuro.md §57).
     */
    public function universeTournaments(): HasMany
    {
        return $this->hasMany(
            UniverseTournament::class
        );
    }


    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }


    public function sourceTemplate(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'source_tournament_template_id'
        );
    }

    public function tournamentGraphNodes(): HasMany
    {
        return $this->hasMany(
            TournamentPhaseNode::class
        );
    }


    public function clones(): HasMany
    {
        return $this->hasMany(
            self::class,
            'source_tournament_template_id'
        );
    }


    public function graphNodes(): HasMany
    {
        return $this
            ->hasMany(
                TournamentPhaseNode::class
            )
            ->orderBy('sequence_number')
            ->orderBy('id');
    }


    public function graphConnections(): HasMany
    {
        return $this
            ->hasMany(
                TournamentPhaseConnection::class
            )
            ->orderBy('priority')
            ->orderBy('sequence_number')
            ->orderBy('id');
    }


    public function graphStarts(): HasMany
    {
        return $this
            ->hasMany(
                TournamentStart::class
            )
            ->orderBy('sequence_number')
            ->orderBy('id');
    }


    public function graphTerminals(): HasMany
    {
        return $this
            ->hasMany(
                TournamentTerminal::class
            )
            ->orderBy('sequence_number')
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
            ->where(
                'visibility',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
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


        if (! $disk->exists(
            $this->image
        )) {
            return null;
        }


        return $disk->url(
            $this->image
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Estado público
    |--------------------------------------------------------------------------
    */


    public function isPublished(): bool
    {
        return
            $this->visibility === 'PUBLIC'
            &&
            $this->status === 'ACTIVE'
            &&
            $this->published_at !== null;
    }


    public function canBeCloned(): bool
    {
        return
            $this->isPublished()
            &&
            $this->allow_cloning;
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
            'TRN%06d',
            $sequence
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Etiquetas
    |--------------------------------------------------------------------------
    */


    /*
     * Cómo se reconoce una plantilla en la biblioteca.
     *
     * Icono, color, frase, categoría y etiquetas viven en `settings` y no en
     * columnas propias porque no los lee ningún motor: solo la biblioteca,
     * para poder recorrer cuarenta plantillas sin abrirlas una a una. Si no
     * se elige ninguno, hay uno por defecto: una plantilla nunca se queda
     * sin cara.
     */

    public const CATEGORIES = [
        'CUP' => 'Copa',
        'LEAGUE' => 'Liga',
        'QUALIFIER' => 'Clasificatorio',
        'FRIENDLY' => 'Amistoso',
        'RANKING' => 'Ranking',
        'SPECIAL' => 'Especial',
    ];

    public const ACCENTS = [
        'amber', 'violet', 'cyan', 'emerald', 'rose', 'sky', 'slate',
    ];

    public function getDisplayIconAttribute(): string
    {
        $propio = trim((string) data_get($this->settings, 'icon', ''));

        if ($propio !== '') {
            return $propio;
        }

        return match ((string) data_get($this->settings, 'category', '')) {
            'LEAGUE' => '≡',
            'QUALIFIER' => '⇢',
            'FRIENDLY' => '◇',
            'RANKING' => '▲',
            'SPECIAL' => '✦',
            default => '🏆',
        };
    }

    public function getAccentAttribute(): string
    {
        $propio = (string) data_get($this->settings, 'accent', '');

        if (in_array($propio, self::ACCENTS, true)) {
            return $propio;
        }

        return match ((string) data_get($this->settings, 'category', '')) {
            'LEAGUE' => 'cyan',
            'QUALIFIER' => 'sky',
            'FRIENDLY' => 'emerald',
            'RANKING' => 'violet',
            'SPECIAL' => 'rose',
            default => 'amber',
        };
    }

    public function getSummaryAttribute(): ?string
    {
        $propio = trim((string) data_get($this->settings, 'summary', ''));

        return $propio !== '' ? $propio : null;
    }

    public function getCategoryAttribute(): ?string
    {
        $propio = (string) data_get($this->settings, 'category', '');

        return array_key_exists($propio, self::CATEGORIES) ? $propio : null;
    }

    public function getCategoryLabelAttribute(): ?string
    {
        return self::CATEGORIES[$this->category] ?? null;
    }

    /* @return array<int,string> */
    public function getTagsAttribute(): array
    {
        $guardadas = data_get($this->settings, 'tags', []);

        if (! is_array($guardadas)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($etiqueta) => trim((string) $etiqueta),
            $guardadas
        )));
    }


    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {

            'DRAFT' =>
            'Borrador',

            'ACTIVE' =>
            'Activa',

            'ARCHIVED' =>
            'Archivada',

            default =>
            $this->status,
        };
    }


    public function getVisibilityLabelAttribute(): string
    {
        return match ($this->visibility) {

            'PUBLIC' =>
            'Pública',

            'PRIVATE' =>
            'Privada',

            'UNLISTED' =>
            'No listada',

            default =>
            $this->visibility,
        };
    }


    public function getParticipantRangeLabelAttribute(): string
    {
        if (
            $this->max_participants === null
        ) {

            return
                $this->min_participants
                . '+ participantes';
        }


        if (
            $this->min_participants
            ===
            $this->max_participants
        ) {

            return
                $this->min_participants
                . ' participantes';
        }


        return
            $this->min_participants
            . '–'
            . $this->max_participants
            . ' participantes';
    }
}
