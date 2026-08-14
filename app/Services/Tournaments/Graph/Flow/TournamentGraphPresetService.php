<?php

namespace App\Services\Tournaments\Graph\Flow;

use App\Models\PhaseExit;
use App\Models\PhaseTemplate;
use App\Models\TournamentPhaseNode;
use App\Models\TournamentStart;
use App\Models\TournamentTemplate;
use App\Models\TournamentTerminal;
use App\Services\Tournaments\Graph\TournamentGraphConnectionService;
use App\Services\Tournaments\Graph\TournamentGraphEndpointService;
use App\Services\Tournaments\Graph\TournamentGraphNodeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TournamentGraphPresetService
{
    public function __construct(
        private readonly
        TournamentGraphNodeService $nodeService,

        private readonly
        TournamentGraphEndpointService $endpointService,

        private readonly
        TournamentGraphConnectionService $connectionService
    ) {}

    public function apply(
        TournamentTemplate $template,
        array $data
    ): array {
        return DB::transaction(
            function () use (
                $template,
                $data
            ) {
                $lockedTemplate =
                    TournamentTemplate::query()
                    ->whereKey(
                        $template->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureGraphIsEmpty(
                    $lockedTemplate
                );

                return match ($data['preset']) {
                    'LINEAR' =>
                    $this->createLinear(
                        $lockedTemplate,
                        $data
                    ),

                    'GROUPS_KNOCKOUT' =>
                    $this->createTwoStage(
                        $lockedTemplate,
                        $data,
                        'Fase de grupos',
                        'Eliminación principal'
                    ),

                    'SWISS_PLAYOFFS' =>
                    $this->createTwoStage(
                        $lockedTemplate,
                        $data,
                        'Sistema suizo',
                        'Playoffs'
                    ),

                    'MULTI_QUALIFIER' =>
                    $this->createMultiQualifier(
                        $lockedTemplate,
                        $data
                    ),

                    default =>
                    throw ValidationException::withMessages([
                        'preset' => [
                            'La estructura seleccionada no está disponible.',
                        ],
                    ]),
                };
            }
        );
    }

    private function createLinear(
        TournamentTemplate $template,
        array $data
    ): array {
        $phaseTemplates =
            PhaseTemplate::query()
            ->with([
                'exits' =>
                fn($query) =>
                $query
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->orderBy(
                        'priority'
                    )
                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy(
                        'id'
                    ),
            ])
            ->whereIn(
                'id',
                $data['phase_template_ids']
            )
            ->get()
            ->keyBy('id');

        $orderedTemplates =
            collect(
                $data['phase_template_ids']
            )
            ->map(
                fn($id) =>
                $phaseTemplates->get(
                    (int) $id
                )
            )
            ->filter()
            ->values();

        if ($orderedTemplates->isEmpty()) {
            $this->fail(
                'phase_template_ids',
                'No se encontraron las Plantillas seleccionadas.'
            );
        }

        $this->ensureTemplatesHaveExits(
            $orderedTemplates
        );

        $start =
            $this->endpointService
            ->createStart(
                $template,
                [
                    'name' =>
                    $data['start_name'],

                    'description' =>
                    'Inicio generado por el preset lineal.',

                    'source_type' =>
                    'MAIN_POOL',

                    'expected_participants' =>
                    $data['expected_participants'],

                    'status' =>
                    'ACTIVE',
                ]
            );

        $terminal =
            $this->endpointService
            ->createTerminal(
                $template,
                [
                    'name' =>
                    $data['terminal_name'],

                    'description' =>
                    'Destino generado por el preset lineal.',

                    'terminal_type' =>
                    $data['terminal_type'],

                    'expected_participants' =>
                    $data['terminal_type']
                        ===
                        'CHAMPION'
                        ? 1
                        : null,

                    'status' =>
                    'ACTIVE',
                ]
            );

        $nodes =
            collect();

        foreach (
            $orderedTemplates
            as
            $index => $phaseTemplate
        ) {
            $nodes->push(
                $this->nodeService
                    ->create(
                        $template,
                        $phaseTemplate,
                        [
                            'name' =>
                            $phaseTemplate->name,

                            'description' =>
                            'Etapa '
                                .
                                ($index + 1)
                                .
                                ' del recorrido lineal.',

                            'status' =>
                            'ACTIVE',
                        ]
                    )
            );
        }

        $this->connectStartToNode(
            $template,
            $start,
            $nodes->first()
        );

        for (
            $index = 0;
            $index < $nodes->count() - 1;
            $index++
        ) {
            $this->connectNodeToNode(
                $template,
                $nodes[$index],
                $nodes[$index + 1]
            );
        }

        $this->connectNodeToTerminal(
            $template,
            $nodes->last(),
            $terminal
        );

        return [
            'preset' =>
            'LINEAR',

            'starts' =>
            1,

            'nodes' =>
            $nodes->count(),

            'terminals' =>
            1,
        ];
    }

    private function createTwoStage(
        TournamentTemplate $template,
        array $data,
        string $firstNodeName,
        string $secondNodeName
    ): array {
        $templates =
            $this->loadTemplates([
                $data['primary_phase_template_id'],
                $data['secondary_phase_template_id'],
            ]);

        $this->ensureTemplatesHaveExits(
            $templates
        );

        $firstTemplate =
            $templates->firstWhere(
                'id',
                (int) $data['primary_phase_template_id']
            );

        $secondTemplate =
            $templates->firstWhere(
                'id',
                (int) $data['secondary_phase_template_id']
            );

        if (! $firstTemplate || ! $secondTemplate) {
            $this->fail(
                'primary_phase_template_id',
                'No fue posible recuperar las Plantillas seleccionadas.'
            );
        }

        $start =
            $this->endpointService
            ->createStart(
                $template,
                [
                    'name' =>
                    $data['start_name'],

                    'description' =>
                    'Pool inicial generado automáticamente.',

                    'source_type' =>
                    'MAIN_POOL',

                    'expected_participants' =>
                    $data['expected_participants'],

                    'status' =>
                    'ACTIVE',
                ]
            );

        $firstNode =
            $this->nodeService
            ->create(
                $template,
                $firstTemplate,
                [
                    'name' =>
                    $firstNodeName,

                    'description' =>
                    'Primera etapa generada por un preset.',

                    'status' =>
                    'ACTIVE',
                ]
            );

        $secondNode =
            $this->nodeService
            ->create(
                $template,
                $secondTemplate,
                [
                    'name' =>
                    $secondNodeName,

                    'description' =>
                    'Segunda etapa generada por un preset.',

                    'status' =>
                    'ACTIVE',
                ]
            );

        $terminal =
            $this->endpointService
            ->createTerminal(
                $template,
                [
                    'name' =>
                    $data['terminal_name'],

                    'description' =>
                    'Resultado principal del torneo.',

                    'terminal_type' =>
                    $data['terminal_type'],

                    'expected_participants' =>
                    $data['terminal_type']
                        ===
                        'CHAMPION'
                        ? 1
                        : null,

                    'status' =>
                    'ACTIVE',
                ]
            );

        $this->connectStartToNode(
            $template,
            $start,
            $firstNode
        );

        $this->connectNodeToNode(
            $template,
            $firstNode,
            $secondNode
        );

        $this->connectNodeToTerminal(
            $template,
            $secondNode,
            $terminal
        );

        return [
            'preset' =>
            $data['preset'],

            'starts' =>
            1,

            'nodes' =>
            2,

            'terminals' =>
            1,
        ];
    }

    private function createMultiQualifier(
        TournamentTemplate $template,
        array $data
    ): array {
        $templates =
            $this->loadTemplates([
                $data['primary_phase_template_id'],
                $data['secondary_phase_template_id'],
            ]);

        $this->ensureTemplatesHaveExits(
            $templates
        );

        $qualifierTemplate =
            $templates->firstWhere(
                'id',
                (int) $data['primary_phase_template_id']
            );

        $mainTemplate =
            $templates->firstWhere(
                'id',
                (int) $data['secondary_phase_template_id']
            );

        if (! $qualifierTemplate || ! $mainTemplate) {
            $this->fail(
                'primary_phase_template_id',
                'No fue posible recuperar las Plantillas seleccionadas.'
            );
        }

        $mainNode =
            $this->nodeService
            ->create(
                $template,
                $mainTemplate,
                [
                    'name' =>
                    'Torneo principal',

                    'description' =>
                    'Convergencia de los diferentes clasificatorios.',

                    'status' =>
                    'ACTIVE',
                ]
            );

        $terminal =
            $this->endpointService
            ->createTerminal(
                $template,
                [
                    'name' =>
                    $data['terminal_name'],

                    'description' =>
                    'Resultado principal después de la convergencia.',

                    'terminal_type' =>
                    $data['terminal_type'],

                    'expected_participants' =>
                    $data['terminal_type']
                        ===
                        'CHAMPION'
                        ? 1
                        : null,

                    'status' =>
                    'ACTIVE',
                ]
            );

        $starts =
            collect();

        $qualifierNodes =
            collect();

        foreach (
            $data['region_names']
            as
            $regionName
        ) {
            $start =
                $this->endpointService
                ->createStart(
                    $template,
                    [
                        'name' =>
                        $regionName,

                        'description' =>
                        'Origen regional de participantes.',

                        'source_type' =>
                        'QUALIFIER_POOL',

                        'expected_participants' =>
                        $data['participants_per_region'],

                        'status' =>
                        'ACTIVE',
                    ]
                );

            $qualifierNode =
                $this->nodeService
                ->create(
                    $template,
                    $qualifierTemplate,
                    [
                        'name' =>
                        'Clasificación '
                            .
                            $regionName,

                        'description' =>
                        'Ruta clasificatoria de '
                            .
                            $regionName
                            .
                            '.',

                        'status' =>
                        'ACTIVE',
                    ]
                );

            $this->connectStartToNode(
                $template,
                $start,
                $qualifierNode
            );

            $this->connectNodeToNode(
                $template,
                $qualifierNode,
                $mainNode
            );

            $starts->push(
                $start
            );

            $qualifierNodes->push(
                $qualifierNode
            );
        }

        $this->connectNodeToTerminal(
            $template,
            $mainNode,
            $terminal
        );

        return [
            'preset' =>
            'MULTI_QUALIFIER',

            'starts' =>
            $starts->count(),

            'nodes' =>
            $qualifierNodes->count()
                +
                1,

            'terminals' =>
            1,
        ];
    }

    private function connectStartToNode(
        TournamentTemplate $template,
        TournamentStart $start,
        TournamentPhaseNode $targetNode
    ): void {
        $targetNode->loadMissing(
            'entryPorts'
        );

        $entryPort =
            $targetNode
            ->entryPorts
            ->where(
                'status',
                'ACTIVE'
            )
            ->sortBy(
                'sort_order'
            )
            ->first();

        if (! $entryPort) {
            $this->fail(
                'preset',
                'La Fase “'
                    .
                    $targetNode->name
                    .
                    '” no tiene una puerta de entrada activa.'
            );
        }

        $this->connectionService
            ->create(
                $template,
                [
                    'label' =>
                    'Ingreso a '
                        .
                        $targetNode->name,

                    'description' =>
                    'Conexión generada automáticamente.',

                    'source_type' =>
                    'START',

                    'source_start_id' =>
                    $start->id,

                    'target_type' =>
                    'ENTRY_PORT',

                    'target_entry_port_id' =>
                    $entryPort->id,

                    'allocation_mode' =>
                    'ALL',

                    'allocation_value' =>
                    null,

                    'priority' =>
                    10,

                    'status' =>
                    'ACTIVE',
                ]
            );
    }

    private function connectNodeToNode(
        TournamentTemplate $template,
        TournamentPhaseNode $sourceNode,
        TournamentPhaseNode $targetNode
    ): void {
        $sourceExit =
            $this->primaryExit(
                $sourceNode
            );

        $targetNode->loadMissing(
            'entryPorts'
        );

        $entryPort =
            $targetNode
            ->entryPorts
            ->where(
                'status',
                'ACTIVE'
            )
            ->sortBy(
                'sort_order'
            )
            ->first();

        if (! $entryPort) {
            $this->fail(
                'preset',
                'La Fase “'
                    .
                    $targetNode->name
                    .
                    '” no tiene una puerta de entrada activa.'
            );
        }

        $this->connectionService
            ->create(
                $template,
                [
                    'label' =>
                    $sourceExit->name
                        .
                        ' hacia '
                        .
                        $targetNode->name,

                    'description' =>
                    'Conexión generada automáticamente.',

                    'source_type' =>
                    'PHASE_EXIT',

                    'source_node_id' =>
                    $sourceNode->id,

                    'source_phase_exit_id' =>
                    $sourceExit->id,

                    'target_type' =>
                    'ENTRY_PORT',

                    'target_entry_port_id' =>
                    $entryPort->id,

                    'allocation_mode' =>
                    'ALL',

                    'allocation_value' =>
                    null,

                    'priority' =>
                    10,

                    'status' =>
                    'ACTIVE',
                ]
            );
    }

    private function connectNodeToTerminal(
        TournamentTemplate $template,
        TournamentPhaseNode $sourceNode,
        TournamentTerminal $terminal
    ): void {
        $sourceExit =
            $this->primaryExit(
                $sourceNode
            );

        $this->connectionService
            ->create(
                $template,
                [
                    'label' =>
                    $sourceExit->name
                        .
                        ' hacia '
                        .
                        $terminal->name,

                    'description' =>
                    'Conexión final generada automáticamente.',

                    'source_type' =>
                    'PHASE_EXIT',

                    'source_node_id' =>
                    $sourceNode->id,

                    'source_phase_exit_id' =>
                    $sourceExit->id,

                    'target_type' =>
                    'TERMINAL',

                    'target_terminal_id' =>
                    $terminal->id,

                    'allocation_mode' =>
                    'ALL',

                    'allocation_value' =>
                    null,

                    'priority' =>
                    10,

                    'status' =>
                    'ACTIVE',
                ]
            );
    }

    private function primaryExit(
        TournamentPhaseNode $node
    ): PhaseExit {
        $node->loadMissing(
            'phaseTemplate.exits'
        );

        $activeExits =
            $node
            ->phaseTemplate
            ->exits
            ->where(
                'status',
                'ACTIVE'
            )
            ->sortBy(
                fn($exit) =>
                sprintf(
                    '%010d-%010d-%010d',
                    $exit->priority,
                    $exit->sort_order,
                    $exit->id
                )
            )
            ->values();

        $preferred =
            $activeExits
            ->first(
                fn($exit) =>
                ! in_array(
                    $exit->selector_type,
                    [
                        'ELIMINATED',
                        'ELIMINATED_IN_ROUND',
                        'MATCH_LOSERS',
                        'BOTTOM_N',
                        'REMAINING',
                    ],
                    true
                )
            );

        $exit =
            $preferred
            ??
            $activeExits->first();

        if (! $exit) {
            $this->fail(
                'preset',
                'La Fase “'
                    .
                    $node->name
                    .
                    '” no posee una salida activa.'
            );
        }

        return $exit;
    }

    private function loadTemplates(
        array $ids
    ): Collection {
        return PhaseTemplate::query()
            ->with([
                'exits' =>
                fn($query) =>
                $query
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->orderBy(
                        'priority'
                    )
                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy(
                        'id'
                    ),
            ])
            ->whereIn(
                'id',
                $ids
            )
            ->get();
    }

    private function ensureTemplatesHaveExits(
        Collection $templates
    ): void {
        foreach ($templates as $template) {
            if ($template->exits->isEmpty()) {
                $this->fail(
                    'preset',
                    'La Fase “'
                        .
                        $template->name
                        .
                        '” no tiene salidas activas y no puede usarse en un preset automático.'
                );
            }
        }
    }

    private function ensureGraphIsEmpty(
        TournamentTemplate $template
    ): void {
        $hasContent =
            $template
            ->graphStarts()
            ->exists()
            ||
            $template
            ->graphNodes()
            ->exists()
            ||
            $template
            ->graphTerminals()
            ->exists()
            ||
            $template
            ->graphConnections()
            ->exists();

        if ($hasContent) {
            $this->fail(
                'preset',
                'Los presets solo pueden aplicarse a un grafo vacío. Esto evita sobrescribir o mezclar una estructura que ya estás construyendo.'
            );
        }
    }

    private function fail(
        string $field,
        string $message
    ): never {
        throw ValidationException::withMessages([
            $field => [
                $message,
            ],
        ]);
    }
}
