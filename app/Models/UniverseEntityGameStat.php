<?php

namespace App\Models;

use App\Services\Games\GameRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| UniverseEntityGameStat
|--------------------------------------------------------------------------
|
| Las estadísticas de juego de un competidor.
|
| Pertenecen al UniverseEntity, NUNCA a la Entity de la Biblioteca. Naruto
| puede tener un rango 1.2–8.5 en un Universo y 4.0–5.0 en otro, y la
| Entity canónica sigue sin enterarse de que existe algo llamado "juego".
|
| El campo `stats` es JSON a propósito: Highest Number guarda min/max, otro
| juego guardará fuerza y velocidad, y ninguno de los dos necesita una
| migración.
|
*/

class UniverseEntityGameStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'universe_entity_id',
        'game_key',
        'stats',
    ];

    protected function casts(): array
    {
        return [
            'stats' => 'array',
        ];
    }

    public function universeEntity(): BelongsTo
    {
        return $this->belongsTo(UniverseEntity::class);
    }

    public function getDefinitionAttribute(): array
    {
        return app(GameRegistry::class)
            ->definition($this->game_key);
    }

    /*
     * Estadísticas ya saneadas por el engine. Se usa siempre esto en vez
     * de `stats` en crudo: un competidor con datos raros debe poder
     * competir igualmente.
     */
    public function getNormalizedStatsAttribute(): array
    {
        return app(GameRegistry::class)
            ->engine($this->game_key)
            ->normalizeStats($this->stats ?? []);
    }

    /*
     * Pares etiqueta/valor listos para pintar, en el orden que declara el
     * engine.
     */
    public function getDisplayStatsAttribute(): array
    {
        $values = $this->normalized_stats;

        return collect($this->definition['stats'] ?? [])
            ->map(
                fn(array $schema) => [
                    'label' => $schema['label'] ?? $schema['key'],
                    'help' => $schema['help'] ?? null,
                    'value' => $values[$schema['key']] ?? null,
                ]
            )
            ->all();
    }
}
