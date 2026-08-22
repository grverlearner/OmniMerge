<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Models\TournamentInstance;
use App\Models\Universe;
use App\Models\UniverseTournament;
use App\Models\UniverseTournamentModifier;
use App\Models\UniverseTournamentReward;
use App\Services\Games\GameRegistry;
use App\Services\Games\UniverseGameService;
use App\Services\Rewards\RewardProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| UniverseRewardController
|--------------------------------------------------------------------------
|
| Configuración de consecuencias de un torneo:
|
|   RECOMPENSAS   permanentes. Se aplican al terminar y cambian las Game
|                 Stats del competidor para siempre.
|
|   MODIFICADORES temporales. Solo existen mientras se juega y no tocan
|                 nada guardado.
|
| Están en la misma pantalla porque el usuario las piensa juntas, pero no
| comparten tabla ni ciclo de vida.
|
| Ver docs/md/30-Fase-12-Recompensas-Y-Palmares.md
|
*/

class UniverseRewardController extends Controller
{
    public function __construct(
        private readonly GameRegistry $registry,
        private readonly UniverseGameService $games,
        private readonly RewardProcessor $processor
    ) {}

    public function index(
        Universe $universe,
        UniverseTournament $universeTournament
    ): View {

        $this->authorize('view', $universe);

        $gameKey =
            $universeTournament->game_key
            ?: $this->games->defaultKey($universe);

        $definition =
            $this->registry->definition($gameKey);

        $rewards =
            $universeTournament->rewards()
            ->with('trophy')
            ->orderBy('trigger')
            ->orderBy('threshold')
            ->get();

        $modifiers =
            $universeTournament->modifiers()
            ->with('universeEntity')
            ->orderBy('scope')
            ->get();

        $trophies =
            $universe->trophies()
            ->orderBy('name')
            ->get();

        $entities =
            $universe->entities()
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        /* Ediciones ya jugadas, para poder reprocesarlas */
        $editions =
            $universeTournament->instances()
            ->where('status', 'COMPLETED')
            ->with('season')
            ->latest('completed_at')
            ->get();

        return view(
            'universes.rewards.index',
            compact(
                'universe',
                'universeTournament',
                'definition',
                'rewards',
                'modifiers',
                'trophies',
                'entities',
                'editions'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Recompensas permanentes
    |--------------------------------------------------------------------------
    */

    public function storeReward(
        Request $request,
        Universe $universe,
        UniverseTournament $universeTournament
    ): RedirectResponse {

        $this->authorize('update', $universe);

        $gameKey =
            $universeTournament->game_key
            ?: $this->games->defaultKey($universe);

        $data = $request->validate([

            'trigger' => [
                'required',
                Rule::in(array_keys(UniverseTournamentReward::TRIGGERS)),
            ],

            'threshold' => ['nullable', 'integer', 'min:1', 'max:999'],

            /*
             * La stat se valida contra el esquema que declara el Game
             * Engine, no contra una lista fija: así un juego nuevo
             * funciona aquí sin tocar este controlador.
             */
            'stat_key' => [
                'nullable',
                Rule::in($this->statKeys($gameKey)),
            ],

            'operation' => [
                'required',
                Rule::in(array_keys(UniverseTournamentReward::OPERATIONS)),
            ],

            'amount' => ['required', 'numeric', 'between:-9999,9999'],

            'universe_trophy_id' => [
                'nullable',
                Rule::exists('universe_trophies', 'id')
                    ->where('universe_id', $universe->id),
            ],

            'label' => ['nullable', 'string', 'max:150'],
        ], [], [
            'stat_key' => 'estadística',
            'amount' => 'cantidad',
        ]);

        if (
            ! ($data['stat_key'] ?? null)
            && ! ($data['universe_trophy_id'] ?? null)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'stat_key' =>
                    'Una recompensa tiene que dar algo: una estadística, un trofeo, o ambos.',
                ]);
        }

        if (
            $data['trigger'] === 'POSITION'
            && ! ($data['threshold'] ?? null)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'threshold' => 'Indica de qué puesto hablamos.',
                ]);
        }

        $universeTournament->rewards()->create(
            $data + ['game_key' => $gameKey, 'is_active' => true]
        );

