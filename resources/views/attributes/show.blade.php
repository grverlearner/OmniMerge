<x-app-layout>
    <x-slot name="header">
        Detalle del atributo
    </x-slot>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-start">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-3xl font-black text-slate-900">
                        {{ $attribute->name }}
                    </h2>

                    <x-status-badge :status="$attribute->status" />
                </div>

                <p class="mt-2 text-sm font-semibold text-slate-400">
                    {{ $attribute->code }}
                </p>

                <p class="mt-5 max-w-3xl leading-7 text-slate-600">
                    {{ $attribute->description ?: 'Este atributo no tiene descripción.' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('attributes.edit', $attribute) }}"
                    class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Editar
                </a>

                <form
                    method="POST"
                    action="{{ route('attributes.destroy', $attribute) }}"
                    onsubmit="return confirm('¿Eliminar este atributo?')"
                >
                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"
                    >
                        Eliminar
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl bg-slate-50 p-5">
                <p class="text-sm text-slate-500">Tipo de dato</p>
                <p class="mt-2 font-black text-slate-900">
                    {{ $attribute->data_type }}
                </p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-5">
                <p class="text-sm text-slate-500">Origen</p>
                <p class="mt-2 font-black text-slate-900">
                    {{ $attribute->value_source }}
                </p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-5">
                <p class="text-sm text-slate-500">Opciones</p>
                <p class="mt-2 text-2xl font-black text-slate-900">
                    {{ $attribute->options->count() }}
                </p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-5">
                <p class="text-sm text-slate-500">Entidades que lo usan</p>
                <p class="mt-2 text-2xl font-black text-slate-900">
                    {{ $attribute->entity_attributes_count }}
                </p>
            </div>
        </div>
    </section>

    @if ($attribute->usesCatalog())
        <section class="mt-8 grid gap-6 xl:grid-cols-[380px_1fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-black text-slate-900">
                    Agregar opción
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    Crea valores seleccionables para este atributo.
                </p>

                <form
                    method="POST"
                    action="{{ route('attributes.options.store', $attribute) }}"
                    class="mt-6 space-y-5"
                >
                    @csrf

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Nombre *
                        </label>

                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Ejemplo: Fuego"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Código
                        </label>

                        <input
                            type="text"
                            name="code"
                            value="{{ old('code') }}"
                            class="w-full rounded-xl border-slate-300 uppercase focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="FUEGO"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Opción superior
                        </label>

                        <select
                            name="parent_option_id"
                            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Ninguna</option>

                            @foreach ($attribute->options as $parentOption)
                                <option
                                    value="{{ $parentOption->id }}"
                                    @selected(old('parent_option_id') == $parentOption->id)
                                >
                                    {{ $parentOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Icono
                            </label>

                            <input
                                type="text"
                                name="icon"
                                value="{{ old('icon') }}"
                                class="w-full rounded-xl border-slate-300"
                                placeholder="🔥"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Color
                            </label>

                            <input
                                type="color"
                                name="color"
                                value="{{ old('color', '#6366F1') }}"
                                class="h-11 w-full rounded-xl border border-slate-300 bg-white p-1"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Orden
                        </label>

                        <input
                            type="number"
                            name="sort_order"
                            min="0"
                            value="{{ old('sort_order', 0) }}"
                            class="w-full rounded-xl border-slate-300"
                        >
                    </div>

                    <input type="hidden" name="status" value="ACTIVE">

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-indigo-600 px-5 py-3 font-bold text-white hover:bg-indigo-700"
                    >
                        Agregar opción
                    </button>
                </form>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="font-black text-slate-900">
                        Opciones disponibles
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Estas opciones podrán seleccionarse al configurar una entidad.
                    </p>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($attribute->options as $option)
                        <div class="flex items-center gap-4 px-6 py-4">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-xl"
                                style="
                                    background-color: {{ $option->color ?? '#6366F1' }}20;
                                    color: {{ $option->color ?? '#6366F1' }};
                                "
                            >
                                {{ $option->icon ?: '◇' }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-slate-900">
                                    {{ $option->name }}
                                </p>

                                <p class="text-xs font-semibold text-slate-400">
                                    {{ $option->code }}

                                    @if ($option->parent)
                                        · Depende de {{ $option->parent->name }}
                                    @endif
                                </p>
                            </div>

                            <form
                                method="POST"
                                action="{{ route(
                                    'attributes.options.destroy',
                                    [$attribute, $option]
                                ) }}"
                                onsubmit="return confirm('¿Eliminar esta opción?')"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"
                                >
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="py-16 text-center text-sm text-slate-500">
                            Todavía no existen opciones.
                        </div>
                    @endforelse
                </div>
            </article>
        </section>
    @else
        <div class="mt-8 rounded-2xl border border-blue-200 bg-blue-50 p-6 text-blue-900">
            Este atributo usa valores libres, por lo que no necesita un catálogo de opciones.
        </div>
    @endif
</x-app-layout>