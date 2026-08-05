<?php

namespace App\Http\Controllers\Attributes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attributes\StoreAttributeOptionRequest;
use App\Http\Requests\Attributes\UpdateAttributeOptionRequest;
use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AttributeOptionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->input('search')
        );

        $attributeId = $request->input('attribute');

        $options = AttributeOption::query()
            ->ownedBy($request->user())
            ->with([
                'attribute',
                'parent',
            ])
            ->withCount('children')
            ->when(
                $search,
                fn ($query) => $query->where(
                    fn ($subquery) => $subquery
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
                )
            )
            ->when(
                $attributeId,
                fn ($query) => $query->where(
                    'attribute_id',
                    $attributeId
                )
            )
            ->orderBy('attribute_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(18)
            ->withQueryString();

        $attributes = Attribute::query()
            ->ownedBy($request->user())
            ->active()
            ->where(
                fn ($query) => $query
                    ->where('data_type', 'OPTION')
                    ->orWhereIn(
                        'value_source',
                        ['CATALOG', 'MIXED']
                    )
            )
            ->orderBy('name')
            ->get();

        return view(
            'attribute-options.index',
            compact(
                'options',
                'attributes',
                'search',
                'attributeId'
            )
        );
    }

    public function create(Request $request): View
    {
        $attributes = Attribute::query()
            ->ownedBy($request->user())
            ->active()
            ->where(
                fn ($query) => $query
                    ->where('data_type', 'OPTION')
                    ->orWhereIn(
                        'value_source',
                        ['CATALOG', 'MIXED']
                    )
            )
            ->orderBy('name')
            ->get();

        $selectedAttribute = null;

        if ($request->filled('attribute')) {
            $selectedAttribute = $attributes
                ->firstWhere(
                    'id',
                    (int) $request->input('attribute')
                );
        }

        $parentOptions = $selectedAttribute
            ? $selectedAttribute
                ->options()
                ->where('status', 'ACTIVE')
                ->get()
            : collect();
        
        $selectedParentId = $request->integer('parent');

        return view(
            'attribute-options.create',
            compact(
                'attributes',
                'selectedAttribute',
                'parentOptions',
                'selectedParentId'
            )
        );
    }

    public function store(
        StoreAttributeOptionRequest $request,
        Attribute $attribute
    ): RedirectResponse {
        abort_unless(
            $attribute->isSelectable(),
            422,
            'Este atributo no admite opciones.'
        );

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request
                ->file('image')
                ->store(
                    'attribute-options',
                    'public'
                );
        }

        $option = $attribute
            ->options()
            ->create($data);

        return redirect()
            ->route(
                'attribute-options.show',
                $option
            )
            ->with(
                'success',
                'Opción creada correctamente.'
            );
    }

    public function show(
        AttributeOption $attributeOption
    ): View {
        $attributeOption->load([
            'attribute',
            'parent',
            'children',
        ])->loadCount('values');

        $this->authorize(
            'view',
            $attributeOption
        );

        return view(
            'attribute-options.show',
            compact('attributeOption')
        );
    }

    public function edit(
        AttributeOption $attributeOption
    ): View {
        $this->authorize(
            'update',
            $attributeOption
        );

        $attributeOption->load('attribute');

        $parentOptions = $attributeOption
            ->attribute
            ->options()
            ->whereKeyNot($attributeOption->id)
            ->where('status', 'ACTIVE')
            ->get();

        return view(
            'attribute-options.edit',
            compact(
                'attributeOption',
                'parentOptions'
            )
        );
    }

    public function update(
        UpdateAttributeOptionRequest $request,
        Attribute $attribute,
        AttributeOption $option
    ): RedirectResponse {
        $data = $request->validated();

        if ($request->boolean('remove_image')) {
            if ($option->image) {
                Storage::disk('public')
                    ->delete($option->image);
            }

            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            if ($option->image) {
                Storage::disk('public')
                    ->delete($option->image);
            }

            $data['image'] = $request
                ->file('image')
                ->store(
                    'attribute-options',
                    'public'
                );
        }

        unset($data['remove_image']);

        $option->update($data);

        return redirect()
            ->route(
                'attribute-options.show',
                $option
            )
            ->with(
                'success',
                'Opción actualizada correctamente.'
            );
    }

    public function destroy(
        AttributeOption $attributeOption
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $attributeOption
        );

        if ($attributeOption->values()->exists()) {
            return back()->with(
                'error',
                'La opción está siendo utilizada por entidades.'
            );
        }

        if ($attributeOption->children()->exists()) {
            return back()->with(
                'error',
                'Primero debes eliminar o mover sus opciones hijas.'
            );
        }

        if ($attributeOption->image) {
            Storage::disk('public')
                ->delete($attributeOption->image);
        }

        $attributeOption->delete();

        return redirect()
            ->route('attribute-options.index')
            ->with(
                'success',
                'Opción eliminada correctamente.'
            );
    }
}