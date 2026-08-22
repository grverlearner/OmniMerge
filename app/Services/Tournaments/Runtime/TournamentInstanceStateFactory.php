<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentTemplate;
use App\Models\UniverseEntity;
use App\Services\Tournaments\CompetitionLab\LabStateFactory;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| TournamentInstanceStateFactory
|--------------------------------------------------------------------------
|
| Construye el estado inicial del motor para una competición REAL.
|
| La única diferencia con el Competition Lab son los participantes:
| aquí son competidores del Universo en lugar de participantes
| sintéticos. Todo el resto del estado (nodos, puertos, conexiones,
| terminales, resumen) lo ensambla LabStateFactory::assemble(), que es
| exactamente el mismo código que usa el Lab.
|
*/

class TournamentInstanceStateFactory
{
    public function __construct(
        private readonly
        LabStateFactory $labStateFactory,

        private readonly
        TournamentParticipantResolver $participantResolver,

        private readonly
        \App\Services\Games\GameRegistry $gameRegistry
    ) {}

    /*
     * $assignments = [ startId => [universeCompetitorId, ...], ... ]
     */
    public function create(
        TournamentTemplate $template,
        int $userId,
        array $assignments,
        Collection $universeEntities,
        ?string $gameKey = null,
        array $modifiers = []
    ): array {

        $participants = [];
        $starts = [];

        foreach (
            $template->graphStarts
            as
            $start
        ) {

            $universeEntityIds =
                array_values(
                    $assignments[$start->id]
                    ?? []
                );

            if ($universeEntityIds === []) {
                continue;
            }

            $keys = [];

            $position = 0;

            foreach ($universeEntityIds as $universeEntityId) {

                $universeEntity =
                    $universeEntities->get(
                        (int) $universeEntityId
                    );

                if (! $universeEntity) {
                    continue;
                }

                $position++;

                $key =
                    $this->runtimeKey(
                        $universeEntity
                    );

                $participants[$key] =
                    $this->participant(
                        $universeEntity,
                        $start,
                        $key,
                        $position
                    );

                $keys[] = $key;
            }

            if ($keys === []) {
                continue;
            }

            $starts[$start->id] = [

                'id' =>
                (int) $start->id,

                'code' =>
                $start->code,

                'name' =>
                $start->name,

                'status' =>
                'READY',

                'participant_ids' =>
                $keys,

                'participant_count' =>
                count($keys),
            ];
        }

        $state =
            $this->labStateFactory
            ->assemble(
                $template,
                $userId,
                [
                    'participant_mode' =>
                    'UNIVERSE_COMPETITORS',

                    'ordering_strategy' =>
                    'MANUAL',

                    'seed' =>
                    0,
                ],
                $participants,
                $starts
            );

        /*
         * El estado del Lab se identifica por lab_id; el de una
         * competición real, por la propia instancia. Se marca el origen
         * para que quede claro al inspeccionar el JSON.
         */
        $state['origin'] =
            'TOURNAMENT_INSTANCE';

        /*
         * Juego de la competicion (Fase 11), congelado igual que todo lo
         * demas. Su presencia es lo que distingue una competicion real
         * —que se resuelve con un Game Engine— del Lab de diseno, que
         * sigue resolviendo al azar con participantes sinteticos.
         */
        if ($gameKey !== null) {

            $definition =
                $this->gameRegistry->definition($gameKey);

            /*
             * Modificadores temporales (Fase 12), congelados igual que
             * todo lo demas. Solo alteran las stats mientras se juega:
             * nada de esto se guarda en el competidor.
             */
            $state['modifiers'] = $modifiers;

            $state['game'] = [

                'key' =>
                $definition['key'],

                'name' =>
                $definition['name'],

                'icon' =>
                $definition['icon'] ?? null,

                'accent' =>
                $definition['accent'] ?? 'violet',

                'configuration' =>
                [],
            ];
        }

        $state['timeline'] = [
            [
                'step' =>
                1,

                'type' =>
                'INSTANCE_INITIALIZED',

                'level' =>
                'SUCCESS',

                'message' =>
                'Competición preparada con '
                    . count($participants)
                    . ' competidores del Universo.',
            ],
        ];

        return $state;
    }

    /*
    |--------------------------------------------------------------------------
    | Clave del participante dentro del motor
    |--------------------------------------------------------------------------
    |
    | El motor trata esta clave como una cadena opaca, igual que hace con
    | los 'LAB-...' del Competition Lab. Se deriva del UniverseEntity
    | para poder volver del estado a la fila proyectada.
    |
    */

    private function runtimeKey(
        UniverseEntity $universeEntity
    ): string {

        return 'UC-'
            . str_pad(
                (string) $universeEntity->id,
                6,
                '0',
                STR_PAD_LEFT
            );
    }

    private function participant(
        UniverseEntity $universeEntity,
        $start,
        string $key,
        int $position
    ): array {

        /*
         * Resuelve y congela la Entidad, su versión y sus atributos.
         * A partir de aquí el torneo no vuelve a mirar la Biblioteca.
         */
        $context =
            $this->participantResolver
            ->resolve($universeEntity);

        $location = [

            'type' =>
            'START',

            'id' =>
            (int) $start->id,

            'code' =>
            $start->code,

            'name' =>
            $start->name,
        ];

        return [

            /*
             * Se conservan los nombres de campo del Lab para que el
             * motor y toda la interfaz existente funcionen sin cambios.
             */
            'preview_id' =>
            $key,

            'lab_id' =>
            $key,

            'name' =>
            $context['name'],

            'source_start_id' =>
            (int) $start->id,

            'source_start_name' =>
            $start->name,

            'initial_position' =>
            $position,

            'seed' =>
            $position,

            /*
             * Contexto de la Biblioteca congelado (Fase 7). Estos campos
             * existían vacíos desde el diseño original del Lab: aquí por
             * fin se rellenan con la Entidad, su versión y sus atributos.
             *
             * Los motores los ignoran: para ellos el participante sigue
             * siendo una clave de array.
             */
            'universe_entity_id' =>
            $context['universe_entity_id'],

            'source_entity_id' =>
            $context['source_entity_id'],

            'entity_version_id' =>
            $context['entity_version_id'],

            'entity_version_name' =>
            $context['entity_version_name'],

            'entity_type_name' =>
            $context['entity_type_name'],

            'attributes' =>
            $context['attributes'],

            /* Estadisticas de juego congeladas (Fase 11) */
            'game_stats' =>
            $context['game_stats'] ?? [],

            'image_url' =>
            $context['image_url'],

            'status' =>
            'WAITING',

            'current_location' =>
            $location,

            'journey' => [
                $location,
            ],

            'statistics' => [
                'matches' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'points' => 0,
            ],
        ];
    }
}
