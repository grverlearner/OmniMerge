<?php

namespace App\Http\Controllers\Attributes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attributes\StoreAttributeGroupRequest;
use App\Http\Requests\Attributes\UpdateAttributeGroupRequest;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttributeGroupController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AttributeGroup::class);

        $search = trim((string) $request->input('search'));

        $groups = AttributeGroup::query()
            ->ownedBy($request->user())
            ->withCount('attributes')
            ->when(
                $search,
                fn ($query) => $query->where(
                    fn ($subquery) => $subquery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                )
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view(
            'attribute-groups.index',
            compact('groups', 'search')
        );
    }

    public function create(Request $request): View
    {
        $this->authorize('create', AttributeGroup::class);

        $attributes = Attribute::query()
            ->ownedBy($request->user())
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'attribute-groups.create',
            compact('attributes')
        );
    }

    public function store(
        StoreAttributeGroupRequest $request
    ): RedirectResponse {
        $data = $request->validated();
        $attributeIds = $data['attribute_ids'] ?? [];

        unset($data['attribute_ids']);

        $group = $request->user()
            ->attributeGroups()
            ->create($data);

        $group->attributes()->sync($attributeIds);

        return redirect()
            ->route('attribute-groups.show', $group)
            ->with(
                'success',
                'Grupo de atributos creado correctamente.'
            );
    }

    public function show(AttributeGroup $attributeGroup): View
    {
        $this->authorize('view', $attributeGroup);

        $attributeGroup->load('attributes');

        return view(
            'attribute-groups.show',
            compact('attributeGroup')
        );
    }

    public function edit(
        Request $request,
        AttributeGroup $attributeGroup
    ): View {
        $this->authorize('update', $attributeGroup);

        $attributes = Attribute::query()
            ->ownedBy($request->user())
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $attributeGroup->load('attributes');

        return view(
            'attribute-groups.edit',
            compact('attributeGroup', 'attributes')
        );
    }

    public function update(
        UpdateAttributeGroupRequest $request,
        AttributeGroup $attributeGroup
    ): RedirectResponse {
        $data = $request->validated();
        $attributeIds = $data['attribute_ids'] ?? [];

        unset($data['attribute_ids']);

        $attributeGroup->update($data);
        $attributeGroup->attributes()->sync($attributeIds);

        return redirect()
            ->route('attribute-groups.show', $attributeGroup)
            ->with(
                'success',
                'Grupo actualizado correctamente.'
            );
    }

    public function destroy(
        AttributeGroup $attributeGroup
    ): RedirectResponse {
        $this->authorize('delete', $attributeGroup);

        $attributeGroup->attributes()->detach();
        $attributeGroup->delete();

        return redirect()
            ->route('attribute-groups.index')
            ->with(
                'success',
                'Grupo eliminado correctamente.'
            );
    }
}