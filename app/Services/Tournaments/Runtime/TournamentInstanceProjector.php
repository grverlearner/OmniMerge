<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentInstance;
use App\Models\TournamentInstanceEvent;
use App\Models\TournamentInstanceMatch;
use App\Models\TournamentInstanceParticipant;
use App\Models\TournamentInstancePhase;

/*
|--------------------------------------------------------------------------
| TournamentInstanceProjector
|--------------------------------------------------------------------------
|
| Vuelca el estado del motor (JSON) a las tablas consultables.
|
| El JSON es la fuente de verdad del motor; estas tablas son la vista
| legible: permiten listar encuentros, ver la clasificación o revisar el
| historial sin recorrer el estado a mano.
|
| Es IDEMPOTENTE: proyectar dos veces el mismo estado deja el mismo
| resultado. Los eventos son la excepción, y por eso se añaden solo los
| que aún no existen (el ledger nunca se reescribe).
|
| Deliberadamente NO conoce ningún motor concreto: recorre el runtime de
| cada nodo buscando encuentros, de modo que un motor futuro que respete
| la misma forma queda proyectado sin tocar esta clase.
|
*/

class TournamentInstanceProjector
{
    public function project(
        TournamentInstance $instance,
        array $state
    ): void {

        $this->projectParticipants(
            $instance,
            $state
        );

        $this->projectPhases(
            $instance,
            $state
        );

        $this->projectMatches(
            $instance,
            $state
        );

        $this->appendEvents(
            $instance,
            $state
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Participantes
    |--------------------------------------------------------------------------
    |
    | El nombre y el seed NO se actualizan: se congelaron al crear la
    | competición. Aquí solo se refresca lo que cambia al jugar.
    |
    */

    private function projectParticipants(
        TournamentInstance $instance,
        array $state
    ): void {

        foreach (
            ($state['participants'] ?? [])
            as
            $key => $participant
        ) {

            $statistics =
                $participant['statistics']
                ?? [];

            $location =
                $participant['current_location']
                ?? [];

            TournamentInstanceParticipant::query()
                ->where(
                    'tournament_instance_id',
                    $instance->id
                )
                ->where(
                    'runtime_key',
                    (string) $key
                )
                ->update([

                    'status' =>
                    $participant['status']
                        ?? 'WAITING',

                    'matches' =>
                    (int) ($statistics['matches'] ?? 0),

                    'wins' =>
                    (int) ($statistics['wins'] ?? 0),

                    'draws' =>
                    (int) ($statistics['draws'] ?? 0),

                    'losses' =>
                    (int) ($statistics['losses'] ?? 0),

                    'points' =>
                    (int) ($statistics['points'] ?? 0),

                    'final_location_type' =>
                    $location['type'] ?? null,

                    'final_location_name' =>
                    $location['name'] ?? null,

                    'updated_at' =>
                    now(),
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Fases
    |--------------------------------------------------------------------------
    */

    private function projectPhases(
        TournamentInstance $instance,
        array $state
    ): void {

        foreach (
            ($state['nodes'] ?? [])
            as
            $node
        ) {

            TournamentInstancePhase::query()
                ->updateOrCreate(
                    [
                        'tournament_instance_id' =>
                        $instance->id,

                        'node_id' =>
                        (int) ($node['id'] ?? 0),
                    ],
                    [
                        'node_code' =>
                        $node['code'] ?? null,

                        'node_name' =>
                        $node['name'] ?? 'Fase',

                        'phase_type' =>
                        $node['phase_type'] ?? null,

                        'status' =>
                        $node['status'] ?? 'LOCKED',

                        'participant_count' =>
                        count(
                            $node['participant_ids'] ?? []
                        ),
                    ]
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Encuentros
    |--------------------------------------------------------------------------
    */

    private function projectMatches(
        TournamentInstance $instance,
        array $state
    ): void {

        $names =
            $this->participantNames(
                $state
            );

        foreach (
            ($state['nodes'] ?? [])
            as
            $node
        ) {

            $runtime =
                $node['runtime'] ?? null;

            if (! is_array($runtime)) {
                continue;
            }

            $nodeId =
                (int) ($node['id'] ?? 0);

            foreach (
                $this->collectMatches($runtime)
                as
                $match
            ) {

                $this->storeMatch(
                    $instance,
                    $nodeId,
                    $match,
                    $names
                );
            }
        }
    }

    /*
     * Recorrido recursivo: cualquier array con id + los dos huecos de
     * participante se considera un encuentro, venga del motor que venga.
     */
    private function collectMatches(
        array $runtime,
        ?int $roundNumber = null,
        ?string $roundLabel = null
    ): array {

        $found = [];

        if ($this->looksLikeMatch($runtime)) {

            $runtime['__round_number'] = $roundNumber;
            $runtime['__round_label'] = $roundLabel;

            return [$runtime];
        }

        foreach ($runtime as $key => $value) {

            if (! is_array($value)) {
                continue;
            }

            $childRound = $roundNumber;
            $childLabel = $roundLabel;

            /*
             * Al atravesar una ronda se recuerda su número y etiqueta
             * para poder mostrarlos junto a cada encuentro.
             */
            if (
                isset($value['number'])
                &&
                isset($value['matches'])
            ) {
                $childRound = (int) $value['number'];
                $childLabel = $value['label'] ?? null;
            }

            $found = array_merge(
                $found,
                $this->collectMatches(
                    $value,
                    $childRound,
                    $childLabel
                )
            );
        }

        return $found;
    }

    private function looksLikeMatch(
        array $candidate
    ): bool {

        return array_key_exists('id', $candidate)
            && is_string($candidate['id'] ?? null)
            && array_key_exists('status', $candidate)
            && (
                array_key_exists('participant_a_id', $candidate)
                ||
                array_key_exists('participant_b_id', $candidate)
            );
    }

    private function storeMatch(
        TournamentInstance $instance,
        int $nodeId,
        array $match,
        array $names
    ): void {

        $keyA =
            $match['participant_a_id']
            ?? null;

        $keyB =
            $match['participant_b_id']
            ?? null;

        $winner =
            $match['winner_id']
            ?? null;

        $loser = null;

        if ($winner !== null) {

            $loser =
                $winner === $keyA
                ? $keyB
                : $keyA;
        }

        $scoreA =
            $match['score_a'] ?? null;

        $scoreB =
            $match['score_b'] ?? null;

        TournamentInstanceMatch::query()
            ->updateOrCreate(
                [
                    'tournament_instance_id' =>
                    $instance->id,

                    'runtime_match_id' =>
                    (string) $match['id'],
                ],
                [
                    'node_id' =>
                    $nodeId,

                    'round_number' =>
                    $match['__round_number'] ?? null,

                    'label' =>
                    $match['__round_label'] ?? null,

                    'status' =>
                    $match['status'] ?? 'PENDING',

                    'participant_a_key' =>
                    $keyA,

                    'participant_b_key' =>
                    $keyB,

                    'participant_a_name' =>
                    $keyA !== null
                        ? ($names[$keyA] ?? null)
                        : null,

                    'participant_b_name' =>
                    $keyB !== null
                        ? ($names[$keyB] ?? null)
                        : null,

                    'score_a' =>
                    is_numeric($scoreA)
                        ? (int) $scoreA
                        : null,

                    'score_b' =>
                    is_numeric($scoreB)
                        ? (int) $scoreB
                        : null,

                    'winner_key' =>
                    $winner,

                    'loser_key' =>
                    $loser,

                    'is_draw' =>
                    $winner === null
                        && is_numeric($scoreA)
                        && is_numeric($scoreB)
                        && (int) $scoreA === (int) $scoreB,

                    'series' =>
                    $match['games']
                        ?? $match['series']
                        ?? null,
                ]
            );
    }

    private function participantNames(
        array $state
    ): array {

        $names = [];

        foreach (
            ($state['participants'] ?? [])
            as
            $key => $participant
        ) {

            $names[(string) $key] =
                $participant['name']
                ?? (string) $key;
        }

        return $names;
    }

    /*
    |--------------------------------------------------------------------------
    | Eventos
    |--------------------------------------------------------------------------
    |
    | Append-only. Se añaden únicamente los eventos del timeline que
    | todavía no están en el ledger.
    |
    */

    private function appendEvents(
        TournamentInstance $instance,
        array $state
    ): void {

        $timeline =
            array_values(
                $state['timeline'] ?? []
            );

        if ($timeline === []) {
            return;
        }

        $existing =
            (int)
            TournamentInstanceEvent::query()
            ->where(
                'tournament_instance_id',
                $instance->id
            )
            ->max('sequence');

        $rows = [];

        foreach ($timeline as $index => $event) {

            $sequence = $index + 1;

            if ($sequence <= $existing) {
                continue;
            }

            $rows[] = [

                'tournament_instance_id' =>
                $instance->id,

                'sequence' =>
                $sequence,

                'type' =>
                mb_substr(
                    (string) ($event['type'] ?? 'EVENT'),
                    0,
                    60
                ),

                'level' =>
                mb_substr(
                    (string) ($event['level'] ?? 'INFO'),
                    0,
                    20
                ),

                'message' =>
                (string) ($event['message'] ?? ''),

                'context' =>
                null,

                'created_at' =>
                now(),
            ];
        }

        if ($rows === []) {
            return;
        }

        TournamentInstanceEvent::query()
            ->insert($rows);
    }
}
