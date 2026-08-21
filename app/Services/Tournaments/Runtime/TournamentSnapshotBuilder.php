<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentTemplate;
use App\Services\Tournaments\GroupStage\GroupStageSettingsService;
use App\Services\Tournaments\RoundRobin\RoundRobinSettingsService;
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| TournamentSnapshotBuilder
|--------------------------------------------------------------------------
|
| Congela la configuración completa de una TournamentTemplate.
|
| El alcance NO es una suposición: RELATION_TREE reproduce exactamente
| el árbol que carga TournamentGraphRuntimeService::loadGraph(), que es
| la definición autoritativa de todo lo que el motor lee. Si el motor
| algún día necesitara leer algo más, lo añadiría allí y habría que
| añadirlo aquí; por eso ambos sitios están comentados cruzados.
|
| El formato es genérico y recursivo:
|
|   ['class' => FQCN, 'attributes' => [...], 'relations' => [...]]
|
| Así no hay 23 mapeos escritos a mano que mantener.
|
*/

class TournamentSnapshotBuilder
{
    public const SCHEMA_VERSION = 1;

    public function __construct(
        private readonly
        SingleEliminationSettingsService $singleEliminationSettings,

        private readonly
        RoundRobinSettingsService $roundRobinSettings,

        private readonly
        GroupStageSettingsService $groupStageSettings
    ) {}

    /*
     * Un array vacío significa "congela solo los atributos de este
     * modelo, no sigas navegando". Sirve para cortar los ciclos del
     * grafo (una conexión apunta a un nodo, que tiene conexiones...).
     */
    private const RELATION_TREE = [

        'graphStarts' => [
            'outgoingConnections' => [],
        ],

        'graphNodes' => [

            'phaseTemplate' => [

                'exits' => [],

                'inputGates' => [
                    'outgoingConnections' => [],
                ],

                /* Single Elimination */
                'singleEliminationSetting' => [],
                'singleEliminationRoundRules' => [],
                'singleEliminationConnections' => [],
                'singleEliminationRounds' => [
                    'encounters' => [
                        'slots' => [],
                        'results' => [
                            'outgoingConnections' => [],
                        ],
                    ],
                ],

                /* Round Robin */
                'roundRobinSetting' => [],
                'roundRobinTiebreakers' => [],

                /* Group Stage */
                'groupStageSetting' => [],
                'groupStageGroups' => [],
                'groupStageTiebreakers' => [],
                'groupStageAdvancementRules' => [
                    'phaseExit' => [],
                    'group' => [],
                ],

                /*
                 * Swiss no se ejecuta en esta fase, pero loadGraph() lo
                 * carga. Se congela igualmente para que ninguna relación
                 * quede sin cargar y provoque una consulta perezosa
                 * contra la configuración viva.
                 */
                'swissSetting' => [],
                'swissRoundRules' => [],
                'swissTiebreakers' => [],
                'swissAdvancementRules' => [
                    'phaseExit' => [],
                ],
            ],

            'entryPorts' => [
                'incomingConnections' => [],
                'inputGate' => [],
            ],

            'outgoingConnections' => [],
        ],

        'graphTerminals' => [
            'incomingConnections' => [],
        ],

        'graphConnections' => [
            'sourceStart' => [],
            'sourceNode' => [],
            'sourcePhaseExit' => [],
            'targetEntryPort' => [
                'node' => [],
            ],
            'targetTerminal' => [],
        ],
    ];

    public function build(
        TournamentTemplate $template
    ): array {

        /*
         * Los ajustes de cada motor se crean de forma perezosa: una fase
         * que nunca se abrió en su pestaña de Reglas no tiene fila de
         * ajustes todavía, aunque el motor los usaría igualmente con sus
         * valores por defecto.
         *
         * Si no se materializan ANTES de congelar, el snapshot quedaría
         * incompleto y el motor acabaría consultando la base de datos
         * viva para rellenar el hueco. Se materializan aquí, sobre la
         * plantilla viva, que es donde corresponde.
         */
        $this->materializePhaseSettings(
            $template
        );

        $template->load(
            $this->eagerLoadPaths(
                self::RELATION_TREE
            )
        );

        return [

            'schema_version' =>
            self::SCHEMA_VERSION,

            'captured_at' =>
            now()->toIso8601String(),

            'root' =>
            $this->dump(
                $template,
                self::RELATION_TREE
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Materializar ajustes perezosos
    |--------------------------------------------------------------------------
    |
    | Usa los mismos servicios ensure() que emplean los motores, así que
    | los valores por defecto congelados son exactamente los que se
    | habrían usado al ejecutar. Es idempotente.
    |
    | Swiss queda fuera a propósito: no forma parte de esta fase.
    |
    */

    private function materializePhaseSettings(
        TournamentTemplate $template
    ): void {

        $template->loadMissing(
            'graphNodes.phaseTemplate'
        );

        foreach ($template->graphNodes as $node) {

            $phase = $node->phaseTemplate;

            if (! $phase) {
                continue;
            }

            match ($phase->phase_type) {

                'SINGLE_ELIMINATION' =>
                $this->singleEliminationSettings
                    ->ensure($phase),

                'ROUND_ROBIN' =>
                $this->roundRobinSettings
                    ->ensure($phase),

                'GROUP_STAGE' =>
                $this->groupStageSettings
                    ->ensure($phase),

                default =>
                null,
            };
        }

        /*
         * Se descartan las relaciones para que el load() posterior
         * traiga las filas recién creadas.
         */
        $template->setRelation(
            'graphNodes',
            $template->graphNodes->each(
                fn($node) =>
                $node->phaseTemplate?->unsetRelations()
            )
        );
    }

    public function hash(
        array $snapshot
    ): string {

        return hash(
            'sha256',
            json_encode(
                $snapshot['root'] ?? [],
                JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
            )
                ?: ''
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Volcado recursivo
    |--------------------------------------------------------------------------
    */

    private function dump(
        Model $model,
        array $tree
    ): array {

        $relations = [];

        foreach ($tree as $name => $subTree) {

            $related =
                $model->getRelation($name);

            if ($related === null) {

                $relations[$name] = null;

                continue;
            }

            if ($related instanceof EloquentCollection) {

                $relations[$name] =
                    $related
                    ->map(
                        fn(Model $item) =>
                        $this->dump(
                            $item,
                            $subTree
                        )
                    )
                    ->values()
                    ->all();

                continue;
            }

            $relations[$name] =
                $this->dump(
                    $related,
                    $subTree
                );
        }

        return [

            'class' =>
            $model::class,

            /*
             * Atributos crudos: exactamente lo que hay en la fila.
             * Al rehidratar se devuelven con setRawAttributes(), de
             * modo que los casts se comportan igual que con el modelo
             * original.
             */
            'attributes' =>
            $model->getAttributes(),

            'relations' =>
            $relations,
        ];
    }

    /*
     * Convierte el árbol anidado en rutas con punto para el eager load.
     */
    private function eagerLoadPaths(
        array $tree,
        string $prefix = ''
    ): array {

        $paths = [];

        foreach ($tree as $name => $subTree) {

            $path =
                $prefix === ''
                ? $name
                : $prefix . '.' . $name;

            $paths[] = $path;

            if ($subTree !== []) {

                $paths = array_merge(
                    $paths,
                    $this->eagerLoadPaths(
                        $subTree,
                        $path
                    )
                );
            }
        }

        return $paths;
    }
}
