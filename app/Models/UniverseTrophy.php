<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| UniverseTrophy
|--------------------------------------------------------------------------
|
| Un trofeo del Universo. Pertenece al mundo, no a la Biblioteca: la
| Entity canónica jamás sabe que ganó nada.
|
*/

class UniverseTrophy extends Model
{
    use HasFactory;

    protected $table = 'universe_trophies';

    protected $fillable = [
        'universe_id',
        'name',
        'description',
        'icon',
        'image',
        'tier',
    ];

    public const TIERS = [
        'GOLD' => 'Oro',
        'SILVER' => 'Plata',
        'BRONZE' => 'Bronce',
        'SPECIAL' => 'Especial',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(UniverseTrophyAward::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return Storage::disk('public')->url($this->image);
    }

    public function getDisplayIconAttribute(): string
    {
        return $this->icon ?: '🏆';
    }
}
