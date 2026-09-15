<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Collection;
use App\Models\Entity;
use App\Models\PhaseTemplate;
use App\Models\TournamentTemplate;
use App\Services\Community\CreatorDirectory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| CommunityHomeController
|--------------------------------------------------------------------------
|
| La puerta de la Comunidad.
|
| Antes «Comunidad» era un enlace que caía directamente en el explorador de
| la Biblioteca, y la mitad de lo que se comparte —las plantillas de torneo
| y de fase— vivía en otro módulo sin que desde aquí se supiera. Esta página
| mira las dos mitades a la vez: qué se ha publicado, qué se copia, quién lo
| hace y qué es lo tuyo dentro de todo eso.
|
| `buscar` es un buscador para las cinco clases de pieza y para las personas.
| Responde JSON al buscador en vivo de la portada y una página a quien envía
| el formulario.
|
| Ver docs/md/78-Comunidad.md
|
*/

class CommunityHomeController extends Controller
{
    /* [singular, plural, color, icono] */
    public const TIPOS = [
        'entidad' => ['Entidad', 'Entidades', '#818cf8', 'libro'],
        'coleccion' => ['Colección', 'Colecciones', '#34d399', 'capas'],
        'atributo' => ['Atributo', 'Atributos', '#22d3ee', 'panel'],
        'torneo' => ['Torneo', 'Torneos', '#fbbf24', 'trofeo'],
        'fase' => ['Fase', 'Fases', '#f472b6', 'grafo'],
    ];

    public function __construct(
        private readonly CreatorDirectory $directorio
    ) {}


    public function __invoke(Request $request): View
    {
        $yo = $request->user();

        /* ---------------------------------------------- lo que hay */

        $cifras = [];
        $muestras = [];

        foreach (array_keys(self::TIPOS) as $tipo) {
            $cifras[$tipo] = $this->publicos($tipo)->count();

            $muestras[$tipo] = $this->publicos($tipo)
                ->whereNotNull('image')
                ->latest('published_at')
                ->limit(4)
                ->get()
                ->map(fn($m) => $this->pieza($tipo, $m));
        }

        $copiasTotales = collect(array_keys(self::TIPOS))
            ->sum(fn($tipo) => (int) $this->publicos($tipo)->sum('clones_count'));


        /* ------------------------------------ lo último y lo copiado */

        $recientes = collect();
        $copiados = collect();

        foreach (array_keys(self::TIPOS) as $tipo) {
            $recientes = $recientes->merge(
                $this->publicos($tipo)
                    ->with('user:id,name,username,avatar')
                    ->latest('published_at')
                    ->limit(12)
                    ->get()
                    ->map(fn($m) => $this->pieza($tipo, $m))
            );

            $copiados = $copiados->merge(
                $this->publicos($tipo)
                    ->with('user:id,name,username,avatar')
                    ->where('clones_count', '>', 0)
                    ->orderByDesc('clones_count')
                    ->limit(8)
                    ->get()
                    ->map(fn($m) => $this->pieza($tipo, $m))
            );
        }

        $recientes = $recientes
            ->sortByDesc(fn($p) => $p['fecha']?->timestamp ?? 0)
            ->take(18)
            ->values();

        $copiados = $copiados
            ->sortByDesc('copias')
            ->take(8)
            ->values();


        /* ------------------------------------------------- la gente */

        $creadores = $this->directorio->creadores();

        $creadoresPorTipo = collect(array_keys(CreatorDirectory::TIPOS))
            ->mapWithKeys(fn($tipo) => [
                $tipo => $creadores->where('tipo_creador', $tipo)->sortByDesc('pub_total')->take(4)->values(),
            ]);


        /* --------------------------------------------------- lo tuyo */

        $huella = [];

        foreach (array_keys(self::TIPOS) as $tipo) {
            $modelo = $this->modelo($tipo);

            $huella[$tipo] = [
                'publicado' => $this->publicos($tipo)->where('user_id', $yo->id)->count(),
                'total' => $modelo::query()->where('user_id', $yo->id)->count(),
                'copias' => (int) $modelo::query()->where('user_id', $yo->id)->sum('clones_count'),
                'vistas' => (int) $modelo::query()->where('user_id', $yo->id)->sum('views_count'),
            ];
        }

        return view('community.home.index', [
            'yo' => $yo,
            'tipos' => self::TIPOS,
            'cifras' => $cifras,
            'muestras' => $muestras,
            'copiasTotales' => $copiasTotales,
            'recientes' => $recientes,
            'copiados' => $copiados,
            'creadores' => $creadores,
            'creadoresPorTipo' => $creadoresPorTipo,
            'tiposCreador' => CreatorDirectory::TIPOS,
            'carasCreadores' => $this->directorio->caras($creadoresPorTipo->flatten()->pluck('id')),
            'mosaico' => $this->publicos('entidad')->whereNotNull('image')->latest('published_at')->limit(32)->get(),
            'huella' => $huella,
            'miTipo' => $this->directorio->resumen($yo)['tipo'],
        ]);
    }


    public function buscar(Request $request): View|JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        $grupos = mb_strlen($q) < 2 ? [] : $this->resultados($q);

        if ($request->expectsJson()) {
            return response()->json(['q' => $q, 'grupos' => $grupos]);
        }

