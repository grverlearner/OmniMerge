<?php

namespace App\Http\Controllers\Versions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Versions\BulkEntityVersionRequest;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\EntityVersion;
use App\Models\Version;
use App\Services\Versions\EntityVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class BulkEntityVersionController extends Controller
{
    public function create(
        Request $request,
        Version $version
    ): View {

        $this->authorize(
            'update',
            $version
        );


        $user =
            $request->user();


        $search =
            trim(
                (string) $request->input(
                    'search'
                )
            );


        $typeId =
            $request->filled(
                'type'
            )
            ? $request->integer(
                'type'
            )
            : null;


        /*
         * Incluimos eliminadas al determinar ya usadas,
         * porque el UNIQUE sigue existiendo.
         */
        $usedEntityIds =
            EntityVersion::withTrashed()
            ->where(
                'version_id',
                $version->id
            )
            ->pluck(
                'entity_id'
            );


        $entities =
            Entity::query()
            ->ownedBy(
                $user
            )
            ->with(
                'entityType'
            )
            ->whereNotIn(
                'id',
                $usedEntityIds
            )
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
                            );
                    }
                )
            )
            ->when(
                $typeId,
                fn($query) =>
                $query->where(
                    'entity_type_id',
                    $typeId
                )
            )
            ->orderBy(
                'name'
            )
            ->limit(200)
            ->get();


        $entityTypes =
            EntityType::query()
            ->ownedBy(
                $user
            )
            ->active()
            ->orderBy(
                'name'
            )
            ->get();


        /*
         * Quien cumple las reglas de catalogo del molde.
         *
         * Un molde con reglas ACTIVATES no es para cualquiera: esta esperando a
         * las entidades que tienen esos valores. Marcarlas cambia el trabajo de
         * «elegir entre doscientas» a «confirmar estas dieciseis».
         */

        $optionIds =
            $version
            ->catalogLinks()
            ->where('relation_type', 'ACTIVATES')
            ->pluck('attribute_option_id')
            ->unique();


        $eligibleIds =
            $optionIds->isEmpty()
            ? collect()
            : Entity::query()
            ->ownedBy($user)
            ->whereHas(
                'entityAttributes.values',
                fn($query) =>
                $query->whereIn(
                    'attribute_option_id',
                    $optionIds
                )
            )
            ->pluck('id');


        /*
         * Cuantas quedan fuera de la pagina: el limite de 200 existe, y
         * callarselo hace creer que la biblioteca es mas pequena de lo que es.
         */

        $totalDisponibles =
            Entity::query()
            ->ownedBy($user)
            ->whereNotIn('id', $usedEntityIds)
            ->count();


        $yaAplicada =
            $usedEntityIds->count();


        return view(
            'versions.bulk-entities',
            compact(
                'version',
                'entities',
                'entityTypes',
                'eligibleIds',
                'totalDisponibles',
                'yaAplicada',
                'search',
                'typeId'
            )
        );
    }


    public function store(
        BulkEntityVersionRequest $request,
        Version $version,
        EntityVersionService $service
    ): RedirectResponse {

        $this->authorize(
            'update',
            $version
        );


        $data =
            $request->validated();


        $entities =
            Entity::query()
            ->ownedBy(
                $request->user()
            )
            ->whereIn(
                'id',
                $data['entity_ids']
            )
            ->get()
            ->keyBy(
                'id'
            );


        $storedPaths = [];


        try {

            $images = [];


            /*
            |--------------------------------------------------------------------------
            | Individuales
            |--------------------------------------------------------------------------
            */

            foreach (
                $request->file(
                    'images',
                    []
                )
                as $entityId => $file
            ) {

                if (
                    ! $entities->has(
                        (int) $entityId
                    )
                ) {
                    continue;
                }


                $path =
                    $file->store(
                        'entity-versions',
                        'public'
                    );


                $images[(int) $entityId] =
                    $path;


                $storedPaths[] =
                    $path;
            }


            /*
            |--------------------------------------------------------------------------
            | Imágenes masivas por nombre
            |--------------------------------------------------------------------------
            */

            $available = [];


            foreach (
                $entities
                as $entity
            ) {

                if (
                    isset(
                        $images[$entity->id]
                    )
                ) {
                    continue;
                }


                $available[Str::slug(
                    $entity->name
                )][] =
                    $entity->id;
            }


            foreach (
                $request->file(
                    'bulk_images',
                    []
                )
                as $file
            ) {

                $base =
                    pathinfo(
                        $file
                            ->getClientOriginalName(),
                        PATHINFO_FILENAME
                    );


                $key =
                    Str::slug(
                        $base
                    );


                if (
                    empty($available[$key])
                ) {
                    continue;
                }


                $entityId =
                    array_shift(
                        $available[$key]
                    );


                $path =
                    $file->store(
                        'entity-versions',
                        'public'
                    );


                $images[$entityId] =
                    $path;


                $storedPaths[] =
                    $path;
            }


            /*
            |--------------------------------------------------------------------------
            | Copiar la cara de la entidad
            |--------------------------------------------------------------------------
            |
            | Para las que lo pidan y sigan sin imagen. Es lo que se quiere de
            | partida cuando la version todavia no tiene cara propia, y se dice
            | en el mensaje de exito para que nadie crea que subio algo.
            |
            */

            $disk =
                Storage::disk('public');


            $copiarDeEntidad =
                collect(
                    $data['use_entity_image']
                        ?? []
                )
                ->map(fn($id) => (int) $id);


            $copiadas = 0;


            foreach (
                $entities
                as $entity
            ) {

                if (
                    isset($images[$entity->id])
                    || ! $copiarDeEntidad->contains($entity->id)
                    || ! $entity->image
                    || ! $disk->exists($entity->image)
                ) {
                    continue;
                }


                $destino =
                    'entity-versions/'
                    . uniqid('ev-', true)
                    . '.'
                    . (pathinfo($entity->image, PATHINFO_EXTENSION) ?: 'jpg');


                $disk->copy(
                    $entity->image,
                    $destino
                );


                $images[$entity->id] =
                    $destino;

                $storedPaths[] =
                    $destino;

                $copiadas++;
            }


            /*
            |--------------------------------------------------------------------------
            | Todas deben tener imagen
            |--------------------------------------------------------------------------
            */

            $missing = [];


            foreach (
                $entities
                as $entity
            ) {

                if (
                    ! isset(
                        $images[$entity->id]
                    )
                ) {

                    $missing[] =
                        $entity->name;
                }
            }


            if (! empty($missing)) {

                throw ValidationException::withMessages([
                    'bulk_images' =>
                    'Cada versión necesita una imagen y estas se han quedado sin ninguna: '
                        . implode(
                            ', ',
                            array_slice($missing, 0, 10)
                        )
                        . (count($missing) > 10 ? '…' : '')
                        . '. Súbeles un archivo, o marca «usar la imagen de la entidad».',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Rows
            |--------------------------------------------------------------------------
            */

            $rows = [];


            foreach (
                $entities
                as $entity
            ) {

                $name =
                    trim(
                        (string) (
                            $data['names'][$entity->id]
                            ?? ''
                        )
                    );


                $rows[] = [
                    'entity_id' =>
                    $entity->id,

                    'name' =>
                    $name !== ''
                        ? $name
                        : (
                            $entity->name
                            . ' — '
                            . $version->name
                        ),

                    'description' =>
                    $data['descriptions'][$entity->id]
                        ?? null,

                    'image' =>
                    $images[$entity->id],
                ];
            }


            $result =
                $service->createMany(
                    $request->user(),
                    $version,
                    $rows
                );


            $creadas =
                count($result['created']);


            $mensaje =
                $creadas === 1
                ? '1 versión creada.'
                : $creadas . ' versiones creadas.';


            if ($copiadas > 0) {

                $mensaje .=
                    ' '
                    . ($copiadas === 1
                        ? 'A una se le copió la imagen de su entidad'
                        : 'A ' . $copiadas . ' se les copió la imagen de su entidad')
                    . ': cámbiala cuando quieras.';
            }


            return redirect()
                ->route(
                    'versions.show',
                    $version
                )
                ->with(
                    'success',
                    $mensaje
                );
        } catch (Throwable $exception) {

            foreach (
                $storedPaths
                as $path
            ) {

                Storage::disk(
                    'public'
                )
                    ->delete(
                        $path
                    );
            }


            throw $exception;
        }
    }
}
