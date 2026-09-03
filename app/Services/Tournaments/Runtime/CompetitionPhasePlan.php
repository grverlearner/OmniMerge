<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentInstance;
use App\Models\TournamentInstancePhase;
use App\Services\Games\GameRegistry;

/*
|--------------------------------------------------------------------------
| CompetitionPhasePlan
|--------------------------------------------------------------------------
|
| Que se juega, y como, en cada fase de una edicion.
|
| Hay tres niveles y cada uno solo puede abrir lo que el de arriba
| permitio:
|
|   el torneo        "el juego siempre es el mismo" / "puede variar"
|   la competicion   elige el suyo, y decide si lo baja a las fases
|   la fase          la excepcion, solo si la competicion la habilito
|
| Esa ultima condicion es la que hace que esto no sea un simple
| `?? $padre`: una fase puede tener guardado un juego de cuando la
| competicion permitia variarlo, y si despues se cerro esa puerta, ese
| valor tiene que dejar de aplicarse SIN borrarlo -porque volver a abrirla
| deberia devolverlo tal cual-.
|
*/
class CompetitionPhasePlan
{
    public function __construct(
        private readonly CompetitionBattleFormat $formats,
        private readonly GameRegistry $games,
        private readonly PlacementDemands $demands,
    ) {
    }

    /*
     * Todo lo que aplica a un nodo del grafo.
     *
     * @return array{
     *     game_key: string,
     *     game_name: string,
     *     game_icon: ?string,
     *     game_from: string,
     *     series_format: string,
     *     best_of: int,
     *     fixed_games: int,
     *     battle_participants: ?int,
     *     decision_mode: string,
     *     allow_draws: bool,
     *     battle_from: string
     * }
     */
    public function forNode(TournamentInstance $competition, ?int $nodeId = null): array
    {
        $phase = $nodeId === null
            ? null
            : $this->phaseOf($competition, $nodeId);

        return $this->game($competition, $phase)
            + $this->battle($competition, $phase, $nodeId);
    }

    /*
     * El plan de todas las fases proyectadas de la competicion, por
     * node_id. Es lo que la pantalla necesita para pintarlas de una vez.
     *
     * @return array<int,array<string,mixed>>
     */
    public function forAll(TournamentInstance $competition): array
    {
        $competition->loadMissing('phases');

        $plan = [];

        foreach ($competition->phases as $phase) {
            $plan[(int) $phase->node_id] =
                $this->game($competition, $phase)
                + $this->battle($competition, $phase, (int) $phase->node_id);
        }

        return $plan;
    }

    /*
    |--------------------------------------------------------------------------
    | El juego
    |--------------------------------------------------------------------------
    */

