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
        \App\Services\Games\GameStatsService $gameStats,

        private readonly
        \App\Services\Universes\UniverseEntityVersionResolver $versions
    ) {}

    /*
     * @param  array|null  $context  las reglas del torneo {mode, rules[]}
     */
    public function resolve(
        UniverseEntity $universeEntity,
        ?array $context = null
    ): array {

        /*
         * Con que cara sale en ESTE torneo.
         *
         * Un personaje no es uno solo: Naruto tiene su version de nino y su
         * Sennin, cada una con su imagen. Un torneo definido como «los que
         * llevan saga -> shippuden» tiene que ensenar la de Shippuden, y la
         * version buena es exactamente la que tambien lo lleva.
         *
         * Se resuelve AQUI y se congela con el resto: si manana cambias las
         * versiones de la entidad, el torneo ya jugado sigue ensenando la
         * cara con la que se jugo.
         */
        $cara = $this->versions->face($universeEntity, $context);

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
            $cara['version_id']
                ?? ($universeEntity->source_entity_version_id
                    ? (int) $universeEntity->source_entity_version_id
                    : null),

            'entity_version_name' =>
            $cara['version_name']
                ?? $this->baseVersionName($universeEntity),

            /* De donde salio la cara: de una version que caso, o de la entidad */
            'version_from' =>
            $cara['from'],

            'entity_type_name' =>
            $universeEntity->entity_type_name,

            'name' =>
            $cara['name'],

            'image_url' =>
            $cara['image_url'],

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
