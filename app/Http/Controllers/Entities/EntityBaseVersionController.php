<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\Controller;

use App\Models\Entity;
use App\Models\EntityVersion;

use App\Services\Entities\EntityBaseVersionService;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EntityBaseVersionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Establecer Base activa
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Entity $entity,
        EntityBaseVersionService $service
    ): RedirectResponse {

        $this->authorize(
            'update',
            $entity
        );


        $data =
            $request->validate([
                'entity_version_id' => [
                    'required',
                    'integer',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | No confiar solamente en el ID enviado desde HTML
        |--------------------------------------------------------------------------
        |
        | El Service vuelve a validar:
        |
        | - usuario;
        | - Entidad;
        | - propiedad;
        | - estado ACTIVE.
        |
        */

        $entityVersion =
            EntityVersion::query()
            ->findOrFail(
                (int) $data['entity_version_id']
            );


        $service->setActiveBase(
            $request->user(),
            $entity,
            $entityVersion
        );


        return back()
            ->with(
                'success',
                "{$entityVersion->name} ahora es la Base activa de {$entity->name}."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Restaurar Base original
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        Entity $entity,
        EntityBaseVersionService $service
    ): RedirectResponse {

        $this->authorize(
            'update',
            $entity
        );


        $service->resetToOriginal(
            $request->user(),
            $entity
        );


        return back()
            ->with(
                'success',
                'La Entidad volvió a utilizar su Base original.'
            );
    }
}
