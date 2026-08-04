@php
    $editing = isset($entity);
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Nombre *
        </label>

        <input
            name="name"
            value="{{ old('name', $entity->name ?? '') }}"
            required
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Ejemplo: Naruto Uzumaki, Perú, Pyron"
        >
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Tipo de entidad
        </label>

        <select
            name="entity_type_id"
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >
            <option value="">Sin tipo</option>

            @foreach ($entityTypes as $type)
                <option
                    value="{{ $type->id }}"
                    @selected(
                        old(
                            'entity_type_id',
                            $entity->entity_type_id
                                ?? request('type')
                        ) == $type->id
                    )
                >
                    {{ $type->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Código
        </label>

        <input
            name="code"
            value="{{ old('code', $entity->code ?? '') }}"
            class="w-full rounded-xl border-slate-300 uppercase focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="NARUTO_UZUMAKI"
        >

        <p class="mt-2 text-xs text-slate-500">
            Se genera automáticamente si lo dejas vacío.
        </p>
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Identificador URL
        </label>

        <input
            name="slug"
            value="{{ old('slug', $entity->slug ?? '') }}"
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="naruto-uzumaki"
        >
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Descripción
        </label>

        <textarea
            name="description"
            rows="6"
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Describe esta entidad..."
        >{{ old('description', $entity->description ?? '') }}</textarea>
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">
            Imagen
        </label>

        <input
            name="image"
            type="file"
            accept=".jpg,.jpeg,.png,.webp"
            class="block w-full rounded-xl border border-slate-300 bg-white text-sm file:mr-4 file:border-0 file:bg-indigo-50 file:px-4 file:py-3 file:font-semibold file:text-indigo-700"
        >

        <p class="mt-2 text-xs text-slate-500">
            JPG, PNG o WEBP. Máximo 2 MB.
        </p>

        @if ($editing && $entity->image_url)
            <div class="mt-4 flex items-center gap-4">
                <img
                    src="{{ $entity->image_url }}"
                    alt="{{ $entity->name }}"
                    class="h-24 w-24 rounded-2xl object-cover"
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
            Estado *
        </label>

        <select
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
                            $entity->status ?? 'ACTIVE'
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
            Visibilidad *
        </label>

        <select
            name="visibility"
            required
            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >
            @foreach ([
                'PRIVATE' => 'Privado',
                'PUBLIC' => 'Público',
                'UNLISTED' => 'No listado',
            ] as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(
                        old(
                            'visibility',
                            $entity->visibility ?? 'PRIVATE'
                        ) === $value
                    )
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-8 rounded-2xl border border-indigo-100 bg-indigo-50 p-5">
    <p class="font-semibold text-indigo-900">
        Atributos personalizados
    </p>

    <p class="mt-1 text-sm text-indigo-700">
        En el siguiente sprint podrás agregar atributos como
        poder, elemento, anime, país, habilidades y cualquier
        valor personalizado.
    </p>
</div>

<div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6">
    <a
        href="{{ route('entities.index') }}"
        class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
    >
        Cancelar
    </a>

    <button
        type="submit"
        class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700"
    >
        {{ $editing ? 'Guardar cambios' : 'Crear entidad' }}
    </button>
</div>