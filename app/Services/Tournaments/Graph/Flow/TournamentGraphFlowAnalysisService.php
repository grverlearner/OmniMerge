<?php

namespace App\Services\Tournaments\Graph\Flow;

use App\Models\TournamentPhaseConnection;
use App\Models\TournamentTemplate;
use Illuminate\Support\Collection;

class TournamentGraphFlowAnalysisService
{
    /**
     * Analiza el grafo sin modificar ningún registro.
     *
     * El resultado permite:
     * - organizar visualmente los elementos por niveles;
     * - detectar componentes no alcanzables;
     * - conocer rutas desde los inicios;
     * - conocer terminales alcanzables;
     * - mostrar bifurcaciones y convergencias;
     * - ofrecer estadísticas para el Flow Builder.
     */
    public function analyze(TournamentTemplate $template): array
    {
        $template->loadMissing([
            'graphNodes.phaseTemplate.exits',
            'graphNodes.entryPorts.incomingConnections',
            'graphStarts.outgoingConnections',
            'graphTerminals.incomingConnections',
            'graphConnections.sourceStart',
            'graphConnections.sourceNode',
            'graphConnections.sourcePhaseExit',
            'graphConnections.targetEntryPort.node',
            'graphConnections.targetTerminal',
        ]);

        $nodes = $template->graphNodes
            ->where('status', 'ACTIVE')
            ->values();

        $starts = $template->graphStarts
            ->where('status', 'ACTIVE')
            ->values();

        $terminals = $template->graphTerminals
            ->where('status', 'ACTIVE')
            ->values();

        $connections = $template->graphConnections
            ->where('status', 'ACTIVE')
            ->values();

        $nodeIds = $nodes
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $nodeEdges = $this->nodeEdges($connections);
        $startTargets = $this->startTargets($connections);
        $incomingByNode = $this->incomingByNode($nodeIds, $nodeEdges, $startTargets);
        $outgoingByNode = $this->outgoingByNode($nodeIds, $nodeEdges);

        $levels = $this->calculateLevels(
            $nodeIds,
            $nodeEdges,
            $startTargets
        );

        $reachableNodeIds = collect($levels)
            ->flatten(1)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $unreachableNodeIds = collect($nodeIds)
            ->reject(fn(int $id) => $reachableNodeIds->contains($id))
            ->values();

        $branchingNodes = $nodes
            ->filter(function ($node) use ($connections) {
                return $this->outgoingSourceGroups(
                    $connections,
                    (int) $node->id
                )->count() > 1;
            })
            ->map(fn($node) => [
                'id' => (int) $node->id,
                'code' => $node->code,
                'name' => $node->name,
                'branches' => $this->outgoingSourceGroups(
                    $connections,
                    (int) $node->id
                )->count(),
            ])
            ->values()
            ->all();

        $convergingNodes = $nodes
            ->filter(
                fn($node) => ($incomingByNode[(int) $node->id] ?? 0) > 1
            )
            ->map(fn($node) => [
                'id' => (int) $node->id,
                'code' => $node->code,
                'name' => $node->name,
                'incoming_routes' =>
                $incomingByNode[(int) $node->id] ?? 0,
            ])
            ->values()
            ->all();

        $startRoutes = $starts
            ->map(function ($start) use (
                $connections,
                $nodeEdges,
                $nodes,
                $terminals
            ) {
                return $this->describeStartRoute(
                    (int) $start->id,
                    $connections,
                    $nodeEdges,
                    $nodes,
                    $terminals
                );
            })
            ->values()
            ->all();

        $terminalReachability = $terminals
            ->map(function ($terminal) use (
                $connections,
                $starts,
                $nodes
            ) {
                $incoming = $connections
                    ->where(
                        'target_terminal_id',
                        $terminal->id
                    )
                    ->values();

                return [
                    'id' => (int) $terminal->id,
                    'code' => $terminal->code,
                    'name' => $terminal->name,
                    'type' => $terminal->terminal_type,
                    'incoming_connections' => $incoming->count(),
                    'sources' => $incoming
                        ->map(
                            fn($connection) =>
                            $connection->source_label
                        )
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                    'reachable_from_starts' =>
                    $this->startsReachingTerminal(
                        (int) $terminal->id,
                        $connections,
                        $starts,
                        $nodes
                    ),
                ];
            })
            ->values()
            ->all();

        $levelPayload = collect($levels)
            ->map(function (array $ids, int $level) use ($nodes) {
                return [
                    'level' => $level,
                    'label' => $this->levelLabel($level),
                    'node_ids' => array_values($ids),
                    'nodes' => $nodes
                        ->whereIn('id', $ids)
                        ->sortBy(function ($node) {
                            return sprintf(
                                '%010d-%010d-%010d',
                                $node->y_position ?? 0,
                                $node->x_position ?? 0,
                                $node->sequence_number ?? 0
                            );
                        })
                        ->map(fn($node) => [
                            'id' => (int) $node->id,
                            'code' => $node->code,
                            'name' => $node->name,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'levels' => $levelPayload,

            'unreachable_node_ids' =>
            $unreachableNodeIds->all(),

            'branching_nodes' => $branchingNodes,

            'converging_nodes' => $convergingNodes,

            'start_routes' => $startRoutes,

            'terminal_reachability' =>
            $terminalReachability,

            'incoming_by_node' => $incomingByNode,

            'outgoing_by_node' => $outgoingByNode,

            'stats' => [
                'starts' => $starts->count(),
                'nodes' => $nodes->count(),
                'connections' => $connections->count(),
                'terminals' => $terminals->count(),
                'levels' => count($levels),
                'branches' => count($branchingNodes),
                'convergences' => count($convergingNodes),
                'unreachable_nodes' =>
                $unreachableNodeIds->count(),
            ],
        ];
    }

    /**
     * Devuelve pares [nodo_origen, nodo_destino].
     */
    private function nodeEdges(Collection $connections): array
    {
        return $connections
            ->filter(
                fn(TournamentPhaseConnection $connection) =>
                $connection->source_type === 'PHASE_EXIT'
                    &&
                    $connection->target_type === 'ENTRY_PORT'
                    &&
                    $connection->source_node_id !== null
                    &&
                    $connection->targetEntryPort !== null
            )
            ->map(fn(TournamentPhaseConnection $connection) => [
                (int) $connection->source_node_id,
                (int) $connection
                    ->targetEntryPort
                    ->tournament_phase_node_id,
            ])
            ->unique(
                fn(array $edge) =>
                $edge[0] . ':' . $edge[1]
            )
            ->values()
            ->all();
    }

    /**
     * Nodos alcanzados directamente por cada TournamentStart.
     */
    private function startTargets(Collection $connections): array
    {
        $targets = [];

        foreach ($connections as $connection) {
            if (
                $connection->source_type !== 'START'
                ||
                $connection->target_type !== 'ENTRY_PORT'
                ||
                $connection->source_start_id === null
                ||
                $connection->targetEntryPort === null
            ) {
                continue;
            }

            $startId = (int) $connection->source_start_id;
            $nodeId = (int) $connection
                ->targetEntryPort
                ->tournament_phase_node_id;

            $targets[$startId] ??= [];
            $targets[$startId][] = $nodeId;
            $targets[$startId] = array_values(
                array_unique($targets[$startId])
            );
        }

        return $targets;
    }

    private function incomingByNode(
        array $nodeIds,
        array $nodeEdges,
        array $startTargets
    ): array {
        $counts = array_fill_keys($nodeIds, 0);

        foreach ($nodeEdges as [$sourceId, $targetId]) {
            if (array_key_exists($targetId, $counts)) {
                $counts[$targetId]++;
            }
        }

        foreach ($startTargets as $targets) {
            foreach ($targets as $targetId) {
                if (array_key_exists($targetId, $counts)) {
                    $counts[$targetId]++;
                }
            }
        }

        return $counts;
    }

    private function outgoingByNode(
        array $nodeIds,
        array $nodeEdges
    ): array {
        $counts = array_fill_keys($nodeIds, 0);

        foreach ($nodeEdges as [$sourceId, $targetId]) {
            if (array_key_exists($sourceId, $counts)) {
                $counts[$sourceId]++;
            }
        }

        return $counts;
    }

    /**
     * Ordena el grafo en niveles sin depender de las posiciones del canvas.
     *
     * Los nodos conectados directamente desde un Start pertenecen al nivel 1.
     * Un nodo con varias entradas se coloca después de la rama más profunda.
     */
    private function calculateLevels(
        array $nodeIds,
        array $nodeEdges,
        array $startTargets
    ): array {
        $adjacency =
            array_fill_keys(
                $nodeIds,
                []
            );

        foreach (
            $nodeEdges
            as
            [$sourceId, $targetId]
        ) {
            $adjacency[$sourceId] ??= [];

            if (
                ! in_array(
                    $targetId,
                    $adjacency[$sourceId],
                    true
                )
            ) {
                $adjacency[$sourceId][] =
                    $targetId;
            }
        }

        $initialIds =
            collect(
                $startTargets
            )
            ->flatten()
            ->map(
                fn($id) =>
                (int) $id
            )
            ->unique()
            ->filter(
                fn(int $id) =>
                in_array(
                    $id,
                    $nodeIds,
                    true
                )
            )
            ->values()
            ->all();

        $levelsByNode = [];

        foreach ($initialIds as $nodeId) {
            $levelsByNode[$nodeId] = 1;
        }

        /*
     * Un DAG puede procesarse por relajación.
     * Como máximo se necesitan N recorridos para propagar
     * la ruta más larga hasta todos los nodos alcanzables.
     */

        $iterations =
            max(
                1,
                count($nodeIds)
            );

        for (
            $iteration = 0;
            $iteration < $iterations;
            $iteration++
        ) {
            $changed = false;

            foreach (
                $nodeEdges
                as
                [$sourceId, $targetId]
            ) {
                if (
                    ! isset(
                        $levelsByNode[$sourceId]
                    )
                ) {
                    continue;
                }

                $candidateLevel =
                    $levelsByNode[$sourceId]
                    +
                    1;

                if (
                    ! isset(
                        $levelsByNode[$targetId]
                    )
                    ||
                    $candidateLevel
                    >
                    $levelsByNode[$targetId]
                ) {
                    $levelsByNode[$targetId] =
                        $candidateLevel;

                    $changed = true;
                }
            }

            if (! $changed) {
                break;
            }
        }

        $levels = [];

        foreach (
            $levelsByNode
            as
            $nodeId => $level
        ) {
            $levels[$level] ??= [];

            $levels[$level][] =
                (int) $nodeId;
        }

        ksort(
            $levels
        );

        foreach ($levels as &$ids) {
            $ids =
                array_values(
                    array_unique(
                        $ids
                    )
                );
        }

        unset($ids);

        return $levels;
    }

    /**
     * Agrupa conexiones por salida real del nodo.
     *
     * Varias conexiones desde la misma PhaseExit representan fan-out,
     * mientras salidas diferentes representan ramas semánticas distintas.
     */
    private function outgoingSourceGroups(
        Collection $connections,
        int $nodeId
    ): Collection {
        return $connections
            ->where('source_type', 'PHASE_EXIT')
            ->where('source_node_id', $nodeId)
            ->groupBy(
                fn($connection) =>
                (string) $connection->source_phase_exit_id
            );
    }

    private function describeStartRoute(
        int $startId,
        Collection $connections,
        array $nodeEdges,
        Collection $nodes,
        Collection $terminals
    ): array {
        $directNodeIds = $connections
            ->where('source_type', 'START')
            ->where('source_start_id', $startId)
            ->where('target_type', 'ENTRY_PORT')
            ->map(
                fn($connection) =>
                $connection->targetEntryPort
                    ?->tournament_phase_node_id
            )
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $reachableNodeIds =
            $this->reachableNodes(
                $directNodeIds,
                $nodeEdges
            );

        $reachableTerminalIds = $connections
            ->where('target_type', 'TERMINAL')
            ->filter(function ($connection) use (
                $startId,
                $reachableNodeIds
            ) {
                if ($connection->source_type === 'START') {
                    return (int) $connection->source_start_id
                        === $startId;
                }

                return in_array(
                    (int) $connection->source_node_id,
                    $reachableNodeIds,
                    true
                );
            })
            ->pluck('target_terminal_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        return [
            'start_id' => $startId,

            'direct_node_ids' =>
            $directNodeIds,

            'reachable_node_ids' =>
            $reachableNodeIds,

            'reachable_nodes' => $nodes
                ->whereIn('id', $reachableNodeIds)
                ->pluck('name')
                ->values()
                ->all(),

            'reachable_terminal_ids' =>
            $reachableTerminalIds->all(),

            'reachable_terminals' => $terminals
                ->whereIn('id', $reachableTerminalIds)
                ->pluck('name')
                ->values()
                ->all(),
        ];
    }

    private function reachableNodes(
        array $initialIds,
        array $nodeEdges
    ): array {
        $adjacency = [];

        foreach ($nodeEdges as [$sourceId, $targetId]) {
            $adjacency[$sourceId] ??= [];
            $adjacency[$sourceId][] = $targetId;
        }

        $queue = array_values(array_unique($initialIds));
        $visited = [];

        while ($queue !== []) {
            $nodeId = (int) array_shift($queue);

            if (isset($visited[$nodeId])) {
                continue;
            }

            $visited[$nodeId] = true;

            foreach ($adjacency[$nodeId] ?? [] as $targetId) {
                if (! isset($visited[$targetId])) {
                    $queue[] = (int) $targetId;
                }
            }
        }

        return array_map(
            'intval',
            array_keys($visited)
        );
    }

    private function startsReachingTerminal(
        int $terminalId,
        Collection $connections,
        Collection $starts,
        Collection $nodes
    ): array {
        $nodeEdges = $this->nodeEdges($connections);

        return $starts
            ->filter(function ($start) use (
                $terminalId,
                $connections,
                $nodeEdges,
                $nodes
            ) {
                $route = $this->describeStartRoute(
                    (int) $start->id,
                    $connections,
                    $nodeEdges,
                    $nodes,
                    collect()
                );

                if (in_array(
                    $terminalId,
                    $route['reachable_terminal_ids'],
                    true
                )) {
                    return true;
                }

                return $connections
                    ->where('source_type', 'START')
                    ->where('source_start_id', $start->id)
                    ->where('target_type', 'TERMINAL')
                    ->where(
                        'target_terminal_id',
                        $terminalId
                    )
                    ->isNotEmpty();
            })
            ->map(fn($start) => [
                'id' => (int) $start->id,
                'code' => $start->code,
                'name' => $start->name,
            ])
            ->values()
            ->all();
    }

    private function levelLabel(int $level): string
    {
        return match ($level) {
            1 => 'Primeras fases',
            2 => 'Segunda etapa',
            3 => 'Tercera etapa',
            default => 'Etapa ' . $level,
        };
    }
}
