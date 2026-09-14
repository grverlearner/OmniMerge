<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Collection as LibraryCollection;
use App\Models\Entity;
use App\Models\User;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| El perfil de biblioteca de un creador
|--------------------------------------------------------------------------
|
| Es su biblioteca vista desde fuera, y solo eso: entidades, colecciones,
| atributos y valores de catalogo. Sus plantillas de torneo viven en otra
| comunidad y aqui solo hay un enlace, porque mezclar las dos cosas convierte
| el perfil en un cajon.
|
| Lo que la pagina anterior no contestaba y ahora si:
|
|   · Cuanto le han copiado. Es el unico numero que dice si su trabajo le
|     sirve a alguien mas que a el.
|   · Que le has copiado tu. Evita volver a copiar lo mismo.
|   · De quien se inspira el. La atribucion tambien va hacia atras.
|   · De que esta hecha su biblioteca, reparto por tipo de entidad incluido.
|
*/

class CreatorController extends Controller
{
    public function show(
        Request $request,
        User $user
    ): View {

        $isOwner =
            $request
            ->user()
            ->is($user);


        abort_unless(
            $user->isActive(),
            404
        );


        abort_unless(
            $user->isPublicProfile()
                || $isOwner,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Pestana y parametros
        |--------------------------------------------------------------------------
        */

        $permitidas = [
            'overview',
            'entities',
            'collections',
            'attributes',
            'catalogs',
        ];

        $tab =
            (string) $request->input(
                'tab',
                'overview'
            );

        if (
            ! in_array(
                $tab,
                $permitidas,
                true
            )
        ) {
            $tab = 'overview';
        }


        $search =
            trim(
                (string) $request->input(
                    'search'
                )
            );

        $sort =
            (string) $request->input(
                'sort',
                'newest'
            );

        $image =
            (string) $request->input(
                'image'
            );

        $entityTypeId =
            $request->integer('entity_type') ?: null;

        $dataType =
            (string) $request->input(
                'data_type'
            );

        $attributeId =
            $request->integer('attribute') ?: null;

        $perPage =
            (int) $request->input(
                'per_page',
                24
            );

        if (
            ! in_array(
                $perPage,
                [12, 24, 48, 96],
                true
            )
        ) {
            $perPage = 24;
        }


        $yo =
            $request->user()->id;


        /*
        |--------------------------------------------------------------------------
        | Cifras publicas
        |--------------------------------------------------------------------------
        */

        $user->loadCount([

            'entities as public_entities_count'
            => fn($query) => $this->entidadesPublicas($query),

            'collections as public_collections_count'
            => fn($query) => $this->coleccionesPublicas($query),

            'attributes as public_attributes_count'
            => fn($query) => $this->atributosPublicos($query),
        ]);


        $catalogosCount =
            $this
            ->consultaCatalogos($user)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Cuanto le han copiado
        |--------------------------------------------------------------------------
        |
        | `clones_count` es una columna, no un conteo: sumarla es barato y es el
        | unico numero que dice si su trabajo le sirve a alguien mas que a el.
        |
        */

        $vecesCopiado =
            (int) $this->entidadesPublicas($user->entities())->sum('clones_count')
            + (int) $this->coleccionesPublicas($user->collections())->sum('clones_count')
            + (int) $this->atributosPublicos($user->attributes())->sum('clones_count');


        /*
        |--------------------------------------------------------------------------
        | Que le has copiado tu
        |--------------------------------------------------------------------------
        |
        | Con los ids de lo suyo en la mano, se cuenta lo que hay en la
        | biblioteca de quien mira apuntando ahi. Evita copiar dos veces lo
        | mismo, que es lo que pasa cuando nadie lo dice.
        |
        */

        $idsEntidades = $this->entidadesPublicas($user->entities())->pluck('id');
        $idsColecciones = $this->coleccionesPublicas($user->collections())->pluck('id');
        $idsAtributos = $this->atributosPublicos($user->attributes())->pluck('id');

        $loQueTengoSuyo = $isOwner

            ? 0

            : Entity::query()
            ->where('user_id', $yo)
            ->whereIn('source_entity_id', $idsEntidades)
            ->count()

            + LibraryCollection::query()
            ->where('user_id', $yo)
            ->whereIn('source_collection_id', $idsColecciones)
            ->count()

            + Attribute::query()
            ->where('user_id', $yo)
            ->whereIn('source_attribute_id', $idsAtributos)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | De quien se inspira el
        |--------------------------------------------------------------------------
        |
        | La atribucion tambien va hacia atras: si su biblioteca sale de la de
        | otros, se dice, igual que se dice cuando alguien copia de el.
        |
        */

        $inspiraciones =
            User::query()
            ->whereIn(
                'id',
                Entity::query()
                    ->whereIn('id', function ($sub) use ($user) {
                        $sub->select('source_entity_id')
                            ->from('entities')
                            ->where('user_id', $user->id)
                            ->whereNotNull('source_entity_id');
                    })
                    ->select('user_id')
            )
            ->where('id', '!=', $user->id)
            ->where('status', 'ACTIVE')
            ->limit(8)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | De que esta hecha su biblioteca
        |--------------------------------------------------------------------------
        */

        $repartoPorTipo =
            $this
            ->entidadesPublicas($user->entities())
            ->selectRaw('entity_type_id, COUNT(*) as total')
            ->groupBy('entity_type_id')
            ->orderByDesc('total')
            ->get()
            ->map(
                function ($fila) {

                    $fila->tipo =
                        $fila->entity_type_id
                        ? \App\Models\EntityType::find($fila->entity_type_id)
                        : null;

                    return $fila;
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Lo mas copiado de el
        |--------------------------------------------------------------------------
        */

        $loMasCopiado =
            $this->entidadesPublicas($user->entities())
            ->where('clones_count', '>', 0)
            ->with('entityType')
            ->orderByDesc('clones_count')
            ->limit(8)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | El mosaico de portada
        |--------------------------------------------------------------------------
        */

        $mosaico =
            $this->entidadesPublicas($user->entities())
            ->with('baseVersionSetting.entityVersion', 'presentation.entityVersion')
            ->latest('published_at')
            ->limit(24)
            ->get()
            ->filter(
                fn($entidad) =>
                (bool) ($entidad->public_image_url ?: $entidad->image_url)
            )
            ->take(18)
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Lo ultimo que ha publicado
        |--------------------------------------------------------------------------
        |
        | Las tres clases mezcladas y ordenadas por fecha: es la unica forma de
        | ver si sigue trabajando o lleva un ano parado.
        |
        */

        $actividad =
            collect()
            ->merge(
                $this->entidadesPublicas($user->entities())
                    ->latest('published_at')->limit(8)->get()
                    ->map(fn($x) => ['clase' => 'entidad', 'cosa' => $x, 'fecha' => $x->published_at])
            )
            ->merge(
                $this->coleccionesPublicas($user->collections())
                    ->latest('published_at')->limit(8)->get()
                    ->map(fn($x) => ['clase' => 'coleccion', 'cosa' => $x, 'fecha' => $x->published_at])
            )
            ->merge(
                $this->atributosPublicos($user->attributes())
                    ->latest('published_at')->limit(8)->get()
                    ->map(fn($x) => ['clase' => 'atributo', 'cosa' => $x, 'fecha' => $x->published_at])
            )
            ->filter(fn($fila) => $fila['fecha'] !== null)
            ->sortByDesc('fecha')
            ->take(12)
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Los listados de cada pestana
        |--------------------------------------------------------------------------
        */

        $entities = null;
        $collections = null;
        $attributes = null;
        $catalogs = null;

        $entityTypes =
            \App\Models\EntityType::query()
            ->whereIn(
                'id',
                $repartoPorTipo->pluck('entity_type_id')->filter()
            )
            ->orderBy('name')
            ->get();

        $suscatalogos =
            $this->atributosPublicos($user->attributes())
            ->where('data_type', 'OPTION')
            ->withCount('options')
            ->orderBy('name')
            ->get();


        if ($tab === 'overview' || $tab === 'entities') {

            $consulta =
                $this->entidadesPublicas($user->entities())
                ->with([
                    'creator',
                    'entityType',
                    'sourceEntity.creator',
                    'presentation.entityVersion.version',
                    'presentation.mediaImage',
                    'baseVersionSetting.entityVersion',
                    'clones' => fn($q) => $q->where('user_id', $yo),
                ])
                ->withCount(['entityAttributes', 'collections'])
                ->when(
                    $search,
                    fn($q) => $q->where(
                        fn($s) => $s
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                    )
                )
                ->when(
                    $entityTypeId,
                    fn($q) => $q->where('entity_type_id', $entityTypeId)
                )
                ->when(
                    $image === 'yes',
                    fn($q) => $q->whereNotNull('image')
                )
                ->when(
                    $image === 'no',
                    fn($q) => $q->whereNull('image')
                );

            $this->ordenar($consulta, $sort, 'clones_count');

            $entities = $tab === 'entities'
                ? $consulta->paginate($perPage)->withQueryString()
                : $consulta->limit(12)->get();
        }


        if ($tab === 'overview' || $tab === 'collections') {

            $consulta =
                $this->coleccionesPublicas($user->collections())
                ->with([
                    'creator',
                    'sourceCollection.creator',
                    'entities' => fn($q) => $this->entidadesPublicas($q)->limit(12),
                    'clones' => fn($q) => $q->where('user_id', $yo),
                ])
                ->withCount([
                    'entities' => fn($q) => $this->entidadesPublicas($q),
                ])
                ->when(
                    $search,
                    fn($q) => $q->where('name', 'like', "%{$search}%")
                );

            $this->ordenar($consulta, $sort, 'clones_count');

            $collections = $tab === 'collections'
                ? $consulta->paginate($perPage)->withQueryString()
                : $consulta->limit(6)->get();
        }


        if ($tab === 'overview' || $tab === 'attributes') {

            $consulta =
                $this->atributosPublicos($user->attributes())
                ->with([
                    'creator',
                    'sourceAttribute.creator',
                    'options' => fn($q) => $q
                        ->where('status', 'ACTIVE')
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->limit(12),
                    'clones' => fn($q) => $q->where('user_id', $yo),
                ])
                ->withCount(['options', 'entityAttributes'])
                ->when(
                    $search,
                    fn($q) => $q->where('name', 'like', "%{$search}%")
                )
                ->when(
                    $dataType,
                    fn($q) => $q->where('data_type', $dataType)
                );

            $this->ordenar($consulta, $sort, 'clones_count');

            $attributes = $tab === 'attributes'
                ? $consulta->paginate($perPage)->withQueryString()
                : $consulta->limit(6)->get();
        }


        if ($tab === 'catalogs') {

            $consulta =
                $this->consultaCatalogos($user)
                ->with([
                    'attribute.creator',
                    'user',
                    'parent',
                    'sourceOption.user',
                    'clones' => fn($q) => $q->where('user_id', $yo),
                ])
                ->withCount(['values', 'children'])
                ->when(
                    $search,
                    fn($q) => $q->where('name', 'like', "%{$search}%")
                )
                ->when(
                    $attributeId,
                    fn($q) => $q->where('attribute_id', $attributeId)
                )
                ->when(
                    $image === 'yes',
                    fn($q) => $q->whereNotNull('image')
                )
                ->when(
                    $image === 'no',
                    fn($q) => $q->whereNull('image')
                );

            $this->ordenar($consulta, $sort, null);

            $catalogs =
                $consulta
                ->paginate($perPage)
                ->withQueryString();
        }


        $statistics = [
            'entities' => $user->public_entities_count,
            'collections' => $user->public_collections_count,
            'attributes' => $user->public_attributes_count,
            'catalogs' => $catalogosCount,
        ];


        return view(
            'community.creator',
            compact(
                'user',
                'isOwner',
                'tab',
                'search',
                'sort',
                'image',
                'entityTypeId',
                'dataType',
                'attributeId',
                'perPage',
                'statistics',
                'vecesCopiado',
                'loQueTengoSuyo',
                'inspiraciones',
                'repartoPorTipo',
                'loMasCopiado',
                'mosaico',
                'actividad',
                'entityTypes',
                'suscatalogos',
                'entities',
                'collections',
                'attributes',
                'catalogs'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Que cuenta como publico
    |--------------------------------------------------------------------------
    |
    | Las tres reglas viven aqui una sola vez. Repetirlas en cada consulta es
    | como se acaba ensenando por error algo que su autor no publico.
    |
    */

    private function entidadesPublicas(Builder|Relation $query): Builder|Relation
    {
        return $query
            ->where('visibility', 'PUBLIC')
            ->where('status', 'ACTIVE')
            ->whereNotNull('published_at');
    }


    private function coleccionesPublicas(Builder|Relation $query): Builder|Relation
    {
        return $query
            ->where('visibility', 'PUBLIC')
            ->where('status', 'ACTIVE')
            ->whereNotNull('published_at');
    }


    private function atributosPublicos(Builder|Relation $query): Builder|Relation
    {
        return $query
            ->where('scope', 'PUBLIC')
            ->where('status', 'ACTIVE')
            ->whereNotNull('published_at');
    }


    /*
     * Un valor de catalogo es publico cuando lo es el catalogo que lo contiene:
     * un valor no se publica por su cuenta.
     */
    private function consultaCatalogos(User $user): Builder
    {
        return AttributeOption::query()
            ->where('attribute_options.user_id', $user->id)
            ->where('attribute_options.status', 'ACTIVE')
            ->whereHas(
                'attribute',
                fn($query) =>
                $query
                    ->where('scope', 'PUBLIC')
                    ->where('status', 'ACTIVE')
                    ->whereNotNull('published_at')
            );
    }


    /*
     * El orden, compartido. `$columnaCopias` es null para los valores de
     * catalogo, que no llevan cuenta de copias.
     */
    private function ordenar(
        Builder|Relation $query,
        string $sort,
        ?string $columnaCopias
    ): void {

        match ($sort) {

            'oldest' => $query
                ->orderBy('created_at')
                ->orderBy('id'),

            'name_asc' => $query->orderBy('name'),

            'name_desc' => $query->orderByDesc('name'),

            'popular' => $columnaCopias
                ? $query
                ->orderByDesc($columnaCopias)
                ->orderByDesc('created_at')
                : $query->orderBy('name'),

            default => $query
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
        };
    }
}
