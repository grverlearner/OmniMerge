<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\GroupStage\GroupStageAllocator;
use Closure;
use Illuminate\Validation\ValidationException;

final class LabManualDecisionManager
{
    public function __construct(
        private readonly GroupStageAllocator $groupStageAllocator
    ) {}

    /*
    |--------------------------------------------------------------------------
    | El reparto que ya trazó el grafo
    |--------------------------------------------------------------------------
    |
    | Una fase de grupos «manual» pregunta quién va a cada grupo. Tiene
    | sentido cuando los participantes llegan en montón: alguien tiene que
    | decidir.
    |
    | No lo tiene cuando el recorrido YA lo decidió. Si la fase tiene cuatro
    | puertas de entrada y cuatro grupos, y el grafo mandó tres competidores
    | por cada puerta, el reparto está hecho: lo dibujó el usuario al conectar
    | las fases anteriores. Volver a preguntárselo es pedirle dos veces lo
    | mismo —y la segunda vez sin las flechas delante—.
    |
    | La correspondencia es por ORDEN: la primera puerta llena el primer
    | grupo, la segunda el segundo. Es lo que se ve al dibujarlo, y no hay
    | ninguna otra pista en el modelo: una puerta no sabe a qué grupo va.
    |
    | Solo se aplica cuando encaja EXACTO —tantas puertas con gente como
    | grupos, y en cada puerta justo los que caben en su grupo—. En cuanto
    | algo no cuadra se vuelve a preguntar, porque colocar a alguien en un
    | grupo por aproximación es peor que preguntar.
    |
    | @param  array<int,array<int,string>>  $byPort  en el orden de las puertas
    | @return array{order: array<int,string>, map: array<int,array<string,mixed>>}|null
    */
    public function orderingFromPorts(
        PhaseTemplate $phase,
        array $participantIds,
        array $byPort
    ): ?array {

        if ($phase->phase_type !== 'GROUP_STAGE' || $byPort === []) {
            return null;
        }

        $phase->loadMissing(['groupStageSetting', 'groupStageGroups']);

        $settings = $phase->groupStageSetting;

        if (! $settings || $settings->distribution_mode !== 'MANUAL') {
            return null;
        }

        /* Las puertas por las que de verdad llegó alguien */
        $llenas = array_values(array_filter(
            array_map(
                fn ($ids) => array_values(array_unique(array_filter((array) $ids))),
                $byPort
            ),
            fn ($ids) => $ids !== []
        ));

        $allocation = $this->groupStageAllocator->allocate(
            $phase,
            $settings,
            $phase->groupStageGroups,
            count($participantIds)
        );

        if (! ($allocation['valid'] ?? false)) {
            return null;
        }

        $grupos = array_values($allocation['groups']);

        if (count($llenas) !== count($grupos)) {
            return null;
        }

        $orden = [];
        $mapa = [];

        foreach ($grupos as $indice => $grupo) {

            $delPuerto = $llenas[$indice];

            if (count($delPuerto) !== (int) $grupo['size']) {
                return null;
            }

            foreach ($delPuerto as $participantId) {

                if (! in_array($participantId, $participantIds, true)) {
                    return null;
                }

                $orden[] = $participantId;
            }

            $mapa[] = [
                'group_name' => $grupo['name'],
                'port_index' => $indice,
                'participant_ids' => $delPuerto,
            ];
        }

        /* Y que no falte ni sobre nadie */
        if (count($orden) !== count($participantIds)) {
            return null;
        }

        return ['order' => $orden, 'map' => $mapa];
    }

    public function preparationDecision(
        PhaseTemplate $phase,
        array $participantIds
    ): ?array {
        $participantIds = array_values($participantIds);

        if (
            $phase->phase_type === 'SINGLE_ELIMINATION'
            && count($participantIds) !== count(array_unique($participantIds))
        ) {
            $this->fail(
                'La entrada de Single Elimination contiene participantes duplicados.'
            );
        }

        $participantIds = array_values(array_unique($participantIds));

        return match ($phase->phase_type) {
            'GROUP_STAGE' => $this->groupStageDecision($phase, $participantIds),
            'ROUND_ROBIN' => $this->roundRobinDecision($phase, $participantIds),
            'SINGLE_ELIMINATION' => $this->singleEliminationDecision($phase, $participantIds),
            default => null,
        };
    }

