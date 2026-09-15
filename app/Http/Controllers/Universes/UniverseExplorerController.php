<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Models\Universe;
use App\Models\UniverseEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| UniverseExplorerController
|--------------------------------------------------------------------------
|
| El mapa del Universo.
|
| No es una lista de entidades: es una vista panoramica donde CADA entidad
| es una cara, y las caras se reparten en cuadros segun el criterio que se
| elija. Cambiar de criterio -de tipo a aldea, de aldea a estado- vuelve a
| repartir a todo el mundo delante de los ojos.
|
| Por eso el reparto no se hace aqui. Aqui se prepara UNA vez el censo
| completo del Universo -cada entidad con su cara, su tipo, sus atributos y
| lo que ha hecho compitiendo- y el navegador lo reagrupa al vuelo. Pedirle
| al servidor un reparto nuevo por cada criterio mataria justo la sensacion
| que hace util esta pantalla.
|
| Los atributos salen del snapshot de cada entidad del Universo: no se
| consulta la Biblioteca. Un atributo puede traer VARIOS valores, y esa es
| la parte interesante del mapa: quien esta en dos cuadros a la vez.
|
| Ver docs/md/69-Universos-Explorar.md
|
*/

class UniverseExplorerController extends Controller
{
    /*
     * Criterios que no son atributos: salen de la propia entidad o de lo
     * que ha hecho en el Universo. Existen porque son datos que ya estaban
     * guardados y que nadie estaba mirando.
     */
    private const PROPIOS = [

        'TIPO' => [
            'etiqueta' => 'Tipo de entidad',
            'ayuda' => 'Personaje, Lugar, Anime… lo que se copió al importarla.',
        ],

        'ESTADO' => [
            'etiqueta' => 'Estado en el universo',
            'ayuda' => 'Activa, inactiva o retirada.',
        ],

        'COMPITE' => [
            'etiqueta' => '¿Ha competido?',
            'ayuda' => 'Quién ha entrado alguna vez en una competición y quién sigue esperando.',
        ],

        'TITULO' => [
            'etiqueta' => '¿Tiene título?',
            'ayuda' => 'Quién ha ganado alguna competición de este universo.',
        ],

        'TROFEO' => [
            'etiqueta' => '¿Tiene trofeo?',
            'ayuda' => 'Quién ha recibido algún trofeo de la vitrina del universo.',
        ],
    ];


