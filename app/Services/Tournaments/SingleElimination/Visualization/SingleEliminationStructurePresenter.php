<?php

namespace App\Services\Tournaments\SingleElimination\Visualization;

use App\Models\PhaseSingleEliminationConnection;
use App\Models\PhaseTemplate;
use Illuminate\Support\Collection;

class SingleEliminationStructurePresenter
{
    private array $issuesByEntity = [];

    private int $globalEncounterNumber = 0;

    private int $globalSlotNumber = 0;

    public function present(
        PhaseTemplate $phaseTemplate,
        $settings,
        array $validation
    ): array {
        $this->issuesByEntity =
            $this->indexIssues(
                $validation
            );

        $this->globalEncounterNumber =
            0;

        $this->globalSlotNumber =
            0;

        $rounds =
            $phaseTemplate
            ->singleEliminationRounds;

        $encounterRoundMap = $rounds->flatMap(
            fn($round) => $round->encounters->mapWithKeys(
                fn($encounter) => [$encounter->id => $round]
            )
        );

        $connections = $phaseTemplate
            ->singleEliminationConnections
            ->map(
                fn(PhaseSingleEliminationConnection $connection) =>
                $this->presentConnection(
                    $connection,
                    $encounterRoundMap
                )
            )
            ->values();

        $connectionIndex = $connections->keyBy('key');

        $presentedRounds = $rounds->map(
            fn($round) => $this->presentRound(
                $round,
                $connectionIndex
            )
        )->values();

        $inputGates = $phaseTemplate->inputGates->map(
            fn($gate) => $this->presentInputGate(
                $gate,
                $connectionIndex
            )
        )->values();

        $exits = $phaseTemplate->exits->map(
            fn($exit) => $this->presentExit(
                $exit,
                $connectionIndex
            )
        )->values();

        $stats = $validation['stats'];

        $stats['manual_elements'] = $this->countFlaggedElements(
            $phaseTemplate,
            'generation_source',
            'MANUAL'
        );

        $stats['locked_elements'] = $this->countFlaggedElements(
            $phaseTemplate,
            'is_locked',
            true
        );

        return [
            'phase' => $this->decorate(
                'PHASE_TEMPLATE',
                $phaseTemplate->id,
                [
                    'kind' => 'PHASE_TEMPLATE',
                    'kind_label' => 'Fase',
                    'id' => $phaseTemplate->id,
                    'name' => $phaseTemplate->name,
                    'code' => $phaseTemplate->code,
                    'description' => $phaseTemplate->description,
                    'status' => $phaseTemplate->status,
                    'contract' =>
                    $phaseTemplate->participant_contract_label,
                    'generation_source' => 'CONFIGURED',
                    'locked' => false,
                    'route_keys' => [],
                    'details' => [
                        [
                            'label' => 'Contrato',
                            'value' =>
                            $phaseTemplate
                                ->participant_contract_label,
                        ],
                        [
                            'label' => 'Tipo',
                            'value' => 'Eliminación Simple',
                        ],
                    ],
                ]
            ),

            'structure' => [
                'status' =>
                $settings->structure_status
                    ??
                    'NOT_GENERATED',

                'status_label' =>
                $settings->structure_status_label,

                'version' =>
                (int) (
                    $settings->structure_version
                    ??
                    0
                ),

                'generated_at' =>
                $settings
                    ->structure_generated_at
                    ?->toIso8601String(),

                'generated_at_label' =>
                $settings
                    ->structure_generated_at
                    ?->diffForHumans(),

                'validated_at' =>
                $settings
                    ->structure_validated_at
                    ?->toIso8601String(),

                'configuration_mode' =>
                $settings->configuration_mode,

                'executable' =>
                (bool) $validation['executable'],
            ],

            'stats' =>
            $stats,

            'counts' =>
            $validation['counts'],

            'input_gates' =>
            $inputGates->all(),

            'rounds' =>
            $presentedRounds->all(),

            'connections' =>
            $connections->all(),

            'exits' =>
            $exits->all(),

            'issues' =>
            $this->allIssues(
                $validation
            ),

            'options' => [
                'default_view' => ($stats['encounters'] ?? 0) > 32
                    ? 'compact'
                    : 'blocks',

                'large_structure' => ($stats['encounters'] ?? 0) > 32,
            ],
        ];
    }

