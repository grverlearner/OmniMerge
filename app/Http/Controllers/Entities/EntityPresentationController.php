<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\Controller;

use App\Models\Entity;

use App\Services\Entities\EntityPresentationService;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EntityPresentationController extends Controller
{
    public function edit(
        Request $request,
        Entity $entity
    ): View {

        $this->authorize(
            'update',
            $entity
        );


        $entity->load([
            'entityType',

            'presentation.entityVersion.version',
            'presentation.mediaImage',

            'entityVersions' =>
            fn($query) =>
            $query
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->with([
                    'version',
                    'images',
                ])
                ->orderByDesc(
                    'is_default'
                )
                ->orderBy(
                    'sort_order'
                )
                ->orderBy(
                    'name'
                ),
        ]);


        return view(
            'entities.presentation',
            compact(
                'entity'
            )
        );
    }


    public function update(
        Request $request,
        Entity $entity,
        EntityPresentationService $service
    ): RedirectResponse {

        $this->authorize(
            'update',
            $entity
        );


        $data =
            $request->validate([
                'mode' => [
                    'required',

                    Rule::in([
                        'BASE',
                        'VERSION_PRIMARY',
                        'VERSION_MEDIA',
                    ]),
                ],

                'entity_version_id' => [
                    'nullable',
                    'integer',
                ],

                'entity_version_image_id' => [
                    'nullable',
                    'integer',
                ],

                'use_version_name' => [
                    'nullable',
                    'boolean',
                ],

                'use_version_description' => [
                    'nullable',
                    'boolean',
                ],
            ]);


        $service->update(
            $request->user(),
            $entity,
            [
                ...$data,

                'use_version_name' =>
                $request->boolean(
                    'use_version_name'
                ),

                'use_version_description' =>
                $request->boolean(
                    'use_version_description'
                ),
            ]
        );


        return back()
            ->with(
                'success',
                'Presentación pública actualizada correctamente.'
            );
    }
}
