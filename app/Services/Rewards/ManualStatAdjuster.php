<?php

namespace App\Services\Rewards;

use App\Models\TournamentInstance;
use App\Models\UniverseEntity;
use App\Models\UniverseStatChange;
use App\Services\Games\GameRegistry;
use App\Services\Games\GameStatsService;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| ManualStatAdjuster
|--------------------------------------------------------------------------
|
| Cambiar a mano la estadística de un competidor.
|
| Por qué existe
| --------------
| Porque a veces hace falta y no hay regla que lo cubra: corregir un valor
| mal capturado, compensar algo que pasó fuera del sistema, probar un
| ajuste. Sin esto, la única salida sería editar la base de datos.
|
| Por qué es incómodo a propósito
| -------------------------------
| Un ajuste manual salta por encima de todo lo que normalmente EXPLICA un
| cambio: el torneo que lo produjo, la recompensa que lo justificaba, la
| posición que se ganó. Un valor que aparece sin motivo es un valor que
| dentro de seis meses nadie sabe defender.
|
| Así que se hace lo único que lo vuelve honesto: escribir en el mismo
| sitio que todo lo demás —universe_stat_changes— con source_type MANUAL.
| El cambio se ve, se puede auditar, y se distingue de lo que alguien ganó
| jugando. La confirmación explícita la exige el controlador; esto se
| encarga de que quede rastro.
|
| Lo que NO hace
| --------------
| No se deshace solo. No es un bonus temporal: para eso están los
| modificadores, que viven en el estado de la competición y mueren con
| ella. Esto toca el valor guardado.
|
*/

class ManualStatAdjuster
{
    public function __construct(
        private readonly GameRegistry $registry,
        private readonly GameStatsService $stats
    ) {}

    /**
     * @return array{stat_key: string, before: string, after: string}|null
     *         null si la stat no existe en el juego de la competición
     */
    public function apply(
        TournamentInstance $instance,
        int $universeEntityId,
        string $statKey,
        string $operation,
        float $amount,
        ?string $reason = null
    ): ?array {

        $gameKey = $instance->game_key;

        if (! $gameKey) {
            return null;
        }

        $definition = $this->registry->definition($gameKey);

        /*
         * La stat la declara el Game Engine. Aquí nunca se pregunta "¿es
         * Highest Number?": se pregunta "¿existe esta stat en este juego?".
         */
        $exists =
            collect($definition['stats'] ?? [])
            ->contains(fn(array $schema) => ($schema['key'] ?? null) === $statKey);

        if (! $exists) {
            return null;
        }

        $entity =
            UniverseEntity::query()
            ->where('universe_id', $instance->universe_id)
            ->find($universeEntityId);

        if (! $entity) {
            return null;
        }

        return DB::transaction(
            function () use (
                $instance,
                $entity,
                $definition,
                $statKey,
                $operation,
                $amount,
                $reason
            ) {

                $record =
                    $this->stats->ensure($entity, $definition['key']);

                $current = $record->normalized_stats;

                $before = (float) ($current[$statKey] ?? 0);

                $current[$statKey] =
                    match ($operation) {
                        'SUBTRACT' => $before - $amount,
                        'MULTIPLY' => $before * $amount,
                        'SET' => $amount,
                        default => $before + $amount,
                    };

                /*
                 * El engine normaliza: un rango invertido se endereza y un
                 * valor fuera de límites se recorta. Por eso el "después"
                 * se lee de lo guardado y no de lo calculado — puede no
                 * ser lo que se pidió, y eso es lo que hay que registrar.
                 */
                $saved =
                    $this->stats->update(
                        $entity,
                        $definition['key'],
                        $current
                    );

                $after = (float) ($saved->normalized_stats[$statKey] ?? $before);

                UniverseStatChange::query()->create([

                    'universe_id' => $instance->universe_id,
                    'universe_entity_id' => $entity->id,
                    'universe_season_id' => $instance->universe_season_id,
                    'tournament_instance_id' => $instance->id,
                    'universe_tournament_reward_id' => null,

                    'source_type' => 'MANUAL',

                    'game_key' => $definition['key'],
                    'stat_key' => $statKey,

                    'value_before' => $before,
                    'value_after' => $after,
                    'delta' => round($after - $before, 4),

                    'reason' =>
                    $reason ?: 'Ajuste manual',
                ]);

                return [
                    'stat_key' => $statKey,
                    'before' => $this->trim($before),
                    'after' => $this->trim($after),
                ];
            }
        );
    }

    private function trim(float $value): string
    {
        return rtrim(
            rtrim(
                number_format($value, 3, '.', ''),
                '0'
            ),
            '.'
        ) ?: '0';
    }
}
