<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Models\PhaseExit;
use App\Models\PhaseInputGate;
use App\Models\PhaseTemplate;
use App\Models\TournamentInstance;
use App\Models\TournamentTemplate;
use App\Models\UniverseTournament;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/*
 * El taller de torneos, de un vistazo.
 *
 * Un panel que solo cuenta cuántas cosas hay no sirve para decidir qué hacer
 * a continuación. Este responde a cuatro preguntas distintas, y por eso está
 * partido en cuatro bloques de datos:
 *
 *   qué hay          las cifras del taller
 *   qué falta        lo que está a medio terminar y no se puede jugar
 *   qué está vivo    las plantillas que ya sostienen competiciones reales
 *   qué tocaste      por dónde ibas la última vez
 *
 * Todo se pide con agregados y `withCount`: el panel no puede permitirse una
 * consulta por ficha.
 */
class TournamentDashboardController extends Controller
{
    public function __invoke(
        Request $request
    ): View {
        $this->authorize('viewAny', TournamentTemplate::class);
        $this->authorize('viewAny', PhaseTemplate::class);

        $user = $request->user();

        $torneos = fn () => TournamentTemplate::query()->ownedBy($user);
        $fases = fn () => PhaseTemplate::query()->ownedBy($user);

        return view('tournaments.dashboard', [
            'statistics' => $this->cifras($user, $torneos, $fases),
            'pending' => $this->queFalta($torneos, $fases),
            'engines' => $this->motores($fases),
            'categories' => $this->tipos($torneos),
            'live' => $this->enJuego($user),
            'recent' => $this->loUltimo($torneos, $fases),
            'mostUsed' => $this->masReutilizadas($fases),
        ]);
    }

    /*
     * Qué hay en el taller.
     *
     * `phase_exits` y `phase_gates` se cuentan sobre las tablas hijas porque
     * son las piezas que de verdad conectan un torneo: una fase sin salidas
     * no enlaza con nada.
     *
     * @return array<string,int>
     */
    private function cifras($user, callable $torneos, callable $fases): array
    {
        return [
            'tournaments' => $torneos()->count(),

            'active_tournaments' => $torneos()->where('status', 'ACTIVE')->count(),

            'phases' => $fases()->count(),

            'active_phases' => $fases()->where('status', 'ACTIVE')->count(),

            'public' => $torneos()->where('visibility', 'PUBLIC')->count()
                + $fases()->where('visibility', 'PUBLIC')->count(),

            'phase_exits' => PhaseExit::query()
                ->whereHas('phaseTemplate', fn ($q) => $q->where('user_id', $user->id))
                ->count(),

            'phase_gates' => PhaseInputGate::query()
                ->whereHas('phaseTemplate', fn ($q) => $q->where('user_id', $user->id))
                ->count(),

            /* Competiciones reales jugadas con plantillas de este usuario */
            'competitions' => TournamentInstance::query()
                ->whereHas('tournamentTemplate', fn ($q) => $q->where('user_id', $user->id))
                ->count(),

            'running' => TournamentInstance::query()
                ->whereHas('tournamentTemplate', fn ($q) => $q->where('user_id', $user->id))
                ->where('status', 'RUNNING')
                ->count(),
        ];
    }

