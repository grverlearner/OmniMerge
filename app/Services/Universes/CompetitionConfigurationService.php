<?php

namespace App\Services\Universes;

use App\Models\TournamentInstance;
use App\Models\TournamentInstancePhase;
use App\Models\TournamentInstanceReward;
use App\Models\TournamentInstanceState;
use App\Models\UniverseTournament;
use App\Services\Games\GameRegistry;
use App\Services\Tournaments\Runtime\CompetitionPhasePlan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| CompetitionConfigurationService
|--------------------------------------------------------------------------
|
| Guardar lo que se configuro de una edicion.
|
| Se llama en dos momentos y por eso vive aparte del servicio que la crea:
|
|   al crear    despues de proyectar las fases, porque hasta entonces las
|               filas de fase no existen y no hay donde escribir su
|               excepcion
|   al editar   sobre una edicion que todavia no empezo
|
| Lo unico que no toca nunca es la plantilla ni los participantes: eso
| congelo el estado inicial y cambiarlo dejaria un cuadro dibujado para
| otra gente.
|
*/
class CompetitionConfigurationService
{
    public function __construct(
        private readonly GameRegistry $games,
        private readonly CompetitionPhasePlan $plan,
    ) {
    }

    /*
     * @param  array<string,mixed>  $data          lo validado del formulario
     * @param  array|null           $phases        phases[nodeId][campo], null = no se hablo de fases
     * @param  array|null           $rewards       premios propios, null = no se hablo de premios
     * @param  array|null           $startRules    reglas de reparto
     */
    public function apply(
        TournamentInstance $competition,
        UniverseTournament $tournament,
        array $data,
        ?array $phases = null,
        ?array $rewards = null,
        ?array $startRules = null,
        ?UploadedFile $image = null
    ): TournamentInstance {

        return DB::transaction(function () use (
            $competition,
            $tournament,
            $data,
            $phases,
            $rewards,
            $startRules,
            $image
        ) {

            $this->applyIdentity($competition, $data, $image);

            $this->applyRules($competition, $tournament, $data);

            if ($startRules !== null) {
                $competition->start_rules = $startRules;
            }

            $competition->save();

            if ($phases !== null) {
                $this->applyPhases($competition, $phases);
            }

            if ($rewards !== null) {
                $this->applyRewards($competition, $rewards);
            }

            /*
             * El plan reescrito en el estado, aqui y no solo en ejecucion.
             *
             * El runtime tambien lo hace en cada accion, pero la ficha de
             * la competicion se lee del estado guardado: sin esto, guardar
             * "la final al mejor de 5" no se veria hasta jugar algo.
             */
            $this->refreshState($competition->refresh());

            return $competition;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Lo basico
    |--------------------------------------------------------------------------
    */

    private function applyIdentity(
        TournamentInstance $competition,
        array $data,
        ?UploadedFile $image
    ): void {

        $competition->name = $data['name'] ?? $competition->name;

        $competition->description = $data['description'] ?? null;

        if (array_key_exists('universe_season_id', $data)) {
            $competition->universe_season_id = $data['universe_season_id'] ?: null;
        }

        if ($image) {

            /*
             * La anterior se borra: dejar imagenes huerfanas en el disco
             * es basura que nadie va a limpiar despues.
             */
            if ($competition->image) {
                Storage::disk('public')->delete($competition->image);
            }

            $competition->image = $image->store('competitions', 'public');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Como se juega
    |--------------------------------------------------------------------------
    */

    private function applyRules(
        TournamentInstance $competition,
        UniverseTournament $tournament,
        array $data
    ): void {

        /*
         * El juego. Un torneo de juego unico impone el suyo: aqui no se
         * rechaza el envio, se corrige, porque el permiso pudo cerrarse
         * despues de abrir la pantalla.
         */
        $gameKey = strtoupper((string) ($data['game_key'] ?? ''));

        if (($tournament->game_mode ?: 'SINGLE') !== 'VARIED') {
            $gameKey = strtoupper((string) ($tournament->game_key ?: $competition->game_key));
        }

        if ($this->games->has($gameKey)) {
            $competition->game_key = $gameKey;
        }

        /*
         * Bajar la decision a las fases solo si el torneo lo permitio.
         */
        $competition->game_scope = $tournament->allow_phase_game
            ? (($data['game_scope'] ?? 'COMPETITION') === 'PHASE' ? 'PHASE' : 'COMPETITION')
            : 'COMPETITION';

        $competition->battle_scope = $tournament->allow_phase_battle
            ? (($data['battle_scope'] ?? 'COMPETITION') === 'PHASE' ? 'PHASE' : 'COMPETITION')
            : 'COMPETITION';

        $competition->series_format = $data['series_format'] ?? 'BEST_OF';

        $competition->best_of = max(1, (int) ($data['best_of'] ?? 1));

        $competition->fixed_games = max(1, (int) ($data['fixed_games'] ?? 1));

        $competition->battle_participants =
            ($data['battle_participants'] ?? null) !== null
            ? max(2, (int) $data['battle_participants'])
            : null;

        $competition->decision_mode = $data['decision_mode'] ?? 'SERIES_THEN_POINTS';

        $competition->allow_draws = (bool) ($data['allow_draws'] ?? false);
    }

    /*
    |--------------------------------------------------------------------------
    | La excepcion de cada fase
    |--------------------------------------------------------------------------
    |
    | Solo sobre fases que EXISTEN. Una edicion se juega con una plantilla
    | concreta, y un nodo que no esta en ella no puede tener excepcion:
    | crear la fila igualmente dejaria ajustes fantasma que nadie ve y que
    | nadie puede quitar.
    |
    */

    private function applyPhases(TournamentInstance $competition, array $phases): void
    {
        $existing = $competition->phases()->pluck('id', 'node_id');

        foreach ($phases as $nodeId => $row) {

            $id = $existing[(int) $nodeId] ?? null;

            if ($id === null) {
                continue;
            }

            $juego = $row['game_key'] ?? null;

            TournamentInstancePhase::query()
                ->whereKey($id)
                ->update([
                    'game_key' => $this->games->has($juego) ? $juego : null,
                    'series_format' => $row['series_format'] ?? null,
                    'best_of' => $row['best_of'] ?? null,
                    'fixed_games' => $row['fixed_games'] ?? null,
                    'battle_participants' => $row['battle_participants'] ?? null,
                    'decision_mode' => $row['decision_mode'] ?? null,
                    'allow_draws' => $row['allow_draws'] ?? null,

                    /*
                     * Solo tiene sentido en fase de grupos, y solo si es uno
                     * de los modos que existen. Cualquier otra cosa se guarda
                     * como nulo, que significa «lo que diga la plantilla».
                     */
                    'overall_ranking_mode' =>
                    \App\Services\Tournaments\GroupStage\GroupStageOverallRanking::isValid(
                        $row['overall_ranking_mode'] ?? null
                    )
                        ? $row['overall_ranking_mode']
                        : null,
                ]);
        }

        $competition->unsetRelation('phases');
    }

    /*
    |--------------------------------------------------------------------------
    | Los premios propios
    |--------------------------------------------------------------------------
    |
    | Se borran y se reescriben, igual que los del torneo: no tienen
    | identidad propia -nadie enlaza a "el premio numero 3"- y el orden en
    | que se escribieron ES su orden.
    |
    | Los trofeos si tienen identidad, y esos no se tocan: un trofeo que ya
    | gano alguien no puede desaparecer porque se reordenase una lista.
    |
    */

    private function applyRewards(TournamentInstance $competition, array $rewards): void
    {
        $nodes = $competition->phases()->pluck('node_id')->all();

        $competition->rewards()->delete();

        foreach ($rewards as $row) {

            /*
             * Un premio colgado de una fase que esta edicion no juega se
             * guarda como premio de la competicion entera en vez de
             * perderse. Perderlo en silencio seria peor: el usuario lo
             * escribio.
             */
            $node = $row['node_id'] ?? null;

            if ($node !== null && ! in_array((int) $node, array_map('intval', $nodes), true)) {
                $node = null;
            }

            TournamentInstanceReward::query()->create([
                'tournament_instance_id' => $competition->id,
                'node_id' => $node,
                'trigger' => $row['trigger'] ?? 'POSITION',
                'threshold' => $row['threshold'] ?? null,
                'game_key' => $competition->game_key,
                'stat_key' => $row['stat_key'] ?? null,
                'operation' => $row['operation'] ?? 'ADD',
                'amount' => $row['amount'] ?? 0,
                'universe_trophy_id' => $row['universe_trophy_id'] ?? null,
                'label' => $row['label'] ?? null,
                'carry_forward' => $row['carry_forward'] ?? true,
                'is_active' => true,
            ]);
        }

        $competition->unsetRelation('rewards');
    }

    /*
    |--------------------------------------------------------------------------
    | El estado, al dia
    |--------------------------------------------------------------------------
    */

    private function refreshState(TournamentInstance $competition): void
    {
        $row = TournamentInstanceState::query()
            ->where('tournament_instance_id', $competition->id)
            ->first();

        if (! $row) {
            return;
        }

        $row->update([
            'state' => $this->plan->applyToState((array) $row->state, $competition),
        ]);
    }
}
