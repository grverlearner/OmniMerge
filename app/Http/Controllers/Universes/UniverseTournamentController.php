<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Universes\StoreUniverseTournamentRequest;
use App\Http\Requests\Universes\UpdateUniverseTournamentRequest;
use App\Models\TournamentTemplate;
use App\Models\Universe;
use App\Services\Universes\UniverseTournamentEligibility;
use App\Models\UniverseTrophy;
use App\Models\UniverseTournament;
use App\Services\Games\GameRegistry;
use App\Services\Games\UniverseGameService;
use App\Services\Universes\UniverseTournamentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UniverseTournamentController extends Controller
{
    public function __construct(
        private readonly
        UniverseTournamentService $service
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(
        Universe $universe
    ): View {

        $this->authorize(
            'view',
            $universe
        );

        $universeTournaments =
            $universe
            ->universeTournaments()
            ->with([
                'tournamentTemplate',
            ])
            ->latest()
            ->paginate(20);

        $statistics = [

            'total' =>
            $universe
                ->universeTournaments()
                ->count(),

            'active' =>
            $universe
                ->universeTournaments()
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'draft' =>
            $universe
                ->universeTournaments()
                ->where(
                    'status',
                    'DRAFT'
                )
                ->count(),
        ];

        return view(
            'universes.tournaments.index',
            compact(
                'universe',
                'universeTournaments',
                'statistics'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    |
    | El puente entre configuración y ejecución: desde aquí se lanza una
    | competición real y se ven las que ya se jugaron.
    |
    */

    public function show(
        Universe $universe,
        UniverseTournament $universeTournament
    ): View {

        $this->authorize(
            'view',
            $universe
        );

        $universeTournament->load(
            'tournamentTemplate'
        );

        $competitions =
            $universeTournament
            ->instances()
            ->with(['season', 'participants'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $eligibility = app(UniverseTournamentEligibility::class);

        $catalog = $eligibility->catalog($universe);

        $rules = $eligibility->normalize($universeTournament->eligibility);

        return view(
            'universes.tournaments.show',
            [
                'universe' => $universe,
                'universeTournament' => $universeTournament,
                'competitions' => $competitions,

                /* El juego con el que se pelea, entero: reglas incluidas */
                'game' => app(GameRegistry::class)
                    ->definitions()[$universeTournament->game_key] ?? null,

                /*
                 * Quien puede competir HOY. Se recalcula al mirar y no se
                 * guarda: la regla es lo permanente, la lista cambia sola
                 * cuando el universo crece.
                 */
                'eligibilityRules' => $rules,
                'eligibilityText' => $eligibility->describe($rules, $catalog),
                'eligibilityPreview' => $eligibility->preview($universe, $universeTournament->eligibility, 40),

                'rewards' => $universeTournament
                    ->rewards()
                    ->where('is_active', true)
                    ->with('trophy')
                    ->get(),
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    |
    | Se adopta una plantilla existente de la Biblioteca de Torneos.
    | La plantilla no se copia ni se modifica.
    |
    */

    public function create(
        Request $request,
        Universe $universe
    ): View {

        $this->authorize(
            'update',
            $universe
        );

        $templates =
            TournamentTemplate::query()
            ->ownedBy(
                $request->user()
            )
            ->withCount('graphNodes')
            ->orderBy('name')
            ->get();

        /*
         * Juegos disponibles (Fase 11). El torneo elige con cual se
         * resolveran sus batallas; si no elige, hereda el del Universo.
         */
        $games =
            app(GameRegistry::class)->definitions();

        $defaultGameKey =
            app(UniverseGameService::class)->defaultKey($universe);

        return view(
            'universes.tournaments.create',
            [
                'universe' => $universe,
                'templates' => $templates,
                'games' => $games,
                'defaultGameKey' => $defaultGameKey,
                ...$this->configurationContext($universe, null),
            ]
        );
    }

    /*
     * Todo lo que necesitan las pantallas de alta y edicion ademas de la
     * identidad del torneo.
     *
     * Va en un solo sitio porque las dos pantallas piden exactamente lo
     * mismo: el catalogo de atributos del universo, sus trofeos, y una
     * vista previa de a quien deja competir la regla actual.
     */
    private function configurationContext(
        Universe $universe,
        ?UniverseTournament $tournament
    ): array {

        $eligibility = app(UniverseTournamentEligibility::class);

        $catalog = $eligibility->catalog($universe);

        /*
         * Lo que hay guardado, o lo que se quedo a medias tras un error de
         * validacion. Sin esto, equivocarte en el nombre te borraba las
         * reglas de participacion que acababas de escribir.
         */
        $current = old('eligibility') !== null
            ? [
                'mode' => old('eligibility_mode', 'ALL'),
                'rules' => array_values((array) old('eligibility')),
            ]
            : ($tournament?->eligibility ?? ['mode' => 'ALL', 'rules' => []]);

        return [
            'eligibilityCatalog' => $catalog,
            'eligibilityRules' => $eligibility->normalize($current),
            'eligibilityPreview' => $eligibility->preview($universe, $current),

            /*
             * Todos los competidores, para que la galeria responda en el
             * acto al marcar un atributo en vez de esperar al servidor.
             */
            'eligibilityRoster' => $eligibility->roster($universe),

            'trophies' => UniverseTrophy::query()
                ->where('universe_id', $universe->id)
                ->orderBy('name')
                ->get(),

            'previewUrl' => route(
                'universes.tournaments.eligibility-preview',
                $universe
            ),
        ];
    }

    /*
     * Los datos validados, con la elegibilidad en la forma que espera el
     * dominio.
     *
     * El formulario manda listas paralelas porque es lo que un formulario
     * sabe hacer; el servicio la quiere como { mode, rules }. Sin traducir
     * aqui se guardaba la lista cruda, y entonces normalize() no encontraba
     * ninguna regla dentro: el filtro existia en la base de datos y no
     * filtraba a nadie.
     */
    private function withEligibility($request): array
    {
        return [
            ...$request->validated(),
            'eligibility' => $request->eligibilityPayload(),
            'rewards' => $request->rewardsPayload(),
        ];
    }

    /*
     * A quien deja competir una regla, sin recargar.
     *
     * La pantalla lo pide cada vez que se toca un filtro: sin ver los
     * competidores que quedan, elegir atributos es escribir a ciegas.
     */
    public function eligibilityPreview(
        Request $request,
        Universe $universe
    ) {
        $this->authorize('update', $universe);

        $data = $request->validate([
            'mode' => ['nullable', 'in:ALL,ANY'],
            'rules' => ['nullable', 'array', 'max:20'],
            'rules.*.attribute' => ['nullable', 'string', 'max:120'],
            'rules.*.values' => ['nullable', 'array', 'max:60'],
            'rules.*.values.*' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json(
            app(UniverseTournamentEligibility::class)->preview($universe, [
                'mode' => $data['mode'] ?? 'ALL',
                'rules' => $data['rules'] ?? [],
            ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreUniverseTournamentRequest $request,
        Universe $universe
    ): RedirectResponse {

        $this->service
            ->create(
                $universe,

                $this->withEligibility($request),

                $request->file('image')
            );

        return redirect()
            ->route(
                'universes.tournaments.index',
                $universe
            )
            ->with(
                'success',
                'Torneo añadido al Universo.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        Universe $universe,
        UniverseTournament $universeTournament
    ): View {

        $this->authorize(
            'update',
            $universe
        );

        $universeTournament->load(
            'tournamentTemplate'
        );

        $games =
            app(GameRegistry::class)->definitions();

        $defaultGameKey =
            app(UniverseGameService::class)->defaultKey($universe);

        /*
         * Las plantillas tambien se ofrecen al editar: un torneo puede
         * cambiar de forma entre temporadas, y esa es la eleccion por
         * defecto que heredaran las ediciones futuras.
         */
        $templates =
            TournamentTemplate::query()
            ->ownedBy($universe->user)
            ->withCount('graphNodes')
            ->orderBy('name')
            ->get();

        return view(
            'universes.tournaments.edit',
            [
                'universe' => $universe,
                'universeTournament' => $universeTournament,
                'templates' => $templates,
                'games' => $games,
                'defaultGameKey' => $defaultGameKey,
                ...$this->configurationContext($universe, $universeTournament),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateUniverseTournamentRequest $request,
        Universe $universe,
        UniverseTournament $universeTournament
    ): RedirectResponse {

        $this->service
            ->update(
                $universeTournament,

                $this->withEligibility($request),

                $request->file('image')
            );

        return redirect()
            ->route(
                'universes.tournaments.index',
                $universe
            )
            ->with(
                'success',
                'Torneo actualizado correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Universe $universe,
        UniverseTournament $universeTournament
    ): RedirectResponse {

        $this->authorize(
            'update',
            $universe
        );

        $this->service
            ->delete($universeTournament);

        return redirect()
            ->route(
                'universes.tournaments.index',
                $universe
            )
            ->with(
                'success',
                'Torneo quitado del Universo. La plantilla sigue en tu Biblioteca de Torneos.'
            );
    }
}