    /*
     * Qué falta por terminar.
     *
     * Esta es la parte que de verdad decide en qué trabajar. No son avisos
     * de estilo: cada línea describe algo que impide jugar, o una pieza que
     * se quedó suelta. Cada una lleva el enlace a la biblioteca ya filtrada
     * para poder ir directo a arreglarlo.
     *
     * El orden es por gravedad: primero lo que rompe, después lo que sobra.
     *
     * @return array<int,array<string,mixed>>
     */
    private function queFalta(callable $torneos, callable $fases): array
    {
        $olvido = Carbon::now()->subDays(30);

        $lineas = [
            [
                'severity' => 'error',
                'title' => 'Torneos sin ninguna fase',
                'detail' => 'No hay nada que jugar: el recorrido está vacío.',
                'count' => $torneos()->doesntHave('graphNodes')->count(),
                'url' => route('tournaments.templates.index', ['sort' => 'phases_asc']),
                'action' => 'Ver los torneos',
            ],
            [
                'severity' => 'error',
                'title' => 'Torneos sin ningún final',
                'detail' => 'Nadie llega a ninguna parte: falta al menos un terminal.',
                'count' => $torneos()->doesntHave('graphTerminals')->count(),
                'url' => route('tournaments.templates.index'),
                'action' => 'Ver los torneos',
            ],
            [
                'severity' => 'error',
                'title' => 'Torneos sin ninguna entrada',
                'detail' => 'Nadie puede empezar: falta por dónde entra la gente.',
                'count' => $torneos()->doesntHave('graphStarts')->count(),
                'url' => route('tournaments.templates.index'),
                'action' => 'Ver los torneos',
            ],
            [
                'severity' => 'warning',
                'title' => 'Fases sin salidas',
                'detail' => 'Se juegan, pero desde ellas no avanza nadie a ninguna parte.',
                'count' => $fases()->doesntHave('exits')->count(),
                'url' => route('tournaments.phase-templates.index', ['sort' => 'exits_desc']),
                'action' => 'Ver las fases',
            ],
            [
                'severity' => 'warning',
                'title' => 'Fases sin puertas de entrada',
                'detail' => 'Ningún torneo puede conectarles gente todavía.',
                'count' => $fases()->doesntHave('inputGates')->count(),
                'url' => route('tournaments.phase-templates.index'),
                'action' => 'Ver las fases',
            ],
            [
                'severity' => 'info',
                'title' => 'Fases que no usa ningún torneo',
                'detail' => 'Están hechas y esperando: encájalas en algún recorrido.',
                'count' => $fases()->doesntHave('tournamentPhaseNodes')->count(),
                'url' => route('tournaments.phase-templates.index'),
                'action' => 'Ver las fases',
            ],
            [
                'severity' => 'info',
                'title' => 'Borradores parados',
                'detail' => 'Sin tocar desde hace más de un mes. O se terminan, o se archivan.',
                'count' => $torneos()->where('status', 'DRAFT')->where('updated_at', '<', $olvido)->count()
                    + $fases()->where('status', 'DRAFT')->where('updated_at', '<', $olvido)->count(),
                'url' => route('tournaments.templates.index', ['status' => 'DRAFT', 'sort' => 'oldest']),
                'action' => 'Ver los borradores',
            ],
        ];

        /* Lo que ya está bien no ocupa sitio */
        return array_values(array_filter($lineas, fn ($linea) => $linea['count'] > 0));
    }

    /*
     * De qué están hechas las fases.
     *
     * Un reparto por motor dice más que el total: doce fases de eliminación
     * directa y ninguna de grupos es un taller con un hueco.
     *
     * @return array<int,array<string,mixed>>
     */
    private function motores(callable $fases): array
    {
        $conteo = $fases()
            ->selectRaw('phase_type, count(*) as total')
            ->groupBy('phase_type')
            ->pluck('total', 'phase_type');

        $catalogo = [
            'SINGLE_ELIMINATION' => ['Eliminación directa', 'amber', '🏆'],
            'ROUND_ROBIN' => ['Todos contra todos', 'cyan', '🔄'],
            'GROUP_STAGE' => ['Fase de grupos', 'violet', '▦'],
            'SWISS' => ['Sistema suizo', 'emerald', '⇄'],
            'LEAGUE' => ['Liga / División', 'sky', '≡'],
            'CUSTOM' => ['Personalizada', 'slate', '◇'],
        ];

        $total = max(1, (int) $conteo->sum());

        $salida = [];

        foreach ($catalogo as $clave => [$etiqueta, $color, $icono]) {

            $valor = (int) ($conteo[$clave] ?? 0);

            /* Los motores que no existen todavía no se enseñan vacíos */
            if ($valor === 0 && in_array($clave, ['LEAGUE', 'CUSTOM'], true)) {
                continue;
            }

            $salida[] = [
                'key' => $clave,
                'label' => $etiqueta,
                'accent' => $color,
                'icon' => $icono,
                'count' => $valor,
                'share' => (int) round($valor / $total * 100),
                'url' => route('tournaments.phase-templates.index', ['type' => $clave]),
            ];
        }

        return $salida;
    }

