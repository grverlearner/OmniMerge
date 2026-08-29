<?php

namespace App\Services\Universes;

use App\Models\TournamentInstanceParticipant;
use App\Models\Universe;
use App\Models\UniverseEntity;
use App\Models\UniverseTrophyAward;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| UniverseEntityBrowser
|--------------------------------------------------------------------------
|
| El panel de competidores: buscar, filtrar, ordenar y ensenar.
|
| Filtrar por atributo NO se puede hacer en SQL. Los atributos viven en un
| JSON copiado -attribute_snapshot-, y consultarlo con LIKE encontraria
| «hoja» dentro de «hojarasca». Asi que el filtro por atributo se aplica en
| memoria, sobre el conjunto ya reducido por lo que SI sabe la base de
| datos -nombre, estado, tipo-.
|
| Es un universo, no un censo: unos cientos de entidades. Traerlas para
| filtrarlas cuesta menos que un indice invertido que habria que mantener.
|
*/
class UniverseEntityBrowser
{
    public function __construct(
        private readonly UniverseEntityVersionResolver $versions,
        private readonly \App\Services\Games\GameStatsService $gameStats,
    ) {
    }

    public const SORTS = [
        'name' => 'Nombre',
        'recent' => 'Añadidos hace poco',
        'titles' => 'Más títulos',
        'wins' => 'Más victorias',
        'winrate' => 'Mejor porcentaje',
        'tournaments' => 'Más torneos',
        'trophies' => 'Más trofeos',
        'attributes' => 'Más atributos',
    ];

    public const VIEWS = [
        'GRID' => 'Cuadrícula',
        'GALLERY' => 'Galería',
        'LIST' => 'Lista',
        'TABLE' => 'Tabla',
        'CARD' => 'Ficha',
    ];

