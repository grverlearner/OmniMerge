<?php

namespace App\Services\Tournaments\RoundRobin;

use App\Models\PhaseInputGate;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| RoundRobinGateService
|--------------------------------------------------------------------------
|
| Puertas de entrada de una liga.
|
| No reutiliza PhaseInputGateService a propósito, por el mismo motivo que
| tampoco lo hace Fase de grupos: aquél sincroniza slots del cuadro interno
| de Eliminación Directa y marca su estructura como desactualizada. Una liga
| no tiene cuadro que desactualizar.
|
| Lo propio de aquí es la regla de parrilla —qué puestos iniciales reclama
| la puerta—, que vive en `settings` porque es vocabulario de un solo motor.
|
| La capacidad se DERIVA de la regla en vez de pedirse aparte: una puerta
| que reclama los puestos 1 a 4 admite exactamente 4, y dejar que alguien
| escriba otra cantidad solo permitiría guardar una contradicción.
|
*/
class RoundRobinGateService
{
    public function __construct(
        private readonly RoundRobinSeedRuleResolver $seedRules
    ) {}

    public function create(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseInputGate {

        return DB::transaction(function () use ($phaseTemplate, $data) {

            $locked = PhaseTemplate::query()
                ->whereKey($phaseTemplate->id)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence =
                ((int) $locked->inputGates()->max('sequence_number')) + 1;

            $sortOrder =
                ((int) $locked->inputGates()->max('sort_order')) + 10;

            return $locked->inputGates()->create([
                ...$this->payload($data),

                'sequence_number' => $sequence,
                'code' => PhaseInputGate::formatCode($sequence),
                'sort_order' => $sortOrder,
                'generation_source' => 'MANUAL',
            ]);
        });
    }

    public function update(
        PhaseInputGate $gate,
        array $data
    ): PhaseInputGate {

        $gate->forceFill(
            $this->payload($data)
        )->save();

        return $gate->fresh();
    }

    public function delete(PhaseInputGate $gate): void
    {
        $gate->delete();
    }

    /*
     * Lo que una puerta de liga entiende.
     *
     * El resto de campos del modelo —política de mezcla, comportamiento en
     * vacío, si acepta lotes— existen para el grafo y no los decide esta
     * pantalla, así que se dejan en sus valores neutros en vez de inventar
     * controles que nadie va a tocar.
     */
    private function payload(array $data): array
    {
        $rule = [
            'type' => $data['seed_type'],
        ];

        foreach (['count', 'from', 'to'] as $field) {
            if (isset($data['seed_' . $field]) && $data['seed_' . $field] !== null) {
                $rule[$field] = (int) $data['seed_' . $field];
            }
        }

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,

            'input_type' => 'POOL',
            'merge_policy' => 'APPEND',

            /*
             * El reparto lo dicta la regla de parrilla, no un modo aparte.
             */
            'distribution_mode' => 'INPUT_ORDER',
            'empty_behavior' => 'ALLOW_EMPTY',

            ...$this->capacityFor($rule),

            'is_required' => (bool) ($data['is_required'] ?? false),
            'accepts_batch' => true,
            'accepts_multiple_connections' => true,

            'priority' => (int) ($data['priority'] ?? 10),
            'status' => $data['status'] ?? 'ACTIVE',

            'settings' => ['seed_rule' => $rule],
        ];
    }

    /*
     * Cuánta gente admite la puerta, deducido de los puestos que reclama.
     */
    private function capacityFor(array $rule): array
    {
        $exact = match ($rule['type']) {
            'FIRST_N', 'LAST_N' => (int) ($rule['count'] ?? 0),
            'POSITION' => 1,
            'RANGE' => max(0, (int) ($rule['to'] ?? 0) - (int) ($rule['from'] ?? 0) + 1),
            default => null,
        };

        if ($exact === null || $exact < 1) {
            return [
                'min_participants' => null,
                'max_participants' => null,
                'exact_participants' => null,
            ];
        }

        return [
            'min_participants' => $exact,
            'max_participants' => $exact,
            'exact_participants' => $exact,
        ];
    }
}
