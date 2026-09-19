<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| Una marca que pone un admin sobre un contenido
|--------------------------------------------------------------------------
|
|   VERIFIED   verificado por el equipo: es lo que dice ser
|   TRUSTED    confiable: de un creador o una pieza de calidad probada
|   FEATURED   destacado: una recomendación del equipo
|   HIDDEN     oculto: deja de verse en la comunidad sin borrarse
|
| Las tres primeras son solo etiquetas, no cambian permisos. La cuarta sí
| cambia algo: saca el contenido de la comunidad.
|
*/
class ContentFlag extends Model
{
    public const FLAGS = [
        'VERIFIED' => ['label' => 'Verificado', 'icon' => 'check', 'tone' => '#38bdf8'],
        'TRUSTED' => ['label' => 'Confiable', 'icon' => 'medalla', 'tone' => '#34d399'],
        'FEATURED' => ['label' => 'Destacado', 'icon' => 'chispa', 'tone' => '#fbbf24'],
        'HIDDEN' => ['label' => 'Oculto', 'icon' => 'ojo-tachado', 'tone' => '#fb7185'],
    ];

    /* Las que se enseñan como insignia al público */
    public const PUBLIC_FLAGS = ['VERIFIED', 'TRUSTED', 'FEATURED'];

    protected $fillable = [
        'content_type',
        'content_id',
        'flag',
        'note',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMetaAttribute(): array
    {
        return self::FLAGS[$this->flag] ?? ['label' => $this->flag, 'icon' => 'punto', 'tone' => '#94a3b8'];
    }
}
