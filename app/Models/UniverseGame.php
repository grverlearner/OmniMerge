<?php

namespace App\Models;

use App\Services\Games\GameRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| UniverseGame
|--------------------------------------------------------------------------
|
| Qué juegos usa un Universo y con qué configuración.
|
| El juego en sí no vive aquí: vive en código (GameRegistry). Esta tabla
| solo guarda la decisión del usuario sobre ese juego dentro de su mundo.
|
*/

class UniverseGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'universe_id',
        'game_key',
        'is_enabled',
        'is_default',
        'configuration',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
            'configuration' => 'array',
        ];
    }

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    /*
     * La definición viene del engine, no de la base de datos: si el juego
     * cambia sus reglas, el Universo las hereda sin migración.
     */
    public function getDefinitionAttribute(): array
    {
        return app(GameRegistry::class)
            ->definition($this->game_key);
    }

    public function getNameAttribute(): string
    {
        return $this->definition['name'] ?? $this->game_key;
    }

    public function getIconAttribute(): string
    {
        return $this->definition['icon'] ?? '🎲';
    }
}
