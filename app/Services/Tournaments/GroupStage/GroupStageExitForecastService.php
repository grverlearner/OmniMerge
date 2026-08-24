<?php

namespace App\Services\Tournaments\GroupStage;

use App\Models\PhaseTemplate;

/*
|--------------------------------------------------------------------------
| GroupStageExitForecastService
|--------------------------------------------------------------------------
|
| Cuánta gente sale de verdad por cada puerta de una Fase de grupos.
|
| Existe porque la fase responde dos veces a la misma pregunta y solo una
| de las dos manda:
|
|   - El SELECTOR de la puerta ("Top 8") es lo que leen el validador de
|     flujo y las pantallas del grafo.
|
|   - Las REGLAS de clasificación ("los 2 primeros de cada grupo") son lo
|     que ejecuta el motor cuando la fase termina.
|
| Cuando discrepan, el grafo se valida con un número y el torneo se juega
| con otro. El daño no aparece al configurar: aparece cuando ya se jugó
| todo, la puerta de la siguiente fase recibe una cantidad que nunca
| aceptó, y la competición se queda bloqueada con la fase entera gastada.
|
| Así que el pronóstico se toma de donde está la verdad —las reglas— y se
| reutiliza el mismo cálculo que ya alimenta la vista previa, sin abrir una
| segunda forma de contar.
|
*/
class GroupStageExitForecastService
{
    public function __construct(
        private readonly
        GroupStageAllocator $allocator,

        private readonly
        GroupStageAdvancementCalculator $advancementCalculator
    ) {}

    /*
     * Participantes con los que tiene sentido pronosticar una fase cuando
     * nadie dice cuántos van a entrar. El mismo criterio que usa la vista
     * previa de la estructura, para que las dos pantallas no discrepen.
     */
    public function referenceParticipants(
        PhaseTemplate $phaseTemplate
    ): int {
        return (int) (
            $phaseTemplate->exact_participants
            ?? $phaseTemplate->min_participants
            ?? 8
        );
    }

    /*
     * Tamaño de cada grupo con esa cantidad de participantes.
     *
     * Devuelve [] cuando el reparto todavía no es válido: en ese caso ya
     * hay otro error configurado que se está avisando por su cuenta, y
     * añadir un segundo aviso derivado solo haría ruido.
     *
     * @return array<int,int>
     */
    public function groupSizes(
        PhaseTemplate $phaseTemplate,
        int $participants
    ): array {
        $allocation =
            $this->allocate(
                $phaseTemplate,
                $participants
            );

        if ($allocation === null) {
            return [];
        }

        return array_values(
            array_map(
                fn(array $group) => (int) ($group['size'] ?? 0),
                $allocation
            )
        );
    }

    /*
     * Lo que cada puerta va a emitir realmente.
     *
     * @return array{
     *     participants: int,
     *     group_sizes: array<int,int>,
     *     by_exit: array<int,int>,
     *     by_rule: array<int,int>,
     *     unselected: int
     * }|null
     */
    public function forecast(
        PhaseTemplate $phaseTemplate,
        ?int $participants = null
    ): ?array {
        $participants ??=
            $this->referenceParticipants(
                $phaseTemplate
            );

        $groups =
            $this->allocate(
                $phaseTemplate,
                $participants
            );

        if ($groups === null) {
            return null;
        }

        $settings =
            $phaseTemplate->groupStageSetting;

        $forecast =
            $this
            ->advancementCalculator
            ->forecast(
                $groups,
                $phaseTemplate
                    ->groupStageAdvancementRules()
                    ->with('phaseExit')
                    ->orderBy('sort_order')
                    ->get(),
                (string) (
                    $settings->cutoff_tie_policy
                    ?? 'USE_TIEBREAKERS'
                )
            );

        $byExit = [];

        foreach ($forecast['outputs'] as $output) {

            if (($output['exit_id'] ?? null) === null) {
                continue;
            }

            $byExit[(int) $output['exit_id']] =
                (int) $output['expected_count'];
        }

        $byRule = [];

        foreach ($forecast['rules'] as $rule) {
            $byRule[(int) $rule['id']] =
                (int) $rule['expected_count'];
        }

        return [
            'participants' =>
            $participants,

            'group_sizes' =>
            array_values(
                array_map(
                    fn(array $group) => (int) ($group['size'] ?? 0),
                    $groups
                )
            ),

            'by_exit' =>
            $byExit,

            'by_rule' =>
            $byRule,

            'unselected' =>
            (int) $forecast['unselected_count'],
        ];
    }

    /*
     * El reparto en grupos, o null si la configuración todavía no permite
     * calcularlo.
     */
    private function allocate(
        PhaseTemplate $phaseTemplate,
        int $participants
    ): ?array {

        if (
            $phaseTemplate->phase_type
            !==
            'GROUP_STAGE'
            ||
            $participants < 1
        ) {
            return null;
        }

        $settings =
            $phaseTemplate->groupStageSetting;

        if ($settings === null) {
            return null;
        }

        $allocation =
            $this
            ->allocator
            ->allocate(
                $phaseTemplate,
                $settings,
                $phaseTemplate->groupStageGroups,
                $participants
            );

        if (! ($allocation['valid'] ?? false)) {
            return null;
        }

        return $allocation['groups'] ?? null;
    }
}
