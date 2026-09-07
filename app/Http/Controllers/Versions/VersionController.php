<?php

namespace App\Http\Controllers\Versions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Versions\VersionRequest;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Entity;
use App\Models\Version;
use App\Services\Versions\VersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class VersionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(
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
                (string) $request->input(
                    'search'
                )
            );

        $kind =
            (string) $request->input(
                'kind'
            );

        $scope =
            (string) $request->input(
                'scope'
            );

        $status =
            (string) $request->input(
                'status'
            );

        $activation =
            (string) $request->input(
                'activation'
            );


        $base =
            Version::query()
            ->ownedBy(
                $user
            );


        $stats = [
            'total' => (clone $base)
                ->count(),

            'shared' => (clone $base)
                ->where(
                    'scope',
                    'SHARED'
                )
                ->count(),

            'exclusive' => (clone $base)
                ->where(
                    'scope',
                    'EXCLUSIVE'
                )
                ->count(),

            'automatic' => (clone $base)
                ->whereIn(
                    'activation_mode',
                    [
                        'AUTO',
                        'BOTH',
                    ]
                )
                ->count(),

            'active' => (clone $base)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),
        ];


        $versions =
            Version::query()
            ->ownedBy(
                $user
            )
            ->with([
                'parent',
                'catalogLinks.option.attribute',
            ])
            ->withCount([
                'entityVersions',
                'children',
                'catalogLinks',
            ])
            ->when(
                $search,
                fn($query) =>
                $query->where(
                    function ($subquery) use (
                        $search
                    ) {

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
                            );
                    }
                )
            )
            ->when(
                $kind,
                fn($query) =>
                $query->where(
                    'version_kind',
                    $kind
                )
            )
            ->when(
                $scope,
                fn($query) =>
                $query->where(
                    'scope',
                    $scope
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
                $activation,
                fn($query) =>
                $query->where(
                    'activation_mode',
                    $activation
                )
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->paginate(24)
            ->withQueryString();


        $treeVersions =
            Version::query()
            ->ownedBy(
                $user
            )
            ->whereNull(
                'parent_version_id'
            )
            /*
             * Las hijas tambien necesitan su cuenta: el arbol ensena en cada
             * rama en cuantas entidades se ha aplicado ese molde, y sin esto
             * las de segundo y tercer nivel salian a cero siempre.
             */
            ->with([
                'children' => fn($relation) => $relation
                    ->withCount('entityVersions')
                    ->with([
                        'children' => fn($nieta) => $nieta->withCount('entityVersions'),
                    ]),
            ])
            ->withCount(
                'entityVersions'
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->get();


        return view(
            'versions.index',
            compact(
                'versions',
                'treeVersions',
                'stats',

                'search',
                'kind',
                'scope',
                'status',
                'activation'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(
        Request $request,
        VersionService $service
    ): View {

        $this->authorize(
            'create',
            Version::class
        );


        return view(
            'versions.form',
            array_merge(
                $this->formResources(
                    $request
                ),
                [
                    'version' =>
                    null,

                    'previewCode' =>
                    $service->nextCode(
                        $request->user()
                    ),
                ]
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        VersionRequest $request,
        VersionService $service
    ): RedirectResponse {

        $this->authorize(
            'create',
            Version::class
        );


        $data =
            $request->validated();


        $catalogLinks =
            $data['catalog_links']
            ?? [];


        unset(
            $data['catalog_links'],
            $data['image']
        );


        $imagePath =
            $request
            ->file(
                'image'
            )
            ->store(
                'versions',
                'public'
            );


        $data['image'] =
            $imagePath;


        try {

            $version =
                $service->create(
                    $request->user(),
                    $data,
                    $catalogLinks
                );
        } catch (Throwable $exception) {

            Storage::disk(
                'public'
            )
                ->delete(
                    $imagePath
                );


            throw $exception;
        }


        return redirect()
            ->route(
                'versions.show',
                $version
            )
            ->with(
                'success',
                'Versión creada correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Version $version
    ): View {

        $this->authorize(
            'view',
            $version
        );


        $user =
            $request->user();


        $version->load([
            'parent.parent',

            'children' => fn($q) => $q
                ->withCount('entityVersions')
                ->orderBy('sort_order')
                ->orderBy('name'),

            'catalogLinks.attribute',
            'catalogLinks.option.attribute',

            'entityVersions' => fn($q) => $q
                ->with(['entity.entityType'])
                ->withCount(['versionAttributes', 'images'])
                ->orderByDesc('is_default')
                ->orderBy('name'),
        ]);


        $version->loadCount([
            'entityVersions',
            'children',
            'catalogLinks',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Las reglas, agrupadas
        |--------------------------------------------------------------------------
        |
        | Solo los enlaces ACTIVATES deciden algo: el resolver agrupa por
        | condition_group —los grupos son alternativas OR entre si— y dentro de
        | cada grupo encadena con el operador de cada enlace. Los de CONTEXT y
        | RELATED son documentacion: se guardan, se ensenan, y el motor no los
        | mira. Eso se dice en la pantalla en vez de dejarlo adivinar.
        |
        */

        $activationGroups =
            $version
            ->catalogLinks
            ->where('relation_type', 'ACTIVATES')
            ->sortBy('id')
            ->groupBy('condition_group');


        $contextLinks =
            $version
            ->catalogLinks
            ->where('relation_type', '!=', 'ACTIVATES')
            ->sortBy('id')
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Cuantas entidades tiene cada opcion enlazada
        |--------------------------------------------------------------------------
        |
        | Una regla que apunta a una opcion que nadie usa no activa nada nunca,
        | y eso es invisible hasta que se cuenta.
        |
        */

        $linkedOptionIds =
            $version
            ->catalogLinks
            ->pluck('attribute_option_id')
            ->unique()
            ->values();


        $optionUsage =
            DB::table('entity_attribute_values')
            ->join(
                'entity_attributes',
                'entity_attributes.id',
                '=',
                'entity_attribute_values.entity_attribute_id'
            )
            ->join(
                'entities',
                'entities.id',
                '=',
                'entity_attributes.entity_id'
            )
            ->where('entities.user_id', $user->id)
            ->whereNull('entities.deleted_at')
            ->whereNotNull('entity_attribute_values.attribute_option_id')
            ->groupBy('entity_attribute_values.attribute_option_id')
            ->selectRaw(
                'entity_attribute_values.attribute_option_id as option_id,
                 COUNT(DISTINCT entities.id) as total'
            )
            ->pluck('total', 'option_id');


        /*
        |--------------------------------------------------------------------------
        | Lo que ya comparten las que la llevan
        |--------------------------------------------------------------------------
        |
        | El reves de una regla. Una regla dice «activa con esto»; esto dice
        | «las que ya la llevan tienen esto en comun», que es justo de donde
        | sale la regla que habria que escribir. Si las tres que llevan
        | «Shippuden» tienen las tres Anime = Naruto: Shippuden, la regla se
        | escribe sola.
        |
        */

        $sharedTraits = collect();


        if ($assignedIdsParaRasgos = $version->entityVersions->pluck('entity_id')->unique()) {

            $conteos =
                $assignedIdsParaRasgos->isEmpty()
                ? collect()
                : DB::table('entity_attribute_values')
                ->join(
                    'entity_attributes',
                    'entity_attributes.id',
                    '=',
                    'entity_attribute_values.entity_attribute_id'
                )
                ->whereIn(
                    'entity_attributes.entity_id',
                    $assignedIdsParaRasgos
                )
                ->whereNotNull('entity_attribute_values.attribute_option_id')
                ->groupBy('entity_attribute_values.attribute_option_id')
                ->selectRaw(
                    'entity_attribute_values.attribute_option_id as option_id,
                     COUNT(DISTINCT entity_attributes.entity_id) as total'
                )
                ->pluck('total', 'option_id');


            if ($conteos->isNotEmpty()) {

                $sharedTraits =
                    AttributeOption::query()
                    ->whereIn('id', $conteos->keys())
                    ->with('attribute')
                    ->get()
                    ->map(
                        function (AttributeOption $opcion) use ($conteos, $assignedIdsParaRasgos) {

                            $cuantas = (int) $conteos[$opcion->id];

                            $opcion->setAttribute('cuantas', $cuantas);

                            $opcion->setAttribute(
                                'todas',
                                $cuantas === $assignedIdsParaRasgos->count()
                            );

                            return $opcion;
                        }
                    )
                    ->sortByDesc('cuantas')
                    ->values();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Cobertura
        |--------------------------------------------------------------------------
        |
        | Las mismas tres medidas que la pantalla de Cobertura, para que un
        | mismo molde no diga aqui una cosa y alli otra.
        |
        */

        $assignedIds =
            $version
            ->entityVersions
            ->pluck('entity_id');


        $activationOptionIds =
            $version
            ->catalogLinks
            ->where('relation_type', 'ACTIVATES')
            ->pluck('attribute_option_id')
            ->unique()
            ->values();


        if ($version->isExclusive()) {

            $coverageMode = 'EXCLUSIVE';

            $eligibleEntities = collect();

        } elseif ($activationOptionIds->isNotEmpty()) {

            $coverageMode = 'AUTO';

            $eligibleEntities =
                Entity::query()
                ->ownedBy($user)
                ->whereHas(
                    'entityAttributes.values',
                    fn($query) =>
                    $query->whereIn(
                        'attribute_option_id',
                        $activationOptionIds
                    )
                )
                ->with('entityType')
                ->orderBy('name')
                ->get();

        } else {

            $coverageMode = 'MANUAL';

            $eligibleEntities =
                Entity::query()
                ->ownedBy($user)
                ->active()
                ->with('entityType')
                ->orderBy('name')
                ->get();
        }


        $missingEntities =
            $eligibleEntities
            ->whereNotIn('id', $assignedIds)
            ->values();


        $coveragePercentage =
            $eligibleEntities->isEmpty()
            ? null
            : (int) round(
                (
                    $eligibleEntities->count()
                    - $missingEntities->count()
                )
                    / $eligibleEntities->count()
                    * 100
            );


        /*
        |--------------------------------------------------------------------------
        | Para el formulario de reglas
        |--------------------------------------------------------------------------
        */

        $catalogAttributes =
            Attribute::query()
            ->ownedBy($user)
            ->active()
            ->where('data_type', 'OPTION')
            ->with([
                'options' => fn($q) => $q
                    ->where('status', 'ACTIVE')
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get()
            ->sortByDesc(
                fn($atributo) =>
                $atributo->options->count()
            )
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Candidatas a asociar
        |--------------------------------------------------------------------------
        |
        | Las que aun no la llevan. Van primero las que cumplen las reglas,
        | porque son las que el molde estaba esperando.
        |
        */

        $eligibleIds =
            $eligibleEntities->pluck('id');


        $candidateEntities =
            Entity::query()
            ->ownedBy($user)
            ->active()
            ->whereNotIn('id', $assignedIds)
            ->with('entityType')
            ->orderBy('name')
            ->get()
            ->map(
                function (Entity $entidad) use ($eligibleIds, $coverageMode) {

                    $entidad->setAttribute(
                        'cumple_reglas',
                        $coverageMode === 'AUTO'
                            && $eligibleIds->contains($entidad->id)
                    );

                    return $entidad;
                }
            )
            ->sortByDesc('cumple_reglas')
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Cifras de las entidades vinculadas
        |--------------------------------------------------------------------------
        */

        $appliedStats = [
            'total' => $version->entityVersions->count(),

            'default' => $version->entityVersions
                ->where('is_default', true)
                ->count(),

            'with_overrides' => $version->entityVersions
                ->where('version_attributes_count', '>', 0)
                ->count(),

            'with_media' => $version->entityVersions
                ->where('images_count', '>', 0)
                ->count(),

            'types' => $version->entityVersions
                ->pluck('entity.entity_type_id')
                ->filter()
                ->unique()
                ->count(),
        ];


        /*
         * La ruta de jerarquia, de la raiz hasta esta.
         */

        $ancestros = collect();

        $nodo = $version->parent;

        while ($nodo && $ancestros->count() < 5) {
            $ancestros->prepend($nodo);
            $nodo = $nodo->relationLoaded('parent') ? $nodo->parent : null;
        }


        return view(
            'versions.show',
            compact(
                'version',

                'activationGroups',
                'contextLinks',
                'optionUsage',
                'linkedOptionIds',
                'sharedTraits',
                'catalogAttributes',

                'coverageMode',
                'eligibleEntities',
                'missingEntities',
                'coveragePercentage',

                'candidateEntities',
                'appliedStats',
                'ancestros'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        Request $request,
        Version $version,
        VersionService $service
    ): View {

        $this->authorize(
            'update',
            $version
        );


        $version->load([
            'catalogLinks',
        ]);


        return view(
            'versions.form',
            array_merge(
                $this->formResources(
                    $request,
                    $version
                ),
                [
                    'version' =>
                    $version,

                    'previewCode' =>
                    $version->code,
                ]
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        VersionRequest $request,
        Version $version,
        VersionService $service
    ): RedirectResponse {

        $this->authorize(
            'update',
            $version
        );


        $data =
            $request->validated();


        $catalogLinks =
            $data['catalog_links']
            ?? [];


        unset(
            $data['catalog_links'],
            $data['image']
        );


        $oldImage =
            $version->image;

        $newImage =
            null;


        if (
            $request->hasFile(
                'image'
            )
        ) {

            $newImage =
                $request
                ->file(
                    'image'
                )
                ->store(
                    'versions',
                    'public'
                );


            $data['image'] =
                $newImage;
        }


        try {

            $service->update(
                $request->user(),
                $version,
                $data,
                $catalogLinks
            );
        } catch (Throwable $exception) {

            if ($newImage) {

                Storage::disk(
                    'public'
                )
                    ->delete(
                        $newImage
                    );
            }


            throw $exception;
        }


        if (
            $newImage
            &&
            $oldImage
        ) {

            Storage::disk(
                'public'
            )
                ->delete(
                    $oldImage
                );
        }


        return redirect()
            ->route(
                'versions.show',
                $version
            )
            ->with(
                'success',
                'Versión actualizada correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Version $version
    ): RedirectResponse {

        $this->authorize(
            'delete',
            $version
        );


        /*
         * Soft Delete:
         * mantenemos archivos para futura restauración/historial.
         */
        $version->delete();


        return redirect()
            ->route(
                'versions.index'
            )
            ->with(
                'success',
                'Versión eliminada.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Recursos formulario
    |--------------------------------------------------------------------------
    */

    private function formResources(
        Request $request,
        ?Version $editing = null
    ): array {

        $user =
            $request->user();


        $parentVersions =
            Version::query()
            ->ownedBy(
                $user
            )
            ->when(
                $editing,
                fn($query) =>
                $query->whereKeyNot(
                    $editing->id
                )
            )
            ->orderBy(
                'name'
            )
            ->get();


        /*
         * Solo Catálogos.
         */
        $catalogAttributes =
            Attribute::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->where(
                'data_type',
                'OPTION'
            )
            ->with([
                'options' =>
                fn($query) =>
                $query
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy(
                        'name'
                    ),
            ])
            ->orderBy(
                'name'
            )
            ->get();


        $catalogPayload =
            $catalogAttributes
            ->map(
                fn($attribute) => [
                    'id' =>
                    (string) $attribute->id,

                    'name' =>
                    $attribute->name,

                    'options' =>
                    $attribute
                        ->options
                        ->map(
                            fn($option) => [
                                'id' =>
                                (string) $option->id,

                                'name' =>
                                $option->name,

                                'image_url' =>
                                $option->image_url,
                            ]
                        )
                        ->values()
                        ->all(),
                ]
            )
            ->values()
            ->all();


        $linkPayload =
            $editing
            ? $editing
            ->catalogLinks
            ->map(
                fn($link) => [
                    'attribute_id' =>
                    (string) $link->attribute_id,

                    'attribute_option_id' =>
                    (string) $link->attribute_option_id,

                    'relation_type' =>
                    $link->relation_type,

                    'condition_group' =>
                    (int) $link->condition_group,

                    'logical_operator' =>
                    $link->logical_operator,

                    'is_required' =>
                    (bool) $link->is_required,

                    'priority' =>
                    (int) $link->priority,
                ]
            )
            ->values()
            ->all()

            : [];


        return compact(
            'parentVersions',
            'catalogAttributes',
            'catalogPayload',
            'linkPayload'
        );
    }
}
