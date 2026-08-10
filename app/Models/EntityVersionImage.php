<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class EntityVersionImage extends Model
{
    use HasFactory;


    protected $fillable = [
        'entity_version_id',

        'image',

        'caption',
        'media_type',
        'alt_text',

        'sort_order',
    ];


    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function entityVersion(): BelongsTo
    {
        return $this->belongsTo(
            EntityVersion::class
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
        $disk = Storage::disk(
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
    | Tipo amigable
    |--------------------------------------------------------------------------
    */

    public function getMediaTypeLabelAttribute(): string
    {
        return match ($this->media_type) {

            'PORTRAIT' =>
            'Retrato',

            'FULL_BODY' =>
            'Cuerpo completo',

            'COMBAT' =>
            'Combate',

            'OUTFIT' =>
            'Apariencia',

            'REFERENCE' =>
            'Referencia',

            'ALTERNATIVE' =>
            'Alternativa',

            default =>
            'Otra',
        };
    }
}