        return back()->with(
            'success',
            'Recompensa añadida. Se aplicará cuando termine una edición de este torneo.'
        );
    }

    public function destroyReward(
        Universe $universe,
        UniverseTournament $universeTournament,
        UniverseTournamentReward $reward
    ): RedirectResponse {

        $this->authorize('update', $universe);

        abort_unless(
            $reward->universe_tournament_id === $universeTournament->id,
            404
        );

        $reward->delete();

        return back()->with(
            'success',
            'Recompensa eliminada. Lo ya concedido no se revierte: '
                . 'sigue en el historial de progresión.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Modificadores temporales
    |--------------------------------------------------------------------------
    */

    public function storeModifier(
        Request $request,
        Universe $universe,
        UniverseTournament $universeTournament
    ): RedirectResponse {

        $this->authorize('update', $universe);

        $gameKey =
            $universeTournament->game_key
            ?: $this->games->defaultKey($universe);

        $data = $request->validate([

            'scope' => [
                'required',
                Rule::in(array_keys(UniverseTournamentModifier::SCOPES)),
            ],

            'scope_value' => ['nullable', 'string', 'max:120'],

            'target' => [
                'required',
                Rule::in(array_keys(UniverseTournamentModifier::TARGETS)),
            ],

            'universe_entity_id' => [
                'nullable',
                Rule::exists('universe_entities', 'id')
                    ->where('universe_id', $universe->id),
            ],

            'stat_key' => [
                'required',
                Rule::in($this->statKeys($gameKey)),
            ],

            'operation' => [
                'required',
                Rule::in(array_keys(UniverseTournamentReward::OPERATIONS)),
            ],

            'amount' => ['required', 'numeric', 'between:-9999,9999'],

            'label' => ['nullable', 'string', 'max:150'],
        ]);

        if (
            $data['target'] === 'ENTITY'
            && ! ($data['universe_entity_id'] ?? null)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'universe_entity_id' => 'Elige a quién beneficia.',
                ]);
        }

        if (
            in_array($data['scope'], ['PHASE', 'ROUND'], true)
            && ! trim((string) ($data['scope_value'] ?? ''))
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'scope_value' =>
                    $data['scope'] === 'PHASE'
                        ? 'Escribe el nombre exacto de la fase.'
                        : 'Indica el número de ronda.',
                ]);
        }

        $universeTournament->modifiers()->create(
            $data + ['game_key' => $gameKey, 'is_active' => true]
        );

        return back()->with(
            'success',
            'Bonus añadido. Solo afectará a las competiciones que se creen '
                . 'a partir de ahora: las ya en curso tienen su configuración congelada.'
        );
    }

    public function destroyModifier(
        Universe $universe,
        UniverseTournament $universeTournament,
        UniverseTournamentModifier $modifier
    ): RedirectResponse {

        $this->authorize('update', $universe);

        abort_unless(
            $modifier->universe_tournament_id === $universeTournament->id,
            404
        );

        $modifier->delete();

        return back()->with('success', 'Bonus eliminado.');
    }

    /*
    |--------------------------------------------------------------------------
    | Reprocesar una edición
    |--------------------------------------------------------------------------
    |
    | Existe porque las reglas se pueden cambiar después de jugar. Es
    | seguro repetirlo: el procesador es idempotente por clave única, así
    | que solo aplica lo que todavía no se aplicó.
    |
    */

    public function reprocess(
        Universe $universe,
        UniverseTournament $universeTournament,
        TournamentInstance $competition
    ): RedirectResponse {

        $this->authorize('update', $universe);

        abort_unless(
            $competition->universe_tournament_id === $universeTournament->id,
            404
        );

        $summary =
            $this->processor->process($competition, true);

        return back()->with(
            'success',

            $summary['applied'] === 0 && $summary['trophies'] === 0
                ? 'No había nada nuevo que aplicar: todas las recompensas de esa '
                    . 'edición ya estaban concedidas.'
                : "Aplicadas {$summary['applied']} recompensas y "
                    . "{$summary['trophies']} trofeos nuevos."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Interno
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<int, string>
     */
    private function statKeys(string $gameKey): array
    {
        return collect(
            $this->registry->definition($gameKey)['stats'] ?? []
        )
            ->pluck('key')
            ->all();
    }
}
