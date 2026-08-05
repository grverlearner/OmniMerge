<?php

namespace App\Http\Controllers\Attributes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attributes\StoreAttributeRequest;
use App\Http\Requests\Attributes\UpdateAttributeRequest;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttributeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Attribute::class);

        $search = trim((string) $request->input('search'));
        $dataType = $request->input('data_type');

        $attributes = Attribute::query()
            ->ownedBy($request->user())
            ->withCount([
                'options',
                'entityAttributes',
            ])
            ->when(
                $search,
                fn ($query) => $query->where(
                    fn ($subquery) => $subquery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                )
            )
            ->when(
                $dataType,
                fn ($query) => $query->where(
                    'data_type',
                    $dataType
                )
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view(
            'attributes.index',
            compact('attributes', 'search', 'dataType')
        );
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Attribute::class);

        $groups = AttributeGroup::query()
            ->ownedBy($request->user())
            ->where('status', 'ACTIVE')
            ->orderBy('sort_order')
            ->get();

        return view(
            'attributes.create',
            compact('groups')
        );
    }

    public function store(
        StoreAttributeRequest $request
    ): RedirectResponse {
        $data = $request->validated();
        $groupIds = $data['group_ids'] ?? [];

        unset($data['group_ids']);

        $attribute = $request->user()
            ->attributes()
            ->create($data);

        $attribute->groups()->sync($groupIds);

        return redirect()
            ->route('attributes.show', $attribute)
            ->with(
                'success',
                'Atributo creado correctamente.'
            );
    }

    public function show(Attribute $attribute): View
    {
        $this->authorize('view', $attribute);

        $attribute->load([
            'options',
            'groups',
        ])->loadCount('entityAttributes');

        return view(
            'attributes.show',
            compact('attribute')
        );
    }

    public function edit(
        Request $request,
        Attribute $attribute
    ): View {
        $this->authorize('update', $attribute);

        $groups = AttributeGroup::query()
            ->ownedBy($request->user())
            ->where('status', 'ACTIVE')
            ->orderBy('sort_order')
            ->get();

        $attribute->load('groups');

        return view(
            'attributes.edit',
            compact('attribute', 'groups')
        );
    }

    public function update(
        UpdateAttributeRequest $request,
        Attribute $attribute
    ): RedirectResponse {
        $data = $request->validated();
        $groupIds = $data['group_ids'] ?? [];

        unset($data['group_ids']);

        $attribute->update($data);
        $attribute->groups()->sync($groupIds);

        return redirect()
            ->route('attributes.show', $attribute)
            ->with(
                'success',
                'Atributo actualizado correctamente.'
            );
    }

    public function destroy(
        Attribute $attribute
    ): RedirectResponse {
        $this->authorize('delete', $attribute);

        if ($attribute->entityAttributes()->exists()) {
            return back()->with(
                'error',
                'Este atributo está siendo utilizado por entidades.'
            );
        }

        $attribute->delete();

        return redirect()
            ->route('attributes.index')
            ->with(
                'success',
                'Atributo eliminado correctamente.'
            );
    }
}