    private function presentInputGate(
        $gate,
        Collection $connectionIndex
    ): array {
        $routeKeys = $gate
            ->outgoingConnections
            ->map(
                fn($connection) =>
                $this->key(
                    'CONNECTION',
                    $connection->id
                )
            )
            ->values()
            ->all();

        return $this->decorate(
            'INPUT_GATE',
            $gate->id,
            [
                'kind' => 'INPUT_GATE',
                'kind_label' => 'Puerta de entrada',
                'id' => $gate->id,
                'code' => $gate->code,
                'name' => $gate->name,
                'description' => $gate->description,
                'status' => $gate->status,
                'type' => $gate->input_type,
                'type_label' => $gate->type_label,
                'input_type' => $gate->input_type,
                'merge_policy' => $gate->merge_policy,
                'distribution_mode' => $gate->distribution_mode,
                'empty_behavior' => $gate->empty_behavior,
                'min_participants' => $gate->min_participants,
                'max_participants' => $gate->max_participants,
                'exact_participants' => $gate->exact_participants,
                'priority' => (int) $gate->priority,
                'sort_order' => (int) $gate->sort_order,
                'accepts_multiple_connections' => (bool) $gate->accepts_multiple_connections,
                'contract' => $gate->contract_label,
                'distribution' => $gate->distribution_label,
                'required' => (bool) $gate->is_required,
                'accepts_batch' => (bool) $gate->accepts_batch,
                'generation_source' =>
                $gate->generation_source,
                'locked' => (bool) $gate->is_locked,
                'route_keys' => $routeKeys,

                'routes' =>
                $this->routesFromKeys(
                    $routeKeys,
                    $connectionIndex
                ),

                'contextual_ports' =>
                $gate
                    ->contextualEntryPorts
                    ->count(),

                'details' => [
                    [
                        'label' => 'Tipo',
                        'value' => $gate->type_label,
                    ],
                    [
                        'label' => 'Capacidad',
                        'value' => $gate->contract_label,
                    ],
                    [
                        'label' => 'Distribución',
                        'value' => $gate->distribution_label,
                    ],
                    [
                        'label' => 'Política de unión',
                        'value' => $gate->merge_policy,
                    ],
                    [
                        'label' => 'Si está vacía',
                        'value' => $gate->empty_behavior,
                    ],
                ],
            ]
        );
    }

