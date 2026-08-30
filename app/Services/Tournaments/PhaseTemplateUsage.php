<?php

namespace App\Services\Tournaments;

use App\Models\PhaseTemplate;
use App\Models\TournamentInstance;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| Dónde se está usando una plantilla de fase
|--------------------------------------------------------------------------
|
| Una plantilla de fase no se edita en el vacío. En cuanto entra en una
| plantilla de torneo deja de ser un borrador propio y pasa a ser una pieza
| de otra cosa: cambiarle las rondas, las salidas o las puertas cambia el
| recorrido de todos los torneos que la montaron.
|
| Y no solo eso. El validador del grafo comprueba que lo que sale de una
| fase encaja con lo que espera la siguiente; tocar una salida puede dejar
| conexiones apuntando a algo que ya no existe, y eso no se ve hasta que
| alguien intenta jugar.
|
| Esto no impide nada —la plantilla es del usuario y sabrá lo que hace—;
| sirve para poder AVISARLE, con los nombres delante, y ofrecerle la salida
| razonable: duplicarla y editar la copia.
|
| Las competiciones ya empezadas son el caso tranquilo: se juegan sobre un
| snapshot inmutable, así que un cambio de hoy no las toca. Se cuentan
| igualmente, porque saber que una plantilla ya se jugó cambia las ganas de
| tocarla.
|
*/
class PhaseTemplateUsage
{
    /**
     * @return array{
     *     in_use: bool,
     *     tournaments: Collection,
     *     nodes: int,
     *     played: int,
     * }
     */
    public function of(PhaseTemplate $phase): array
    {
        $nodes = $phase
            ->tournamentPhaseNodes()
            ->with('tournamentTemplate')
            ->get();

        $tournaments = $nodes
            ->pluck('tournamentTemplate')
            ->filter()
            ->unique('id')
            ->values();

        /*
         * Cuántas competiciones se han jugado ya con alguna de esas
         * plantillas. No bloquea nada, pero es el dato que de verdad pesa:
         * una plantilla que nadie ha jugado se retoca sin miedo.
         */
        $played = $tournaments->isEmpty()
            ? 0
            : TournamentInstance::query()
                ->whereIn('tournament_template_id', $tournaments->pluck('id'))
                ->count();

        return [
            'in_use' => $nodes->isNotEmpty(),
            'tournaments' => $tournaments,
            'nodes' => $nodes->count(),
            'played' => $played,
        ];
    }
}
