<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\Controller;

use App\Http\Requests\Entities\SaveEntityAttributesRequest;

use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\Entity;

use App\Services\Attributes\AttributeContextService;
use App\Services\Entities\EntityAttributeValueService;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntityAttributeController extends Controller
{
    public function edit(
        Request $request,
        Entity $entity,
        AttributeContextService $contextService
    ): View {

        $this->authorize(
            'update',
            $entity
        );


        $attributes =
            Attribute::query()
            ->ownedBy(
                $request->user()
            )
            ->active()
            ->with([
                'groups',

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
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->get();


        $groups =
            AttributeGroup::query()
            ->ownedBy(
                $request->user()
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->get();


        $entity->load([
            'entityAttributes.attribute',
            'entityAttributes.values.option',
        ]);


        $contextPayload =
            $contextService
            ->frontendPayload(
                $request->user()
            );


        return view(
            'entities.attributes',
            compact(
                'entity',
                'attributes',
                'groups',
                'contextPayload'
            )
        );
    }


    public function update(
        SaveEntityAttributesRequest $request,
        Entity $entity,
        EntityAttributeValueService $service,
        AttributeContextService $contextService
    ): RedirectResponse {

        $this->authorize(
            'update',
            $entity
        );


        $context =
            $contextService
            ->sanitizeSelection(
                $request->user(),

                $request->input(
                    'selected_attribute_ids',
                    []
                ),

                $request->input(
                    'attributes',
                    []
                )
            );


        $service->sync(
            $entity,
            $request->user(),

            $context['selected_attribute_ids'],

            $context['inputs']
        );


        return redirect()
            ->route(
                'entities.show',
                $entity
            )
            ->with(
                'success',
                'Características actualizadas correctamente.'
            );
    }
}
