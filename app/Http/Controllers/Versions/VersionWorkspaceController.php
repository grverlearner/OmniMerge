<?php

namespace App\Http\Controllers\Versions;

use App\Http\Controllers\Controller;

use App\Models\Attribute;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\EntityVersion;
use App\Models\Version;

use App\Services\Versions\VersionResolverService;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class VersionWorkspaceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | APLICADAS — cada molde ya puesto sobre una entidad
    |--------------------------------------------------------------------------
    |
    | Esta pantalla enseña la union de dos cosas: una entidad y una definicion.
    | Por eso se puede mirar de dos maneras opuestas —por version aplicada, o
    | agrupada por entidad— y por eso el filtro por entidad lleva su foto: sin
    | la cara, elegir entre cien nombres en un desplegable no es elegir.
    |
    */

    public function entities(
        Request $request
    ): View {

        $this->authorize(
            'viewAny',
            EntityVersion::class
        );


        $user =
            $request->user();


        $search =
            trim(
                (string) $request->input(
                    'search'
                )
            );

        $versionId =
            $request->integer('version')
            ?: null;

        $typeId =
            $request->integer('type')
            ?: null;

        $entityId =
            $request->integer('entity')
            ?: null;

        $status =
            strtoupper(
                (string) $request->input('status')
            );

        $default =
            (string) $request->input('default');

        $overrides =
            (string) $request->input('overrides');

        $media =
            (string) $request->input('media');

        $sort =
            (string) $request->input('sort', 'default');


        $base =
            EntityVersion::query()
            ->ownedBy($user);


        $stats = [
            'total' => (clone $base)
                ->count(),

            'entities' => (clone $base)
                ->distinct()
                ->count('entity_id'),

            'default' => (clone $base)
                ->where('is_default', true)
                ->count(),

            'with_overrides' => (clone $base)
                ->whereHas('versionAttributes')
                ->count(),

            'with_media' => (clone $base)
                ->whereHas('images')
                ->count(),
        ];


        $query =
            EntityVersion::query()
            ->ownedBy($user)
            ->with([
                'entity.entityType',
                'version.parent',
            ])
            ->withCount([
                'versionAttributes',
                'images',
                'children',
            ])
            ->when(
                $search,
                fn($q) =>
                $q->where(
                    fn($sub) =>
                    $sub
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas(
                            'entity',
                            fn($e) =>
                            $e->where('name', 'like', "%{$search}%")
                        )
                        ->orWhereHas(
                            'version',
                            fn($v) =>
                            $v->where('name', 'like', "%{$search}%")
                        )
                )
            )
            ->when(
                $versionId,
                fn($q) =>
                $q->where('version_id', $versionId)
            )
            ->when(
                $entityId,
                fn($q) =>
                $q->where('entity_id', $entityId)
            )
            ->when(
                $typeId,
                fn($q) =>
                $q->whereHas(
                    'entity',
                    fn($e) =>
                    $e->where('entity_type_id', $typeId)
                )
            )
            ->when(
                $status,
                fn($q) =>
                $q->where('status', $status)
            )
            ->when(
                $default === 'YES',
                fn($q) =>
                $q->where('is_default', true)
            )
            ->when(
                $default === 'NO',
                fn($q) =>
                $q->where('is_default', false)
            )
            ->when(
                $overrides === 'YES',
                fn($q) =>
                $q->whereHas('versionAttributes')
            )
            ->when(
                $overrides === 'NO',
                fn($q) =>
                $q->whereDoesntHave('versionAttributes')
            )
            ->when(
                $media === 'YES',
                fn($q) =>
                $q->whereHas('images')
            )
            ->when(
                $media === 'NO',
                fn($q) =>
                $q->whereDoesntHave('images')
            );


        /*
         * El orden.
         *
         * «Por entidad» ordena por el nombre de la entidad, no por el de la
         * version, para que la vista agrupada salga con los bloques enteros
         * y no partidos entre dos paginas.
         */

        $nombreDeLaEntidad =
            Entity::query()
            ->select('name')
            ->whereColumn(
                'entities.id',
                'entity_versions.entity_id'
            );


        match ($sort) {

            'name' =>
            $query->orderBy('name'),

            'entity' =>
            $query
                ->orderBy($nombreDeLaEntidad)
                ->orderByDesc('is_default')
                ->orderBy('name'),

            'changes' =>
            $query->orderByDesc('version_attributes_count'),

            'images' =>
            $query->orderByDesc('images_count'),

            'recent' =>
            $query->orderByDesc('updated_at'),

            default =>
            $query
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderBy('name'),
        };


        $entityVersions =
            $query
            ->paginate(24)
            ->withQueryString();


        $versions =
            Version::query()
            ->ownedBy($user)
            ->active()
            ->withCount('entityVersions')
            ->orderBy('name')
            ->get();


        $entityTypes =
            EntityType::query()
            ->ownedBy($user)
            ->active()
            ->orderBy('name')
            ->get();


        /*
         * Las entidades que ya aplican alguna definicion, con su cara: son
         * las que puebla el selector visual de arriba.
         */

        $entitiesWithVersions =
            Entity::query()
            ->ownedBy($user)
            ->whereHas('entityVersions')
            ->withCount('entityVersions')
            ->orderBy('name')
            ->get();


        $entitiesWithout =
            Entity::query()
            ->ownedBy($user)
            ->active()
            ->whereDoesntHave('entityVersions')
            ->count();


        $selectedEntity =
            $entityId
            ? $entitiesWithVersions->firstWhere('id', $entityId)
            : null;


        return view(
            'versions.workspace.entities',
            compact(
                'entityVersions',
                'versions',
                'entityTypes',
                'entitiesWithVersions',
                'entitiesWithout',
                'selectedEntity',
                'stats',

                'search',
                'versionId',
                'typeId',
                'entityId',
                'status',
                'default',
                'overrides',
                'media',
                'sort'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COBERTURA — a quien le falta cada molde
    |--------------------------------------------------------------------------
    |
    | Antes esta pantalla era una lista de barras de progreso: decia que a
    | «Naruto clasico» le faltaban 16 entidades, y ahi se acababa. Un numero
    | que no se puede tocar no sirve de nada.
    |
    | Ahora cada fila trae POR NOMBRE Y CON FOTO a quien le falta, y un boton
    | que lleva a aplicarsela. Y se calcula para todas las definiciones, no
    | solo para las que tienen regla de catalogo: una definicion compartida
    | sin regla puede aplicarla cualquier entidad, asi que su base es la
    | biblioteca entera —eso es cobertura manual, y se dice—.
    |
    */

    public function coverage(
        Request $request
    ): View {

        $this->authorize(
            'viewAny',
            Version::class
        );


        $user =
            $request->user();


        $search =
            trim(
                (string) $request->input('search')
            );

        $estado =
            (string) $request->input('state');

        $sort =
            (string) $request->input('sort', 'missing');


        $versions =
            Version::query()
            ->ownedBy($user)
            ->active()
            ->with([
                'catalogLinks.option.attribute',
            ])
            ->withCount('entityVersions')
            ->when(
                $search,
                fn($q) =>
                $q->where(
                    fn($sub) =>
                    $sub
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                )
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();


        /*
         * La biblioteca entera: base de las definiciones compartidas que no
         * tienen ninguna regla de catalogo que las restrinja.
         */

        $todasLasEntidades =
            Entity::query()
            ->ownedBy($user)
            ->active()
            ->orderBy('name')
            ->get();


        $coverageRows =
            $versions
            ->map(
                function (
                    Version $version
                ) use (
                    $user,
                    $todasLasEntidades
                ) {

                    $optionIds =
                        $version
                        ->catalogLinks
                        ->where('relation_type', 'ACTIVATES')
                        ->pluck('attribute_option_id')
                        ->unique()
                        ->values();


                    $aplicadaEn =
                        EntityVersion::query()
                        ->where('version_id', $version->id)
                        ->pluck('entity_id')
                        ->unique();


                    /*
                     * Exclusiva: esta reservada para una sola entidad, asi que
                     * no tiene sentido medirla contra la biblioteca.
                     */

                    if ($version->isExclusive()) {

                        return [
                            'version' => $version,
                            'mode' => 'EXCLUSIVE',
                            'eligible' => null,
                            'covered' => $aplicadaEn->count(),
                            'missing' => null,
                            'percentage' => null,
                            'missing_entities' => collect(),
                            'options' => $version
                                ->catalogLinks
                                ->where('relation_type', 'ACTIVATES'),
                        ];
                    }


                    if ($optionIds->isEmpty()) {

                        /*
                         * Sin regla de catalogo: la puede aplicar cualquiera,
                         * asi que el universo es la biblioteca entera.
                         */

                        $elegibles =
                            $todasLasEntidades;

                        $modo = 'MANUAL';

                    } else {

                        $elegibles =
                            Entity::query()
                            ->ownedBy($user)
                            ->whereHas(
                                'entityAttributes.values',
                                fn($q) =>
                                $q->whereIn(
                                    'attribute_option_id',
                                    $optionIds
                                )
                            )
                            ->orderBy('name')
                            ->get();

                        $modo = 'AUTO';
                    }


                    $elegiblesIds =
                        $elegibles->pluck('id');


                    $covered =
                        $elegiblesIds
                        ->intersect($aplicadaEn)
                        ->count();


                    $faltan =
                        $elegibles
                        ->reject(
                            fn($entidad) =>
                            $aplicadaEn->contains($entidad->id)
                        )
                        ->values();


                    $percentage =
                        $elegibles->count() > 0
                        ? (int) round(
                            ($covered / $elegibles->count()) * 100
                        )
                        : 0;


                    return [
                        'version' => $version,
                        'mode' => $modo,
                        'eligible' => $elegibles->count(),
                        'covered' => $covered,
                        'missing' => $faltan->count(),
                        'percentage' => $percentage,
                        'missing_entities' => $faltan->take(18),
                        'options' => $version
                            ->catalogLinks
                            ->where('relation_type', 'ACTIVATES'),
                    ];
                }
            );


        /*
         * El filtro por estado se aplica sobre lo ya calculado: la cobertura
         * no es una columna de la base de datos.
         */

        $coverageRows =
            $coverageRows
            ->when(
                $estado === 'INCOMPLETE',
                fn($filas) =>
                $filas->filter(
                    fn($fila) =>
                    $fila['missing'] !== null
                        && $fila['missing'] > 0
                )
            )
            ->when(
                $estado === 'COMPLETE',
                fn($filas) =>
                $filas->filter(
                    fn($fila) =>
                    $fila['missing'] === 0
                )
            )
            ->when(
                $estado === 'AUTO',
                fn($filas) =>
                $filas->filter(
                    fn($fila) =>
                    $fila['mode'] === 'AUTO'
                )
            )
            ->when(
                $estado === 'MANUAL',
                fn($filas) =>
                $filas->filter(
                    fn($fila) =>
                    $fila['mode'] === 'MANUAL'
                )
            );


        $coverageRows =
            match ($sort) {

                'name' =>
                $coverageRows->sortBy(
                    fn($fila) =>
                    $fila['version']->name
                ),

                'best' =>
                $coverageRows->sortByDesc(
                    fn($fila) =>
                    $fila['percentage'] ?? -1
                ),

                'worst' =>
                $coverageRows->sortBy(
                    fn($fila) =>
                    $fila['percentage'] ?? 999
                ),

                default =>
                $coverageRows->sortByDesc(
                    fn($fila) =>
                    $fila['missing'] ?? -1
                ),
            };


        $coverageRows =
            $coverageRows->values();


        /*
         * El resumen de arriba: cuanto falta en total, y donde duele mas.
         */

        /*
         * Una definicion que ninguna entidad puede llevar tiene cero faltantes
         * y cero por ciento: no esta terminada, esta desconectada. No cuenta
         * ni como completa ni en la media, porque falsearia las dos.
         */

        $medibles =
            $coverageRows->filter(
                fn($fila) =>
                $fila['percentage'] !== null
                    && $fila['eligible'] > 0
            );


        $resumen = [
            'definiciones' => $coverageRows->count(),

            'completas' => $medibles
                ->where('missing', 0)
                ->count(),

            'faltantes' => (int) $coverageRows
                ->sum(
                    fn($fila) =>
                    $fila['missing'] ?? 0
                ),

            'media' => $medibles->isEmpty()
                ? 0
                : (int) round(
                    $medibles->avg('percentage')
                ),

            'entidades' => $todasLasEntidades->count(),
        ];


        return view(
            'versions.workspace.coverage',
            compact(
                'coverageRows',
                'resumen',
                'search',
                'estado',
                'sort'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | IMAGENES — las caras de cada version
    |--------------------------------------------------------------------------
    |
    | Aqui no se navega: se mira y se corrige. Por eso cada imagen lleva
    | encima sus tres acciones —hacerla portada, cambiarle el tipo o el pie, y
    | borrarla— en vez de obligar a entrar a la ficha de la version para cada
    | retoque. Las tres ya existian en EntityVersionController y devuelven
    | back(), asi que se pueden usar desde aqui tal cual.
    |
    */

    public function media(
        Request $request
    ): View {

        $this->authorize(
            'viewAny',
            EntityVersion::class
        );


        $user =
            $request->user();


        $search =
            trim(
                (string) $request->input('search')
            );

        $versionId =
            $request->integer('version')
            ?: null;

        $typeId =
            $request->integer('type')
            ?: null;

        $mediaType =
            strtoupper(
                (string) $request->input('media_type')
            );

        $estado =
            (string) $request->input('state');

        $sort =
            (string) $request->input('sort', 'images');


        $base =
            EntityVersion::query()
            ->ownedBy($user);


        $stats = [
            'total' => (clone $base)
                ->count(),

            'con_galeria' => (clone $base)
                ->whereHas('images')
                ->count(),

            'sin_nada' => (clone $base)
                ->whereDoesntHave('images')
                ->whereNull('image')
                ->count(),

            'imagenes' => \App\Models\EntityVersionImage::query()
                ->whereIn(
                    'entity_version_id',
                    (clone $base)->select('id')
                )
                ->count(),
        ];


        $query =
            EntityVersion::query()
            ->ownedBy($user)
            ->with([
                'entity.entityType',
                'version',
                'images' => fn($q) => $q
                    ->when(
                        $mediaType,
                        fn($sub) =>
                        $sub->where('media_type', $mediaType)
                    )
                    ->orderBy('sort_order'),
            ])
            ->withCount('images')
            ->when(
                $search,
                fn($q) =>
                $q->where(
                    fn($sub) =>
                    $sub
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas(
                            'entity',
                            fn($e) =>
                            $e->where('name', 'like', "%{$search}%")
                        )
                        ->orWhereHas(
                            'version',
                            fn($v) =>
                            $v->where('name', 'like', "%{$search}%")
                        )
                )
            )
            ->when(
                $versionId,
                fn($q) =>
                $q->where('version_id', $versionId)
            )
            ->when(
                $typeId,
                fn($q) =>
                $q->whereHas(
                    'entity',
                    fn($e) =>
                    $e->where('entity_type_id', $typeId)
                )
            )
            ->when(
                $mediaType,
                fn($q) =>
                $q->whereHas(
                    'images',
                    fn($i) =>
                    $i->where('media_type', $mediaType)
                )
            )
            ->when(
                $estado === 'GALLERY',
                fn($q) =>
                $q->whereHas('images')
            )
            ->when(
                $estado === 'COVER_ONLY',
                fn($q) =>
                $q
                    ->whereDoesntHave('images')
                    ->whereNotNull('image')
            )
            ->when(
                $estado === 'EMPTY',
                fn($q) =>
                $q
                    ->whereDoesntHave('images')
                    ->whereNull('image')
            );


        $nombreDeLaEntidad =
            Entity::query()
            ->select('name')
            ->whereColumn(
                'entities.id',
                'entity_versions.entity_id'
            );


        match ($sort) {

            'name' =>
            $query->orderBy('name'),

            'entity' =>
            $query
                ->orderBy($nombreDeLaEntidad)
                ->orderBy('name'),

            'recent' =>
            $query->orderByDesc('updated_at'),

            'empty' =>
            $query
                ->orderBy('images_count')
                ->orderBy('name'),

            default =>
            $query
                ->orderByDesc('images_count')
                ->orderBy('name'),
        };


        $entityVersions =
            $query
            ->paginate(24)
            ->withQueryString();


        $versions =
            Version::query()
            ->ownedBy($user)
            ->active()
            ->orderBy('name')
            ->get();


        $entityTypes =
            EntityType::query()
            ->ownedBy($user)
            ->active()
            ->orderBy('name')
            ->get();


        return view(
            'versions.workspace.media',
            compact(
                'entityVersions',
                'versions',
                'entityTypes',
                'stats',

                'search',
                'versionId',
                'typeId',
                'mediaType',
                'estado',
                'sort'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PROBADOR (retirado)
    |--------------------------------------------------------------------------
    |
    | El taller tenia una quinta pantalla que preguntaba «dado este catalogo,
    | que version saldria». Se retiro porque exigia entender el resolver antes
    | de poder usarla, y casi nadie llegaba a usarla. El motor
    | (VersionResolverService) sigue intacto y en uso; lo unico que desaparecio
    | es la pantalla. Si vuelve a hacer falta, esta en el historial de git.
    |
    */


    /*
    |--------------------------------------------------------------------------
    | COMPARAR VERSIONES DE UNA ENTIDAD
    |--------------------------------------------------------------------------
    */

    public function compare(
        Request $request,
        Entity $entity,
        VersionResolverService $resolver
    ): View {

        $this->authorize(
            'update',
            $entity
        );


        $entity->load([
            'entityAttributes.attribute',
            'entityAttributes.values.option',

            'entityVersions.version',
        ]);


        $selectedIds =
            collect(
                (array) $request->input(
                    'versions',
                    []
                )
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->filter()
            ->unique()
            ->take(4)
            ->values();


        if (
            $selectedIds->isEmpty()
        ) {

            $selectedIds =
                $entity
                ->entityVersions
                ->take(2)
                ->pluck(
                    'id'
                );
        }


        $selectedVersions =
            EntityVersion::query()
            ->where(
                'entity_id',
                $entity->id
            )
            ->whereIn(
                'id',
                $selectedIds
            )
            ->with([
                'version',
            ])
            ->get()
            ->sortBy(
                fn($item) =>
                $selectedIds
                    ->search(
                        $item->id
                    )
            )
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Columna BASE
        |--------------------------------------------------------------------------
        */

        $baseMap =
            $entity
            ->entityAttributes
            ->mapWithKeys(
                function (
                    $assignment
                ) {

                    if (
                        ! $assignment
                            ->attribute
                    ) {
                        return [];
                    }


                    return [
                        $assignment
                            ->attribute_id
                        => [
                            'attribute' =>
                            $assignment->attribute,

                            'display' =>
                            $assignment
                                ->values
                                ->map(
                                    fn($value) =>
                                    $value
                                        ->displayValue()
                                )
                                ->filter()
                                ->implode(
                                    ', '
                                ),
                        ],
                    ];
                }
            );


        $columns =
            collect([
                [
                    'key' =>
                    'base',

                    'label' =>
                    'Entidad base',

                    'name' =>
                    $entity->name,

                    'image_url' =>
                    $entity->image_url,

                    'map' =>
                    $baseMap,
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | Columnas Versiones
        |--------------------------------------------------------------------------
        */

        foreach (
            $selectedVersions
            as $entityVersion
        ) {

            $effective =
                $resolver
                ->effectiveAttributes(
                    $entityVersion
                );


            $map =
                $effective
                ->mapWithKeys(
                    fn($item) => [
                        $item['attribute']->id
                        => [
                            'attribute' =>
                            $item['attribute'],

                            'display' =>
                            $item['display'],

                            'source' =>
                            $item['source_name'],
                        ],
                    ]
                );


            $columns->push([
                'key' =>
                'version-'
                    . $entityVersion->id,

                'label' =>
                $entityVersion
                    ->version
                    ->name,

                'name' =>
                $entityVersion->name,

                'image_url' =>
                $entityVersion
                    ->image_url,

                'map' =>
                $map,
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Filas
        |--------------------------------------------------------------------------
        */

        $attributeMap =
            collect();


        foreach (
            $columns
            as $column
        ) {

            foreach (
                $column['map']
                as $attributeId => $value
            ) {

                $attributeMap->put(
                    $attributeId,
                    $value['attribute']
                );
            }
        }


        $rows =
            $attributeMap
            ->sortBy(
                fn($attribute) =>
                $attribute
                    ->sort_order
                    ?? 0
            )
            ->map(
                function (
                    $attribute
                ) use (
                    $columns
                ) {

                    return [
                        'attribute' =>
                        $attribute,

                        'values' =>
                        $columns
                            ->mapWithKeys(
                                fn($column) => [
                                    $column['key']
                                    =>
                                    $column['map'][$attribute->id]['display']
                                        ?? null,
                                ]
                            ),
                    ];
                }
            )
            ->values();


        return view(
            'entity-versions.compare',
            compact(
                'entity',
                'selectedVersions',
                'columns',
                'rows'
            )
        );
    }
}
