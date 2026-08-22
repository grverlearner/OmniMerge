<?php

namespace App\Services\Universes;

use App\Models\TournamentInstance;
use App\Models\Universe;
use App\Models\UniverseActivity;
use App\Models\UniverseSeason;

/*
|--------------------------------------------------------------------------
| UniverseActivityRecorder
|--------------------------------------------------------------------------
|
| Registra los momentos que hacen que un Universo se sienta vivo.
|
| Solo se anota lo que un usuario contaría a otro: empezó una
| competición, hubo campeón, arrancó una temporada. El detalle del motor
| ya está en tournament_instance_events y no hace falta duplicarlo.
|
| Todos los métodos son tolerantes a fallo: si algo no se puede
| registrar, no debe tumbar la acción que lo provocó. Perder una línea
| de actividad es preferible a perder un resultado.
|
*/

class UniverseActivityRecorder
{
    public function competitionStarted(
        TournamentInstance $instance
    ): void {

        $this->record(
            $instance->universe_id,
            'COMPETITION_STARTED',
            '⚔',
            "Comenzó «{$instance->name}»",
            [
                'universe_season_id' => $instance->universe_season_id,
                'tournament_instance_id' => $instance->id,
            ]
        );
    }

    public function competitionCompleted(
        TournamentInstance $instance,
        $champion = null
    ): void {

        $this->record(
            $instance->universe_id,
            'COMPETITION_COMPLETED',
            '🏁',
            "Finalizó «{$instance->name}»",
            [
                'universe_season_id' => $instance->universe_season_id,
                'tournament_instance_id' => $instance->id,
            ]
        );

        if (! $champion) {
            return;
        }

        $this->record(
            $instance->universe_id,
            'CHAMPION_CROWNED',
            '🏆',
            "{$champion->name} ganó «{$instance->name}»",
            [
                'universe_season_id' => $instance->universe_season_id,
                'universe_entity_id' => $champion->universe_entity_id,
                'tournament_instance_id' => $instance->id,
            ]
        );
    }

    public function seasonStarted(
        UniverseSeason $season
    ): void {

        $this->record(
            $season->universe_id,
            'SEASON_STARTED',
            '◷',
            "Comenzó la Temporada {$season->number}: «{$season->name}»",
            [
                'universe_season_id' => $season->id,
            ]
        );
    }

    public function entitiesImported(
        Universe $universe,
        int $count
    ): void {

        if ($count < 1) {
            return;
        }

        $this->record(
            $universe->id,
            'ENTITIES_IMPORTED',
            '✦',
            $count === 1
                ? 'Se incorporó 1 entidad al Universo'
                : "Se incorporaron {$count} entidades al Universo",
            []
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Escritura
    |--------------------------------------------------------------------------
    */

    private function record(
        ?int $universeId,
        string $type,
        string $icon,
        string $message,
        array $links
    ): void {

        if (! $universeId) {
            return;
        }

        try {

            UniverseActivity::query()->create([

                'universe_id' => $universeId,

                'universe_season_id' =>
                $links['universe_season_id'] ?? null,

                'universe_entity_id' =>
                $links['universe_entity_id'] ?? null,

                'tournament_instance_id' =>
                $links['tournament_instance_id'] ?? null,

                'type' => $type,
                'icon' => $icon,

                'message' => mb_substr($message, 0, 255),

                'occurred_at' => now(),
            ]);
        } catch (\Throwable) {

            /*
             * Silencio deliberado: la actividad es una crónica, no una
             * fuente de verdad. No debe hacer fallar un torneo.
             */
        }
    }
}