        return view('community.home.buscar', [
            'q' => $q,
            'grupos' => $grupos,
            'total' => collect($grupos)->sum('total'),
        ]);
    }


    /*
     * Seis de cada clase, con cuántas hay en total y a dónde ir a verlas todas:
     * el buscador no sustituye a los exploradores, lleva a ellos ya filtrados.
     */
    private function resultados(string $q): array
    {
        $grupos = [];
        $like = '%' . addcslashes($q, '%_\\') . '%';

        foreach (self::TIPOS as $tipo => [, $plural, $tono, $icono]) {
            $consulta = $this->publicos($tipo)
                ->where(fn($sub) => $sub->where('name', 'like', $like)->orWhere('code', 'like', $like));

            $grupos[] = [
                'clave' => $tipo,
                'etiqueta' => $plural,
                'tono' => $tono,
                'icono' => $icono,
                'total' => (clone $consulta)->count(),
                'ver_todo' => $this->verTodo($tipo, $q),
                'items' => $consulta
                    ->with('user:id,name,username,avatar')
                    ->orderByDesc('clones_count')
                    ->limit(6)
                    ->get()
                    ->map(fn($m) => $this->pieza($tipo, $m))
                    ->all(),
            ];
        }

        $personas = $this->directorio->creadores()->filter(
            fn($u) => str_contains(mb_strtolower($u->name . ' ' . $u->username), mb_strtolower($q))
        );

        $grupos[] = [
            'clave' => 'creador',
            'etiqueta' => 'Creadores',
            'tono' => '#34d399',
            'icono' => 'usuario',
            'total' => $personas->count(),
            'ver_todo' => route('community.creators.index', ['q' => $q]),
            'items' => $personas->sortByDesc('pub_total')->take(6)->map(fn($u) => [
                'tipo' => 'creador',
                'etiqueta' => CreatorDirectory::TIPOS[$u->tipo_creador][0],
                'tono' => CreatorDirectory::TIPOS[$u->tipo_creador][1],
                'nombre' => $u->name,
                'img' => $u->avatar_url,
                'url' => route('profiles.show', $u->username),
                'autor' => null,
                'pie' => '@' . $u->username . ' · ' . $u->pub_total . ' publicadas',
                'copias' => $u->copias_total,
            ])->values()->all(),
        ];

        return $grupos;
    }


    private function publicos(string $tipo): Builder
    {
        $biblioteca = fn(Builder $q, string $columna = 'visibility') => $q
            ->where($columna, 'PUBLIC')
            ->where('status', 'ACTIVE')
            ->whereNotNull('published_at');

        return match ($tipo) {
            'entidad' => $biblioteca(Entity::query()),
            'coleccion' => $biblioteca(Collection::query()),
            'atributo' => $biblioteca(Attribute::query(), 'scope'),
            'torneo' => TournamentTemplate::query()->published(),
            'fase' => PhaseTemplate::query()->published(),
        };
    }


    /** @return class-string<Model> */
    private function modelo(string $tipo): string
    {
        return match ($tipo) {
            'entidad' => Entity::class,
            'coleccion' => Collection::class,
            'atributo' => Attribute::class,
            'torneo' => TournamentTemplate::class,
            'fase' => PhaseTemplate::class,
        };
    }


    private function verTodo(string $tipo, string $q = ''): string
    {
        return match ($tipo) {
            'entidad' => route('community.index', array_filter(['tab' => 'entities', 'search' => $q])),
            'coleccion' => route('community.index', array_filter(['tab' => 'collections', 'search' => $q])),
            'atributo' => route('community.index', array_filter(['tab' => 'attributes', 'search' => $q])),
            'torneo' => route('tournaments.community.index', array_filter(['kind' => 'tournaments', 'q' => $q])),
            'fase' => route('tournaments.community.index', array_filter(['kind' => 'phases', 'q' => $q])),
        };
    }


    /*
     * Una pieza cualquiera, dicha igual venga de donde venga. Arrays y no
     * modelos: la misma forma sirve a la vista y al JSON del buscador.
     */
    private function pieza(string $tipo, Model $m): array
    {
        [$singular, , $tono, $icono] = self::TIPOS[$tipo];

        $fecha = $m->published_at ? Carbon::parse($m->published_at) : null;

        return [
            'tipo' => $tipo,
            'etiqueta' => $singular,
            'tono' => $tono,
            'icono' => $icono,
            'nombre' => $m->name,
            'img' => $m->image_url,
            'url' => match ($tipo) {
                'entidad' => route('community.entities.show', $m),
                'coleccion' => route('community.collections.show', $m),
                'atributo' => route('community.attributes.show', $m),
                'torneo' => route('tournaments.community.tournament', $m),
                'fase' => route('tournaments.community.phase', $m),
            },
            'autor' => $m->relationLoaded('user') && $m->user ? [
                'nombre' => $m->user->name,
                'usuario' => $m->user->username,
                'avatar' => $m->user->avatar_url,
                'url' => route('profiles.show', $m->user->username),
            ] : null,
            'pie' => $fecha?->diffForHumans(),
            'fecha' => $fecha,
            'copias' => (int) $m->clones_count,
            'vistas' => (int) $m->views_count,
        ];
    }
}
