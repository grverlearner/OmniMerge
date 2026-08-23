<?php

namespace App\Services\Games;

use App\Models\GameEncounterParticipant;
use App\Models\TournamentInstanceMatch;
use App\Models\UniverseEntity;
use App\Models\UniverseEntityGameStat;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| GameStatsService
|--------------------------------------------------------------------------
|
| Las estadísticas de juego de un competidor, en dos mitades bien
| distintas:
|
|   CONFIGURADAS  el rango de Highest Number, la fuerza de otro juego.
|                 Se guardan, porque no se derivan de nada.
|
|   DERIVADAS     batallas, victorias, derrotas, win rate.
|                 NO se guardan: se calculan desde los enfrentamientos
|                 jugados. Mismo criterio que la clasificación de la Fase
|                 10 — lo derivado nunca se desincroniza, y borrar una
|                 competición corrige los números solo.
|
*/

class GameStatsService
{
    public function __construct(
        private readonly GameRegistry $registry
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Estadísticas configuradas
    |--------------------------------------------------------------------------
    */

    /**
     * Devuelve las stats del competidor para un juego, creándolas con los
     * valores por defecto del engine si todavía no existen.
     */
    public function ensure(
        UniverseEntity $entity,
        string $gameKey
    ): UniverseEntityGameStat {

        $engine =
            $this->registry->engine($gameKey);

        $key =
            $engine->definition()['key'];

        $record =
            $entity->gameStats()
            ->firstOrNew([
                'game_key' => $key,
            ]);

        if (! $record->exists) {

            /*
             * Los valores de partida los decide el UNIVERSO, no el motor.
             * El motor solo dice que estadisticas existen y en que rango
             * absoluto tienen sentido; con que numeros entra un competidor
             * nuevo es una decision del mundo.
             */
            $record->stats =
                $this->configurationFor($entity, $key)
                ->initialStats();

            $record->save();
        }

        return $record;
    }

    /**
     * Configuracion del juego en el Universo de este competidor.
     */
    private function configurationFor(
        UniverseEntity $entity,
        string $gameKey
    ): \App\Support\Games\GameConfiguration {

        $universe = $entity->relationLoaded('universe')
            ? $entity->universe
            : $entity->universe()->first();

        if (! $universe) {

            return new \App\Support\Games\GameConfiguration(
                $this->registry->definition($gameKey)
            );
        }

        return app(UniverseGameService::class)
            ->configuration($universe, $gameKey);
    }

    /**
     * Asegura las stats de TODOS los juegos disponibles. Se usa al
     * importar competidores: llegan listos para competir.
     */
    public function ensureAll(UniverseEntity $entity): void
    {
        foreach ($this->registry->keys() as $key) {
            $this->ensure($entity, $key);
        }
    }

    public function update(
        UniverseEntity $entity,
        string $gameKey,
        array $stats
    ): UniverseEntityGameStat {

        $record =
            $this->ensure($entity, $gameKey);

        /*
         * Primero el motor, que garantiza coherencia interna (un rango no
         * puede estar invertido); despues el Universo, que acota al rango
         * que ese mundo permite.
         */
        $normalized =
            $this->registry
            ->engine($gameKey)
            ->normalizeStats($stats);

        $record->stats =
            $this->configurationFor($entity, $gameKey)
            ->clampStats($normalized);

        $record->save();

        return $record;
    }

    /**
     * Stats normalizadas de todos los juegos, listas para congelar en el
     * estado de una competición.
     *
     * @return array<string, array>
     */
    public function frozenStats(UniverseEntity $entity): array
    {
        $stored =
            $entity->relationLoaded('gameStats')
            ? $entity->gameStats
            : $entity->gameStats()->get();

        $byKey =
            $stored->keyBy('game_key');

        $frozen = [];

        foreach ($this->registry->all() as $key => $engine) {

            $frozen[$key] =
                $engine->normalizeStats(
                    $byKey->get($key)?->stats ?? []
                );
        }

        return $frozen;
    }

    /*
    |--------------------------------------------------------------------------
    | Estadísticas derivadas
    |--------------------------------------------------------------------------
    */

    /**
     * Récord del competidor en un juego.
     *
     * Batallas y enfrentamientos se cuentan por separado a propósito: en
     * un BO3 se puede perder dos enfrentamientos y aun así ganar la
     * batalla, y ambas cifras dicen cosas distintas del competidor.
     */
    public function record(
        UniverseEntity $entity,
        string $gameKey
    ): array {

        $encounters =
            GameEncounterParticipant::query()
            ->join(
                'game_encounters',
                'game_encounters.id',
                '=',
                'game_encounter_participants.game_encounter_id'
            )
            ->where(
                'game_encounter_participants.universe_entity_id',
                $entity->id
            )
            ->where('game_encounters.game_key', $gameKey)
            ->selectRaw(
                'count(*) as played,
                 sum(game_encounter_participants.is_winner) as won,
                 sum(game_encounters.is_draw) as drawn,
                 max(game_encounter_participants.value) as best_value,
                 avg(game_encounter_participants.value) as average_value'
            )
            ->first();

        $played = (int) ($encounters->played ?? 0);
        $won = (int) ($encounters->won ?? 0);
        $drawn = (int) ($encounters->drawn ?? 0);

        $battles =
            $this->battleRecord($entity, $gameKey);

        return [

            'game_key' =>
            $gameKey,

            /* Batallas: la competición completa entre participantes */
            'battles' =>
            $battles['played'],

            'battles_won' =>
            $battles['won'],

            'battles_lost' =>
            $battles['lost'],

            'battle_win_rate' =>
            $this->rate(
                $battles['won'],
                $battles['played']
            ),

            /* Enfrentamientos: cada juego dentro de la batalla */
            'encounters' =>
            $played,

            'encounters_won' =>
            $won,

            'encounters_lost' =>
            max(0, $played - $won - $drawn),

            'encounters_drawn' =>
            $drawn,

            'encounter_win_rate' =>
            $this->rate($won, $played),

            'best_value' =>
            $encounters->best_value !== null
                ? round((float) $encounters->best_value, 2)
                : null,

            'average_value' =>
            $encounters->average_value !== null
                ? round((float) $encounters->average_value, 2)
                : null,

            'has_activity' =>
            $played > 0 || $battles['played'] > 0,
        ];
    }

    /**
     * Récord + configuración de cada juego, para la pestaña "Juegos" de la
     * ficha del competidor.
     *
     * @return Collection<int, array>
     */
    public function profile(UniverseEntity $entity): Collection
    {
        return collect($this->registry->all())
            ->map(
                function ($engine, string $key) use ($entity) {

                    $definition = $engine->definition();

                    return [

                        'definition' =>
                        $definition,

                        'stats' =>
                        $this->ensure($entity, $key),

                        'record' =>
                        $this->record($entity, $key),

                        /*
                         * De donde partio (Fase 12). Se reconstruye desde
                         * el historial de cambios: el primer valor que se
                         * registro es el que tenia antes de ganar nada. Sin
                         * historial, sigue siendo el de partida.
                         */
                        'initial' =>
                        $this->initialStats($entity, $key),
                    ];
                }
            )
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Interno
    |--------------------------------------------------------------------------
    */

    /**
     * Las batallas ya están proyectadas desde la Fase 6 en
     * tournament_instance_matches. No se duplican aquí: se leen.
     */
    private function battleRecord(
        UniverseEntity $entity,
        string $gameKey
    ): array {

        $row =
            TournamentInstanceMatch::query()
            ->join(
                'tournament_instances',
                'tournament_instances.id',
                '=',
                'tournament_instance_matches.tournament_instance_id'
            )
            ->where(
                'tournament_instances.game_key',
                $gameKey
            )
            ->where(
                'tournament_instance_matches.status',
                'COMPLETED'
            )
            ->where(
                fn($query) =>
                $query
                    ->where(
                        'tournament_instance_matches.participant_a_universe_entity_id',
                        $entity->id
                    )
                    ->orWhere(
                        'tournament_instance_matches.participant_b_universe_entity_id',
                        $entity->id
                    )
            )
            ->selectRaw(
                'count(*) as played,
                 sum(case when tournament_instance_matches.winner_universe_entity_id = ?
                     then 1 else 0 end) as won,
                 sum(tournament_instance_matches.is_draw) as drawn',
                [$entity->id]
            )
            ->first();

        $played = (int) ($row->played ?? 0);
        $won = (int) ($row->won ?? 0);
        $drawn = (int) ($row->drawn ?? 0);

        return [
            'played' => $played,
            'won' => $won,
            'lost' => max(0, $played - $won - $drawn),
        ];
    }

    /**
     * Valores de partida de cada stat, antes de que ninguna recompensa
     * los tocara.
     *
     * @return array<string, float>
     */
    public function initialStats(
        UniverseEntity $entity,
        string $gameKey
    ): array {

        $current =
            $this->ensure($entity, $gameKey)
            ->normalized_stats;

        /*
         * El primer cambio registrado de cada stat guarda el valor que
         * habia antes. Ese es el punto de partida real.
         */
        $firsts =
            \App\Models\UniverseStatChange::query()
            ->where('universe_entity_id', $entity->id)
            ->where('game_key', $gameKey)
            ->orderBy('id')
            ->get()
            ->groupBy('stat_key')
            ->map(
                fn($changes) =>
                (float) $changes->first()->value_before
            );

        foreach ($current as $key => $value) {

            $current[$key] =
                $firsts->has($key)
                ? $firsts->get($key)
                : (float) $value;
        }

        return $current;
    }

    private function rate(int $won, int $total): float
    {
        return $total > 0
            ? round($won * 100 / $total, 1)
            : 0.0;
    }
}