    public function resolvePreparation(
        PhaseTemplate $phase,
        array $runtime,
        array $participants,
        array $payload,
        Closure $prepareEngine
    ): array {
        $decision = $runtime['manual_decision'] ?? null;

        if (! is_array($decision) || ($decision['scope'] ?? null) !== 'PREPARATION') {
            $this->fail('La fase no tiene una decisión de preparación pendiente.');
        }

        if (($payload['decision_id'] ?? null) !== ($decision['id'] ?? null)) {
            $this->fail('La decisión enviada ya no corresponde al estado actual del Lab.');
        }

        $participantIds = array_values($decision['eligible_participant_ids'] ?? []);
        $preparedParticipants = $participants;

        switch ($decision['type'] ?? '') {
            case 'GROUP_ASSIGNMENT':
                $participantIds = $this->resolveGroupAssignment(
                    $decision,
                    $participantIds,
                    $payload['group_assignments'] ?? []
                );
                break;

            case 'PARTICIPANT_ORDER':
                $participantIds = $this->validatePermutation(
                    $participantIds,
                    $payload['ordered_participant_ids'] ?? []
                );
                break;

            case 'SINGLE_ELIMINATION_SETUP':
                if ((bool) data_get($decision, 'constraints.requires_order', false)) {
                    $participantIds = $this->validatePermutation(
                        $participantIds,
                        $payload['ordered_participant_ids'] ?? []
                    );
                }

                $byeCount = (int) data_get($decision, 'constraints.bye_count', 0);
                $byeIds = array_values($payload['selected_participant_ids'] ?? []);

                if (count($byeIds) !== count(array_unique($byeIds))) {
                    $this->fail('La selección manual de BYEs no puede repetir participantes.');
                }

                if (count($byeIds) !== $byeCount) {
                    $this->fail("Debes seleccionar exactamente {$byeCount} participante(s) para BYE.");
                }

                foreach ($byeIds as $participantId) {
                    if (! in_array($participantId, $participantIds, true)) {
                        $this->fail('Uno de los BYE seleccionados no pertenece a esta fase.');
                    }

                    $preparedParticipants[$participantId]['manual_bye'] = true;
                }
                break;

            default:
                $this->fail('El tipo de decisión manual no está soportado.');
        }

        return $prepareEngine(
            $participantIds,
            $preparedParticipants
        );
    }

    private function groupStageDecision(
        PhaseTemplate $phase,
        array $participantIds
    ): ?array {
        $phase->loadMissing([
            'groupStageSetting',
            'groupStageGroups',
        ]);

        $settings = $phase->groupStageSetting;
        if (! $settings || $settings->distribution_mode !== 'MANUAL') {
            return null;
        }

        $allocation = $this->groupStageAllocator->allocate(
            $phase,
            $settings,
            $phase->groupStageGroups,
            count($participantIds)
        );

        if (! ($allocation['valid'] ?? false)) {
            $this->fail(implode(' ', $allocation['errors'] ?? [
                'No fue posible preparar los grupos manuales.',
            ]));
        }

        $groups = [];
        foreach ($allocation['groups'] as $index => $group) {
            $groups[] = [
                'key' => 'GROUP_' . ($index + 1),
                'name' => $group['name'],
                'code' => $group['code'],
                'size' => (int) $group['size'],
            ];
        }

        return $this->pendingRuntime(
            'GROUP_STAGE',
            $participantIds,
            [
                'id' => $this->decisionId($phase, 'GROUP_ASSIGNMENT', $participantIds),
                'scope' => 'PREPARATION',
                'type' => 'GROUP_ASSIGNMENT',
                'title' => 'Asignación manual de grupos',
                'description' => 'Asigna cada participante a un grupo respetando exactamente las capacidades configuradas.',
                'eligible_participant_ids' => $participantIds,
                'groups' => $groups,
                'constraints' => [
                    'all_participants_required' => true,
                    'unique_assignment' => true,
                ],
            ]
        );
    }

    private function roundRobinDecision(
        PhaseTemplate $phase,
        array $participantIds
    ): ?array {
        $phase->loadMissing('roundRobinSetting');

        if ($phase->roundRobinSetting?->initial_order_mode !== 'MANUAL') {
            return null;
        }

        return $this->pendingRuntime(
            'ROUND_ROBIN',
            $participantIds,
            [
                'id' => $this->decisionId($phase, 'PARTICIPANT_ORDER', $participantIds),
                'scope' => 'PREPARATION',
                'type' => 'PARTICIPANT_ORDER',
                'title' => 'Orden manual del Round Robin',
                'description' => 'Ordena a todos los participantes. Esta secuencia alimentará el Circle Method del calendario.',
                'eligible_participant_ids' => $participantIds,
                'constraints' => [
                    'all_participants_required' => true,
                ],
            ]
        );
    }