    /*
     * Y de qué clase son los torneos.
     *
     * La categoría vive en `settings`, así que se cuenta en PHP sobre una
     * proyección mínima en vez de con un GROUP BY sobre JSON: son decenas de
     * filas, no millones, y así funciona igual en MySQL y en SQLite.
     *
     * @return array<int,array<string,mixed>>
     */
    private function tipos(callable $torneos): array
    {
        $plantillas = $torneos()->get(['id', 'settings']);

        $total = max(1, $plantillas->count());

        $conteo = $plantillas
            ->groupBy(fn ($plantilla) => $plantilla->category ?? 'NONE')
            ->map->count();

        $catalogo = TournamentTemplate::CATEGORIES + ['NONE' => 'Sin clasificar'];

        $colores = [
            'CUP' => 'amber',
            'LEAGUE' => 'cyan',
            'QUALIFIER' => 'sky',
            'FRIENDLY' => 'emerald',
            'RANKING' => 'violet',
            'SPECIAL' => 'rose',
            'NONE' => 'slate',
        ];

        $salida = [];

        foreach ($catalogo as $clave => $etiqueta) {

            $valor = (int) ($conteo[$clave] ?? 0);

            if ($valor === 0) {
                continue;
            }

            $salida[] = [
                'key' => $clave,
                'label' => $etiqueta,
                'accent' => $colores[$clave] ?? 'slate',
                'count' => $valor,
                'share' => (int) round($valor / $total * 100),
                'url' => route('tournaments.templates.index', $clave === 'NONE' ? [] : ['category' => $clave]),
            ];
        }

        usort($salida, fn ($a, $b) => $b['count'] <=> $a['count']);

        return $salida;
    }

    /*
     * Qué está vivo ahora mismo.
     *
     * Una plantilla deja de ser un ejercicio cuando alguien la juega. Aquí se
     * enseñan las competiciones reales montadas sobre plantillas de este
     * usuario, las que están en curso primero, porque son las únicas en las
     * que se puede intervenir.
     *
     * @return array<string,mixed>
     */
    private function enJuego($user): array
    {
        $competiciones = TournamentInstance::query()
            ->whereHas('tournamentTemplate', fn ($q) => $q->where('user_id', $user->id))
            ->with([
                'universe:id,name,code',
                'tournamentTemplate:id,name,code,settings',
            ])
            ->orderByRaw("CASE status WHEN 'RUNNING' THEN 0 WHEN 'DRAFT' THEN 1 ELSE 2 END")
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $universos = UniverseTournament::query()
            ->whereHas('tournamentTemplate', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        return [
            'competitions' => $competiciones,
            'universe_tournaments' => $universos,
        ];
    }

    /*
     * Por dónde iba.
     *
     * Ordenado por última MODIFICACIÓN y no por creación: lo que interesa al
     * volver es lo que se estaba tocando, no lo que se creó primero. Las dos
     * bibliotecas se mezclan en una sola lista porque el trabajo se hace
     * saltando de una a otra.
     *
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    private function loUltimo(callable $torneos, callable $fases)
    {
        $plantillas = $torneos()
            ->withCount(['graphNodes', 'graphTerminals'])
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn ($t) => [
                'kind' => 'torneo',
                'name' => $t->name,
                'code' => $t->code,
                'icon' => $t->display_icon,
                'accent' => $t->accent,
                'image' => $t->image_url,
                'status' => $t->status,
                'status_label' => $t->status_label,
                'detail' => $t->graph_nodes_count . ' fases · ' . $t->graph_terminals_count . ' finales',
                'url' => route('tournaments.templates.show', $t),
                'updated_at' => $t->updated_at,
            ]);

        $piezas = $fases()
            ->withCount(['exits', 'tournamentPhaseNodes'])
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn ($f) => [
                'kind' => 'fase',
                'name' => $f->name,
                'code' => $f->code,
                'icon' => $f->display_icon,
                'accent' => $f->accent,
                'image' => $f->image_url,
                'status' => $f->status,
                'status_label' => $f->status,
                'detail' => $f->type_label . ' · ' . $f->exits_count . ' salidas',
                'url' => route('tournaments.phase-templates.show', $f),
                'updated_at' => $f->updated_at,
            ]);

        return $plantillas
            ->concat($piezas)
            ->sortByDesc('updated_at')
            ->take(6)
            ->values();
    }

    /*
     * Las fases que más se repiten.
     *
     * Una fase usada por cinco torneos es una pieza de infraestructura: si se
     * toca, se tocan cinco recorridos. Verlo aquí evita el susto.
     */
    private function masReutilizadas(callable $fases)
    {
        return $fases()
            ->withCount('tournamentPhaseNodes')
            ->having('tournament_phase_nodes_count', '>', 0)
            ->orderByDesc('tournament_phase_nodes_count')
            ->limit(4)
            ->get();
    }
}
