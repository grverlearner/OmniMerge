<?php

namespace App\Services\Rewards;

use App\Models\GameEncounterParticipant;
use App\Models\TournamentInstance;
use App\Models\TournamentInstanceParticipant;
use App\Models\UniverseEntity;
use App\Models\UniverseStatChange;
use App\Models\UniverseTournamentReward;
use App\Models\UniverseTrophyAward;
use App\Services\Games\GameRegistry;
use App\Services\Games\GameStatsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| RewardProcessor
|--------------------------------------------------------------------------
|
| Aplica las consecuencias permanentes de una competición terminada.
|
| Idempotencia
| ------------
| No se apoya en una bandera, sino en una clave única de base de datos:
| (competición, competidor, juego, stat, regla). Procesar dos veces no
| puede duplicar nada aunque el intento anterior se interrumpiera a la
| mitad, y por eso mismo se puede reprocesar sin miedo cuando cambia una
| regla. La marca `rewards_processed_at` solo evita trabajo inútil.
|
| Independencia del juego
| -----------------------
| Aquí nunca se pregunta si el juego es Highest Number. Se pregunta si la
| stat que la regla quiere tocar existe en el esquema que declara el Game
| Engine. Una regla que apunta a una stat inexistente se ignora en vez de
| romper la competición.
|
| Ver docs/md/30-Fase-12-Recompensas-Y-Palmares.md
|
*/

class RewardProcessor
{
    public function __construct(
        private readonly GameRegistry $registry,
        private readonly GameStatsService $stats,
        private readonly TournamentPlacementResolver $placements
    ) {}

    /**
     * Procesa una competición terminada.
     *
     * @return array{applied: int, trophies: int, skipped: int}
     */
    public function process(
        TournamentInstance $instance,
        bool $force = false
    ): array {

        $summary = [
            'applied' => 0,
            'trophies' => 0,
            'skipped' => 0,
        ];

        if ($instance->status !== 'COMPLETED') {
            return $summary;
        }

        if (
            $instance->rewards_processed_at !== null
            && ! $force
        ) {
            return $summary;
        }

        $tournament =
            $instance->universeTournament;

        if (! $tournament) {
            return $summary;
        }

        $rules =
            $tournament->rewards()
            ->where('is_active', true)
            ->with('trophy')
            ->get();

        /* Las posiciones se calculan igualmente: sirven al palmarés */
        $ordered =
            $this->placements->resolve($instance);

        if ($rules->isEmpty()) {

            $instance->forceFill([
                'rewards_processed_at' => now(),
            ])->save();

            return $summary;
        }

        $gameKey =
            $instance->game_key
            ?: GameRegistry::DEFAULT_KEY;

        $encounterWins =
            $this->encounterWins($instance);

        DB::transaction(
            function () use (
                $instance,
                $rules,
                $ordered,
                $gameKey,
                $encounterWins,
                &$summary
            ) {

                foreach ($rules as $rule) {

                    $qualifiers =
                        $this->qualifiers(
                            $rule,
                            $ordered,
                            $encounterWins
                        );

                    foreach ($qualifiers as $participant) {

                        $entity =
                            $participant->universeEntity;

                        if (! $entity) {
                            $summary['skipped']++;
                            continue;
                        }

                        if (
                            $this->applyStat(
                                $instance,
                                $rule,
                                $entity,
                                $gameKey,
                                $participant
                            )
                        ) {
                            $summary['applied']++;
                        }

                        if (
                            $this->awardTrophy(
                                $instance,
                                $rule,
                                $entity,
                                $participant
                            )
                        ) {
                            $summary['trophies']++;
                        }
                    }
                }

                $instance->forceFill([
                    'rewards_processed_at' => now(),
                ])->save();
            }
        );

        return $summary;
    }

    /*
    |--------------------------------------------------------------------------
    | Quién se lleva qué
    |--------------------------------------------------------------------------
    */

    /**
     * @param  Collection<int, TournamentInstanceParticipant> $ordered
     * @return Collection<int, TournamentInstanceParticipant>
     */
    private function qualifiers(
        UniverseTournamentReward $rule,
        Collection $ordered,
        array $encounterWins
    ): Collection {

        return match ($rule->trigger) {

            'POSITION' =>
            $ordered->filter(
                fn($participant) =>
                (int) $participant->placement === (int) $rule->threshold
            ),

            /*
             * Participar es haber entrado en la competición, no haber
             * jugado: en un cuadro con BYEs se puede llegar lejos sin
             * disputar una sola batalla, y eso sigue siendo participar.
             * Solo queda fuera quien nunca llegó a entrar.
             */
            'PARTICIPATION' =>
            $ordered->filter(
                fn($participant) =>
                $participant->outcome !== 'UNPLACED'
            ),

            'UNBEATEN' =>
            $ordered->filter(
                fn($participant) =>
                (int) $participant->matches > 0
                    && (int) $participant->losses === 0
            ),

            'WIN_COUNT' =>
            $ordered->filter(
                fn($participant) =>
                (int) $participant->wins >= (int) $rule->threshold
            ),

            'ENCOUNTER_WIN_COUNT' =>
            $ordered->filter(
                fn($participant) =>
                ($encounterWins[(int) $participant->universe_entity_id] ?? 0)
                    >= (int) $rule->threshold
            ),

            default =>
            collect(),
        };
    }

