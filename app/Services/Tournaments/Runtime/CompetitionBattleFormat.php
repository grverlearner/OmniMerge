<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\PhaseTemplate;
use App\Models\TournamentInstance;

/*
|--------------------------------------------------------------------------
| CompetitionBattleFormat
|--------------------------------------------------------------------------
|
| Quien decide cuantos juegos tiene un enfrentamiento.
|
| La respuesta es la COMPETICION, no la plantilla. Cuantas rondas tiene un
| cuadro o como se cruzan sus puestos describe la forma del torneo y no
| cambia entre ediciones; cuantos juegos dura un enfrentamiento describe
| como se juega ESTA edicion, y cambia todos los anos.
|
| Las columnas siguen en las tablas de ajustes de fase porque el motor las
| lee, pero han dejado de editarse ahi: son el valor por defecto y nada mas.
|
| Tres niveles, de mas fuerte a mas debil:
|
|   la fase de la competicion   la excepcion -"menos la final, que es al 5"-
|   la competicion              lo normal -"todo al mejor de 3"-
|   la plantilla                el defecto, si nadie dijo nada
|
| ---------------------------------------------------------------
|
| El motor no se toca.
|
| Cada motor lee `series_format`, `default_best_of` y `fixed_games` del
| modelo de ajustes de su fase, y son cinco motores. En vez de cambiarlos
| todos -y de arriesgarse a que uno se quede atras- aqui se reescribe ese
| modelo EN MEMORIA justo antes de que el motor lo lea. No se guarda nada:
| la plantilla sigue intacta en la base de datos.
|
*/
class CompetitionBattleFormat
{
    private const RELATIONS = [
        'ROUND_ROBIN' => 'roundRobinSetting',
        'GROUP_STAGE' => 'groupStageSetting',
        'SINGLE_ELIMINATION' => 'singleEliminationSetting',
        'SWISS' => 'swissSetting',
    ];

    /*
     * El formato de una fase concreta de una competicion.
     *
     * @return array{series_format: string, best_of: int, fixed_games: int}
     */
    public function resolve(TournamentInstance $competition, ?int $nodeId = null): array
    {
        $format = [
            'series_format' => $competition->series_format ?: 'BEST_OF',
            'best_of' => max(1, (int) ($competition->best_of ?: 1)),
            'fixed_games' => max(1, (int) ($competition->fixed_games ?: 1)),
        ];

        if ($nodeId === null) {
            return $this->normalize($format);
        }

        $phase = $competition->phases
            ->firstWhere('node_id', $nodeId);

        if ($phase === null) {
            return $this->normalize($format);
        }

        /*
         * Solo se pisa lo que la fase dice de verdad. Una fase que solo
         * cambia el numero de juegos no deberia cambiar tambien el modo.
         */
        if ($phase->series_format !== null) {
            $format['series_format'] = $phase->series_format;
        }

        if ($phase->best_of !== null) {
            $format['best_of'] = max(1, (int) $phase->best_of);
        }

        if ($phase->fixed_games !== null) {
            $format['fixed_games'] = max(1, (int) $phase->fixed_games);
        }

        return $this->normalize($format);
    }

    /*
     * Escribe ese formato sobre los ajustes de la fase, en memoria.
     *
     * Se llama justo antes de que el motor prepare la fase. El modelo NO se
     * guarda: la plantilla queda como estaba, y dos competiciones sobre la
     * misma plantilla pueden jugarse con formatos distintos a la vez.
     */
    public function applyTo(
        PhaseTemplate $phase,
        TournamentInstance $competition,
        ?int $nodeId = null
    ): void {

        $relation = self::RELATIONS[$phase->phase_type] ?? null;

        if ($relation === null) {
            return;
        }

        $phase->loadMissing($relation);

        $settings = $phase->getRelation($relation);

        if ($settings === null) {
            return;
        }

        $format = $this->resolve($competition, $nodeId);

        /*
         * Lo que habia antes, guardado antes de pisarlo.
         *
         * La huella de la estructura -la que dice "esto es el mismo cuadro
         * que se valido"- incluye el formato de batalla. Al imponer el de
         * la competicion, esa huella cambiaba y el motor se negaba a jugar
         * con «la estructura avanzada cambio despues de su ultima
         * validacion».
         *
         * Era una falsa alarma: cuantos juegos dura un enfrentamiento no
         * cambia la forma del cuadro. Las rondas, los cruces y las salidas
         * son exactamente las mismas. Asi que se conserva el original y la
         * huella lo usa, en vez de sacar el formato de la huella —lo que
         * habria invalidado de golpe todas las estructuras ya validadas—.
         */
        $settings->setAttribute('structure_format_before', [
            'series_format' => $settings->series_format,
            'default_best_of' => $settings->default_best_of,
            'fixed_games' => $settings->fixed_games,
        ]);

        /*
         * forceFill y no save: el objetivo es que el motor lea otro valor,
         * no cambiar la plantilla de nadie.
         */
        $settings->forceFill([
            'series_format' => $format['series_format'],
            'default_best_of' => $format['best_of'],
            'fixed_games' => $format['fixed_games'],
        ]);
    }

    /*
     * Un "mejor de" par no puede decidirse: al mejor de 4 se empata a 2.
     *
     * Se sube al impar siguiente en vez de rechazarlo, porque esto corre en
     * ejecucion y detener una competicion por un numero par seria peor que
     * jugar un juego mas.
     */
    private function normalize(array $format): array
    {
        if ($format['series_format'] === 'BEST_OF' && $format['best_of'] % 2 === 0) {
            $format['best_of']++;
        }

        return $format;
    }

    /*
     * Como se lee un formato, para poder ensenarlo.
     */
    public function label(array $format): string
    {
        return $format['series_format'] === 'FIXED_GAMES'
            ? ($format['fixed_games'] === 1
                ? 'Un solo juego'
                : $format['fixed_games'] . ' juegos fijos')
            : ($format['best_of'] === 1
                ? 'A un juego'
                : 'Al mejor de ' . $format['best_of']);
    }
}
