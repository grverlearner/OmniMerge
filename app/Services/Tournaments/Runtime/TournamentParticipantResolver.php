<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\UniverseEntity;

/*
|--------------------------------------------------------------------------
| TournamentParticipantResolver
|--------------------------------------------------------------------------
|
| Prepara el contexto que llevará un participante dentro del torneo.
|
| Lee de la ENTIDAD DEL UNIVERSO, nunca de la Biblioteca: lo que juega es
| lo que se importó. Antes resolvía versión y atributos contra la Entity
| original, lo que hacía que un torneo dependiera de datos que el usuario
| podía cambiar por fuera.
|
| Ya no hay nada que resolver: la copia se hizo al importar
| (UniverseEntityImporter). Aquí solo se traslada.
|
| Ver docs/md/27-Entidades-Propias-Del-Universo.md
|
*/

class TournamentParticipantResolver
{
    public function __construct(
        private readonly
        \App\Services\Games\GameStatsService $gameStats
    ) {}

    public function resolve(
        UniverseEntity $universeEntity
    ): array {

        return [

            'universe_entity_id' =>
            (int) $universeEntity->id,

            /*
             * Procedencia. Se arrastra solo para poder enlazar a la
             * Biblioteca desde la ficha; jamás para agregar estadísticas.
             */
            'source_entity_id' =>
            $universeEntity->source_entity_id
                ? (int) $universeEntity->source_entity_id
                : null,

            'entity_version_id' =>
            $universeEntity->source_entity_version_id
                ? (int) $universeEntity->source_entity_version_id
                : null,

            'entity_version_name' =>
            $this->baseVersionName($universeEntity),

            'entity_type_name' =>
            $universeEntity->entity_type_name,

            'name' =>
            $universeEntity->display_label,

            'image_url' =>
            $universeEntity->image_url,

            'attributes' =>
            $universeEntity->attribute_snapshot ?? [],

            /*
             * Game Stats congeladas (Fase 11). Se copian igual que los
             * atributos: si el usuario sube el rango de un competidor a
             * mitad de un torneo, el torneo en curso no cambia.
             *
             * Van indexadas por juego porque un Universo puede tener
             * varios y cada uno define sus propias estadisticas.
             */
            'game_stats' =>
            $this->gameStats
                ->frozenStats($universeEntity),
        ];
    }

    /*
     * Nombre de la versión que se importó, si la copia registró alguna
     * marcada como base.
     */
    private function baseVersionName(
        UniverseEntity $universeEntity
    ): ?string {

        foreach (
            ($universeEntity->version_snapshot ?? [])
            as $version
        ) {

            if ($version['is_base'] ?? false) {
                return $version['name'] ?? null;
            }
        }

        return null;
    }
}
