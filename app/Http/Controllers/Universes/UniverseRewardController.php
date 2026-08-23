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
use App\Services\Rewards\PhaseBonusGranter;
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
        private readonly RewardProcessor $processor,
        private readonly PhaseBonusGranter $phaseBonuses
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

        /*
         * Todos los juegos habilitados en el Universo, no solo el del
         * torneo.
         *
         * Cada juego tiene sus propias estadisticas, asi que una
         * recompensa de "+0.5 max_value" solo significa algo dentro del
         * juego que declara esa stat. Poder elegir el juego permite, por
         * ejemplo, que un mismo torneo premie en Highest Number aunque se
         * juegue a Rounded Number.
         */
        $availableGames =
            $this->games->enabled($universe)
            ->map(
                fn($game) => $this->registry->definition($game->game_key)
            )
            ->values();

        /* Esquema de stats por juego, para que el formulario cambie solo */
        $statsByGame =
            $availableGames
            ->mapWithKeys(
                fn(array $game) => [
                    $game['key'] => collect($game['stats'] ?? [])
                        ->map(
                            fn(array $stat) => [
                                'key' => $stat['key'],
                                'label' => $stat['label'] ?? $stat['key'],
                            ]
                        )
                        ->values(),
                ]
            );

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

        /*
         * Las fases del recorrido, para elegirlas de una lista en vez de
         * escribir el nombre a mano y fallar por una tilde.
         *
         * El nombre es la clave real: es con lo que compara el runtime,
         * porque el estado congelado no conoce ids de plantilla.
         */
        $phases =
            $universeTournament
            ->tournamentTemplate
            ?->graphNodes()
            ->with('phaseTemplate')
            ->get()
            ->map(
                fn($node) => [
                    'name' => $node->name ?: ($node->phaseTemplate?->name ?? $node->code),
                    'type' => $node->phaseTemplate?->type_label ?? '',
                ]
            )
            ->filter(fn($phase) => (string) $phase['name'] !== '')
            ->values()
            ?? collect();

        /*
         * Las rondas que EXISTEN de verdad.
         *
         * No se calculan de la plantilla —cuántas jornadas tiene una liga
         * depende de cuántos entren— sino que se leen de la última edición
         * jugada. Si el torneo no se ha jugado nunca no hay lista que
         * ofrecer, y el formulario lo dice en vez de inventar números.
         */
        $lastEdition =
            $universeTournament->instances()
            ->whereHas('matches')
            ->latest('id')
            ->first();

        $rounds =
            $lastEdition
            ? $lastEdition->matches()
                ->whereNotNull('round_number')
                ->get(['round_number', 'node_id'])
                ->groupBy('round_number')
                ->map(fn($group, $number) => [
                    'number' => (int) $number,
                    'phases' => $group->pluck('node_id')->unique()->count(),
                ])
                ->sortKeys()
                ->values()
            : collect();

        $trophies =
            $universe->trophies()
            ->orderBy('name')
            ->get();

        $entities =
            $universe->entities()
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        /*
         * Competiciones vivas: son las únicas a las que tiene sentido
         * llevarles una regla nueva.
         */
        $running =
            $universeTournament->instances()
            ->whereIn('status', ['DRAFT', 'RUNNING', 'PAUSED'])
            ->with('season')
            ->latest('id')
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
                'availableGames',
                'statsByGame',
                'rewards',
                'modifiers',
                'phases',
                'rounds',
                'running',
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

            'game_key' => [
                'nullable',
                Rule::in($this->registry->keys()),
            ],

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
                Rule::in($this->statKeys($this->chosenGame($request, $gameKey))),
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
            $data + [
                'game_key' => $this->chosenGame($request, $gameKey),
                'is_active' => true,
            ]
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

            'game_key' => [
                'nullable',
                Rule::in($this->registry->keys()),
            ],

            'scope' => [
                'required',
                Rule::in(array_keys(UniverseTournamentModifier::SCOPES)),
            ],

            'scope_value' => ['nullable', 'string', 'max:120'],

            /* Solo para un bonus que hay que ganarse jugando */
            'award_phase' => ['nullable', 'string', 'max:120'],

            'selector_type' => [
                'nullable',
                Rule::in(array_keys(UniverseTournamentModifier::SELECTORS)),
            ],

            'selector_from' => ['nullable', 'integer', 'between:1,999'],
            'selector_to' => ['nullable', 'integer', 'between:1,999'],

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
                Rule::in($this->statKeys($this->chosenGame($request, $gameKey))),
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

        if ($data['target'] === 'PHASE_PODIUM') {

            $data['selector_type'] = $data['selector_type'] ?: 'TOP_N';
            $data['selector_from'] = (int) ($data['selector_from'] ?? 0);

            if ($data['selector_from'] < 1) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'selector_from' => 'Indica de qué puesto hablamos.',
                    ]);
            }

            if ($data['selector_type'] === 'RANK_RANGE') {

                $data['selector_to'] = (int) ($data['selector_to'] ?? 0);

                if ($data['selector_to'] < $data['selector_from']) {

                    return back()
                        ->withInput()
                        ->withErrors([
                            'selector_to' => 'El puesto final tiene que ir después del inicial.',
                        ]);
                }
            } else {
                $data['selector_to'] = null;
            }

            /*
             * Apunta a un competidor que todavía no se conoce, así que no
             * puede llevar uno fijado.
             */
            $data['universe_entity_id'] = null;

        } else {

            $data['award_phase'] = null;
            $data['selector_type'] = null;
            $data['selector_from'] = null;
            $data['selector_to'] = null;
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
            $data + [
                'game_key' => $this->chosenGame($request, $gameKey),
                'is_active' => true,
            ]
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
    | Llevar los bonus a una competición que ya está en marcha
    |--------------------------------------------------------------------------
    |
    | Los bonus se congelan al crear la competición, y esa regla es buena:
    | cambiar una configuración a mitad de torneo reescribiría lo que ya
    | pasó. Pero deja fuera un caso legítimo — acabas de crear la regla y
    | quieres verla funcionar en el torneo que tienes abierto.
    |
    | Esto lo permite, con dos límites que la hacen segura:
    |
    |   · Lo YA CONCEDIDO no se toca. Un podio que se ganó jugando sigue
    |     siendo suyo aunque la regla que lo dio se borre después.
    |   · Solo entra en competiciones vivas. Una terminada es historia.
    |
    | Las fases que ya acabaron conceden su podio en el momento, porque es
    | justo lo que se está pidiendo al pulsar el botón.
    |
    */

    public function syncModifiers(
        Universe $universe,
        UniverseTournament $universeTournament,
        TournamentInstance $competition
    ): RedirectResponse {

        $this->authorize('update', $universe);

        abort_unless(
            $competition->universe_tournament_id === $universeTournament->id,
            404
        );

        if ($competition->isClosed()) {

            return back()->withErrors([
                'sync' => 'Esa competición ya terminó: su configuración es historia y no se toca.',
            ]);
        }

        $state = $competition->state?->state;

        if (! is_array($state)) {

            return back()->withErrors([
                'sync' => 'Esa competición todavía no tiene estado guardado.',
            ]);
        }

        /* Lo ganado jugando sobrevive a cualquier cambio de reglas */
        $earned =
            collect($state['modifiers'] ?? [])
            ->filter(fn($modifier) => isset($modifier['granted_key']))
            ->values()
            ->all();

        $rules =
            $universeTournament->modifiers()
            ->where('is_active', true)
            ->get()
            ->map(
                fn($modifier) => [
                    'rule_id' => (string) $modifier->id,
                    'scope' => $modifier->scope,
                    'scope_value' => $modifier->scope_value,
                    'award_phase' => $modifier->award_phase,
                    'selector_type' => $modifier->selector_type,
                    'selector_from' => $modifier->selector_from,
                    'selector_to' => $modifier->selector_to,
                    'target' => $modifier->target,
                    'universe_entity_id' => $modifier->universe_entity_id,
                    'game_key' => $modifier->game_key,
                    'stat_key' => $modifier->stat_key,
                    'operation' => $modifier->operation,
                    'amount' => (float) $modifier->amount,
                    'label' => $modifier->label,
                ]
            )
            ->all();

        $state['modifiers'] = array_merge($rules, $earned);

        $before = count($earned);

        $state = $this->phaseBonuses->grant($state);

        $granted =
            collect($state['modifiers'])
            ->filter(fn($modifier) => isset($modifier['granted_key']))
            ->count();

        $competition->state->update(['state' => $state]);

        $nuevos = $granted - $before;

        return back()->with(
            'success',

            'Bonus sincronizados con ' . $competition->code . '. '
                . ($nuevos > 0
                    ? $nuevos . ' concedidos por fases ya terminadas.'
                    : 'Los que se ganan jugando se concederán cuando terminen sus fases.')
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
    /**
     * Juego elegido en el formulario, o el del torneo si no se eligio.
     * Un juego que no existe cae al del torneo en vez de romper.
     */
    private function chosenGame(Request $request, string $fallback): string
    {
        $chosen = (string) $request->input('game_key', '');

        return $this->registry->has($chosen)
            ? $this->registry->definition($chosen)['key']
            : $fallback;
    }

    private function statKeys(string $gameKey): array
    {
        return collect(
            $this->registry->definition($gameKey)['stats'] ?? []
        )
            ->pluck('key')
            ->all();
    }
}
