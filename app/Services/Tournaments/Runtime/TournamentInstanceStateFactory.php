<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentTemplate;
use App\Models\UniverseCompetitor;
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
        LabStateFactory $labStateFactory
    ) {}

    /*
     * $assignments = [ startId => [universeCompetitorId, ...], ... ]
     */
    public function create(
        TournamentTemplate $template,
        int $userId,
        array $assignments,
        Collection $competitors
    ): array {

        $participants = [];
        $starts = [];

        foreach (
            $template->graphStarts
            as
            $start
        ) {

            $competitorIds =
                array_values(
                    $assignments[$start->id]
                    ?? []
                );

            if ($competitorIds === []) {
                continue;
            }

            $keys = [];

            $position = 0;

            foreach ($competitorIds as $competitorId) {

                $competitor =
                    $competitors->get(
                        (int) $competitorId
                    );

                if (! $competitor) {
                    continue;
                }

                $position++;

                $key =
                    $this->runtimeKey(
                        $competitor
                    );

                $participants[$key] =
                    $this->participant(
                        $competitor,
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
    | los 'LAB-...' del Competition Lab. Se deriva del UniverseCompetitor
    | para poder volver del estado a la fila proyectada.
    |
    */

    private function runtimeKey(
        UniverseCompetitor $competitor
    ): string {

        return 'UC-'
            . str_pad(
                (string) $competitor->id,
                6,
                '0',
                STR_PAD_LEFT
            );
    }

    private function participant(
        UniverseCompetitor $competitor,
        $start,
        string $key,
        int $position
    ): array {

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
            $competitor->display_label,

            'source_start_id' =>
            (int) $start->id,

            'source_start_name' =>
            $start->name,

            'initial_position' =>
            $position,

            'seed' =>
            $position,

            /*
             * Enlace con la Biblioteca. Estos campos ya existían vacíos
             * en el Lab: aquí por fin se rellenan.
             */
            'entity_id' =>
            $competitor->entity_id
                ? (int) $competitor->entity_id
                : null,

            'entity_version_id' =>
            null,

            'universe_competitor_id' =>
            (int) $competitor->id,

            'image_url' =>
            $competitor->entity?->image_url,

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