    private function game(
        TournamentInstance $competition,
        ?TournamentInstancePhase $phase
    ): array {

        $key = (string) ($competition->game_key ?: GameRegistry::DEFAULT_KEY);

        $from = 'COMPETITION';

        /*
         * La fase solo manda si la competicion bajo esa decision. Un juego
         * guardado en la fase de cuando si se podia se conserva, pero
         * duerme.
         */
        if (
            $competition->game_scope === 'PHASE'
            && $phase?->game_key
            && $this->games->has($phase->game_key)
        ) {
            $key = strtoupper((string) $phase->game_key);
            $from = 'PHASE';
        }

        if (! $this->games->has($key)) {
            $key = GameRegistry::DEFAULT_KEY;
            $from = 'FALLBACK';
        }

        $definition = $this->games->definition($key);

        return [
            'game_key' => $definition['key'],
            'game_name' => $definition['name'],
            'game_icon' => $definition['icon'] ?? null,
            'game_accent' => $definition['accent'] ?? 'violet',
            'game_from' => $from,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | La batalla
    |--------------------------------------------------------------------------
    */

    private function battle(
        TournamentInstance $competition,
        ?TournamentInstancePhase $phase,
        ?int $nodeId
    ): array {

        $perPhase = $competition->battle_scope === 'PHASE';

        /*
         * CompetitionBattleFormat ya sabe resolver cuantos juegos dura un
         * enfrentamiento, y lo hace en tres niveles. Se le pasa el nodo
         * solo cuando la fase puede opinar.
         */
        $format = $this->formats->resolve(
            $competition,
            $perPhase ? $nodeId : null
        );

        $participants = $competition->battle_participants;
        $decision = (string) ($competition->decision_mode ?: 'SERIES_THEN_POINTS');
        $draws = (bool) $competition->allow_draws;

        $from = 'COMPETITION';

        if ($perPhase && $phase) {

            if ($phase->battle_participants !== null) {
                $participants = (int) $phase->battle_participants;
                $from = 'PHASE';
            }

            if ($phase->decision_mode !== null) {
                $decision = (string) $phase->decision_mode;
                $from = 'PHASE';
            }

            if ($phase->allow_draws !== null) {
                $draws = (bool) $phase->allow_draws;
                $from = 'PHASE';
            }

            if (
                $phase->series_format !== null
                || $phase->best_of !== null
                || $phase->fixed_games !== null
            ) {
                $from = 'PHASE';
            }
        }

        return $format + [
            'battle_participants' => $participants,
            'decision_mode' => $decision,
            'allow_draws' => $draws,
            'battle_from' => $from,
            'battle_label' => $this->formats->label($format),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Dejarlo donde el motor lo lee
    |--------------------------------------------------------------------------
    |
    | El motor no consulta la base de datos: lee el estado. Aqui se escribe
    | el plan de cada fase dentro de su nodo, en `runtime.competition`, y
    | de ahi lo recogen EncounterRuntime -que juego se juega, si cabe
    | empate- y MatchSeriesRuntime -como se decide la serie-.
    |
    | Se reescribe en CADA accion, no solo al crear: cambiar el formato de
    | una fase que todavia no empezo tiene que surtir efecto, y un estado
    | guardado hace tres semanas no puede quedarse con el plan viejo.
    |
    | Va en una rama de PRIMER NIVEL -`competition_plan`- y no dentro del
    | nodo. Esto no es estetica: el motor decide si una fase ya se preparo
    | preguntando `isset($state['nodes'][$id]['runtime'])`, asi que crear
    | ese `runtime` para colgar el plan hacia que TODAS las fases pareciesen
    | ya preparadas y ninguna llegase a empezar.
    |
    | Se refleja ademas dentro del nodo, pero solo si ese `runtime` YA
    | existe: MatchSeriesRuntime recibe el runtime del nodo y no el estado
    | entero, y ahi es donde lo lee. Para entonces la fase ya arranco, asi
    | que reflejarlo no inventa nada.
    */
    public function applyToState(array $state, TournamentInstance $competition): array
    {
        if (! isset($state['nodes']) || ! is_array($state['nodes'])) {
            return $state;
        }

        /*
         * Que puestos hay que decidir en cada fase. Sale de las salidas de
         * la fase Y de los premios por posicion -del torneo y de esta
         * edicion-. Ver PlacementDemands.
         */
        $puestos = $this->demands->forCompetition($competition);

        foreach ($state['nodes'] as $nodeId => $node) {

            if (! is_array($node)) {
                continue;
            }

            $plan = $this->forNode($competition, (int) $nodeId);

            $resumen = [

                'game_key' => $plan['game_key'],
                'game_name' => $plan['game_name'],
                'game_icon' => $plan['game_icon'],
                'game_accent' => $plan['game_accent'],
                'game_from' => $plan['game_from'],

                'series_format' => $plan['series_format'],
                'best_of' => $plan['best_of'],
                'fixed_games' => $plan['fixed_games'],

                'battle_participants' => $plan['battle_participants'],
                'decision_mode' => $plan['decision_mode'],
                'allow_draws' => $plan['allow_draws'],
                'battle_from' => $plan['battle_from'],
            ];

            $state['competition_plan'][(int) $nodeId] = $resumen;

            /*
             * Dentro del nodo solo si ya arranco. Crearlo aqui le diria al
             * motor que la fase ya se preparo.
             */
            if (isset($state['nodes'][$nodeId]['runtime'])
                && is_array($state['nodes'][$nodeId]['runtime'])) {

                $state['nodes'][$nodeId]['runtime']['competition'] = $resumen;

                /*
                 * Como ordena sus grupos esta fase.
                 *
                 * La plantilla lo decide y la EDICION puede cambiarlo, igual
                 * que ya cambia el juego o el formato de batalla. Se escribe
                 * en cada accion —no solo al preparar— para que retocarlo con
                 * la competicion ya empezada se note en cuanto el motor
                 * recalcule.
                 *
                 * Sin excepcion propia no se toca nada: manda lo que el motor
                 * leyo de la plantilla.
                 */
                $propio = $this->phaseOf($competition, (int) $nodeId)?->overall_ranking_mode;

                if (\App\Services\Tournaments\GroupStage\GroupStageOverallRanking::isValid($propio)) {
                    $state['nodes'][$nodeId]['runtime']['overall_ranking_mode'] = $propio;
                }

                /*
                 * Que puestos tiene que decidir esta fase.
                 *
                 * Se reescribe en CADA accion, no solo al preparar la fase,
                 * por el mismo motivo que el formato de batalla: si se anade
                 * un premio al 5.o puesto con la competicion ya empezada,
                 * tiene que notarse cuando la fase llegue ahi. Una fase que
                 * ya cerro no vuelve a mirarlo —el premio llego tarde—.
                 */
                $state['nodes'][$nodeId]['runtime']['placement_wanted'] =
                    $puestos[(int) $nodeId] ?? [];
            }
        }

        return $state;
    }

    private function phaseOf(TournamentInstance $competition, int $nodeId): ?TournamentInstancePhase
    {
        $competition->loadMissing('phases');

        return $competition->phases->firstWhere('node_id', $nodeId);
    }

    /*
    |--------------------------------------------------------------------------
    | Como se lee
    |--------------------------------------------------------------------------
    */

    public const DECISION_MODES = [
        'SERIES_THEN_POINTS' => 'Marcador, y las anotaciones si empatan',
        'POINTS_ONLY' => 'Solo las anotaciones acumuladas',
    ];

    public function decisionLabel(string $mode): string
    {
        return self::DECISION_MODES[$mode] ?? $mode;
    }
}
