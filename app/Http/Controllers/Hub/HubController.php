<?php

namespace App\Http\Controllers\Hub;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Collection as CollectionModel;
use App\Models\Entity;
use App\Models\PhaseTemplate;
use App\Models\TournamentInstance;
use App\Models\TournamentTemplate;
use App\Models\Universe;
use App\Models\UniverseEntity;
use App\Services\Admin\ContentFlags;
use App\Services\Tournaments\Runtime\CompetitorFaces;
use App\Support\Site\CommunityModeration;
use App\Support\Site\SiteSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| HubController
|--------------------------------------------------------------------------
|
| La puerta de entrada de OmniMerge.
|
| Lo que habia aqui eran contadores y seis tarjetas de modulo con un emoji
| cada una, un texto generico y dos promesas caducadas: el saludo decia que
| «mas adelante» habria universos y torneos -que llevan tiempo hechos- y una
| tarjeta anunciaba «Rankings y analitica, proximamente», cuando la
| clasificacion de universo existe y se usa.
|
| Lo que tiene que contestar esta pantalla es otra cosa: que hay dentro de
| cada modulo AHORA, con sus caras y sus numeros de verdad, y donde hace
| falta que entres. Un modulo se reconoce por lo que contiene, no por su
| descripcion.
|
| Ver docs/md/74-Centro-OmniMerge.md
|
*/

