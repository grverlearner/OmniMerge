<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\GameEncounter;
use App\Models\TournamentInstance;
use App\Models\TournamentInstanceMatch;
use App\Models\TournamentInstanceParticipant;
use App\Services\Games\GameRegistry;
use App\Services\Tournaments\History\EntityCompetitionStatsService;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| BattleViewService
|--------------------------------------------------------------------------
|
| Todo lo que hace falta para pintar UNA batalla.
|
| No calcula nada del torneo: lee lo que el Runtime ya decidió y lo que el
| Game Engine ya registró. Es una capa de presentación, no de dominio —
| por eso vive aquí y no toca el motor.
|
| Reúne cuatro fuentes que hasta ahora nadie había juntado:
|
|   tournament_instance_matches   la serie y su marcador
|   tournament_instance_participants  quién compite, con su imagen
|   game_encounters               los números reales de cada enfrentamiento
|   tournament_instance_matches   (histórico) el head to head previo
|
*/

class BattleViewService
{
    public function __construct(
        private readonly GameRegistry $registry,
        private readonly EntityCompetitionStatsService $stats
    ) {}

    /**
     * Datos completos de una batalla.
     */
    public function battle(
        TournamentInstance $instance,
        TournamentInstanceMatch $match
    ): array {

        $participants =
            $this->participantsOf($instance, $match);

        $encounters =
            $this->encountersOf($instance, $match);

        $definition =
            $this->registry->definition($instance->game_key);

        return [

            'match' => $match,

            'game' => $definition,

            'participants' => $participants,

            /*
             * La serie, con la distinción que importa: un BO3 termina
             * cuando alguien llega a 2; unos "2 enfrentamientos fijos" se
             * juegan los dos y decide el acumulado.
             */
            'series' => [
                'label' => $match->series_label,
                'mode' => $match->series_mode_label,
                'is_fixed' => $match->is_fixed_series,
                'score' => $match->series_score,
                'played' => $match->games_played,
                'remaining' => $match->games_remaining,
                'wins_required' => $match->wins_required,
                'completed' => ($match->series['status'] ?? null) === 'COMPLETED',
            ],

            'encounters' => $encounters,

            'head_to_head' =>
            $this->headToHead($match),

            'is_playable' =>
            $match->status === 'PENDING'
                && ! $instance->isClosed()
                && $instance->status !== 'PAUSED',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Participantes
    |--------------------------------------------------------------------------
    */

    private function participantsOf(
        TournamentInstance $instance,
        TournamentInstanceMatch $match
    ): Collection {

        $keys =
            array_values(
                array_filter([
                    $match->participant_a_key,
                    $match->participant_b_key,
                ])
            );

        $rows =
            TournamentInstanceParticipant::query()
            ->where('tournament_instance_id', $instance->id)
            ->whereIn('runtime_key', $keys)
            ->with('universeEntity.gameStats')
            ->get()
            ->keyBy('runtime_key');

        $gameKey =
            $instance->game_key;

        /* Se respeta el orden A/B del encuentro, no el de la consulta */
        return collect($keys)
            ->map(
                function (string $key) use ($rows, $match, $gameKey) {

                    $participant = $rows->get($key);

                    $entity = $participant?->universeEntity;

                    /*
                     * Un competidor importado antes de que existiera el
                     * motor de juegos no tiene fila de estadisticas. El
                     * motor le aplicara igualmente las de por defecto al
                     * tirar, asi que aqui se muestran ESAS y no un hueco
                     * vacio: es lo que de verdad va a jugar.
                     */
                    $stats =
                        $entity?->gameStats
                        ->firstWhere('game_key', $gameKey);

                    return [

                        'key' => $key,

                        'participant' => $participant,

                        'entity' => $entity,

                        'name' =>
                        $participant?->name
                            ?? $entity?->display_label
                            ?? $key,

                        'image_url' =>
                        $entity?->image_url,

                        'stats' =>
                        $stats
                            ? $stats->display_stats
                            : $this->fallbackStats($gameKey),

                        'is_winner' =>
                        $match->winner_key === $key,

                        'trophies' =>
                        $entity?->trophyAwards()->count() ?? 0,
                    ];
                }
            );
    }

    /**
     * Estadisticas de partida del juego, con sus etiquetas, para quien
     * todavia no tiene fila propia.
     */
    private function fallbackStats(?string $gameKey): array
    {
        $engine =
            $this->registry->engine($gameKey);

        $values =
            $engine->normalizeStats(
                $engine->defaultStats()
            );

        return collect($engine->definition()['stats'] ?? [])
            ->map(
                fn(array $schema) => [
                    'label' => $schema['label'] ?? $schema['key'],
                    'help' => $schema['help'] ?? null,
                    'value' => $values[$schema['key']] ?? null,
                ]
            )
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Enfrentamientos jugados
    |--------------------------------------------------------------------------
    |
    | Los valores reales (7.82, 6.14…) viven en game_encounters, que es lo
    | que registró el Game Engine. La serie solo guarda quién ganó cada
    | juego, porque a MatchSeriesRuntime se le entrega 1-0.
    |
    */

    private function encountersOf(
        TournamentInstance $instance,
        TournamentInstanceMatch $match
    ): Collection {

        $logged =
            GameEncounter::query()
            ->where('tournament_instance_id', $instance->id)
            ->where('battle_key', $match->runtime_match_id)
            ->with('participants')
            ->orderBy('encounter_number')
            ->get()
            ->keyBy('encounter_number');

        return collect($match->encounter_rows)
            ->map(
                function (array $row) use ($logged) {

                    $encounter =
                        $logged->get($row['number']);

                    return $row + [

                        'encounter' => $encounter,

                        /*
                         * Los números generados. Si el enfrentamiento se
                         * resolvió antes de que existiera el registro
                         * (competiciones anteriores a la Fase 11), queda
                         * vacío en vez de inventar valores.
                         */
                        'values' =>
                        $encounter?->participants
                            ->map(
                                fn($participant) => [
                                    'key' => $participant->participant_key,
                                    'name' => $participant->name,
                                    'display' => $participant->display_value,
                                    'position' => $participant->position,
                                    'is_winner' => $participant->is_winner,
                                ]
                            )
                            ->all()
                            ?? [],

                        'summary' =>
                        $encounter?->payload['summary'] ?? null,
                    ];
                }
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Historial previo
    |--------------------------------------------------------------------------
    */

    private function headToHead(
        TournamentInstanceMatch $match
    ): ?array {

        $left = $match->participantAEntity;
        $right = $match->participantBEntity;

        if (! $left || ! $right) {
            return null;
        }

        /*
         * Se excluye esta misma batalla: contarla como precedente de si
         * misma falsearia el dato.
         */
        return $this->stats
            ->headToHead($left, $right, $match->id);
    }
}
