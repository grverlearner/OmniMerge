<?php

namespace App\Http\Controllers\Attributes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attributes\StoreAttributeOptionRequest;
use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Http\RedirectResponse;

class AttributeOptionController extends Controller
{
    public function store(
        StoreAttributeOptionRequest $request,
        Attribute $attribute
    ): RedirectResponse {
        if (! $attribute->usesCatalog()) {
            return back()->with(
                'error',
                'Este atributo no utiliza un catálogo.'
            );
        }

        $attribute->options()->create(
            $request->validated()
        );

        return back()->with(
            'success',
            'Opción agregada correctamente.'
        );
    }

    public function destroy(
        Attribute $attribute,
        AttributeOption $option
    ): RedirectResponse {
        $this->authorize('update', $attribute);

        abort_unless(
            $option->attribute_id === $attribute->id,
            404
        );

        $option->delete();

        return back()->with(
            'success',
            'Opción eliminada correctamente.'
        );
    }
}