    /**
     * Enfrentamientos ganados por competidor en esta competición.
     *
     * @return array<int, int>
     */
    private function encounterWins(
        TournamentInstance $instance
    ): array {

        return GameEncounterParticipant::query()
            ->join(
                'game_encounters',
                'game_encounters.id',
                '=',
                'game_encounter_participants.game_encounter_id'
            )
            ->where(
                'game_encounters.tournament_instance_id',
                $instance->id
            )
            ->where('game_encounter_participants.is_winner', true)
            ->whereNotNull('game_encounter_participants.universe_entity_id')
            ->groupBy('game_encounter_participants.universe_entity_id')
            ->selectRaw(
                'game_encounter_participants.universe_entity_id as entity_id,
                 count(*) as wins'
            )
            ->pluck('wins', 'entity_id')
            ->map(fn($wins) => (int) $wins)
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Aplicar
    |--------------------------------------------------------------------------
    */

    private function applyStat(
        TournamentInstance $instance,
        UniverseTournamentReward $rule,
        UniverseEntity $entity,
        string $defaultGameKey,
        TournamentInstanceParticipant $participant
    ): bool {

        if (! $rule->stat_key) {
            return false;
        }

        $gameKey =
            $rule->game_key ?: $defaultGameKey;

        $definition =
            $this->registry->definition($gameKey);

        /*
         * La stat tiene que existir en el esquema del juego. Si la regla
         * quedó obsoleta porque el juego cambió, se ignora: perder una
         * recompensa es mucho menos grave que reventar el cierre de una
         * competición ya jugada.
         */
        $exists =
            collect($definition['stats'] ?? [])
            ->contains(
                fn(array $schema) =>
                $schema['key'] === $rule->stat_key
            );

        if (! $exists) {
            return false;
        }

        /* Idempotencia: este motivo ya se aplicó */
        $already =
            UniverseStatChange::query()
            ->where('tournament_instance_id', $instance->id)
            ->where('universe_entity_id', $entity->id)
            ->where('game_key', $definition['key'])
            ->where('stat_key', $rule->stat_key)
            ->where('universe_tournament_reward_id', $rule->id)
            ->exists();

        if ($already) {
            return false;
        }

        $record =
            $this->stats->ensure($entity, $definition['key']);

        $current =
            $record->normalized_stats;

        $before =
            (float) ($current[$rule->stat_key] ?? 0);

        $current[$rule->stat_key] =
            $this->operate(
                $before,
                $rule->operation,
                (float) $rule->amount
            );

        $saved =
            $this->stats->update(
                $entity,
                $definition['key'],
                $current
            );

        $after =
            (float) ($saved->normalized_stats[$rule->stat_key] ?? $before);

        UniverseStatChange::query()->create([

            'universe_id' => $instance->universe_id,
            'universe_entity_id' => $entity->id,
            'universe_season_id' => $instance->universe_season_id,
            'tournament_instance_id' => $instance->id,
            'universe_tournament_reward_id' => $rule->id,

            'source_type' => 'REWARD',

            'game_key' => $definition['key'],
            'stat_key' => $rule->stat_key,

            'value_before' => $before,
            'value_after' => $after,
            'delta' => round($after - $before, 4),

            'reason' =>
            $rule->label
                ?: $rule->condition_label,
        ]);

        return true;
    }

    private function awardTrophy(
        TournamentInstance $instance,
        UniverseTournamentReward $rule,
        UniverseEntity $entity,
        TournamentInstanceParticipant $participant
    ): bool {

        if (! $rule->universe_trophy_id) {
            return false;
        }

        $already =
            UniverseTrophyAward::query()
            ->where('universe_trophy_id', $rule->universe_trophy_id)
            ->where('universe_entity_id', $entity->id)
            ->where('tournament_instance_id', $instance->id)
            ->exists();

        if ($already) {
            return false;
        }

        UniverseTrophyAward::query()->create([

            'universe_trophy_id' => $rule->universe_trophy_id,
            'universe_entity_id' => $entity->id,
            'universe_id' => $instance->universe_id,
            'tournament_instance_id' => $instance->id,
            'universe_season_id' => $instance->universe_season_id,

            'position' =>
            $participant->placement,

            'awarded_at' =>
            $instance->completed_at ?? now(),
        ]);

        return true;
    }

    private function operate(
        float $value,
        string $operation,
        float $amount
    ): float {

        return match ($operation) {
            'ADD' => $value + $amount,
            'SUBTRACT' => $value - $amount,
            'MULTIPLY' => $value * $amount,
            'SET' => $amount,
            default => $value,
        };
    }
}
