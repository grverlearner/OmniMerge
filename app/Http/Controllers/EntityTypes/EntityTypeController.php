<?php

namespace App\Http\Controllers\EntityTypes;

use App\Http\Controllers\Controller;
use App\Http\Requests\EntityTypes\StoreEntityTypeRequest;
use App\Http\Requests\EntityTypes\UpdateEntityTypeRequest;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class EntityTypeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Listado
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {

        $this->authorize(
            'viewAny',
            EntityType::class
        );


        $search = trim(
            (string) $request->input(
                'search'
            )
        );


        $status =
            $request->input('status');


        $sort =
            (string) $request->input(
                'sort',
                'manual'
            );


        $allowedSorts = [
            'manual',
            'newest',
            'oldest',
            'name_asc',
            'name_desc',
            'code_asc',
            'code_desc',
            'entities_desc',
            'entities_asc',
        ];


        if (
            ! in_array(
                $sort,
                $allowedSorts,
                true
            )
        ) {
            $sort = 'manual';
        }


        $perPage =
            (int) $request->input(
                'per_page',
                24
            );


        if (
            ! in_array(
                $perPage,
                [
                    12,
                    24,
                    48,
                ],
                true
            )
        ) {
            $perPage = 24;
        }


        /*
        |--------------------------------------------------------------------------
        | Estadísticas
        |--------------------------------------------------------------------------
        */

        $statsQuery =
            EntityType::query()
            ->ownedBy(
                $request->user()
            );


        $stats = [

            'total' => (clone $statsQuery)
                ->count(),

            'active' => (clone $statsQuery)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'inactive' => (clone $statsQuery)
                ->where(
                    'status',
                    'INACTIVE'
                )
                ->count(),

            'archived' => (clone $statsQuery)
                ->where(
                    'status',
                    'ARCHIVED'
                )
                ->count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | Consulta principal
        |--------------------------------------------------------------------------
        */

        $query =
            EntityType::query()
            ->ownedBy(
                $request->user()
            )
            ->withCount('entities')
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
            );


        /*
        |--------------------------------------------------------------------------
        | Ordenamiento
        |--------------------------------------------------------------------------
        */

        switch ($sort) {

            case 'newest':

                $query
                    ->orderByDesc(
                        'created_at'
                    )
                    ->orderByDesc('id');

                break;


            case 'oldest':

                $query
                    ->orderBy(
                        'created_at'
                    )
                    ->orderBy('id');

                break;


            case 'name_asc':

                $query->orderBy(
                    'name'
                );

                break;


            case 'name_desc':

                $query->orderByDesc(
                    'name'
                );

                break;


            case 'code_asc':

                $query->orderBy(
                    'code'
                );

                break;


            case 'code_desc':

                $query->orderByDesc(
                    'code'
                );

                break;


            case 'entities_desc':

                $query
                    ->orderByDesc(
                        'entities_count'
                    )
                    ->orderBy('name');

                break;


            case 'entities_asc':

                $query
                    ->orderBy(
                        'entities_count'
                    )
                    ->orderBy('name');

                break;


            default:

                $query
                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy(
                        'sequence_number'
                    )
                    ->orderBy('name');

                break;
        }


        $entityTypes =
            $query
            ->paginate($perPage)
            ->withQueryString();


        return view(
            'entity-types.index',
            compact(
                'entityTypes',
                'search',
                'status',
                'sort',
                'perPage',
                'stats'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    */

    public function create(
        Request $request
    ): View {

        $this->authorize(
            'create',
            EntityType::class
        );


        /*
         * Solo es una vista previa.
         *
         * El valor definitivo vuelve a calcularse dentro de una transacción
         * durante store().
         */

        $nextSequence =
            $this->nextSequence(
                $request->user()->id
            );


        $previewCode =
            EntityType::formatCode(
                $nextSequence
            );


        return view(
            'entity-types.create',
            compact(
                'nextSequence',
                'previewCode'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Guardar
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreEntityTypeRequest $request
    ): RedirectResponse {

        $data =
            $request->validated();


        /*
         * Guardar imagen.
         */

        $imagePath = null;


        if ($request->hasFile('image')) {

            $imagePath =
                $request
                ->file('image')
                ->store(
                    'entity-types',
                    'public'
                );


            $data['image'] =
                $imagePath;
        }


        try {

            $entityType =
                DB::transaction(
                    function () use (
                        $request,
                        $data
                    ) {

                        /*
                         * Bloqueamos al usuario durante esta operación.
                         * Así evitamos que dos creaciones simultáneas
                         * reciban el mismo número.
                         */

                        /** @var User $user */
                        $user =
                            User::query()
                            ->whereKey(
                                $request
                                    ->user()
                                    ->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();


                        $sequence =
                            $this->nextSequence(
                                $user->id
                            );


                        $data['sequence_number'] = $sequence;


                        $data['code'] =
                            EntityType::formatCode(
                                $sequence
                            );


                        /*
                         * El orden visual permanece separado del número
                         * histórico de creación.
                         */

                        $lastSortOrder =
                            (int) EntityType
                                ::withTrashed()
                                ->where(
                                    'user_id',
                                    $user->id
                                )
                                ->max(
                                    'sort_order'
                                );


                        $data['sort_order'] =
                            $lastSortOrder
                            + 10;


                        return $user
                            ->entityTypes()
                            ->create($data);
                    }
                );
        } catch (Throwable $exception) {

            /*
             * Si la base de datos falla después de subir una imagen,
             * eliminamos el archivo para no dejar basura.
             */

            if ($imagePath) {
                Storage::disk(
                    'public'
                )->delete(
                    $imagePath
                );
            }

            throw $exception;
        }


        return redirect()
            ->route(
                'entity-types.show',
                $entityType
            )
            ->with(
                'success',
                'Tipo de entidad creado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Mostrar
    |--------------------------------------------------------------------------
    */

    public function show(
        EntityType $entityType
    ): View {

        $this->authorize(
            'view',
            $entityType
        );


        $entityType->loadCount(
            'entities'
        );


        /*
         * No necesitamos una consulta especial para la imagen:
         * Entity ya posee image_url.
         */

        $entities =
            $entityType
            ->entities()
            ->latest()
            ->limit(12)
            ->get();


        return view(
            'entity-types.show',
            compact(
                'entityType',
                'entities'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Editar
    |--------------------------------------------------------------------------
    */

    public function edit(
        EntityType $entityType
    ): View {

        $this->authorize(
            'update',
            $entityType
        );


        return view(
            'entity-types.edit',
            compact(
                'entityType'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateEntityTypeRequest $request,
        EntityType $entityType
    ): RedirectResponse {

        $data =
            $request->validated();


        /*
         * Nueva imagen.
         */

        if ($request->hasFile('image')) {

            $newImage =
                $request
                ->file('image')
                ->store(
                    'entity-types',
                    'public'
                );


            if ($entityType->image) {

                Storage::disk(
                    'public'
                )->delete(
                    $entityType->image
                );
            }


            $data['image'] =
                $newImage;

            /*
         * Eliminar imagen existente.
         */
        } elseif (
            $request->boolean(
                'remove_image'
            )
        ) {

            if ($entityType->image) {

                Storage::disk(
                    'public'
                )->delete(
                    $entityType->image
                );
            }


            $data['image'] = null;
        }


        unset(
            $data['remove_image']
        );


        /*
         * code
         * sequence_number
         * sort_order
         *
         * NO vienen de validated(), por lo que permanecen inmutables.
         */

        $entityType->update(
            $data
        );


        return redirect()
            ->route(
                'entity-types.show',
                $entityType
            )
            ->with(
                'success',
                'Tipo de entidad actualizado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    public function destroy(
        EntityType $entityType
    ): RedirectResponse {

        $this->authorize(
            'delete',
            $entityType
        );


        /*
         * Nunca eliminamos un tipo utilizado por entidades.
         */

        if (
            $entityType
            ->entities()
            ->exists()
        ) {

            return back()->with(
                'error',
                'No puedes eliminar este tipo porque todavía tiene entidades asociadas.'
            );
        }


        /*
         * Evitar archivos huérfanos.
         */

        if ($entityType->image) {

            Storage::disk(
                'public'
            )->delete(
                $entityType->image
            );
        }


        $entityType->delete();


        return redirect()
            ->route(
                'entity-types.index'
            )
            ->with(
                'success',
                'Tipo de entidad eliminado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Siguiente número histórico
    |--------------------------------------------------------------------------
    */

    private function nextSequence(
        int $userId
    ): int {

        $lastSequence =
            (int) EntityType
                ::withTrashed()
                ->where(
                    'user_id',
                    $userId
                )
                ->max(
                    'sequence_number'
                );


        return $lastSequence + 1;
    }
}
