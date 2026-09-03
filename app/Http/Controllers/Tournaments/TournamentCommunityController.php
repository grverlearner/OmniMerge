<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Models\PhaseTemplate;
use App\Models\TournamentTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * La comunidad del taller de torneos.
 *
 * Es una comunidad PROPIA, no la de la Biblioteca de entidades. Aquí solo hay
 * dos cosas —plantillas de torneo y plantillas de fase— y una sola acción que
 * importa: llevarse una a tu espacio para trabajarla.
 *
 * Se mezclan los dos tipos a propósito. Quien busca «una fase de grupos de 16»
 * y quien busca «una copa completa» está haciendo lo mismo: buscar una pieza
 * que le ahorre montarla. Separarlas en dos pantallas obligaría a mirar dos
 * veces.
 *
 * Cuando se miran los dos tipos a la vez, cada uno trae su propio tramo y no
 * una lista fusionada: fusionar dos consultas paginadas exige recorrerlas
 * enteras, y aquí eso no compra nada. Al elegir un tipo concreto, ese sí se
 * pagina entero.
 */
class TournamentCommunityController extends Controller
{
    private const POR_TIPO = 12;

    public function index(Request $request): View
    {
        $buscar = trim((string) $request->input('q', ''));
        $tipo = (string) $request->input('kind', 'all');
        $motor = (string) $request->input('engine', '');
        $categoria = (string) $request->input('category', '');
        $orden = (string) $request->input('sort', 'recent');
        $creador = $request->input('creator');

        $tipo = in_array($tipo, ['all', 'tournaments', 'phases'], true) ? $tipo : 'all';

        /* ------------------------------------------------ los torneos */

        $torneos = null;

        if ($tipo !== 'phases') {

            $consulta = TournamentTemplate::query()
                ->published()
                ->with(['user:id,name,username,avatar'])
                ->withCount(['graphNodes', 'graphStarts', 'graphTerminals', 'graphConnections'])
                ->with([
                    'graphNodes:id,tournament_template_id,phase_template_id,name,sequence_number',
                    'graphNodes.phaseTemplate:id,name,phase_type,settings',
                    'graphStarts:id,tournament_template_id,name,expected_participants,sequence_number',
                    'graphTerminals:id,tournament_template_id,name,terminal_type,expected_participants,sequence_number',
                ])
                ->when($creador, fn ($q) => $q->where('user_id', $creador))
                ->when($categoria, fn ($q) => $q->where('settings->category', $categoria))
                ->when($buscar, fn ($q) => $q->where(fn ($sub) => $sub
                    ->where('name', 'like', "%{$buscar}%")
                    ->orWhere('code', 'like', "%{$buscar}%")
                    ->orWhere('description', 'like', "%{$buscar}%")));

            $this->ordenar($consulta, $orden, 'graph_nodes_count');

            $torneos = $tipo === 'tournaments'
                ? $consulta->paginate(24)->withQueryString()
                : $consulta->limit(self::POR_TIPO)->get();
        }

        /* -------------------------------------------------- las fases */

        $fases = null;

        if ($tipo !== 'tournaments') {

            $consulta = PhaseTemplate::query()
                ->published()
                ->with([
                    'user:id,name,username,avatar',
                    'inputGates' => fn ($q) => $q->where('status', 'ACTIVE')->orderBy('sequence_number'),
                    'exits' => fn ($q) => $q->where('status', 'ACTIVE')->orderBy('sequence_number'),
                ])
                ->withCount(['exits', 'inputGates', 'tournamentPhaseNodes'])
                ->when($creador, fn ($q) => $q->where('user_id', $creador))
                ->when($motor, fn ($q) => $q->where('phase_type', $motor))
                ->when($buscar, fn ($q) => $q->where(fn ($sub) => $sub
                    ->where('name', 'like', "%{$buscar}%")
                    ->orWhere('code', 'like', "%{$buscar}%")
                    ->orWhere('description', 'like', "%{$buscar}%")));

            $this->ordenar($consulta, $orden, 'exits_count');

            $fases = $tipo === 'phases'
                ? $consulta->paginate(24)->withQueryString()
                : $consulta->limit(self::POR_TIPO)->get();
        }

        return view('tournaments.community.index', [
            'tournaments' => $torneos,
            'phases' => $fases,
            'stats' => $this->cifras(),
            'topCreators' => $this->creadoresDestacados(),
            'creator' => $creador ? User::find($creador) : null,
            'q' => $buscar,
            'kind' => $tipo,
            'engine' => $motor,
            'category' => $categoria,
            'sort' => $orden,
        ]);
    }

    /*
     * El orden.
     *
     * `popular` usa las veces que alguien se la ha llevado, que es la única
     * señal honesta que hay aquí: una plantilla que diez personas copiaron
     * les sirvió de algo. `complex` usa el tamaño de la pieza, que es lo que
     * distingue una copa entera de una fase suelta.
     */
    private function ordenar($consulta, string $orden, string $columnaTamano): void
    {
        match ($orden) {
            'popular' => $consulta->orderByDesc('clones_count')->orderByDesc('views_count'),
            'views' => $consulta->orderByDesc('views_count'),
            'name' => $consulta->orderBy('name'),
            'complex' => $consulta->orderByDesc($columnaTamano),
            default => $consulta->orderByDesc('published_at')->orderByDesc('created_at'),
        };
    }

