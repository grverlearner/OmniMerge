@php
    $editing = isset($collection);

    $selectedEntities = old(
        'entity_ids',
        $editing
            ? $collection->entities->pluck('id')->all()
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
            value="{{ old('name', $collection->name ?? '') }}"
            required
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Ejemplo: Protagonistas de anime"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Código
        </label>

        <input
            type="text"
            name="code"
            value="{{ old('code', $collection->code ?? '') }}"
            class="w-full rounded-xl border-slate-300 uppercase focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="PROTAGONISTAS_ANIME"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Identificador URL
        </label>

        <input
            type="text"
            name="slug"
            value="{{ old('slug', $collection->slug ?? '') }}"
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="protagonistas-anime"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Orden
        </label>

        <input
            type="number"
            name="sort_order"
            min="0"
            value="{{ old('sort_order', $collection->sort_order ?? 0) }}"
            class="w-full rounded-xl border-slate-300"
        >
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Descripción
        </label>

        <textarea
            name="description"
            rows="5"
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Describe el propósito de esta colección..."
        >{{ old('description', $collection->description ?? '') }}</textarea>
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Imagen de portada
        </label>

        <input
            type="file"
            name="image"
            accept=".jpg,.jpeg,.png,.webp"
            class="block w-full rounded-xl border border-slate-300 bg-white text-sm file:mr-4 file:border-0 file:bg-indigo-50 file:px-4 file:py-3 file:font-semibold file:text-indigo-700"
        >

        <p class="mt-2 text-xs text-slate-500">
            JPG, PNG o WEBP. Máximo 4 MB.
        </p>

        @if ($editing && $collection->image_url)
            <div class="mt-4 flex items-center gap-4">
                <img
                    src="{{ $collection->image_url }}"
                    alt="{{ $collection->name }}"
                    class="h-28 w-44 rounded-xl object-cover"
                >

                <label class="flex items-center gap-2 text-sm text-red-600">
                    <input
                        type="checkbox"
                        name="remove_image"
                        value="1"
                        class="rounded border-slate-300 text-red-600"
                    >

                    Eliminar imagen actual
                </label>
            </div>
        @endif
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Icono
        </label>

        <input
            type="text"
            name="icon"
            value="{{ old('icon', $collection->icon ?? '') }}"
            class="w-full rounded-xl border-slate-300"
            placeholder="Ejemplo: ⭐"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Color
        </label>

        <input
            type="color"
            name="color"
            value="{{ old('color', $collection->color ?? '#6366F1') }}"
            class="h-12 w-full rounded-xl border border-slate-300 bg-white p-1"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Visibilidad *
        </label>

        <select
            name="visibility"
            required
            class="w-full rounded-xl border-slate-300"
        >
            @foreach ([
                'PRIVATE' => 'Privada',
                'PUBLIC' => 'Pública',
                'UNLISTED' => 'No listada',
            ] as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(
                        old(
                            'visibility',
                            $collection->visibility ?? 'PRIVATE'
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
            Estado *
        </label>

        <select
            name="status"
            required
            class="w-full rounded-xl border-slate-300"
        >
            @foreach ([
                'ACTIVE' => 'Activa',
                'INACTIVE' => 'Inactiva',
                'ARCHIVED' => 'Archivada',
            ] as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(
                        old(
                            'status',
                            $collection->status ?? 'ACTIVE'
                        ) === $value
                    )
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="lg:col-span-2">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-700">
                    Entidades incluidas
                </p>

                <p class="mt-1 text-xs text-slate-500">
                    Selecciona una o varias entidades para esta colección.
                </p>
            </div>

            <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                {{ count($selectedEntities) }} seleccionada(s)
            </span>
        </div>

        <div class="grid max-h-[520px] gap-4 overflow-y-auto rounded-2xl border border-slate-200 p-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($entities as $entity)
                <label class="relative cursor-pointer overflow-hidden rounded-2xl border-2 transition hover:border-indigo-300">
                    <input
                        type="checkbox"
                        name="entity_ids[]"
                        value="{{ $entity->id }}"
                        @checked(
                            in_array(
                                $entity->id,
                                $selectedEntities
                            )
                        )
                        class="peer absolute right-3 top-3 z-10 rounded border-slate-300 text-indigo-600"
                    >

                    <div class="aspect-[16/9] bg-slate-100 peer-checked:ring-4 peer-checked:ring-indigo-500">
                        @if ($entity->image_url)
                            <img
                                src="{{ $entity->image_url }}"
                                alt="{{ $entity->name }}"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <div class="flex h-full items-center justify-center text-4xl text-indigo-400">
                                {{ $entity->entityType?->icon
                                    ?: strtoupper(substr($entity->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <div class="p-4 peer-checked:bg-indigo-50">
                        <p class="font-bold text-slate-900">
                            {{ $entity->name }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ $entity->entityType?->name ?? 'Sin tipo' }}
                        </p>
                    </div>
                </label>
            @empty
                <p class="sm:col-span-2 lg:col-span-3 rounded-xl bg-amber-50 p-5 text-sm text-amber-800">
                    Todavía no tienes entidades para agregar.
                </p>
            @endforelse
        </div>
    </div>
</div>

<div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6">
    <a
        href="{{ route('collections.index') }}"
        class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
    >
        Cancelar
    </a>

    <button
        type="submit"
        class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700"
    >
        {{ $editing ? 'Guardar cambios' : 'Crear colección' }}
    </button>
</div>