    /*
     * Todo lo que la pantalla necesita.
     */
    public function browse(Universe $universe, Request $request): array
    {
        $filtros = $this->filters($request);

        $todas = $this->load($universe);

        $catalogo = $this->catalog($todas);

        $tipos = $todas
            ->pluck('entity_type_name')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $visibles = $this->apply($todas, $filtros);

        $visibles = $this->sort($visibles, $filtros['sort']);

        return [
            'entities' => $visibles->values()->all(),

            'filters' => $filtros,
            'catalog' => $catalogo,
            'types' => $tipos,

            'sorts' => self::SORTS,
            'views' => self::VIEWS,

            'counts' => [
                'total' => $todas->count(),
                'shown' => $visibles->count(),
                'active' => $todas->where('status', 'ACTIVE')->count(),
                'retired' => $todas->where('status', 'RETIRED')->count(),
                'with_versions' => $todas->filter(fn ($e) => count($e['versions']) > 1)->count(),
                'with_trophies' => $todas->filter(fn ($e) => $e['record']['trophies'] > 0)->count(),
                'never_played' => $todas->filter(fn ($e) => $e['record']['tournaments'] === 0)->count(),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Lo que se lee
    |--------------------------------------------------------------------------
    */

    private function load(Universe $universe): Collection
    {
        $entidades = UniverseEntity::query()
            ->where('universe_id', $universe->id)
            ->with('gameStats')
            ->get();

        $records = $this->records($entidades->pluck('id'));

        $trofeos = $this->trophies($entidades->pluck('id'));

        return $entidades
            ->map(fn (UniverseEntity $e) => $this->card($e, $records, $trofeos))
            ->sortBy(fn ($e) => mb_strtolower($e['name']))
            ->values();
    }

    private function card(UniverseEntity $entity, Collection $records, Collection $trofeos): array
    {
        $record = $records->get($entity->id);

        $ganadas = (int) ($record->wins ?? 0);
        $perdidas = (int) ($record->losses ?? 0);
        $jugadas = $ganadas + $perdidas;

        $suyos = $trofeos->get($entity->id, collect());

        return [
            'id' => $entity->id,
            'code' => $entity->code,
            'name' => $entity->display_label,
            'raw_name' => $entity->name,
            'description' => $entity->description,
            'image_url' => $entity->image_url,
            'type' => $entity->entity_type_name,
            'status' => $entity->status,
            'status_label' => $entity->status_label,

            'imported_at' => $entity->imported_at?->format('d/m/Y'),
            'synced_at' => $entity->synced_at?->format('d/m/Y'),
            'from_library' => $entity->source_entity_id !== null,

            /*
             * Los atributos, con su clave y su texto. La clave es con la
             * que se filtra; el texto, con el que se lee.
             */
            'attributes' => collect($entity->attribute_snapshot ?? [])
                ->map(fn ($a) => [
                    'name' => (string) ($a['name'] ?? ''),
                    'key' => mb_strtolower((string) ($a['name'] ?? '')),
                    'display' => (string) ($a['display'] ?? ''),
                    'values' => array_values((array) ($a['values'] ?? [])),
                    'keys' => array_map(
                        fn ($v) => mb_strtolower((string) $v),
                        (array) ($a['values'] ?? [])
                    ),
                    'featured' => (bool) ($a['featured'] ?? false),
                ])
                ->values()
                ->all(),

            'versions' => $this->versions->all($entity),

            'record' => [
                'tournaments' => (int) ($record->tournaments ?? 0),
                'wins' => $ganadas,
                'losses' => $perdidas,
                'titles' => (int) ($record->titles ?? 0),
                'winrate' => $jugadas > 0 ? (int) round($ganadas / $jugadas * 100) : null,
                'trophies' => $suyos->count(),
            ],

            /* Los trofeos que tiene, con su cara */
            'trophies' => $suyos
                ->map(fn ($a) => [
                    'id' => $a->universe_trophy_id,
                    'name' => $a->trophy?->name ?? 'Trofeo',
                    'icon' => $a->trophy?->icon,
                    'image_url' => $a->trophy?->image_url,
                    'tier' => $a->trophy?->tier,
                ])
                ->values()
                ->all(),

            /* Sus estadísticas por juego */
            'stats' => $entity->gameStats
                ->map(fn ($s) => [
                    'game_key' => $s->game_key,
                    'values' => (array) ($s->stats ?? []),
                ])
                ->values()
                ->all(),
        ];
    }

    private function records(Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return TournamentInstanceParticipant::query()
            ->whereIn('universe_entity_id', $ids)
            ->selectRaw(
                'universe_entity_id,
                 count(*) as tournaments,
                 sum(wins) as wins,
                 sum(losses) as losses,
                 sum(case when outcome = \'CHAMPION\' then 1 else 0 end) as titles'
            )
            ->groupBy('universe_entity_id')
            ->get()
            ->keyBy('universe_entity_id');
    }

    private function trophies(Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return UniverseTrophyAward::query()
            ->whereIn('universe_entity_id', $ids)
            ->with('trophy')
            ->get()
            ->groupBy('universe_entity_id');
    }

    /*
    |--------------------------------------------------------------------------
    | El catálogo con el que se filtra
    |--------------------------------------------------------------------------
    |
    | Sale de las entidades que hay, no de una tabla: si nadie lleva
    | «doujutsu», «doujutsu» no se ofrece. Un filtro que no puede casar con
    | nadie es un callejón sin salida.
    |
    */

    private function catalog(Collection $entidades): array
    {
        $indice = [];

        foreach ($entidades as $e) {
            foreach ($e['attributes'] as $a) {

                if ($a['key'] === '') {
                    continue;
                }

                $indice[$a['key']] ??= [
                    'key' => $a['key'],
                    'label' => $a['name'],
                    'entities' => 0,
                    'values' => [],
                ];

                $indice[$a['key']]['entities']++;

                foreach ($a['keys'] as $i => $clave) {

                    if ($clave === '') {
                        continue;
                    }

                    $indice[$a['key']]['values'][$clave] ??= [
                        'key' => $clave,
                        'label' => $a['values'][$i] ?? $clave,
                        'entities' => 0,
                    ];

                    $indice[$a['key']]['values'][$clave]['entities']++;
                }
            }
        }

        ksort($indice);

        /*
         * Los valores se reindexan a lista.
         *
         * Se acumulan en un mapa -clave => cuenta- porque asi es como se
         * cuentan sin duplicar, pero un mapa llega a JavaScript como objeto
         * y la pantalla los recorre como lista. Sin este paso el catalogo
         * se pintaba vacio.
         */
        return collect($indice)
            ->map(fn (array $a) => [
                'key' => $a['key'],
                'label' => $a['label'],
                'entities' => $a['entities'],

                'values' => collect($a['values'])
                    ->sortByDesc('entities')
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Filtrar
    |--------------------------------------------------------------------------
    */

    private function filters(Request $request): array
    {
        $vista = strtoupper((string) $request->input('view', 'GRID'));

        return [
            'search' => trim((string) $request->input('search')),
            'status' => (string) $request->input('status', ''),
            'type' => (string) $request->input('type', ''),

            /* attr[clave] = [valor, valor] */
            'attributes' => collect((array) $request->input('attr', []))
                ->map(fn ($v) => array_values(array_filter((array) $v)))
                ->filter(fn ($v) => $v !== [])
                ->all(),

            /* Y los atributos que basta con tener */
            'has' => array_values(array_filter((array) $request->input('has', []))),

            'only' => (string) $request->input('only', ''),

            'sort' => array_key_exists((string) $request->input('sort'), self::SORTS)
                ? (string) $request->input('sort')
                : 'name',

            'view' => array_key_exists($vista, self::VIEWS) ? $vista : 'GRID',

            'size' => max(1, min(5, (int) $request->input('size', 3))),
        ];
    }

    private function apply(Collection $entidades, array $f): Collection
    {
        return $entidades
            ->when($f['search'] !== '', fn ($c) => $c->filter(
                fn ($e) => $this->matchesSearch($e, mb_strtolower($f['search']))
            ))
            ->when($f['status'] !== '', fn ($c) => $c->where('status', $f['status']))
            ->when($f['type'] !== '', fn ($c) => $c->where('type', $f['type']))
            ->when($f['has'] !== [], fn ($c) => $c->filter(
                fn ($e) => collect($f['has'])->every(
                    fn ($clave) => collect($e['attributes'])->contains('key', $clave)
                )
            ))
            ->when($f['attributes'] !== [], fn ($c) => $c->filter(
                fn ($e) => $this->matchesAttributes($e, $f['attributes'])
            ))
            ->when($f['only'] !== '', fn ($c) => $c->filter(
                fn ($e) => match ($f['only']) {
                    'TROPHIES' => $e['record']['trophies'] > 0,
                    'TITLES' => $e['record']['titles'] > 0,
                    'VERSIONS' => count($e['versions']) > 1,
                    'PLAYED' => $e['record']['tournaments'] > 0,
                    'NEVER' => $e['record']['tournaments'] === 0,
                    'LIBRARY' => $e['from_library'],
                    default => true,
                }
            ));
    }

    private function matchesSearch(array $e, string $q): bool
    {
        if (str_contains(mb_strtolower($e['name']), $q)) {
            return true;
        }

        if (str_contains(mb_strtolower((string) $e['code']), $q)) {
            return true;
        }

        if (str_contains(mb_strtolower((string) $e['type']), $q)) {
            return true;
        }

        /*
         * También por atributo y por valor: buscar «sharingan» debería
         * encontrar a quien lo lleva, no solo a quien se llama así.
         */
        foreach ($e['attributes'] as $a) {

            if (str_contains($a['key'], $q)) {
                return true;
            }

            foreach ($a['keys'] as $v) {
                if (str_contains($v, $q)) {
                    return true;
                }
            }
        }

        foreach ($e['versions'] as $v) {
            if (str_contains(mb_strtolower((string) $v['name']), $q)) {
                return true;
            }
        }

        return false;
    }

    /*
     * Todos los atributos pedidos, y dentro de cada uno vale cualquiera de
     * sus valores. Es lo mismo que hace la elegibilidad de un torneo: si
     * pides «aldea: hoja o arena» y «anime: naruto», hay que cumplir las
     * dos cosas.
     */
    private function matchesAttributes(array $e, array $pedidos): bool
    {
        foreach ($pedidos as $clave => $valores) {

            $suyo = collect($e['attributes'])->firstWhere('key', $clave);

            if (! $suyo) {
                return false;
            }

            if (array_intersect($valores, $suyo['keys']) === []) {
                return false;
            }
        }

        return true;
    }

    private function sort(Collection $entidades, string $criterio): Collection
    {
        return match ($criterio) {
            'recent' => $entidades->sortByDesc('id'),
            'titles' => $entidades->sortByDesc(fn ($e) => $e['record']['titles']),
            'wins' => $entidades->sortByDesc(fn ($e) => $e['record']['wins']),
            'tournaments' => $entidades->sortByDesc(fn ($e) => $e['record']['tournaments']),
            'trophies' => $entidades->sortByDesc(fn ($e) => $e['record']['trophies']),
            'attributes' => $entidades->sortByDesc(fn ($e) => count($e['attributes'])),

            /*
             * El porcentaje de quien no ha jugado no es 0: es que no hay.
             * Ordenarlos como si hubieran perdido todo los pondría al final
             * junto a los que de verdad pierden siempre.
             */
            'winrate' => $entidades->sortByDesc(fn ($e) => $e['record']['winrate'] ?? -1),

            default => $entidades->sortBy(fn ($e) => mb_strtolower($e['name'])),
        };
    }
}