    /* @return array<string,int> */
    private function cifras(): array
    {
        return [
            'tournaments' => TournamentTemplate::query()->published()->count(),
            'phases' => PhaseTemplate::query()->published()->count(),
            'clonable' => TournamentTemplate::query()->published()->where('allow_cloning', true)->count()
                + PhaseTemplate::query()->published()->where('allow_cloning', true)->count(),
            /*
             * Cuenta las dos bibliotecas: quien solo publica fases tambien es
             * alguien que publica, y contando solo torneos el numero salia
             * cero mientras habia gente publicando.
             */
            'creators' => TournamentTemplate::query()->published()->pluck('user_id')
                ->merge(PhaseTemplate::query()->published()->pluck('user_id'))
                ->unique()
                ->count(),
        ];
    }

    /*
     * Quién publica.
     *
     * Un catálogo sin caras es un catálogo. Estos son los que más piezas
     * públicas tienen, para poder entrar por la persona y no solo por la
     * búsqueda.
     */
    private function creadoresDestacados()
    {
        return User::query()
            ->where('status', 'ACTIVE')
            ->where('profile_visibility', 'PUBLIC')
            ->withCount([
                'tournamentTemplates as public_tournaments_count' => fn ($q) => $q->published(),
                'phaseTemplates as public_phases_count' => fn ($q) => $q->published(),
            ])
            ->havingRaw('public_tournaments_count + public_phases_count > 0')
            ->orderByRaw('public_tournaments_count + public_phases_count DESC')
            ->limit(6)
            ->get();
    }

    /*
     * La ficha de un torneo publicado.
     *
     * Enseña el recorrido entero, porque es lo que hay que ver antes de
     * llevárselo: qué fases encadena y de quién son.
     */
    public function tournament(Request $request, TournamentTemplate $tournamentTemplate): View
    {
        abort_unless($tournamentTemplate->isPublished(), 404);

        $tournamentTemplate->load([
            'user:id,name,username,avatar,headline',
            'graphStarts',
            'graphTerminals',
            'graphNodes.phaseTemplate:id,name,phase_type,settings,user_id,visibility,status,published_at',
            'graphNodes.phaseTemplate.user:id,name,username',
        ])->loadCount(['graphNodes', 'graphStarts', 'graphTerminals', 'graphConnections']);

        $this->contarVisita($request, $tournamentTemplate);

        return view('tournaments.community.tournament', [
            'template' => $tournamentTemplate,
            'more' => TournamentTemplate::query()
                ->published()
                ->where('user_id', $tournamentTemplate->user_id)
                ->whereKeyNot($tournamentTemplate->id)
                ->limit(4)
                ->get(),
        ]);
    }

    public function phase(Request $request, PhaseTemplate $phaseTemplate): View
    {
        abort_unless($phaseTemplate->isPublished(), 404);

        $phaseTemplate->load([
            'user:id,name,username,avatar,headline',
            'inputGates' => fn ($q) => $q->where('status', 'ACTIVE')->orderBy('sequence_number'),
            'exits' => fn ($q) => $q->where('status', 'ACTIVE')->orderBy('sequence_number'),
        ])->loadCount(['exits', 'inputGates', 'tournamentPhaseNodes']);

        $this->contarVisita($request, $phaseTemplate);

        return view('tournaments.community.phase', [
            'phase' => $phaseTemplate,
            'more' => PhaseTemplate::query()
                ->published()
                ->where('user_id', $phaseTemplate->user_id)
                ->whereKeyNot($phaseTemplate->id)
                ->limit(4)
                ->get(),
        ]);
    }

    /*
     * El perfil de un creador, visto desde el taller.
     *
     * Deliberadamente parcial: aquí solo se enseña lo que esta persona ha
     * publicado de torneos y de fases. Su biblioteca de entidades vive en la
     * otra comunidad y se enlaza, no se copia.
     */
    public function creator(User $user): View
    {
        abort_unless($user->isActive(), 404);
        abort_unless($user->isPublicProfile() || auth()->user()?->is($user), 404);

        $torneos = TournamentTemplate::query()
            ->published()
            ->where('user_id', $user->id)
            ->withCount(['graphNodes', 'graphTerminals'])
            ->latest('published_at')
            ->get();

        $fases = PhaseTemplate::query()
            ->published()
            ->where('user_id', $user->id)
            ->withCount(['exits', 'inputGates'])
            ->latest('published_at')
            ->get();

        return view('tournaments.community.creator', [
            'creator' => $user,
            'tournaments' => $torneos,
            'phases' => $fases,
            'totals' => [
                'clones' => $torneos->sum('clones_count') + $fases->sum('clones_count'),
                'views' => $torneos->sum('views_count') + $fases->sum('views_count'),
                'engines' => $fases->pluck('phase_type')->unique()->count(),
            ],
        ]);
    }

    /*
     * Una visita se cuenta una vez por sesión y nunca la del dueño.
     *
     * Sin eso el contador mide recargas, no interés, y el propio autor sería
     * su mejor público.
     */
    private function contarVisita(Request $request, $pieza): void
    {
        if ($request->user()?->id === $pieza->user_id) {
            return;
        }

        $clave = 'visto:' . class_basename($pieza) . ':' . $pieza->id;

        if ($request->session()->has($clave)) {
            return;
        }

        $request->session()->put($clave, true);

        $pieza->increment('views_count');
    }
}
