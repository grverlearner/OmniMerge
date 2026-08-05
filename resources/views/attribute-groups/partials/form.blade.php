@php
    $editing = isset($attributeGroup);

    $selectedAttributes = old(
        'attribute_ids',
        $editing
            ? $attributeGroup->attributes->pluck('id')->all()
            : []
    );
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Nombre *
        </label>

        <input
            type="text"
            name="name"
            value="{{ old('name', $attributeGroup->name ?? '') }}"
            required
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Ejemplo: Combate"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Código
        </label>

        <input
            type="text"
            name="code"
            value="{{ old('code', $attributeGroup->code ?? '') }}"
            class="w-full rounded-xl border-slate-300 uppercase focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="COMBATE"
        >
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Descripción
        </label>

        <textarea
            name="description"
            rows="4"
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >{{ old('description', $attributeGroup->description ?? '') }}</textarea>
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Icono
        </label>

        <input
            type="text"
            name="icon"
            value="{{ old('icon', $attributeGroup->icon ?? '') }}"
            class="w-full rounded-xl border-slate-300"
            placeholder="⚔"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Color
        </label>

        <input
            type="color"
            name="color"
            value="{{ old('color', $attributeGroup->color ?? '#6366F1') }}"
            class="h-12 w-full rounded-xl border border-slate-300 bg-white p-1"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Diseño
        </label>

        <select
            name="layout_type"
            class="w-full rounded-xl border-slate-300"
        >
            @foreach ([
                'LIST' => 'Lista',
                'GRID' => 'Cuadrícula',
                'CARDS' => 'Tarjetas',
                'TABLE' => 'Tabla',
                'COMPACT' => 'Compacto',
            ] as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(
                        old(
                            'layout_type',
                            $attributeGroup->layout_type ?? 'LIST'
                        ) === $value
                    )
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Estado
        </label>

        <select
            name="status"
            class="w-full rounded-xl border-slate-300"
        >
            @foreach ([
                'ACTIVE' => 'Activo',
                'INACTIVE' => 'Inactivo',
                'ARCHIVED' => 'Archivado',
            ] as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(
                        old(
                            'status',
                            $attributeGroup->status ?? 'ACTIVE'
                        ) === $value
                    )
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Orden
        </label>

        <input
            type="number"
            name="sort_order"
            min="0"
            value="{{ old('sort_order', $attributeGroup->sort_order ?? 0) }}"
            class="w-full rounded-xl border-slate-300"
        >
    </div>

    <div class="flex items-center gap-5">
        <label class="flex items-center gap-2">
            <input
                type="checkbox"
                name="collapsible"
                value="1"
                @checked(
                    old(
                        'collapsible',
                        $attributeGroup->collapsible ?? true
                    )
                )
                class="rounded border-slate-300 text-indigo-600"
            >

            <span class="text-sm font-medium">
                Puede contraerse
            </span>
        </label>

        <label class="flex items-center gap-2">
            <input
                type="checkbox"
                name="default_expanded"
                value="1"
                @checked(
                    old(
                        'default_expanded',
                        $attributeGroup->default_expanded ?? true
                    )
                )
                class="rounded border-slate-300 text-indigo-600"
            >

            <span class="text-sm font-medium">
                Abierto inicialmente
            </span>
        </label>
    </div>

    <div class="lg:col-span-2">
        <p class="mb-3 text-sm font-semibold text-slate-700">
            Atributos incluidos
        </p>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($attributes as $attribute)
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 hover:border-indigo-300 hover:bg-indigo-50">
                    <input
                        type="checkbox"
                        name="attribute_ids[]"
                        value="{{ $attribute->id }}"
                        @checked(
                            in_array(
                                $attribute->id,
                                $selectedAttributes
                            )
                        )
                        class="rounded border-slate-300 text-indigo-600"
                    >

                    <span>
                        <span class="block font-semibold text-slate-800">
                            {{ $attribute->name }}
                        </span>

                        <span class="text-xs text-slate-500">
                            {{ $attribute->data_type }}
                        </span>
                    </span>
                </label>
            @empty
                <p class="sm:col-span-2 lg:col-span-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">
                    Primero debes crear atributos.
                </p>
            @endforelse
        </div>
    </div>
</div>

<div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6">
    <a
        href="{{ route('attribute-groups.index') }}"
        class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700"
    >
        Cancelar
    </a>

    <button
        type="submit"
        class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white"
    >
        {{ $editing ? 'Guardar cambios' : 'Crear grupo' }}
    </button>
</div>