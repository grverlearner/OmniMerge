<?php

namespace App\Services\Tournaments\Graph\Flow;

use App\Models\PhaseEntryPort;
use App\Models\TournamentPhaseConnection;
use App\Models\TournamentPhaseNode;
use App\Models\TournamentTemplate;
use App\Models\TournamentTerminal;
use App\Services\Tournaments\GroupStage\GroupStageExitForecastService;
use Illuminate\Support\Collection;

class TournamentGraphFlowValidationService
{
    public function __construct(
        private readonly
        TournamentGraphCapacityCalculator $calculator,

        private readonly
        GroupStageExitForecastService $groupStageForecast
    ) {}

    public function validate(
        TournamentTemplate $template,
        array $flowAnalysis
    ): array {
        $template->loadMissing([
            'graphStarts.outgoingConnections',
            'graphNodes.phaseTemplate.exits',
            'graphNodes.phaseTemplate.singleEliminationSetting',
            'graphNodes.phaseTemplate.groupStageSetting',
            'graphNodes.phaseTemplate.groupStageGroups',
            'graphNodes.phaseTemplate.groupStageAdvancementRules',
            'graphNodes.entryPorts.incomingConnections',
            'graphTerminals.incomingConnections',
            'graphConnections.sourceStart',
            'graphConnections.sourceNode',
            'graphConnections.sourcePhaseExit',
            'graphConnections.targetEntryPort.node',
            'graphConnections.targetTerminal',
        ]);

        $errors = [];
        $warnings = [];
        $information = [];

        $connectionForecasts = [];
        $nodeForecasts = [];
        $entryForecasts = [];
        $exitForecasts = [];
        $terminalForecasts = [];

        /*
        |--------------------------------------------------------------------------
        | Starts
        |--------------------------------------------------------------------------
        */

        foreach (
            $template->graphStarts
                ->where('status', 'ACTIVE')
            as
            $start
        ) {
            $sourceForecast =
                $start->expected_participants !== null
                ? $this->calculator->exact(
                    (int) $start->expected_participants
                )
                : $this->calculator->unknown();

            foreach (
                $start->outgoingConnections
                    ->where('status', 'ACTIVE')
                as
                $connection
            ) {
                $connectionForecasts[$connection->id] =
                    $this->calculator->allocate(
                        $sourceForecast,
                        $connection->allocation_mode,
                        $connection->allocation_value
                    );
            }

            if ($start->expected_participants === null) {
                $warnings[] = [
                    'code' =>
                    'START_QUANTITY_UNKNOWN',

                    'message' =>
                    'El inicio “'
                        .
                        $start->name
                        .
                        '” no declara participantes esperados; sus cantidades posteriores serán variables.',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Nodes ordered by calculated levels
        |--------------------------------------------------------------------------
        */

        $orderedNodeIds =
            collect(
                $flowAnalysis['levels']
                    ??
                    []
            )
            ->sortBy('level')
            ->flatMap(
                fn(array $level) =>
                $level['node_ids']
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->values();

        $nodesById =
            $template
            ->graphNodes
            ->keyBy(
                fn($node) =>
                (int) $node->id
            );

        foreach ($orderedNodeIds as $nodeId) {
            /** @var TournamentPhaseNode|null $node */
            $node =
                $nodesById->get(
                    $nodeId
                );

            if (
                ! $node
                ||
                $node->status !== 'ACTIVE'
            ) {
                continue;
            }

            $portForecasts = [];

            foreach (
                $node->entryPorts
                    ->where('status', 'ACTIVE')
                as
                $entryPort
            ) {
                $incomingForecasts =
                    $entryPort
                    ->incomingConnections
                    ->where('status', 'ACTIVE')
                    ->map(
                        fn($connection) =>
                        $connectionForecasts[$connection->id]
                            ??
                            $this->calculator->unknown()
                    )
                    ->values()
                    ->all();

                $portForecast =
                    $this->calculator
                    ->combineForPort(
                        $incomingForecasts,
                        $entryPort->merge_policy
                    );

                $entryForecasts[$entryPort->id] =
                    $portForecast;

                $portForecasts[] =
                    $portForecast;

                $this->validateEntryPort(
                    $node,
                    $entryPort,
                    $portForecast,
                    $errors,
                    $warnings,
                    $information
                );
            }

            $nodeForecast =
                $this->calculator->sum(
                    $portForecasts
                );

            $nodeForecasts[$node->id] =
                $nodeForecast;

            $this->validateNodeContract(
                $node,
                $nodeForecast,
                $errors,
                $warnings,
                $information
            );

            /*
             * En una Fase de grupos el selector de la puerta es decoracion:
             * quien decide cuanta gente sale son las reglas de
             * clasificacion, y el motor entrega esa lista tal cual. Si aqui
             * se valida el selector, el grafo aprueba un numero que el
             * torneo nunca va a producir.
             */
            $ruleDrivenExits =
                $this->groupStageExitForecast(
                    $node,
                    $nodeForecast
                );

            /*
             * Las salidas se pronostican en DOS pasadas.
             *
             * "El resto" no se puede calcular mirando solo su propia salida:
             * depende de lo que se lleven las demas. Asi que primero se
             * resuelven las que se bastan solas, y despues las de resto
             * restando lo ya reclamado.
             *
             * En una pasada, un torneo bien montado -20 entran, 16
             * clasifican, 4 caen- decia que sus eliminados podian ser
             * "entre 0 y 20", y su destino se quejaba para siempre.
             */
            $activeExits =
                $node
                ->phaseTemplate
                ->exits
                ->where('status', 'ACTIVE');

            $remainderTypes = [
                'REMAINING',
                'ELIMINATED',
                'ELIMINATED_IN_ROUND',
                'MATCH_LOSERS',
            ];

            $claimed = [];

            foreach ($activeExits as $exit) {

                if (isset($ruleDrivenExits[$exit->id])) {
                    $claimed[$exit->id] = $ruleDrivenExits[$exit->id];

                    continue;
                }

                if (in_array($exit->selector_type, $remainderTypes, true)) {
                    continue;
                }

                $claimed[$exit->id] =
                    $this->calculator->fromExit(
                        $nodeForecast,
                        $exit,
                        $node->phaseTemplate->singleEliminationSetting
                    );
            }

            foreach (
                $activeExits
                as
                $exit
            ) {
                $exitForecast =
                    $ruleDrivenExits[$exit->id]
                    ??
                    ($claimed[$exit->id]
                        ?? $this->remainderForecast(
                            $node,
                            $exit,
                            $nodeForecast,
                            $claimed
                        ));

                $exitForecasts[$node->id
                    .
                    ':'
                    .
                    $exit->id] =
                    $exitForecast;

                $outgoingConnections =
                    $template
                    ->graphConnections
                    ->where(
                        'source_type',
                        'PHASE_EXIT'
                    )
                    ->where(
                        'source_node_id',
                        $node->id
                    )
                    ->where(
                        'source_phase_exit_id',
                        $exit->id
                    )
                    ->where(
                        'status',
                        'ACTIVE'
                    );

                foreach (
                    $outgoingConnections
                    as
                    $connection
                ) {
                    $connectionForecasts[$connection->id] =
                        $this->calculator->allocate(
                            $exitForecast,
                            $connection->allocation_mode,
                            $connection->allocation_value
                        );
                }

                if (
                    $outgoingConnections->isEmpty()
                    &&
                    $exitForecast['max'] !== 0
                ) {
                    $warnings[] = [
                        'code' =>
                        'EXIT_FLOW_WITHOUT_DESTINATION',

                        'message' =>
                        'La salida “'
                            .
                            $exit->name
                            .
                            '” de “'
                            .
                            $node->name
                            .
                            '” puede producir '
                            .
                            $this->calculator->label(
                                $exitForecast
                            )
                            .
                            ' participantes, pero no tiene destino.',
                    ];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Unreachable Nodes
        |--------------------------------------------------------------------------
        */

        foreach (
            $flowAnalysis['unreachable_node_ids']
                ??
                []
            as
            $nodeId
        ) {
            $node =
                $nodesById->get(
                    (int) $nodeId
                );

            if ($node) {
                $errors[] = [
                    'code' =>
                    'FLOW_UNREACHABLE_NODE',

                    'message' =>
                    'No se puede calcular el flujo de “'
                        .
                        $node->name
                        .
                        '” porque no existe una ruta desde ningún inicio.',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Terminals
        |--------------------------------------------------------------------------
        */

        foreach (
            $template->graphTerminals
                ->where('status', 'ACTIVE')
            as
            $terminal
        ) {
            $incomingForecasts =
                $terminal
                ->incomingConnections
                ->where('status', 'ACTIVE')
                ->map(
                    fn($connection) =>
                    $connectionForecasts[$connection->id]
                        ??
                        $this->calculator->unknown()
                )
                ->values()
                ->all();

            $forecast =
                $this->calculator->sum(
                    $incomingForecasts
                );

            $terminalForecasts[$terminal->id] =
                $forecast;

            $this->validateTerminal(
                $terminal,
                $forecast,
                $errors,
                $warnings,
                $information
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Route completion
        |--------------------------------------------------------------------------
        */

        foreach (
            $flowAnalysis['start_routes']
                ??
                []
            as
            $route
        ) {
            if (
                (
                    $route['reachable_terminal_ids']
                    ??
                    []
                )
                ===
                []
            ) {
                $start =
                    $template
                    ->graphStarts
                    ->firstWhere(
                        'id',
                        $route['start_id']
                    );

                $errors[] = [
                    'code' =>
                    'START_WITHOUT_REACHABLE_TERMINAL',

                    'message' =>
                    'La ruta que comienza en “'
                        .
                        (
                            $start?->name
                            ??
                            'Inicio'
                        )
                        .
                        '” no alcanza ningún destino final.',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $errors =
            $this->uniqueProblems(
                $errors
            );

        $warnings =
            $this->uniqueProblems(
                $warnings
            );

        $information =
            $this->uniqueProblems(
                $information
            );

        return [
            'valid' =>
            $errors === [],

            'errors' =>
            $errors,

            'warnings' =>
            $warnings,

            'information' =>
            $information,

            'forecasts' => [
                'nodes' =>
                $nodeForecasts,

                'entries' =>
                $entryForecasts,

                'exits' =>
                $exitForecasts,

                'connections' =>
                $connectionForecasts,

                'terminals' =>
                $terminalForecasts,
            ],

            'stats' => [
                'errors' =>
                count($errors),

                'warnings' =>
                count($warnings),

                'information' =>
                count($information),

                'calculated_nodes' =>
                count($nodeForecasts),

                'calculated_connections' =>
                count($connectionForecasts),

                'calculated_terminals' =>
                count($terminalForecasts),
            ],
        ];
    }

    /*
     * Lo que cada puerta de una Fase de grupos va a emitir de verdad.
     *
     * Solo se pronostican las puertas que alguna regla activa alimenta con
     * al menos un participante: cuando una regla no selecciona a nadie el
     * motor deja la puerta libre y vuelve a mandar su propio selector, asi
     * que ahi el pronostico correcto sigue siendo el de siempre.
     *
     * Si la fase todavia no reparte grupos validos, o entra una cantidad
     * abierta de participantes, no se inventa un numero: se devuelve nada y
     * manda el calculo de siempre.
     *
     * @return array<int,array{min:int,max:?int,exact:?int}>
     */
    /*
     * El pronostico de una salida de tipo "el resto".
     *
     * Una Eliminacion Directa ya sabe decir cuantos elimina -entran menos
     * los que sobreviven- y su calculo es mejor que una resta generica, asi
     * que ese se respeta. Para todo lo demas, el resto es lo que entra menos
     * lo que se llevan las otras salidas.
     */
    private function remainderForecast(
        $node,
        $exit,
        array $nodeForecast,
        array $claimed
    ): array {

        $single = $node->phaseTemplate->singleEliminationSetting;

        if ($single && $exit->selector_type === 'ELIMINATED') {
            return $this->calculator->fromExit(
                $nodeForecast,
                $exit,
                $single
            );
        }

        return $this->calculator->fromRemainder(
            $nodeForecast,
            array_values($claimed)
        );
    }


    private function groupStageExitForecast(
        TournamentPhaseNode $node,
        array $nodeForecast
    ): array {

        $phaseTemplate =
            $node->phaseTemplate;

        if (
            $phaseTemplate?->phase_type
            !==
            'GROUP_STAGE'
        ) {
            return [];
        }

        $low =
            $nodeForecast['exact']
            ?? $nodeForecast['min']
            ?? null;

        $high =
            $nodeForecast['exact']
            ?? $nodeForecast['max']
            ?? null;

        if (
            $low === null
            ||
            $high === null
            ||
            $low < 1
        ) {
            return [];
        }

        $lowForecast =
            $this->groupStageForecast->forecast(
                $phaseTemplate,
                (int) $low
            );

        $highForecast =
            $low === $high
            ? $lowForecast
            : $this->groupStageForecast->forecast(
                $phaseTemplate,
                (int) $high
            );

        if (
            $lowForecast === null
            ||
            $highForecast === null
        ) {
            return [];
        }

        $forecasts = [];

        foreach ($lowForecast['by_exit'] as $exitId => $count) {

            $other =
                $highForecast['by_exit'][$exitId]
                ?? $count;

            if ($count < 1 && $other < 1) {
                continue;
            }

            $minimum = min($count, $other);
            $maximum = max($count, $other);

            $forecasts[(int) $exitId] =
                $minimum === $maximum
                ? $this->calculator->exact($minimum)
                : $this->calculator->range($minimum, $maximum);
        }

        return $forecasts;
    }

    private function validateEntryPort(
        TournamentPhaseNode $node,
        PhaseEntryPort $entryPort,
        array $forecast,
        array &$errors,
        array &$warnings,
        array &$information
    ): void {
        $problems =
            $this->calculator
            ->compareWithContract(
                $forecast,
                $entryPort->min_participants,
                $entryPort->max_participants,
                $entryPort->exact_participants
            );

        foreach ($problems as $problem) {
            $entry = [
                'code' =>
                'ENTRY_'
                    .
                    $problem['type'],

                'message' =>
                'La entrada “'
                    .
                    $entryPort->name
                    .
                    '” de “'
                    .
                    $node->name
                    .
                    '” '
                    .
                    $problem['message']
                    .
                    '.',
            ];

            if (
                $problem['severity']
                ===
                'ERROR'
            ) {
                $errors[] =
                    $entry;
            } else {
                $warnings[] =
                    $entry;
            }
        }

        if ($problems === []) {
            $information[] = [
                'code' =>
                'ENTRY_CAPACITY_OK',

                'message' =>
                'La entrada “'
                    .
                    $entryPort->name
                    .
                    '” de “'
                    .
                    $node->name
                    .
                    '” recibe '
                    .
                    $this->calculator->label(
                        $forecast
                    )
                    .
                    '.',
            ];
        }
    }

    private function validateNodeContract(
        TournamentPhaseNode $node,
        array $forecast,
        array &$errors,
        array &$warnings,
        array &$information
    ): void {
        $phaseTemplate =
            $node->phaseTemplate;

        $problems =
            $this->calculator
            ->compareWithContract(
                $forecast,
                $phaseTemplate->min_participants,
                $phaseTemplate->max_participants,
                $phaseTemplate->exact_participants
            );

        foreach ($problems as $problem) {
            $entry = [
                'code' =>
                'NODE_'
                    .
                    $problem['type'],

                'message' =>
                'La fase “'
                    .
                    $node->name
                    .
                    '” '
                    .
                    $problem['message']
                    .
                    '.',
            ];

            if (
                $problem['severity']
                ===
                'ERROR'
            ) {
                $errors[] =
                    $entry;
            } else {
                $warnings[] =
                    $entry;
            }
        }

        if ($problems === []) {
            $information[] = [
                'code' =>
                'NODE_CAPACITY_OK',

                'message' =>
                'La fase “'
                    .
                    $node->name
                    .
                    '” recibe '
                    .
                    $this->calculator->label(
                        $forecast
                    )
                    .
                    ' participantes y cumple su contrato.',
            ];
        }
    }

    private function validateTerminal(
        TournamentTerminal $terminal,
        array $forecast,
        array &$errors,
        array &$warnings,
        array &$information
    ): void {
        if (
            $terminal->expected_participants
            !==
            null
        ) {
            $problems =
                $this->calculator
                ->compareWithContract(
                    $forecast,
                    null,
                    null,
                    (int) $terminal
                        ->expected_participants
                );

            foreach (
                $problems
                as
                $problem
            ) {
                $entry = [
                    'code' =>
                    'TERMINAL_'
                        .
                        $problem['type'],

                    'message' =>
                    'El destino “'
                        .
                        $terminal->name
                        .
                        '” '
                        .
                        $problem['message']
                        .
                        '.',
                ];

                if (
                    $problem['severity']
                    ===
                    'ERROR'
                ) {
                    $errors[] =
                        $entry;
                } else {
                    $warnings[] =
                        $entry;
                }
            }
        }

        /*
         * Un destino de campeon que no dice "cabe uno".
         *
         * Se avisa porque casi siempre es un descuido, PERO no cuando el
         * recorrido ya garantiza que llega exactamente uno: entonces no hay
         * nada raro que senalar, y repetirlo era pedir que se escriba a mano
         * un numero que el propio grafo acaba de calcular.
         *
         * Antes esa distincion no se podia hacer: el pronostico de una
         * salida WINNER salia "no se sabe", asi que un campeon perfectamente
         * bien conectado se quejaba igual.
         */
        $llegaExactamenteUno =
            ($forecast['known'] ?? false)
            && ($forecast['exact'] ?? null) === 1;

        if (
            $terminal->terminal_type
            ===
            'CHAMPION'
            &&
            ! $llegaExactamenteUno
            &&
            (
                $terminal->expected_participants
                ===
                null
                ||
                $terminal
                ->expected_participants
                !==
                1
            )
        ) {
            $warnings[] = [
                'code' =>
                'CHAMPION_QUANTITY_UNUSUAL',

                'message' =>
                'El destino “'
                    .
                    $terminal->name
                    .
                    '” es de tipo Campeón, pero no declara exactamente un participante. Esto es válido si el torneo admite varios campeones.',
            ];
        }

        $information[] = [
            'code' =>
            'TERMINAL_FORECAST',

            'message' =>
            'El destino “'
                .
                $terminal->name
                .
                '” puede recibir '
                .
                $this->calculator->label(
                    $forecast
                )
                .
                ' participantes.',
        ];
    }

    private function uniqueProblems(
        array $problems
    ): array {
        return collect(
            $problems
        )
            ->unique(
                fn(array $problem) =>
                $problem['code']
                    .
                    ':'
                    .
                    $problem['message']
            )
            ->values()
            ->all();
    }
}
