@php
    $editing = isset($entityType);
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <label
            for="name"
            class="mb-2 block text-sm font-semibold text-slate-700"
        >
            Nombre *
        </label>

        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $entityType->name ?? '') }}"
            required
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Ejemplo: Personaje, País, Animal"
        >

        @error('name')
            <p class="mt-2 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label
            for="code"
            class="mb-2 block text-sm font-semibold text-slate-700"
        >
            Código
        </label>

        <input
            id="code"
            name="code"
            type="text"
            value="{{ old('code', $entityType->code ?? '') }}"
            class="w-full rounded-xl border-slate-300 uppercase focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="PERSONAJE"
        >

        <p class="mt-2 text-xs text-slate-500">
            Si lo dejas vacío, se generará desde el nombre.
        </p>

        @error('code')
            <p class="mt-2 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label
            for="description"
            class="mb-2 block text-sm font-semibold text-slate-700"
        >
            Descripción
        </label>

        <textarea
            id="description"
            name="description"
            rows="5"
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Explica qué clase de entidades pertenecerán a este tipo."
        >{{ old('description', $entityType->description ?? '') }}</textarea>
    </div>

    <div>
        <label
            for="color"
            class="mb-2 block text-sm font-semibold text-slate-700"
        >
            Color
        </label>

        <input
            id="color"
            name="color"
            type="color"
            value="{{ old('color', $entityType->color ?? '#6366F1') }}"
            class="h-12 w-full rounded-xl border border-slate-300 bg-white p-1"
        >
    </div>

    <div>
        <label
            for="icon"
            class="mb-2 block text-sm font-semibold text-slate-700"
        >
            Icono o símbolo
        </label>

        <input
            id="icon"
            name="icon"
            type="text"
            value="{{ old('icon', $entityType->icon ?? '') }}"
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Ejemplo: ✦, 🐉, 🌍"
        >
    </div>

    <div>
        <label
            for="status"
            class="mb-2 block text-sm font-semibold text-slate-700"
        >
            Estado *
        </label>

        <select
            id="status"
            name="status"
            required
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
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
                            $entityType->status ?? 'ACTIVE'
                        ) === $value
                    )
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label
            for="sort_order"
            class="mb-2 block text-sm font-semibold text-slate-700"
        >
            Orden
        </label>

        <input
            id="sort_order"
            name="sort_order"
            type="number"
            min="0"
            value="{{ old('sort_order', $entityType->sort_order ?? 0) }}"
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >
    </div>
</div>

<div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-6">
    <a
        href="{{ route('entity-types.index') }}"
        class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
    >
        Cancelar
    </a>

    <button
        type="submit"
        class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700"
    >
        {{ $editing ? 'Guardar cambios' : 'Crear tipo' }}
    </button>
</div>