    public function index(
        Request $request,
        Universe $universe
    ): View {

        $this->authorize('view', $universe);

        $entities =
            UniverseEntity::query()
            ->where('universe_id', $universe->id)
            ->withCount([

                'participations',

                'participations as titulos_count' => fn($q) =>
                $q->where('outcome', 'CHAMPION'),

                'trophyAwards',
            ])
            ->with('sourceEntity:id,image')
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | El censo
        |--------------------------------------------------------------------------
        |
        | Una fila por entidad, con todo lo que el mapa necesita para
        | repartirla por cualquier criterio sin volver al servidor.
        */

        $censo = $entities->map(
            fn(UniverseEntity $e) => [

                'id' => $e->id,
                'nombre' => $e->display_label,
                'img' => $e->image_url,
                'url' => route('universes.entities.show', [$universe, $e]),

                'tipo' => $e->entity_type_name ?: null,
                'estado' => $e->status_label,

                'jugadas' => (int) $e->participations_count,
                'titulos' => (int) $e->titulos_count,
                'trofeos' => (int) $e->trophy_awards_count,

                /*
                 * nombre del atributo => lista de valores. Siempre lista,
                 * aunque traiga uno solo: el que trae dos es el que hace
                 * interesante el mapa y no quiero dos formas de leerlo.
                 */
                'attrs' => $this->atributosDe($e),
            ]
        )->values();


        /*
        |--------------------------------------------------------------------------
        | Los criterios con los que se puede repartir el mundo
        |--------------------------------------------------------------------------
        |
        | Cada uno lleva su cobertura: a cuantas entidades les consta ese
        | dato. Es lo que evita elegir a ciegas un criterio que va a dejar a
        | dieciocho de veintidos en «Sin dato».
        */

        $criterios = collect();

        foreach (self::PROPIOS as $clave => $meta) {

            $criterios->push(
                $this->resumirCriterio(
                    $clave,
                    $meta['etiqueta'],
                    $meta['ayuda'],
                    'propio',
                    $censo
                )
            );
        }

        foreach ($this->nombresDeAtributos($censo) as $nombre) {

            $criterios->push(
                $this->resumirCriterio(
                    $nombre,
                    $nombre,
                    'Atributo copiado al importar las entidades.',
                    'atributo',
                    $censo
                )
            );
        }

        /*
         * El orden de la lista, y con el la eleccion por defecto.
         *
         * Hay dos clases de criterio y no compiten en la misma liga:
         *
         *   descriptivos  dicen QUE es cada entidad -su tipo, su aldea, su
         *                 anime-. Dibujan el mundo.
         *   preguntas     responden si o no -¿tiene titulo?, ¿ha competido?-.
         *                 Son utiles, pero parten el mundo en dos y no lo
         *                 describen.
         *
         * Puntuar a los dos juntos hacia que ganase siempre una pregunta, por
         * la simple razon de que un si/no cubre por definicion al cien por
         * cien. El mapa de entrada quedaba en «con titulo / sin titulo», que
         * no cuenta nada de este mundo. Asi que los descriptivos van delante,
         * y dentro de cada clase gana el que mas cubre y mas reparte.
         */
        $total = max(1, $censo->count());

        $nota = fn(array $c) => [

            $c['valores'] > 1 ? 1 : 0,

            $c['familia'] === 'atributo' || in_array($c['clave'], ['TIPO', 'ESTADO'], true)
                ? 1
                : 0,

            ($c['cobertura'] / $total) * 100 + min($c['valores'], 12) * 4,
        ];

        $criterios = $criterios
            ->sortByDesc($nota)
            ->values();

        $porDefecto = $criterios->first()['clave'] ?? 'TIPO';

        $criterioPedido = (string) $request->input('criterio', '');

        if ($criterioPedido !== '' && $criterios->contains('clave', $criterioPedido)) {
            $porDefecto = $criterioPedido;
        }


        return view(
            'universes.explorer.index',
            [
                'universe' => $universe,
                'censo' => $censo,
                'criterios' => $criterios,
                'porDefecto' => $porDefecto,
                'totalEntidades' => $censo->count(),
                'sinImagen' => $censo->where('img', null)->count(),
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Ayudas
    |--------------------------------------------------------------------------
    */

    /*
     * Los atributos de una entidad, normalizados a lista de valores.
     *
     * El snapshot guarda 'values' (lista) y 'display' (lo mismo ya escrito).
     * Se usa 'values' porque es lo unico que distingue «Hoja» de «Hoja, Arena»,
     * y sin esa distincion no hay cuadros compartidos que enseñar.
     */
    private function atributosDe(UniverseEntity $entity): array
    {
        $salida = [];

        foreach (($entity->attribute_snapshot ?? []) as $atributo) {

            $nombre = trim((string) ($atributo['name'] ?? ''));

            if ($nombre === '') {
                continue;
            }

            $valores =
                collect($atributo['values'] ?? [])
                ->map(fn($v) => trim((string) $v))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($valores === []) {

                $escrito = trim((string) ($atributo['display'] ?? ''));

                if ($escrito === '') {
                    continue;
                }

                $valores = [$escrito];
            }

            /* Dos atributos con el mismo nombre: se suman, no se pisan */
            $salida[$nombre] = array_values(
                array_unique(
                    array_merge($salida[$nombre] ?? [], $valores)
                )
            );
        }

        return $salida;
    }


    private function nombresDeAtributos(Collection $censo): Collection
    {
        return $censo
            ->flatMap(fn($e) => array_keys($e['attrs']))
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }


    /*
     * Cuanto reparte un criterio, antes de elegirlo.
     *
     * cobertura  a cuantas entidades les consta el dato
     * valores    en cuantos cuadros las reparte
     * compartidos  cuantas caen en mas de un cuadro a la vez
     */
    private function resumirCriterio(
        string $clave,
        string $etiqueta,
        string $ayuda,
        string $familia,
        Collection $censo
    ): array {

        $valores = collect();
        $cobertura = 0;
        $compartidos = 0;

        foreach ($censo as $entidad) {

            $suyos = $this->valoresDe($entidad, $clave, $familia);

            if ($suyos === []) {
                continue;
            }

            $cobertura++;

            if (count($suyos) > 1) {
                $compartidos++;
            }

            foreach ($suyos as $v) {
                $valores->push($v);
            }
        }

        return [
            'clave' => $clave,
            'etiqueta' => $etiqueta,
            'ayuda' => $ayuda,
            'familia' => $familia,
            'cobertura' => $cobertura,
            'valores' => $valores->unique()->count(),
            'compartidos' => $compartidos,
        ];
    }


    /*
     * Los valores de una entidad para un criterio. Siempre lista; vacia
     * cuando no le consta el dato, que es lo que manda al cuadro «Sin dato».
     */
    private function valoresDe(
        array $entidad,
        string $clave,
        string $familia
    ): array {

        if ($familia === 'atributo') {
            return $entidad['attrs'][$clave] ?? [];
        }

        return match ($clave) {

            'TIPO' => $entidad['tipo'] ? [$entidad['tipo']] : [],

            'ESTADO' => [$entidad['estado']],

            'COMPITE' => [
                $entidad['jugadas'] > 0 ? 'Ha competido' : 'Todavía no ha competido',
            ],

            'TITULO' => [
                $entidad['titulos'] > 0 ? 'Con título' : 'Sin título',
            ],

            'TROFEO' => [
                $entidad['trofeos'] > 0 ? 'Con trofeo' : 'Sin trofeo',
            ],

            default => [],
        };
    }
}
