<?php

namespace App\Http\Controllers\Versions;

use App\Http\Controllers\Controller;

use App\Models\Entity;
use App\Models\Version;

use App\Services\Versions\EntityVersionService;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/*
|--------------------------------------------------------------------------
| Asociar entidades a una definicion, desde su propia ficha
|--------------------------------------------------------------------------
|
| Ya existia el aplicador en lote, que es una pantalla entera con su nombre,
| su descripcion y su imagen por entidad. Sirve cuando se van a asociar veinte
| con cuidado.
|
| Lo que faltaba era lo contrario: estar mirando la ficha de un molde, ver que
| a tres entidades les falta, y ponerselo ahi mismo. Para eso hace falta
| resolver el unico dato obligatorio que tiene una version de entidad —la
| imagen— sin pedirla: se copia la de la propia entidad, que es exactamente lo
| que se quiere de partida cuando la version todavia no tiene cara propia.
|
| Quien quiera cada imagen distinta sigue teniendo el aplicador en lote, y
| desde aqui se enlaza.
|
*/

class VersionEntityLinkController extends Controller
{
    public function store(
        Request $request,
        Version $version,
        EntityVersionService $service
    ): RedirectResponse {

        $this->authorize(
            'update',
            $version
        );


        $user =
            $request->user();


        $data =
            $request->validate(
                [
                    'entity_ids' => [
                        'required',
                        'array',
                        'min:1',
                        'max:100',
                    ],

                    'entity_ids.*' => [
                        'integer',
                        'distinct',
                        Rule::exists('entities', 'id')
                            ->where(
                                fn($q) =>
                                $q
                                    ->where('user_id', $user->id)
                                    ->whereNull('deleted_at')
                            ),
                    ],
                ],
                [
                    'entity_ids.required' =>
                    'Elige al menos una entidad.',
                ]
            );


        $entities =
            Entity::query()
            ->ownedBy($user)
            ->whereIn('id', $data['entity_ids'])
            ->orderBy('name')
            ->get();


        /*
         * Una version de entidad necesita imagen siempre. Si la entidad
         * tampoco tiene, no hay de donde copiarla y hay que decirlo por su
         * nombre en vez de fallar al guardar.
         */

        $disk =
            Storage::disk('public');


        $sinImagen =
            $entities
            ->filter(
                fn(Entity $entidad) =>
                ! $entidad->image
                    || ! $disk->exists($entidad->image)
            )
            ->pluck('name');


        if ($sinImagen->isNotEmpty()) {

            throw ValidationException::withMessages([
                'entity_ids' =>
                'Estas entidades no tienen imagen propia que copiar, así que hay que '
                    . 'subírsela desde el aplicador en lote: '
                    . $sinImagen->take(8)->implode(', ')
                    . ($sinImagen->count() > 8 ? '…' : ''),
            ]);
        }


        $copiadas = [];


        try {

            $filas = [];


            foreach ($entities as $entidad) {

                $destino =
                    'entity-versions/'
                    . uniqid('ev-', true)
                    . '.'
                    . (pathinfo($entidad->image, PATHINFO_EXTENSION) ?: 'jpg');


                $disk->copy(
                    $entidad->image,
                    $destino
                );


                $copiadas[] = $destino;


                $filas[] = [
                    'entity_id' => $entidad->id,

                    'name' => $entidad->name
                        . ' — '
                        . $version->name,

                    'description' => null,

                    'image' => $destino,
                ];
            }


            $resultado =
                $service->createMany(
                    $user,
                    $version,
                    $filas
                );

        } catch (Throwable $excepcion) {

            foreach ($copiadas as $ruta) {
                $disk->delete($ruta);
            }

            throw $excepcion;
        }


        $creadas =
            count($resultado['created']);

        $saltadas =
            count($resultado['skipped']);


        /*
         * Las que se saltaron ya tenian esta version: sus copias de imagen
         * sobran y se borran, que si no quedan huerfanas en el disco.
         */

        if ($saltadas > 0) {

            $usadas =
                collect($resultado['created'])
                ->pluck('image')
                ->all();


            foreach ($copiadas as $ruta) {

                if (! in_array($ruta, $usadas, true)) {
                    $disk->delete($ruta);
                }
            }
        }


        $mensaje =
            $creadas === 1
            ? '1 entidad asociada. Su imagen se copió de la entidad: cámbiala cuando quieras.'
            : $creadas . ' entidades asociadas. Sus imágenes se copiaron de cada entidad: cámbialas cuando quieras.';


        if ($creadas === 0) {

            $mensaje =
                'Ninguna se asoció: ya llevaban esta definición.';
        } elseif ($saltadas > 0) {

            $mensaje .=
                ' (' . $saltadas . ' ya la llevaban.)';
        }


        return back()
            ->with(
                'success',
                $mensaje
            );
    }
}
