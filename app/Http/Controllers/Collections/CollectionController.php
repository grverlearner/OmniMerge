<?php

namespace App\Http\Controllers\Collections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collections\StoreCollectionRequest;
use App\Http\Requests\Collections\UpdateCollectionRequest;
use App\Models\AttributeOption;
use App\Models\Collection;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CollectionController extends Controller
{
    public function index(
        Request $request
    ): View {
        $this->authorize(
            'viewAny',
            Collection::class
        );

        $search = trim(
            (string) $request->input(
                'search'
            )
        );

        $status = $request->input(
            'status'
        );

        $visibility = $request->input(
            'visibility'
        );

        $image = $request->input(
            'image'
        );

        $content = $request->input(
            'content'
        );

        $origin = $request->input(
            'origin'
        );

        $sharing = $request->input(
            'sharing'
        );

        $sort = (string) $request->input(
            'sort',
            'newest'
        );

        $perPage = (int) $request->input(
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

        $base = Collection::query()
            ->ownedBy(
                $request->user()
            );

        $stats = [
            'total' => (clone $base)->count(),

            'public' => (clone $base)
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->count(),

            'active' => (clone $base)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'with_entities' => (clone $base)
                ->whereHas(
                    'entities'
                )
                ->count(),

            'empty' => (clone $base)
                ->whereDoesntHave(
                    'entities'
                )
                ->count(),

            'cloned' => (clone $base)
                ->whereNotNull(
                    'source_collection_id'
                )
                ->count(),

            'shareable' => (clone $base)
                ->where(
                    'allow_cloning',
                    true
                )
                ->count(),
        ];


        /*
         * Cuantas entidades cubren TODAS las colecciones juntas, y cuantas se
         * han quedado fuera de todas. La segunda es la unica cifra de esta
         * pantalla sobre la que se puede actuar.
         */

        $stats['covered'] =
            Entity::query()
            ->ownedBy($request->user())
            ->whereHas('collections')
            ->count();

        $stats['uncovered'] =
            Entity::query()
            ->ownedBy($request->user())
            ->whereDoesntHave('collections')
            ->count();

        $query = Collection::query()
            ->ownedBy(
                $request->user()
            )
            ->withCount('entities')

            /*
             * Lo que hay dentro. Una coleccion se reconoce por sus caras, no
             * por su nombre: la vista de contenido las necesita.
             */
            ->with([
                'entities' => fn($relation) => $relation
                    ->select([
                        'entities.id',
                        'entities.name',
                        'entities.image',
                        'entities.entity_type_id',
                    ])
                    ->with('entityType:id,name'),

                'sourceCollection:id,name',
            ])

            ->when(
                $search,
                fn($query) =>
                $query->where(
                    fn($subquery) =>
                    $subquery
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'code',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'description',
                            'like',
                            "%{$search}%"
                        )
                )
            )

            ->when(
                $status,
                fn($query) =>
                $query->where(
                    'status',
                    $status
                )
            )

            ->when(
                $visibility,
                fn($query) =>
                $query->where(
                    'visibility',
                    $visibility
                )
            )

            ->when(
                $image === 'yes',
                fn($query) =>
                $query->whereNotNull(
                    'image'
                )
            )

            ->when(
                $image === 'no',
                fn($query) =>
                $query->whereNull(
                    'image'
                )
            )

            ->when(
                $content === 'yes',
                fn($query) =>
                $query->whereHas('entities')
            )

            ->when(
                $content === 'no',
                fn($query) =>
                $query->whereDoesntHave('entities')
            )

            ->when(
                $origin === 'own',
                fn($query) =>
                $query->whereNull('source_collection_id')
            )

            ->when(
                $origin === 'cloned',
                fn($query) =>
                $query->whereNotNull('source_collection_id')
            )

            ->when(
                $sharing === 'yes',
                fn($query) =>
                $query->where('allow_cloning', true)
            )

            ->when(
                $sharing === 'no',
                fn($query) =>
                $query->where('allow_cloning', false)
            );

        match ($sort) {
            'oldest' =>
            $query->orderBy(
                'created_at'
            ),

            'name_asc' =>
            $query->orderBy(
                'name'
            ),

            'name_desc' =>
            $query->orderByDesc(
                'name'
            ),

            'code_asc' =>
            $query->orderBy(
                'code'
            ),

            'code_desc' =>
            $query->orderByDesc(
                'code'
            ),

            'entities_desc' =>
            $query->orderByDesc(
                'entities_count'
            ),

            'entities_asc' =>
            $query->orderBy(
                'entities_count'
            ),

            'views_desc' =>
            $query->orderByDesc(
                'views_count'
            ),

            'clones_desc' =>
            $query->orderByDesc(
                'clones_count'
            ),

            default =>
            $query->orderByDesc(
                'created_at'
            ),
        };

        $collections = $query
            ->paginate($perPage)
            ->withQueryString();

        return view(
            'collections.index',
            compact(
                'collections',
                'stats',
                'search',
                'status',
                'visibility',
                'image',
                'content',
                'origin',
                'sharing',
                'sort',
                'perPage'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Cambio rapido desde el indice
    |--------------------------------------------------------------------------
    |
    | Cambiar si una coleccion es publica, o retirarla de circulacion, obligaba
    | a abrir el formulario de edicion entero —con su imagen, su color y su
    | lista de entidades— para tocar un desplegable. Aqui se cambia una cosa y
    | se vuelve.
    |
    */

    public function quickUpdate(
        Request $request,
        Collection $collection
    ): RedirectResponse {

        $this->authorize(
            'update',
            $collection
        );


        $data =
            $request->validate(
                [
                    'field' => [
                        'required',
                        'in:visibility,status,allow_cloning',
                    ],

                    'value' => [
                        'required',
                        'string',
                    ],
                ],
                [
                    'field.in' =>
                    'Desde aquí solo se pueden cambiar la visibilidad, el estado y si se puede copiar.',

                    'field.required' =>
                    'Falta decir qué se cambia.',

                    'value.required' =>
                    'Falta el valor nuevo.',
                ]
            );


        $permitido = [
            'visibility' => ['PUBLIC', 'PRIVATE', 'UNLISTED'],
            'status' => ['ACTIVE', 'INACTIVE', 'ARCHIVED'],
            'allow_cloning' => ['0', '1'],
        ];


        if (
            ! in_array(
                $data['value'],
                $permitido[$data['field']],
                true
            )
        ) {

            return back()->withErrors([
                'value' => 'Ese valor no es válido para ese campo.',
            ]);
        }


        $collection->update([
            $data['field'] => $data['field'] === 'allow_cloning'
                ? $data['value'] === '1'
                : $data['value'],
        ]);


        $dicho = match ($data['field']) {

            'visibility' =>
            '«' . $collection->name . '» ahora es '
                . strtolower($collection->visibility_label) . '.',

            'status' =>
            '«' . $collection->name . '» pasa a '
                . match ($collection->status) {
                    'ACTIVE' => 'activa',
                    'INACTIVE' => 'inactiva',
                    'ARCHIVED' => 'archivada',
                    default => strtolower($collection->status),
                }
                . '.',

            default =>
            $collection->allow_cloning
                ? '«' . $collection->name . '» ya se puede copiar.'
                : '«' . $collection->name . '» ya no se puede copiar.',
        };


        return back()->with(
            'success',
            $dicho
        );
    }

    public function create(
        Request $request
    ): View {
        $this->authorize(
            'create',
            Collection::class
        );

        $entities = Entity::query()
            ->ownedBy(
                $request->user()
            )
            ->with([
                'entityType',

                /*
                 * En que colecciones esta ya. Es lo que decide si vale la pena
                 * meterla tambien aqui, y sin esto se elegia a ciegas.
                 */
                'collections:id,name,color',
            ])
            ->orderBy('name')
            ->get();

        $entityTypes = EntityType::query()
            ->ownedBy(
                $request->user()
            )
            ->active()
            ->orderBy('name')
            ->get();

        $previewCode =
            Collection::formatCode(
                $this->nextSequence(
                    $request->user()->id
                )
            );

        return view(
            'collections.create',
            compact(
                'entities',
                'entityTypes',
                'previewCode'
            )
        );
    }

    public function store(
        StoreCollectionRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        $entityIds =
            $data['entity_ids']
            ?? [];

        unset(
            $data['entity_ids']
        );

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request
                ->file('image')
                ->store(
                    'collections',
                    'public'
                );

            $data['image'] =
                $imagePath;
        }

        try {
            $collection = DB::transaction(
                function () use (
                    $request,
                    $data,
                    $entityIds
                ) {
                    /** @var User $user */
                    $user = User::query()
                        ->whereKey(
                            $request->user()->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    $sequence =
                        $this->nextSequence(
                            $user->id
                        );

                    $data['sequence_number'] =
                        $sequence;

                    $data['code'] =
                        Collection::formatCode(
                            $sequence
                        );

                    $data['slug'] =
                        $this->uniqueSlug(
                            $user->id,
                            $data['name']
                        );

                    $data['sort_order'] =
                        (int) Collection::withTrashed()
                            ->where(
                                'user_id',
                                $user->id
                            )
                            ->max(
                                'sort_order'
                            )
                        + 10;

                    $data['published_at'] =
                        $this->shouldPublish(
                            $data
                        )
                        ? now()
                        : null;

                    $collection = $user
                        ->collections()
                        ->create($data);

                    $this->syncEntities(
                        $collection,
                        $entityIds
                    );

                    return $collection;
                }
            );
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')
                    ->delete(
                        $imagePath
                    );
            }

            throw $exception;
        }

        return redirect()
            ->route(
                'collections.show',
                $collection
            )
            ->with(
                'success',
                'Colección creada correctamente.'
            );
    }

    public function show(
        Request $request,
        Collection $collection
    ): View {
        $this->authorize(
            'view',
            $collection
        );

        $collection->load([
            'entities.entityType',
            'entities.collections:id,name,color',
            'sourceCollection:id,name,color',
        ]);

        $collection->loadCount(
            'entities'
        );


        $miembros =
            $collection->entities;

        $miembrosIds =
            $miembros->pluck('id');


        /*
        |--------------------------------------------------------------------------
        | De que esta hecha
        |--------------------------------------------------------------------------
        |
        | El reparto por tipo. Una coleccion de veinte personajes y tres aldeas
        | no es lo mismo que una de veintitres personajes, y con un numero suelto
        | no se distingue.
        |
        */

        $composicion =
            $miembros
            ->groupBy(fn($entidad) => $entidad->entityType?->name ?? 'Sin tipo')
            ->map(
                fn($grupo, $nombre) => [
                    'nombre' => $nombre,
                    'cuantas' => $grupo->count(),
                    'porcentaje' => $miembros->count() > 0
                        ? (int) round(($grupo->count() / $miembros->count()) * 100)
                        : 0,
                    'muestra' => $grupo->take(4),
                ]
            )
            ->sortByDesc('cuantas')
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Que tienen todas en comun
        |--------------------------------------------------------------------------
        |
        | Los valores de catalogo que comparten TODOS los miembros. Es la firma
        | de la coleccion: si las doce son de Konoha, eso es lo que la define, y
        | es tambien lo que permite proponer a quien mas podria entrar.
        |
        */

        $firma = collect();

        $sugeridas = collect();


        if ($miembros->count() >= 2) {

            $conteos =
                DB::table('entity_attribute_values')
                ->join(
                    'entity_attributes',
                    'entity_attributes.id',
                    '=',
                    'entity_attribute_values.entity_attribute_id'
                )
                ->whereIn(
                    'entity_attributes.entity_id',
                    $miembrosIds
                )
                ->whereNotNull('entity_attribute_values.attribute_option_id')
                ->groupBy('entity_attribute_values.attribute_option_id')
                ->selectRaw(
                    'entity_attribute_values.attribute_option_id as option_id,
                     COUNT(DISTINCT entity_attributes.entity_id) as total'
                )
                ->pluck('total', 'option_id');


            $idsDeLaFirma =
                $conteos
                ->filter(
                    fn($total) =>
                    (int) $total === $miembros->count()
                )
                ->keys();


            if ($idsDeLaFirma->isNotEmpty()) {

                $firma =
                    AttributeOption::query()
                    ->whereIn('id', $idsDeLaFirma)
                    ->with('attribute')
                    ->get();


                /*
                 * Quien mas cumple TODA la firma y no esta dentro. Se pide una
                 * condicion por valor: «tiene este» y «tiene aquel», no «tiene
                 * alguno», que devolveria media biblioteca.
                 */

                $candidatas =
                    Entity::query()
                    ->ownedBy($request->user())
                    ->whereNotIn('id', $miembrosIds)
                    ->with('entityType');


                foreach ($idsDeLaFirma as $opcionId) {

                    $candidatas->whereHas(
                        'entityAttributes.values',
                        fn($query) =>
                        $query->where(
                            'attribute_option_id',
                            $opcionId
                        )
                    );
                }


                $sugeridas =
                    $candidatas
                    ->orderBy('name')
                    ->limit(12)
                    ->get();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Cifras
        |--------------------------------------------------------------------------
        */

        $cifras = [
            'entidades' => $miembros->count(),

            'tipos' => $composicion->count(),

            'con_imagen' => $miembros
                ->filter(fn($entidad) => $entidad->image_url)
                ->count(),

            'compartidas' => $miembros
                ->filter(
                    fn($entidad) =>
                    $entidad->collections->where('id', '!=', $collection->id)->isNotEmpty()
                )
                ->count(),
        ];


        $tiposDeLosMiembros =
            $miembros
            ->pluck('entityType')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();


        return view(
            'collections.show',
            compact(
                'collection',
                'composicion',
                'firma',
                'sugeridas',
                'cifras',
                'tiposDeLosMiembros'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Quitar una entidad de la coleccion
    |--------------------------------------------------------------------------
    |
    | Solo suelta el vinculo: la entidad sigue en la biblioteca y en las demas
    | colecciones donde este. Hasta ahora habia que abrir el formulario de
    | edicion entero y desmarcarla en una rejilla de doscientas.
    |
    */

    public function detachEntity(
        Collection $collection,
        Entity $entity
    ): RedirectResponse {

        $this->authorize(
            'update',
            $collection
        );


        abort_unless(
            $entity->user_id === $collection->user_id,
            404
        );


        $collection
            ->entities()
            ->detach($entity->id);


        return back()->with(
            'success',
            '«' . $entity->name . '» ya no está en esta colección. Sigue en tu biblioteca.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Meter entidades sin salir de la ficha
    |--------------------------------------------------------------------------
    */

    public function attachEntities(
        Request $request,
        Collection $collection
    ): RedirectResponse {

        $this->authorize(
            'update',
            $collection
        );


        $data =
            $request->validate(
                [
                    'entity_ids' => [
                        'required',
                        'array',
                        'min:1',
                        'max:200',
                    ],

                    'entity_ids.*' => [
                        'integer',
                    ],
                ],
                [
                    'entity_ids.required' =>
                    'No has elegido ninguna entidad.',
                ]
            );


        /*
         * Solo las suyas, y solo las que no estuvieran ya: attach() sin
         * comprobar crearia filas duplicadas en la tabla intermedia.
         */

        $permitidas =
            Entity::query()
            ->ownedBy($collection->user)
            ->whereIn('id', $data['entity_ids'])
            ->whereDoesntHave(
                'collections',
                fn($query) =>
                $query->where('collections.id', $collection->id)
            )
            ->pluck('id');


        if ($permitidas->isEmpty()) {

            return back()->with(
                'success',
                'No se ha añadido ninguna: ya estaban todas dentro.'
            );
        }


        $orden =
            (int) $collection
            ->entities()
            ->max('sort_order');


        $collection->entities()->attach(
            $permitidas
                ->mapWithKeys(
                    function ($id) use (&$orden) {

                        $orden += 10;

                        return [
                            $id => [
                                'sort_order' => $orden,
                                'added_at' => now(),
                            ],
                        ];
                    }
                )
                ->all()
        );


        return back()->with(
            'success',
            $permitidas->count() === 1
                ? '1 entidad añadida a la colección.'
                : $permitidas->count() . ' entidades añadidas a la colección.'
        );
    }


    public function edit(
        Request $request,
        Collection $collection
    ): View {
        $this->authorize(
            'update',
            $collection
        );

        $collection->load(
            'entities'
        );

        $entities = Entity::query()
            ->ownedBy(
                $request->user()
            )
            ->with([
                'entityType',

                /*
                 * En que colecciones esta ya. Es lo que decide si vale la pena
                 * meterla tambien aqui, y sin esto se elegia a ciegas.
                 */
                'collections:id,name,color',
            ])
            ->orderBy('name')
            ->get();

        $entityTypes = EntityType::query()
            ->ownedBy(
                $request->user()
            )
            ->active()
            ->orderBy('name')
            ->get();

        $previewCode =
            $collection->code;

        return view(
            'collections.edit',
            compact(
                'collection',
                'entities',
                'entityTypes',
                'previewCode'
            )
        );
    }

    public function update(
        UpdateCollectionRequest $request,
        Collection $collection
    ): RedirectResponse {
        $data =
            $request->validated();

        $entityIds =
            $data['entity_ids']
            ?? [];

        unset(
            $data['entity_ids']
        );

        $oldImage =
            $collection->image;

        $newImage =
            null;

        if (
            $request->hasFile('image')
        ) {
            $newImage = $request
                ->file('image')
                ->store(
                    'collections',
                    'public'
                );

            $data['image'] =
                $newImage;
        } elseif (
            $request->boolean(
                'remove_image'
            )
        ) {
            $data['image'] =
                null;
        }

        unset(
            $data['remove_image']
        );

        if ($this->shouldPublish($data)) {
            $data['published_at'] =
                $collection->published_at
                ?? now();
        } else {
            $data['published_at'] =
                null;
        }

        try {
            DB::transaction(
                function () use (
                    $collection,
                    $data,
                    $entityIds
                ) {
                    $collection->update(
                        $data
                    );

                    $this->syncEntities(
                        $collection,
                        $entityIds
                    );
                }
            );
        } catch (Throwable $exception) {
            if ($newImage) {
                Storage::disk('public')
                    ->delete(
                        $newImage
                    );
            }

            throw $exception;
        }

        if (
            $oldImage
            &&
            (
                $newImage
                ||
                $request->boolean(
                    'remove_image'
                )
            )
        ) {
            Storage::disk('public')
                ->delete(
                    $oldImage
                );
        }

        return redirect()
            ->route(
                'collections.show',
                $collection
            )
            ->with(
                'success',
                'Colección actualizada correctamente.'
            );
    }

    public function destroy(
        Collection $collection
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $collection
        );

        /*
         * Conservamos portada durante SoftDelete.
         */

        $collection->delete();

        return redirect()
            ->route(
                'collections.index'
            )
            ->with(
                'success',
                'Colección eliminada correctamente.'
            );
    }

    private function nextSequence(
        int $userId
    ): int {
        return (
            (int) Collection::withTrashed()
                ->where(
                    'user_id',
                    $userId
                )
                ->max(
                    'sequence_number'
                )
        ) + 1;
    }

    private function uniqueSlug(
        int $userId,
        string $name
    ): string {
        $base =
            Str::slug($name)
            ?: 'coleccion';

        $slug = $base;
        $counter = 2;

        while (
            Collection::withTrashed()
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'slug',
                $slug
            )
            ->exists()
        ) {
            $slug =
                $base . '-' . $counter;

            $counter++;
        }

        return $slug;
    }

    private function shouldPublish(
        array $data
    ): bool {
        return (
            $data['visibility']
            ?? null
        ) === 'PUBLIC'
            &&
            (
                $data['status']
                ?? null
            ) === 'ACTIVE';
    }

    private function syncEntities(
        Collection $collection,
        array $entityIds
    ): void {
        $sync = [];

        foreach (
            array_values(
                array_unique(
                    array_map(
                        'intval',
                        $entityIds
                    )
                )
            )
            as $index => $entityId
        ) {
            $sync[$entityId] = [
                'sort_order' => ($index + 1) * 10,

                'added_at' =>
                now(),
            ];
        }

        $collection
            ->entities()
            ->sync($sync);
    }
}