    private function singleEliminationDecision(
        PhaseTemplate $phase,
        array $participantIds
    ): ?array {
        $phase->loadMissing('singleEliminationSetting');
        $settings = $phase->singleEliminationSetting;

        if (! $settings || $settings->configuration_mode === 'ADVANCED') {
            return null;
        }

        $manualOrder = $settings->seeding_mode === 'MANUAL';
        $byeCount = $phase->allow_byes
            ? max(0, $this->nextPowerOfTwo(count($participantIds)) - count($participantIds))
            : 0;
        $manualBye = $settings->bye_assignment === 'MANUAL' && $byeCount > 0;

        if (! $manualOrder && ! $manualBye) {
            return null;
        }

        return $this->pendingRuntime(
            'SINGLE_ELIMINATION',
            $participantIds,
            [
                'id' => $this->decisionId($phase, 'SINGLE_ELIMINATION_SETUP', $participantIds),
                'scope' => 'PREPARATION',
                'type' => 'SINGLE_ELIMINATION_SETUP',
                'title' => 'Seed y BYE manual',
                'description' => 'Define el orden de seeds y, cuando corresponda, quiénes ocupan las posiciones con BYE.',
                'eligible_participant_ids' => $participantIds,
                'required_selection_count' => $manualBye ? $byeCount : 0,
                'constraints' => [
                    'requires_order' => $manualOrder,
                    'bye_count' => $manualBye ? $byeCount : 0,
                    'all_participants_required' => $manualOrder,
                ],
            ]
        );
    }

    private function resolveGroupAssignment(
        array $decision,
        array $participantIds,
        array $assignments
    ): array {
        $allowedGroups = collect($decision['groups'] ?? [])
            ->keyBy('key');

        if ($allowedGroups->isEmpty()) {
            $this->fail('La decisión no contiene grupos disponibles.');
        }

        $buckets = [];
        foreach ($allowedGroups as $key => $group) {
            $buckets[$key] = [];
        }

        foreach ($participantIds as $participantId) {
            $groupKey = $assignments[$participantId] ?? null;
            if (! $groupKey || ! $allowedGroups->has($groupKey)) {
                $this->fail('Todos los participantes deben tener un grupo válido.');
            }

            $buckets[$groupKey][] = $participantId;
        }

        foreach ($allowedGroups as $key => $group) {
            $expected = (int) $group['size'];
            if (count($buckets[$key]) !== $expected) {
                $this->fail("{$group['name']} necesita exactamente {$expected} participante(s).");
            }
        }

        return array_values(array_merge(...array_values($buckets)));
    }

    private function validatePermutation(
        array $eligible,
        array $ordered
    ): array {
        $ordered = array_values($ordered);

        if (count($ordered) !== count(array_unique($ordered))) {
            $this->fail('El orden manual no puede repetir participantes.');
        }

        if (count($ordered) !== count($eligible)) {
            $this->fail('El orden manual debe contener a todos los participantes una sola vez.');
        }

        $expected = $eligible;
        $received = $ordered;
        sort($expected);
        sort($received);

        if ($expected !== $received) {
            $this->fail('El orden manual contiene participantes faltantes o ajenos a la fase.');
        }

        return $ordered;
    }

    private function pendingRuntime(
        string $engine,
        array $participantIds,
        array $decision
    ): array {
        return [
            'engine' => $engine,
            'status' => 'AWAITING_DECISION',
            'manual_decision' => $decision,
            'participant_ids' => $participantIds,
            'rounds' => [],
            'standings' => [],
            'outcomes' => [],
            'survivor_ids' => [],
            'eliminated_ids' => [],
            'matches_total' => 0,
            'matches_completed' => 0,
            'current_round' => 0,
        ];
    }

    private function decisionId(
        PhaseTemplate $phase,
        string $type,
        array $participantIds
    ): string {
        return 'DEC-' . substr(hash(
            'sha256',
            $phase->id . ':' . $type . ':' . implode('|', $participantIds)
        ), 0, 20);
    }

    private function nextPowerOfTwo(int $value): int
    {
        $power = 1;
        while ($power < max(1, $value)) {
            $power *= 2;
        }

        return $power;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'manual_decision' => [$message],
        ]);
    }
}
