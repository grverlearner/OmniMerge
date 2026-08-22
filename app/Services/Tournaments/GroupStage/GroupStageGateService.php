<?php

namespace App\Services\Tournaments\GroupStage;

use App\Models\PhaseInputGate;
use App\Models\PhaseTemplate;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| GroupStageGateService
|--------------------------------------------------------------------------
|
| Puertas de entrada de una fase de grupos.
|
| No reutiliza PhaseInputGateService a propósito: aquél sincroniza slots
| del cuadro interno, marca la estructura como desactualizada y refresca
| los puertos de entrada de Single Elimination. Nada de eso existe en una
| fase de grupos, donde una puerta no apunta a un slot sino, como mucho,
| a un grupo.
|
| Arrastrar esa maquinaria hasta aquí habría acoplado dos motores que no
| comparten estructura interna.
|
| Ver docs/md/31-Fase-14-Group-Stage.md
|
*/

class GroupStageGateService
{
    public function create(
        PhaseTemplate $phaseTemplate,
        array $data
    ): PhaseInputGate {

        return DB::transaction(
            function () use ($phaseTemplate, $data) {

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
            }
        );
    }

    public function update(
        PhaseInputGate $gate,
        array $data
    ): PhaseInputGate {

        /*
         * El grupo destino vive en `settings` y no viaja en $data, así que
         * se conserva lo que ya hubiera: editar el nombre de una puerta no
         * debe descolgarla de su grupo.
         */
        $settings = $gate->settings ?? [];

        if (array_key_exists('target_group_code', $data)) {

            if (($data['target_group_code'] ?? null) === null) {
                unset($settings['target_group_code']);
            } else {
                $settings['target_group_code'] = $data['target_group_code'];
            }
        }

        $gate->forceFill([
            ...$this->payload($data),
            'settings' => $settings ?: null,
        ])->save();

        return $gate;
    }

    public function delete(PhaseInputGate $gate): void
    {
        $gate->delete();
    }

    /**
     * Campos que una puerta de fase de grupos entiende.
     */
    private function payload(array $data): array
    {
        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,

            'input_type' => $data['input_type'] ?? 'STANDARD',
            'merge_policy' => $data['merge_policy'] ?? 'APPEND',

            /*
             * Cómo se reparte lo que entra por esta puerta. Si la puerta
             * apunta a un grupo concreto, este modo deja de importar.
             */
            'distribution_mode' => $data['distribution_mode'] ?? 'SEQUENTIAL',

            'empty_behavior' => $data['empty_behavior'] ?? 'ALLOW',

            'min_participants' => $data['min_participants'] ?? null,
            'max_participants' => $data['max_participants'] ?? null,
            'exact_participants' => $data['exact_participants'] ?? null,

            'is_required' => (bool) ($data['is_required'] ?? false),
            'accepts_batch' => (bool) ($data['accepts_batch'] ?? true),
            'accepts_multiple_connections' => (bool) ($data['accepts_multiple_connections'] ?? true),

            'priority' => (int) ($data['priority'] ?? 10),
            'status' => $data['status'] ?? 'ACTIVE',
            'is_locked' => false,
        ];
    }
}