class HubController extends Controller
{
    public function __invoke(
        Request $request
    ): View {

        $user = $request->user();


        /*
        |--------------------------------------------------------------------------
        | Lo que hay en cada modulo
        |--------------------------------------------------------------------------
        */

        $statistics = [

            /* Biblioteca */
            'entities' => $user->entities()->count(),
            'entity_types' => $user->entityTypes()->count(),
            'attributes' => $user->attributes()->count(),
            'catalog_values' => $user->attributeOptions()->count(),
            'collections' => $user->collections()->count(),
            'versions' => $user->versions()->count(),

            /* Torneos */
            'tournaments' => $user->tournamentTemplates()->count(),
            'phases' => $user->phaseTemplates()->count(),

            /* Universos */
            'universes' => $user->universes()->count(),
        ];

        $mundos = $user->universes()->pluck('id');

        $statistics['inhabitants'] =
            UniverseEntity::query()->whereIn('universe_id', $mundos)->count();

        $competiciones =
            TournamentInstance::query()
            ->whereIn('universe_id', $mundos)
            ->get(['id', 'universe_id', 'status', 'runtime_status']);

        $statistics['competitions'] = $competiciones->count();

        $statistics['live'] =
            $competiciones->whereIn('status', ['RUNNING', 'PAUSED'])->count();

        $statistics['stuck'] =
            $competiciones->whereIn('runtime_status', ['BLOCKED', 'AWAITING_DECISION'])->count();

        /* Comunidad */
        $statistics['public'] =
            $user->entities()->where('visibility', 'PUBLIC')->count()
            + $user->collections()->where('visibility', 'PUBLIC')->count()
            + $user->attributes()->where('scope', 'PUBLIC')->count();

        $statistics['brought'] =
            $user->entities()->whereNotNull('source_entity_id')->count();

        $statistics['copied_from_me'] =
            Entity::query()
            ->whereIn('source_entity_id', $user->entities()->select('id'))
            ->where('user_id', '!=', $user->id)
            ->count();

        $statistics['total'] =
            $statistics['entities']
            + $statistics['attributes']
            + $statistics['collections']
            + $statistics['tournaments']
            + $statistics['phases']
            + $statistics['universes'];


        /*
        |--------------------------------------------------------------------------
        | Lo que espera por ti, en toda la cuenta
        |--------------------------------------------------------------------------
        |
        | Mismos criterios que los paneles de dentro. Un aviso aqui solo entra si
        | lleva a un sitio donde se puede resolver: uno que no, es una queja.
        */

        $atencion = collect();

        $paradas = $competiciones->whereIn('runtime_status', ['BLOCKED', 'AWAITING_DECISION']);

        if ($paradas->isNotEmpty()) {

            $atencion->push([
                'tono' => '#fb7185',
                'urgente' => true,
                'cuantos' => $paradas->count(),
                'titulo' => $paradas->count() === 1
                    ? 'Una competición está parada esperándote'
                    : $paradas->count() . ' competiciones están paradas esperándote',
                'texto' => 'No avanzarán solas: necesitan una decisión tuya.',
                'donde' => 'Universos',
                'url' => route('universes.dashboard'),
            ]);
        }

        $listas = $competiciones->where('status', 'DRAFT');

        if ($listas->isNotEmpty()) {

            $atencion->push([
                'tono' => '#60a5fa',
                'urgente' => false,
                'cuantos' => $listas->count(),
                'titulo' => $listas->count() === 1
                    ? 'Una competición está lista y sin empezar'
                    : $listas->count() . ' competiciones están listas y sin empezar',
                'texto' => 'Tienen sus participantes puestos; solo falta darles al play.',
                'donde' => 'Universos',
                'url' => route('universes.dashboard'),
            ]);
        }

        $vacios = $user->universes()->whereDoesntHave('entities')->count();

        if ($vacios > 0) {

            $atencion->push([
                'tono' => '#94a3b8',
                'urgente' => false,
                'cuantos' => $vacios,
                'titulo' => $vacios === 1
                    ? 'Un mundo está vacío'
                    : $vacios . ' mundos están vacíos',
                'texto' => 'Sin habitantes no puede jugarse nada en ellos.',
                'donde' => 'Universos',
                'url' => route('universes.index') . '?con=vacios',
            ]);
        }

        /*
         * Salud de la Biblioteca. Una entidad sin imagen no es un error, pero
         * es un hueco en todas las pantallas que la enseñan.
         */
        $sinImagen = $user->entities()->whereNull('image')->count();

        if ($sinImagen > 0) {

            $atencion->push([
                'tono' => '#a78bfa',
                'urgente' => false,
                'cuantos' => $sinImagen,
                'titulo' => $sinImagen === 1
                    ? 'Una entidad no tiene imagen'
                    : $sinImagen . ' entidades no tienen imagen',
                'texto' => 'Salen como un hueco en el mapa, en los torneos y en la comunidad.',
                'donde' => 'Biblioteca',
                'url' => route('entities.index'),
            ]);
        }

        /*
         * La columna es data_type, no type, y el valor que usa catalogo es
         * OPTION: es lo que mira Attribute::usesCatalog().
         */
        $catalogosVacios =
            $user->attributes()
            ->where('data_type', 'OPTION')
            ->whereDoesntHave('options')
            ->count();

        if ($catalogosVacios > 0) {

            $atencion->push([
                'tono' => '#22d3ee',
                'urgente' => false,
                'cuantos' => $catalogosVacios,
                'titulo' => $catalogosVacios === 1
                    ? 'Un atributo de catálogo no tiene valores'
                    : $catalogosVacios . ' atributos de catálogo no tienen valores',
                'texto' => 'No se pueden rellenar hasta que tengan algo entre lo que elegir.',
                'donde' => 'Biblioteca',
                'url' => route('attributes.index'),
            ]);
        }

        $plantillasSinUsar =
            $user->tournamentTemplates()
            ->whereDoesntHave('universeTournaments')
            ->count();

        if ($plantillasSinUsar > 0 && $statistics['universes'] > 0) {

            $atencion->push([
                'tono' => '#fbbf24',
                'urgente' => false,
                'cuantos' => $plantillasSinUsar,
                'titulo' => $plantillasSinUsar === 1
                    ? 'Una plantilla de torneo no se ha usado nunca'
                    : $plantillasSinUsar . ' plantillas de torneo no se han usado nunca',
                'texto' => 'Están diseñadas y ningún universo las ha adoptado todavía.',
                'donde' => 'Torneos',
                'url' => route('tournaments.templates.index'),
            ]);
        }

        $atencion = $atencion->sortByDesc('urgente')->values();


        /*
        |--------------------------------------------------------------------------
        | Las caras de cada modulo
        |--------------------------------------------------------------------------
        |
        | Un modulo se reconoce por lo que contiene. Estas tiras son lo que
        | convierte «Biblioteca · 22 entidades» en algo que se mira.
        */

        $carasBiblioteca =
            $user->entities()
            ->whereNotNull('image')
            ->latest()
            ->limit(10)
            ->get(['id', 'name', 'image']);

        $carasUniversos =
            $user->universes()
            ->withCount([
                'entities',
                'tournamentInstances as vivas_count' => fn($q) =>
                $q->whereIn('status', ['RUNNING', 'PAUSED']),
                'tournamentInstances as jugadas_count' => fn($q) =>
                $q->where('status', 'COMPLETED'),
                'seasons',
            ])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $carasTorneos =
            $user->tournamentTemplates()
            ->latest()
            ->limit(6)
            ->get(['id', 'name', 'image']);

        $carasComunidad =
            $user->entities()
            ->where('visibility', 'PUBLIC')
            ->whereNotNull('image')
            ->limit(8)
            ->get(['id', 'name', 'image']);


        /*
        |--------------------------------------------------------------------------
        | Lo ultimo que has tocado, de todo
        |--------------------------------------------------------------------------
        |
        | Con su imagen de verdad, no con un icono generico: lo que se busca al
        | volver es reconocer la cosa.
        */

        $reciente =
            collect()
            ->concat($this->mapear(
                $user->entities()->latest()->limit(6)->get(),
                'Entidad',
                '#818cf8',
                fn($e) => route('entities.show', $e)
            ))
            ->concat($this->mapear(
                $user->attributes()->latest()->limit(4)->get(),
                'Atributo',
                '#22d3ee',
                fn($a) => route('attributes.show', $a)
            ))
            ->concat($this->mapear(
                $user->collections()->latest()->limit(4)->get(),
                'Colección',
                '#34d399',
                fn($c) => route('collections.show', $c)
            ))
            ->concat($this->mapear(
                $user->tournamentTemplates()->latest()->limit(4)->get(),
                'Torneo',
                '#fbbf24',
                fn($t) => route('tournaments.templates.show', $t)
            ))
            ->concat($this->mapear(
                $user->phaseTemplates()->latest()->limit(4)->get(),
                'Fase',
                '#f472b6',
                fn($p) => route('tournaments.phase-templates.show', $p)
            ))
            ->concat($this->mapear(
                $user->universes()->latest()->limit(3)->get(),
                'Universo',
                '#a78bfa',
                fn($u) => route('universes.show', $u)
            ))
            ->sortByDesc('cuando')
            ->take(18)
            ->values();


        $mosaico =
            $user->entities()
            ->whereNotNull('image')
            ->inRandomOrder()
            ->limit(36)
            ->get(['id', 'image']);

        /*
        |--------------------------------------------------------------------------
        | Lo que se está jugando, y quién ganó lo último
        |--------------------------------------------------------------------------
        |
        | Una competición en marcha es lo más vivo de la cuenta: se enseña con
        | su portada (o la de su mundo), cuánto lleva jugado y un botón para
        | seguir. Las terminadas, con la cara de su campeón.
        */

        $enJuego =
            TournamentInstance::query()
            ->whereIn('universe_id', $mundos)
            ->whereIn('status', ['RUNNING', 'PAUSED', 'DRAFT'])
            ->with('universe:id,name,image,slug')
            ->withCount([
                'matches',
                'matches as jugados_count' => fn($q) => $q->where('status', 'COMPLETED'),
            ])
            ->orderByRaw("FIELD(runtime_status, 'BLOCKED', 'AWAITING_DECISION') DESC")
            ->orderByRaw("FIELD(status, 'RUNNING', 'PAUSED', 'DRAFT')")
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $campeones =
            TournamentInstance::query()
            ->whereIn('universe_id', $mundos)
            ->where('status', 'COMPLETED')
            ->with('universe:id,name,image,slug')
            ->latest('completed_at')
            ->limit(4)
            ->get()
            ->map(function (TournamentInstance $instancia) {
                $campeon = $instancia->participants()->where('placement', 1)->first();

                return [
                    'instancia' => $instancia,
                    'nombre' => $campeon?->name,
                    'cara' => $campeon
                        ? CompetitorFaces::byRuntimeKey($instancia->id, $campeon->runtime_key)
                        : null,
                    'victorias' => $campeon?->wins,
                ];
            });


        /*
        |--------------------------------------------------------------------------
        | Lo que se mira en la comunidad
        |--------------------------------------------------------------------------
        |
        | Lo público de otras personas que más visitas tiene, sin lo que un
        | admin ha ocultado ni lo de cuentas bloqueadas. Si la comunidad está
        | cerrada, no se enseña.
        */

        $tendencias = collect();

        if (app(SiteSettings::class)->get('community_open')) {
            $flags = app(ContentFlags::class);

            foreach (['entity' => Entity::class, 'collection' => CollectionModel::class] as $tipo => $clase) {
                $tendencias = $tendencias->concat(
                    $clase::query()
                        ->where('user_id', '!=', $user->id)
                        ->where('visibility', 'PUBLIC')
                        ->whereNotNull('image')
                        ->whereIn('user_id', CommunityModeration::cuentasVisibles())
                        ->whereNotIn('id', $flags->hiddenIds($tipo))
                        ->with('user')
                        ->orderByDesc('views_count')
                        ->limit(6)
                        ->get()
                        ->map(fn($cosa) => [
                            'tipo' => $tipo,
                            'etiqueta' => $tipo === 'entity' ? 'Entidad' : 'Colección',
                            'modelo' => $cosa,
                            'url' => $tipo === 'entity'
                                ? route('community.entities.show', $cosa)
                                : route('community.collections.show', $cosa),
                        ])
                );
            }

            /* Filas completas de cuatro: ocho si hay, si no cuatro */
            $tendencias = $tendencias->sortByDesc(fn($t) => $t['modelo']->views_count)->values();
            $tendencias = $tendencias->take($tendencias->count() >= 8 ? 8 : 4);
        }

        return view(
            'hub.index',
            compact(
                'statistics',
                'atencion',
                'carasBiblioteca',
                'carasUniversos',
                'carasTorneos',
                'carasComunidad',
                'reciente',
                'mosaico',
                'enJuego',
                'campeones',
                'tendencias'
            )
        );
    }


