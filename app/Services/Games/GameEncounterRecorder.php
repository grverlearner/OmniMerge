<?php

namespace App\Services\Games;

use App\Models\GameEncounter;
use App\Models\TournamentInstance;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| GameEncounterRecorder
|--------------------------------------------------------------------------
|
| Vuelca a base de datos los enfrentamientos que el motor fue resolviendo.
|
| El motor es una función pura y no puede escribir: se limita a acumular
| lo que resolvió en $state['game_log']. Este servicio lo drena.
|
| Es idempotente por diseño: la clave
| (competición, batalla, número de enfrentamiento) es única en base de
| datos, así que reproyectar o reintentar una acción no duplica historia.
|
*/

class GameEncounterRecorder
{
    /**
     * Persiste el log pendiente y devuelve el estado sin él.
     */
    public function drain(
        TournamentInstance $instance,
        array $state
    ): array {

        $log =
            $state['game_log'] ?? [];

        if ($log === []) {
            return $state;
        }

        foreach ($log as $entry) {
            $this->store($instance, $entry);
        }

        unset($state['game_log']);

        return $state;
    }

    private function store(
        TournamentInstance $instance,
        array $entry
    ): void {

        $participants =
            $entry['participants'] ?? [];

        $winnerEntityId =
            collect($participants)
            ->firstWhere('is_winner', true)['universe_entity_id']
            ?? null;

        $encounter =
            GameEncounter::query()
            ->updateOrCreate(
                [
                    'tournament_instance_id' => $instance->id,
                    'battle_key' => (string) $entry['battle_key'],
                    'encounter_number' => (int) $entry['encounter_number'],
                ],
                [
                    'universe_id' =>
                    $instance->universe_id,

                    'universe_season_id' =>
                    $instance->universe_season_id,

                    'game_key' =>
                    (string) $entry['game_key'],

                    'node_id' =>
                    $entry['node_id'] !== null
                        ? (string) $entry['node_id']
                        : null,

                    'phase_name' =>
                    $entry['phase_name'] ?? null,

                    'participant_count' =>
                    count($participants),

                    'is_draw' =>
                    (bool) ($entry['is_draw'] ?? false),

                    'winner_universe_entity_id' =>
                    $winnerEntityId,

                    'payload' => [
                        'summary' => $entry['summary'] ?? null,
                        'tiebreaks' => $entry['tiebreaks'] ?? 0,
                        'winner_key' => $entry['winner_id'] ?? null,
                    ],
                ]
            );

        /*
         * Se reescriben las filas del enfrentamiento en vez de acumular:
         * un mismo enfrentamiento siempre tiene el mismo resultado.
         */
        $encounter->participants()->delete();

        foreach ($participants as $participant) {

            $encounter->participants()->create([

                'universe_entity_id' =>
                $participant['universe_entity_id'] ?? null,

                'participant_key' =>
                (string) $participant['id'],

                'name' =>
                $participant['name'] ?? null,

                'value' =>
                $participant['value'],

                'display_value' =>
                $participant['display'] ?? null,

                'position' =>
                (int) ($participant['position'] ?? 1),

                'is_winner' =>
                (bool) ($participant['is_winner'] ?? false),

                'stats_used' =>
                $participant['stats_used'] ?? null,

                /*
                 * Lo que el engine quiso contar de esta tirada. En Rounded
                 * Number es el decimal del que salio el entero, y sin el
                 * un empate a 3 no se distingue de otro.
                 */
                'detail' =>
                ($participant['detail'] ?? null) ?: null,
            ]);
        }
    }
}
