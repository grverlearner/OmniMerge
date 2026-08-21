<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| UniverseEntity
|--------------------------------------------------------------------------
|
| Entidad PROPIA de un Universo.
|
| No es un enlace a la Biblioteca: es una copia independiente. Su nombre,
| imagen, tipo, atributos y versiones se copiaron al importarla, y a
| partir de ahí evoluciona por su cuenta.
|
|   Biblioteca (Entity)  →  importar  →  UniverseEntity  →  Torneos
|
| Editar la Entity de origen NO altera esta copia: importar no es
| sincronizar. source_entity_id solo indica de dónde vino.
|
| Ver docs/md/27-Entidades-Propias-Del-Universo.md
|
*/

class UniverseEntity extends Model
{
    use HasFactory;

    protected $table = 'universe_entities';

    protected $fillable = [

        'universe_id',

        'sequence_number',
        'code',

        /* Copia propia */
        'name',
        'description',
        'image',
        'entity_type_name',
        'attribute_snapshot',
        'version_snapshot',

        /* Procedencia */
        'source_entity_id',
        'source_entity_version_id',
        'imported_at',

        'display_name',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'attribute_snapshot' => 'array',
            'version_snapshot' => 'array',
            'imported_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function universe(): BelongsTo
    {
        return $this->belongsTo(
            Universe::class
        );
    }

    /*
     * Solo procedencia. Puede ser nula si la Entidad se borró de la
     * Biblioteca: esta copia sigue existiendo igualmente.
     */
    public function sourceEntity(): BelongsTo
    {
        return $this->belongsTo(
            Entity::class,
            'source_entity_id'
        );
    }

    public function sourceEntityVersion(): BelongsTo
    {
        return $this->belongsTo(
            EntityVersion::class,
            'source_entity_version_id'
        );
    }

    /*
     * Participaciones en competiciones de este Universo.
     */
    public function participations(): HasMany
    {
        return $this->hasMany(
            TournamentInstanceParticipant::class,
            'universe_entity_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

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
    | Presentación
    |--------------------------------------------------------------------------
    */

    /*
     * El alias manda sobre el nombre copiado. Se conserva display_name
     * porque ya existía y permite renombrar dentro del Universo sin
     * perder de qué se importó.
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->display_name
            ?: ($this->name ?: 'Entidad');
    }

    /*
     * La imagen es la copia propia; solo si no hay copia se recurre a la
     * de origen, y únicamente para no dejar el hueco vacío.
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {

            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk('public');

            if ($disk->exists($this->image)) {
                return $disk->url($this->image);
            }
        }

        return $this->sourceEntity?->image_url;
    }

    public static function formatCode(
        int $sequence
    ): string {

        return sprintf(
            'UEN%06d',
            $sequence
        );
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {

            'ACTIVE' =>
            'Activa',

            'INACTIVE' =>
            'Inactiva',

            'RETIRED' =>
            'Retirada',

            default =>
            $this->status,
        };
    }

    public static function statuses(): array
    {
        return [
            'ACTIVE' => 'Activa',
            'INACTIVE' => 'Inactiva',
            'RETIRED' => 'Retirada',
        ];
    }
}
