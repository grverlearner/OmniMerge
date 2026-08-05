@php
    $editing = isset($attributeOption);
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    @unless ($editing)
        <div class="lg:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">
                Atributo seleccionable *
            </label>

            <select name="attribute_selector"
                onchange="
                    const value = this.value;
                    if (value) {
                        window.location.href =
                            '{{ route('attribute-options.create') }}'
                            + '?attribute=' + value;
                    }
                "
                class="w-full rounded-xl border-slate-300">
                <option value="">
                    Selecciona un atributo
                </option>

                @foreach ($attributes as $attribute)
                    <option value="{{ $attribute->id }}" @selected($selectedAttribute?->id === $attribute->id)>
                        {{ $attribute->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endunless

    <div>
        <label class="mb-2 block text-sm font-semibold">
            Nombre *
        </label>

        <input name="name" value="{{ old('name', $attributeOption->name ?? '') }}" required
            class="w-full rounded-xl border-slate-300" placeholder="Ejemplo: Naruto">
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold">
            Código
        </label>

        <input name="code" value="{{ old('code', $attributeOption->code ?? '') }}"
            class="w-full rounded-xl border-slate-300 uppercase" placeholder="NARUTO">
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold">
            Descripción
        </label>

        <textarea name="description" rows="5" class="w-full rounded-xl border-slate-300">{{ old('description', $attributeOption->description ?? '') }}</textarea>
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold">
            Imagen
        </label>

        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"
            class="block w-full rounded-xl border border-slate-300 bg-white text-sm file:mr-4 file:border-0 file:bg-indigo-50 file:px-4 file:py-3 file:font-semibold file:text-indigo-700">

        @if ($editing && $attributeOption->image_url)
            <div class="mt-4 flex items-center gap-4">
                <img src="{{ $attributeOption->image_url }}" class="h-28 w-40 rounded-xl object-cover"
                    alt="{{ $attributeOption->name }}">

                <label class="flex items-center gap-2 text-sm text-red-600">
                    <input type="checkbox" name="remove_image" value="1">

                    Eliminar imagen
                </label>
            </div>
        @endif
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold">
            Icono
        </label>

        <input name="icon" value="{{ old('icon', $attributeOption->icon ?? '') }}"
            class="w-full rounded-xl border-slate-300" placeholder="🍥">
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold">
            Color
        </label>

        <input type="color" name="color" value="{{ old('color', $attributeOption->color ?? '#6366F1') }}"
            class="h-12 w-full rounded-xl border bg-white p-1">
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold">
            Opción superior
        </label>

        <select name="parent_option_id" class="w-full rounded-xl border-slate-300">
            <option value="">
                Sin opción superior
            </option>

            @foreach ($parentOptions as $parentOption)
                <option value="{{ $parentOption->id }}" @selected(old('parent_option_id', $attributeOption->parent_option_id ?? ($selectedParentId ?? null)) == $parentOption->id)>
                    {{ $parentOption->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold">
            Valor numérico
        </label>

        <input type="number" step="any" name="numeric_value"
            value="{{ old('numeric_value', $attributeOption->numeric_value ?? '') }}"
            class="w-full rounded-xl border-slate-300">
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold">
            Orden
        </label>

        <input type="number" min="0" name="sort_order"
            value="{{ old('sort_order', $attributeOption->sort_order ?? 0) }}"
            class="w-full rounded-xl border-slate-300">
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold">
            Estado
        </label>

        <select name="status" class="w-full rounded-xl border-slate-300">
            <option value="ACTIVE" @selected(old('status', $attributeOption->status ?? 'ACTIVE') === 'ACTIVE')>
                Activo
            </option>

            <option value="INACTIVE" @selected(old('status', $attributeOption->status ?? 'ACTIVE') === 'INACTIVE')>
                Inactivo
            </option>
        </select>
    </div>

    <div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-6">
        <a href="{{ route('attribute-options.index') }}"
            class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Cancelar
        </a>

        <button type="submit"
            class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700">
            {{ $editing ? 'Guardar cambios' : 'Crear opción' }}
        </button>
    </div>
</div>
