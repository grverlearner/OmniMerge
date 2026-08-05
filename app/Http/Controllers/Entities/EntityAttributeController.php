<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\SaveEntityAttributesRequest;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\Entity;
use App\Services\Entities\EntityAttributeValueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntityAttributeController extends Controller
{
    public function edit(
        Request $request,
        Entity $entity
    ): View {
        $this->authorize('update', $entity);

        $attributes = Attribute::query()
            ->ownedBy($request->user())
            ->active()
            ->with([
                'options' => fn ($query) =>
                    $query->where('status', 'ACTIVE'),
                'groups',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $groups = AttributeGroup::query()
            ->ownedBy($request->user())
            ->where('status', 'ACTIVE')
            ->with([
                'attributes' => fn ($query) =>
                    $query->where('attributes.status', 'ACTIVE'),
            ])
            ->orderBy('sort_order')
            ->get();

        $entity->load([
            'entityAttributes.attribute',
            'entityAttributes.values.option',
        ]);

        $existingValues = $entity
            ->entityAttributes
            ->keyBy('attribute_id');

        return view(
            'entities.attributes',
            compact(
                'entity',
                'attributes',
                'groups',
                'existingValues'
            )
        );
    }

    public function update(
        SaveEntityAttributesRequest $request,
        Entity $entity,
        EntityAttributeValueService $service
    ): RedirectResponse {
        $inputs = $request->input(
            'attributes',
            []
        );

        $attributes = Attribute::query()
            ->ownedBy($request->user())
            ->active()
            ->get();

        foreach ($attributes as $attribute) {
            $service->save(
                $entity,
                $attribute,
                $inputs[$attribute->id] ?? null
            );
        }

        return redirect()
            ->route('entities.show', $entity)
            ->with(
                'success',
                'Atributos guardados correctamente.'
            );
    }
}