    private function presentRound(
        $round,
        Collection $connectionIndex
    ): array {
        $encounters = $round
            ->encounters
            ->map(
                fn($encounter) =>
                $this->presentEncounter(
                    $encounter,
                    $round,
                    $connectionIndex
                )
            )
            ->values();

        $routeKeys = $encounters
            ->flatMap(
                fn(array $encounter) =>
                $encounter['route_keys']
            )
            ->unique()
            ->values()
            ->all();

        $item = $this->decorate(
            'ROUND',
            $round->id,
            [
                'kind' => 'ROUND',
                'kind_label' => 'Ronda',
                'id' => $round->id,
                'code' => $round->code,
                'name' => $round->name,
                'description' => $round->description,
                'status' => $round->status,
                'stage_number' =>
                (int) $round->stage_number,
                'branch_code' => $round->branch_code,
                'round_type' => $round->round_type,
                'sort_order' => (int) $round->sort_order,
                'branch' => $round->branch_label,
                'type_label' => $round->type_label,
                'participants_expected' =>
                (int) $round->participants_expected,
                'qualifiers_expected' =>
                (int) $round->qualifiers_expected,

                'byes' =>
                (int) (
                    $round->settings['byes']
                    ??
                    0
                ),

                'generation_source' =>
                $round->generation_source,

                'locked' =>
                (bool) $round->is_locked,

                'encounter_count' =>
                $encounters->count(),

                'encounter_global_from' =>
                $encounters->min(
                    'global_number'
                ),

                'encounter_global_to' =>
                $encounters->max(
                    'global_number'
                ),

                'route_keys' =>
                $routeKeys,
                'encounters' =>
                $encounters->all(),

                'details' => [
                    [
                        'label' => 'Etapa topológica',
                        'value' =>
                        (string) $round->stage_number,
                    ],
                    [
                        'label' => 'Rama',
                        'value' => $round->branch_label,
                    ],
                    [
                        'label' => 'Tipo',
                        'value' => $round->type_label,
                    ],
                    [
                        'label' =>
                        'Participantes esperados',

                        'value' =>
                        (string)
                        $round->participants_expected,
                    ],
                    [
                        'label' =>
                        'Clasificados esperados',

                        'value' =>
                        (string)
                        $round->qualifiers_expected,
                    ],
                ],
            ]
        );

        $item['issue_count'] = max(
            $item['issue_count'],
            $encounters->sum('issue_count')
        );

        $item['issue_level'] =
            $this->highestLevel([
                $item['issue_level'],
                ...$encounters
                    ->pluck('issue_level')
                    ->all(),
            ]);

        return $item;
    }