    /*
     * El buscador del Centro: todo lo tuyo, de los cuatro modulos, en una
     * sola caja. El de la Biblioteca solo buscaba en la Biblioteca; aqui se
     * encuentra tambien un mundo, un torneo o una competicion por su nombre.
     */
    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = trim((string) $request->input('q'));

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $like = fn($query) => $query->where(fn($w) => $w
            ->where('name', 'like', "%{$q}%")
            ->orWhere('code', 'like', "%{$q}%"));

        $mundos = $user->universes()->pluck('id');

        $grupos = [
            ['Entidad', '#818cf8', 'libro', $like($user->entities()->with('entityType'))->limit(6)->get(),
                fn($e) => route('entities.show', $e), fn($e) => $e->entityType?->name ?? 'Sin tipo'],
            ['Colección', '#34d399', 'capas', $like($user->collections())->limit(4)->get(),
                fn($c) => route('collections.show', $c), fn($c) => $c->code],
            ['Atributo', '#22d3ee', 'controles', $like($user->attributes())->limit(4)->get(),
                fn($a) => route('attributes.show', $a), fn($a) => $a->code],
            ['Universo', '#a78bfa', 'orbita', $like($user->universes())->limit(4)->get(),
                fn($u) => route('universes.show', $u), fn($u) => $u->code],
            ['Torneo', '#fbbf24', 'trofeo', $like($user->tournamentTemplates())->limit(4)->get(),
                fn($t) => route('tournaments.templates.show', $t), fn($t) => $t->code],
            ['Fase', '#f472b6', 'grafo', $like($user->phaseTemplates())->limit(4)->get(),
                fn($p) => route('tournaments.phase-templates.show', $p), fn($p) => $p->code],
            ['Competición', '#10b981', 'espadas', $like(TournamentInstance::query()->whereIn('universe_id', $mundos)->with('universe'))->limit(4)->get(),
                fn($c) => route('universes.competitions.show', [$c->universe_id, $c]), fn($c) => ($c->universe?->name ?? '') . ' · ' . $c->status_label],
        ];

        $resultados = collect($grupos)->flatMap(fn($g) => $g[3]->map(fn($cosa) => [
            'kind' => $g[0],
            'tone' => $g[1],
            'icon' => $g[2],
            'title' => $cosa->name,
            'subtitle' => $g[5]($cosa),
            'image' => $cosa->image_url ?? ($cosa instanceof TournamentInstance ? $cosa->universe?->image_url : null),
            'url' => $g[4]($cosa),
        ]));

        return response()->json(['results' => $resultados->values()]);
    }


    /*
     * Una fila de «lo ultimo», sea del tipo que sea.
     */
    private function mapear(
        $coleccion,
        string $tipo,
        string $tono,
        callable $destino
    ) {

        return $coleccion->map(
            fn($cosa) => [
                'tipo' => $tipo,
                'tono' => $tono,
                'nombre' => $cosa->display_label ?? $cosa->name,
                'img' => $cosa->image_url ?? null,
                'url' => $destino($cosa),
                'cuando' => $cosa->created_at,
            ]
        );
    }
}
