<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentInstanceParticipant;
use App\Models\UniverseEntity;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| CompetitorFaces
|--------------------------------------------------------------------------
|
| La cara con la que JUGÓ un competidor.
|
| Al crear una edición se elige la versión de cada competidor -la del niño
| en un torneo de los niños de Konoha- y se congela su id. Pero las tablas,
| el cuadro, la batalla y el campeón seguían pintando la imagen de siempre
| de la entidad, así que la elección no se veía en ningún sitio.
|
| Aquí se lee la imagen de la versión congelada desde la copia que guarda la
| propia entidad del universo; si no hay versión, o su archivo ya no está,
| la de siempre.
|
| Ver docs/md/79-Sala-De-Participantes.md
|
*/

class CompetitorFaces
{
    /** @var array<int,array<string,?string>> edición => clave => url */
    private static array $porEdicion = [];

    public static function url(?UniverseEntity $entity, ?int $entityVersionId): ?string
    {
        if (! $entity) {
            return null;
        }

        if ($entityVersionId) {
            foreach ((array) ($entity->version_snapshot ?? []) as $version) {
                if (is_array($version) && (int) ($version['id'] ?? 0) === $entityVersionId && ! empty($version['image'])) {
                    $disco = Storage::disk('public');

                    if ($disco->exists($version['image'])) {
                        return $disco->url($version['image']);
                    }
                }
            }
        }

        return $entity->image_url;
    }

    /*
     * Para las filas que solo conocen la clave del competidor -las de cada
     * fase-. Se leen todos los de la edición de una vez.
     */
    public static function byRuntimeKey(int $instanceId, ?string $key, ?UniverseEntity $fallback = null): ?string
    {
        self::$porEdicion[$instanceId] ??= TournamentInstanceParticipant::query()
            ->where('tournament_instance_id', $instanceId)
            ->with('universeEntity')
            ->get()
            ->mapWithKeys(fn ($p) => [
                (string) $p->runtime_key => self::url($p->universeEntity, $p->entity_version_id ? (int) $p->entity_version_id : null),
            ])
            ->all();

        return ($key !== null ? (self::$porEdicion[$instanceId][$key] ?? null) : null) ?? $fallback?->image_url;
    }
}