    private function presentEncounter(
        $encounter,
        $round,
        Collection $connectionIndex
    ): array {
        $globalNumber =
            ++$this->globalEncounterNumber;

        $slots = $encounter
            ->slots
            ->map(
                fn($slot) =>
                $this->presentSlot(
                    $slot,
                    $encounter,
                    $round,
                    $connectionIndex
                )
            )
            ->values();

        $results = $encounter
            ->results
            ->map(
                fn($result) =>
                $this->presentResult(
                    $result,
                    $encounter,
                    $round,
                    $connectionIndex
                )
            )
            ->values();

        $incomingRouteKeys = $slots
            ->flatMap(
                fn(array $slot) =>
                $slot['route_keys']
            )
            ->unique()
            ->values()
            ->all();

        $outgoingRouteKeys = $results
            ->flatMap(
                fn(array $result) =>
                $result['route_keys']
            )
            ->unique()
            ->values()
            ->all();

        $sources = $this->routesFromKeys(
            $incomingRouteKeys,
            $connectionIndex
        );

        $destinations = $this->routesFromKeys(
            $outgoingRouteKeys,
            $connectionIndex
        );

        $item = $this->decorate(
            'ENCOUNTER',
            $encounter->id,
            [
                'kind' => 'ENCOUNTER',
                'kind_label' => 'Encuentro',
                'id' => $encounter->id,
                'code' => $encounter->code,
                'name' => $encounter->name,
                'description' => $encounter->description,
                'status' => $encounter->status,
                'position' =>
                (int) $encounter->position,

                'local_number' =>
                (int) $encounter->position,

                'global_number' =>
                $globalNumber,

                'global_label' =>
                'Encuentro global #'
                    .
                    $globalNumber,

                'round_id' =>
                $round->id,

                'round_key' =>
                $this->key(
                    'ROUND',
                    $round->id
                ),

                'round_name' =>
                $round->name,

                'stage_number' =>
                (int) $round->stage_number,

                'format' =>
                $encounter
                    ->competitive_format_label,

                'entrants' =>
                (int) $encounter->entrants_count,
                'entrants_count' => (int) $encounter->entrants_count,

                'qualifiers' =>
                (int) $encounter->qualifiers_count,
                'qualifiers_count' => (int) $encounter->qualifiers_count,
                'min_entrants_to_start' => (int) $encounter->min_entrants_to_start,

                'profile' =>
                $encounter->profile_label,
                'encounter_profile' => $encounter->encounter_profile,
                'activation_policy' => $encounter->activation_policy,
                'series_format' => $encounter->series_format,
                'best_of' => (int) ($encounter->best_of ?? 1),
                'fixed_games' => (int) ($encounter->fixed_games ?? 1),
                'sort_order' => (int) $encounter->sort_order,

                'series' =>
                $encounter->series_label,

                'allows_incomplete' =>
                (bool)
                $encounter->allows_incomplete,

                'generation_source' =>
                $encounter->generation_source,

                'locked' =>
                (bool) $encounter->is_locked,

                'slots' =>
                $slots->all(),

                'results' =>
                $results->all(),

                'route_keys' =>
                collect($incomingRouteKeys)
                    ->merge($outgoingRouteKeys)
                    ->unique()
                    ->values()
                    ->all(),

                'source_labels' =>
                collect($sources)
                    ->pluck('source_label')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),

                'destination_labels' =>
                collect($destinations)
                    ->pluck('target_label')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),

                'details' => [
                    [
                        'label' => 'Ronda',
                        'value' => $round->name,
                    ],
                    [
                        'label' =>
                        'Número global',

                        'value' =>
                        'Encuentro #'
                            .
                            $globalNumber,
                    ],
                    [
                        'label' =>
                        'Número en la ronda',

                        'value' =>
                        'Encuentro #'
                            .
                            $encounter->position,
                    ],
                    [
                        'label' => 'Formato',
                        'value' =>
                        $encounter
                            ->competitive_format_label,
                    ],
                    [
                        'label' => 'Perfil',
                        'value' =>
                        $encounter->profile_label,
                    ],
                    [
                        'label' => 'Serie',
                        'value' =>
                        $encounter->series_label,
                    ],
                    [
                        'label' => 'Activación',
                        'value' =>
                        $encounter
                            ->activation_policy,
                    ],
                ],
            ]
        );

        $children = $slots->merge($results);

        $item['issue_count'] = max(
            $item['issue_count'],
            $children->sum('issue_count')
        );

        $item['issue_level'] =
            $this->highestLevel([
                $item['issue_level'],
                ...$children
                    ->pluck('issue_level')
                    ->all(),
            ]);

        return $item;
    }

    private function presentSlot(
        $slot,
        $encounter,
        $round,
        Collection $connectionIndex
    ): array {
        $globalNumber =
            ++$this->globalSlotNumber;

        $routeKeys = $slot
            ->incomingConnections
            ->map(
                fn($connection) =>
                $this->key(
                    'CONNECTION',
                    $connection->id
                )
            )
            ->values()
            ->all();

        return $this->decorate(
            'SLOT',
            $slot->id,
            [
                'kind' => 'SLOT',
                'kind_label' => 'Slot',
                'id' => $slot->id,
                'code' => $slot->code,
                'name' =>
                'Slot '
                    .
                    $slot->position,

                'description' => null,
                'status' => $slot->status,
                'position' =>
                (int) $slot->position,

                'local_number' =>
                (int) $slot->position,

                'global_number' =>
                $globalNumber,

                'global_label' =>
                'Slot global #'
                    .
                    $globalNumber,

                'type_label' =>
                $slot->type_label,
                'slot_type' => $slot->slot_type,
                'capacity' => (int) $slot->capacity,
                'required' => (bool) $slot->is_required,
                'source_policy' =>
                $slot->source_policy_label,
                'source_policy_value' => $slot->source_policy,
                'empty_behavior' =>
                $slot->empty_behavior,
                'assignment_rule' => $slot->assignment_rule,
                'sort_order' => (int) $slot->sort_order,
                'generation_source' =>
                $slot->generation_source,
                'locked' => (bool) $slot->is_locked,

                'parent_key' =>
                $this->key(
                    'ENCOUNTER',
                    $encounter->id
                ),

                'round_key' =>
                $this->key(
                    'ROUND',
                    $round->id
                ),

                'route_keys' =>
                $routeKeys,

                'routes' =>
                $this->routesFromKeys(
                    $routeKeys,
                    $connectionIndex
                ),

                'details' => [
                    [
                        'label' => 'Encuentro',
                        'value' => $encounter->name,
                    ],
                    [
                        'label' =>
                        'Número global',

                        'value' =>
                        'Slot #'
                            .
                            $globalNumber,
                    ],
                    [
                        'label' =>
                        'Número en el encuentro',

                        'value' =>
                        'Slot #'
                            .
                            $slot->position,
                    ],
                    [
                        'label' => 'Tipo',
                        'value' => $slot->type_label,
                    ],
                    [
                        'label' => 'Capacidad',
                        'value' =>
                        (string) $slot->capacity,
                    ],
                    [
                        'label' =>
                        'Política de fuente',

                        'value' =>
                        $slot
                            ->source_policy_label,
                    ],
                    [
                        'label' => 'Si queda vacío',
                        'value' =>
                        $slot->empty_behavior,
                    ],
                ],
            ]
        );
    }

    private function presentResult(
        $result,
        $encounter,
        $round,
        Collection $connectionIndex
    ): array {
        $routeKeys = $result
            ->outgoingConnections
            ->map(
                fn($connection) =>
                $this->key(
                    'CONNECTION',
                    $connection->id
                )
            )
            ->values()
            ->all();

        return $this->decorate(
            'RESULT',
            $result->id,
            [
                'kind' => 'RESULT',
                'kind_label' => 'Resultado',
                'id' => $result->id,
                'code' => $result->code,
                'name' => $result->name,
                'description' => $result->description,
                'status' => $result->status,
                'type_label' => $result->type_label,
                'result_type' => $result->result_type,
                'position_from' => $result->position_from,
                'position_to' => $result->position_to,
                'quantity' => (int) $result->quantity,
                'flow_mode' => $result->flow_mode,
                'priority' => (int) $result->priority,
                'sort_order' => (int) $result->sort_order,
                'accepts_multiple_connections' => (bool) $result->accepts_multiple_connections,
                'quantity_label' =>
                $result->quantity_label,
                'position_label' =>
                $result->position_label,
                'participant_status' =>
                $result->participant_status,
                'required' =>
                (bool) $result->is_required,
                'splittable' =>
                (bool) $result->is_splittable,
                'generation_source' =>
                $result->generation_source,
                'locked' =>
                (bool) $result->is_locked,

                'parent_key' =>
                $this->key(
                    'ENCOUNTER',
                    $encounter->id
                ),

                'round_key' =>
                $this->key(
                    'ROUND',
                    $round->id
                ),

                'route_keys' =>
                $routeKeys,

                'routes' =>
                $this->routesFromKeys(
                    $routeKeys,
                    $connectionIndex
                ),

                'details' => [
                    [
                        'label' => 'Encuentro',
                        'value' => $encounter->name,
                    ],
                    [
                        'label' => 'Tipo',
                        'value' =>
                        $result->type_label,
                    ],
                    [
                        'label' => 'Cantidad',
                        'value' =>
                        $result->quantity_label,
                    ],
                    [
                        'label' => 'Posición',
                        'value' =>
                        $result->position_label
                            ??
                            'No aplica',
                    ],
                    [
                        'label' =>
                        'Estado producido',

                        'value' =>
                        $result
                            ->participant_status,
                    ],
                ],
            ]
        );
    }

    private function presentConnection(
        PhaseSingleEliminationConnection $connection,
        Collection $encounterRoundMap
    ): array {
        $sourceEncounter =
            $connection
            ->sourceResult
            ?->encounter;

        $targetEncounter =
            $connection
            ->targetSlot
            ?->encounter;

        $sourceRound = $sourceEncounter
            ? $encounterRoundMap->get(
                $sourceEncounter->id
            )
            : null;

        $targetRound = $targetEncounter
            ? $encounterRoundMap->get(
                $targetEncounter->id
            )
            : null;

        $sourceKey =
            $connection->source_type
            ===
            'INPUT_GATE'
            ? $this->key(
                'INPUT_GATE',
                $connection
                    ->source_input_gate_id
            )
            : $this->key(
                'RESULT',
                $connection->source_result_id
            );

        $targetKey =
            $connection->target_type
            ===
            'PHASE_EXIT'
            ? $this->key(
                'PHASE_EXIT',
                $connection
                    ->target_phase_exit_id
            )
            : $this->key(
                'SLOT',
                $connection->target_slot_id
            );

        /*
        * Las conexiones reales salen de INPUT_GATE o RESULT
        * y llegan a SLOT o PHASE_EXIT.
        *
        * ENCOUNTER es solamente un contenedor visual.
        */
        $sourceOwnerKey =
            $sourceKey;

        $targetOwnerKey =
            $targetKey;

        $key = $this->key(
            'CONNECTION',
            $connection->id
        );

        return $this->decorate(
            'CONNECTION',
            $connection->id,
            [
                'kind' => 'CONNECTION',
                'kind_label' => 'Conexión interna',
                'id' => $connection->id,
                'code' => $connection->code,

                'name' =>
                $connection->label
                    ??
                    $connection->code,

                'description' =>
                $connection->description,

                'status' =>
                $connection->status,

                'source_type' =>
                $connection->source_type,
                'source_input_gate_id' => $connection->source_input_gate_id,
                'source_result_id' => $connection->source_result_id,

                'source_key' =>
                $sourceKey,

                'source_owner_key' =>
                $sourceOwnerKey,

                'source_label' =>
                $connection->source_label,

                'source_round_key' =>
                $sourceRound
                    ? $this->key(
                        'ROUND',
                        $sourceRound->id
                    )
                    : null,

                'target_type' =>
                $connection->target_type,
                'target_slot_id' => $connection->target_slot_id,
                'target_phase_exit_id' => $connection->target_phase_exit_id,

                'target_key' =>
                $targetKey,

                'target_owner_key' =>
                $targetOwnerKey,

                'target_label' =>
                $connection->target_label,

                'target_round_key' =>
                $targetRound
                    ? $this->key(
                        'ROUND',
                        $targetRound->id
                    )
                    : null,

                'allocation' =>
                $connection->allocation_label,

                'allocation_mode' =>
                $connection->allocation_mode,

                'allocation_value' =>
                $connection->allocation_value,

                'condition_type' =>
                $connection->condition_type,

                'priority' =>
                (int) $connection->priority,

                'generation_source' =>
                $connection->generation_source,

                'locked' =>
                (bool) $connection->is_locked,

                'route_keys' => [
                    $key,
                ],

                'details' => [
                    [
                        'label' => 'Origen',
                        'value' =>
                        $connection->source_label,
                    ],
                    [
                        'label' => 'Destino',
                        'value' =>
                        $connection->target_label,
                    ],
                    [
                        'label' => 'Asignación',
                        'value' =>
                        $connection
                            ->allocation_label,
                    ],
                    [
                        'label' => 'Condición',
                        'value' =>
                        $connection
                            ->condition_type,
                    ],
                    [
                        'label' => 'Prioridad',
                        'value' =>
                        (string)
                        $connection->priority,
                    ],
                ],
            ]
        );
    }

    private function presentExit(
        $exit,
        Collection $connectionIndex
    ): array {
        $routeKeys = $exit
            ->incomingInternalConnections
            ->map(
                fn($connection) =>
                $this->key(
                    'CONNECTION',
                    $connection->id
                )
            )
            ->values()
            ->all();

        return $this->decorate(
            'PHASE_EXIT',
            $exit->id,
            [
                'kind' => 'PHASE_EXIT',
                'kind_label' => 'Puerta de salida',
                'id' => $exit->id,
                'code' => $exit->code,
                'name' => $exit->name,
                'description' => $exit->description,
                'status' => $exit->status,
                'selector' => $exit->selector_label,
                'summary' => $exit->selection_summary,
                'contract' => $exit->contract_label,
                'resolution_mode' =>
                $exit->resolution_mode_label,
                'generation_source' => 'CONFIGURED',
                'locked' => false,
                'route_keys' => $routeKeys,

                'routes' =>
                $this->routesFromKeys(
                    $routeKeys,
                    $connectionIndex
                ),

                'details' => [
                    [
                        'label' => 'Selector',
                        'value' =>
                        $exit->selector_label,
                    ],
                    [
                        'label' => 'Capacidad',
                        'value' =>
                        $exit->contract_label,
                    ],
                    [
                        'label' => 'Resolución',
                        'value' =>
                        $exit
                            ->resolution_mode_label,
                    ],
                    [
                        'label' => 'Momento',
                        'value' =>
                        $exit->exit_timing,
                    ],
                    [
                        'label' => 'Rutas entrantes',
                        'value' =>
                        (string)
                        count($routeKeys),
                    ],
                ],
            ]
        );
    }

    private function routesFromKeys(
        array $routeKeys,
        Collection $connectionIndex
    ): array {
        return collect($routeKeys)
            ->map(
                fn(string $key) =>
                $connectionIndex->get($key)
            )
            ->filter()
            ->values()
            ->all();
    }

    private function decorate(
        string $entityType,
        int $entityId,
        array $item
    ): array {
        $key = $this->key(
            $entityType,
            $entityId
        );

        $issues =
            $this->issuesByEntity[$key]
            ??
            [];

        return [
            ...$item,

            'key' =>
            $key,

            'issues' =>
            $issues,

            'issue_count' =>
            count($issues),

            'issue_level' =>
            $this->highestLevel(
                collect($issues)
                    ->pluck('severity')
                    ->all()
            ),
        ];
    }

    private function indexIssues(
        array $validation
    ): array {
        return collect(
            $this->allIssues(
                $validation
            )
        )
            ->groupBy('entity_key')
            ->map(
                fn(Collection $issues) =>
                $issues
                    ->values()
                    ->all()
            )
            ->all();
    }

    private function allIssues(
        array $validation
    ): array {
        return collect([
            'ERROR' =>
            $validation['errors'],

            'WARNING' =>
            $validation['warnings'],

            'RECOMMENDATION' =>
            $validation['recommendations'],
        ])
            ->flatMap(
                fn(
                    array $issues,
                    string $severity
                ) =>
                collect($issues)
                    ->map(
                        fn(array $issue) => [
                            ...$issue,

                            'severity' =>
                            $severity,

                            'entity_key' =>
                            $issue['entity_id']
                                ? $this->key(
                                    $issue['entity_type'],
                                    $issue['entity_id']
                                )
                                : null,
                        ]
                    )
            )
            ->values()
            ->all();
    }

    private function countFlaggedElements(
        PhaseTemplate $phaseTemplate,
        string $field,
        mixed $value
    ): int {
        $encounters = $phaseTemplate
            ->singleEliminationRounds
            ->flatMap
            ->encounters;

        return $phaseTemplate
            ->inputGates
            ->where($field, $value)
            ->count()

            +

            $phaseTemplate
            ->singleEliminationRounds
            ->where($field, $value)
            ->count()

            +

            $encounters
            ->where($field, $value)
            ->count()

            +

            $encounters
            ->flatMap
            ->slots
            ->where($field, $value)
            ->count()

            +

            $encounters
            ->flatMap
            ->results
            ->where($field, $value)
            ->count()

            +

            $phaseTemplate
            ->singleEliminationConnections
            ->where($field, $value)
            ->count();
    }

    private function highestLevel(
        array $levels
    ): string {
        $ranking = [
            'NONE' => 0,
            'RECOMMENDATION' => 1,
            'WARNING' => 2,
            'ERROR' => 3,
        ];

        return collect($levels)
            ->filter()
            ->sortByDesc(
                fn(string $level) =>
                $ranking[$level]
                    ??
                    0
            )
            ->first()
            ??
            'NONE';
    }

    private function key(
        string $type,
        ?int $id
    ): string {
        return $type
            .
            ':'
            .
            ($id ?? 'NULL');
    }
}
