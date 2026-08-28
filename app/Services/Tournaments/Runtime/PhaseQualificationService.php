<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentInstance;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| PhaseQualificationService
|--------------------------------------------------------------------------
|
| Cuántos pasan de cada fase, y por qué puerta.
|
| Para qué
| --------
| Mientras una fase se juega, la clasificación no distingue a quien está
| pasando de quien está quedando fuera: el estado ADVANCED / ELIMINATED
| solo se escribe cuando la fase termina y el grafo reparte. Hasta
| entonces, una liga a mitad es una lista de nombres sin tensión.
|
| Esto lee las PUERTAS DE SALIDA congeladas en el snapshot y deduce dónde
| está el corte. Es una previsión, no un resultado: se dibuja como línea
| de corte, y en cuanto la fase resuelve mandan los estados reales.
|
| Solo se deduce cuando la regla es inequívoca:
|
|   TOP_N        los N primeros            -> corte en N
|   RANK_RANGE   del puesto X al Y         -> corte en Y
|
| Un ENGINE_RULES de fase de grupos no dice cuántos pasan en su puerta: lo
| dicen sus REGLAS DE AVANCE, y ahí el corte es POR GRUPO, no por fase. Por
| eso se devuelve aparte como group_cut: en una fase de grupos la tabla que
| se dibuja es la de cada grupo, y la línea va dentro de cada una.
|
|   EACH_GROUP_TOP_N       los N mejores de cada grupo -> corte por grupo
|   EACH_GROUP_POSITION    del 1 al Y de cada grupo    -> corte por grupo
|
| Una regla que empieza a mitad de tabla (del 3º al 4º, repesca de terceros)
| no marca corte: ahí no hay una frontera única que dibujar.
|
*/

class PhaseQualificationService
{
    public function __construct(
        private readonly TournamentSnapshotHydrator $hydrator
    ) {}

    /**
     * Corte por nodo del grafo.
     *
     * @return Collection<string, array{cut: ?int, group_cut: ?int, label: ?string, rule: string}>
     */
    public function forInstance(TournamentInstance $instance): Collection
    {
        $snapshot = $instance->snapshot?->snapshot;

        if (! $snapshot) {
            return collect();
        }

        try {
            $template = $this->hydrator->hydrate($snapshot, $instance);
        } catch (\Throwable) {
            /*
             * Un snapshot que no hidrata no debe tumbar la pantalla: sin
             * corte la tabla sigue siendo correcta, solo menos expresiva.
             */
            return collect();
        }

        return collect($template->graphNodes)
            ->mapWithKeys(
                function ($node) {

                    $exits = $node->phaseTemplate?->exits ?? collect();

                    /*
                     * La puerta que hace avanzar. Se ignoran las de
                     * descarte: "Eliminados" con REMAINING no marca corte,
                     * lo recoge todo lo que sobra.
                     */
                    $qualifying = $exits
                        ->filter(
                            fn($exit) => in_array(
                                $exit->selector_type,
                                ['TOP_N', 'RANK_RANGE'],
                                true
                            )
                        )
                        ->sortBy('priority')
                        ->first();

                    $groupCut = $this->groupCut($node->phaseTemplate);

                    if (! $qualifying) {

                        return [(string) $node->id => [
                            'cut' => null,
                            'group_cut' => $groupCut,
                            'label' => null,
                            'rule' => $exits->first()?->selector_type ?? 'NONE',
                        ]];
                    }

                    $cut = $qualifying->selector_type === 'RANK_RANGE'
                        ? (int) ($qualifying->selector_to ?? $qualifying->selector_from ?? 0)
                        : (int) ($qualifying->selector_from ?? 0);

                    return [(string) $node->id => [
                        'cut' => $cut > 0 ? $cut : null,
                        'group_cut' => $groupCut,
                        'label' => $qualifying->name,
                        'rule' => $qualifying->selector_type,
                    ]];
                }
            );
    }

    /**
     * Cuántos pasan de CADA grupo, si la fase es de grupos y su regla de
     * avance lo dice sin ambigüedad.
     */
    private function groupCut(mixed $phaseTemplate): ?int
    {
        if (($phaseTemplate?->phase_type ?? null) !== 'GROUP_STAGE') {
            return null;
        }

        $rules = $phaseTemplate->groupStageAdvancementRules ?? collect();

        foreach (collect($rules)->sortBy('sort_order') as $rule) {

            if (($rule->status ?? 'ACTIVE') !== 'ACTIVE') {
                continue;
            }

            if ($rule->rule_type === 'EACH_GROUP_TOP_N') {

                $take = (int) ($rule->take ?? $rule->position_to ?? 0);

                return $take > 0 ? $take : null;
            }

            /*
             * Solo si arranca en el primer puesto: "del 3º al 4º" es una
             * repesca, no una frontera.
             */
            if ($rule->rule_type === 'EACH_GROUP_POSITION') {

                $from = (int) ($rule->position_from ?? 0);
                $to = (int) ($rule->position_to ?? $from);

                return $from === 1 && $to > 0 ? $to : null;
            }
        }

        return null;
    }
}
